<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Log;
use Modules\AI\Models\KnowledgeChunk;
use Modules\AI\Models\KnowledgeDocument;

class KnowledgeBaseService
{
    public function __construct(
        private readonly EmbeddingService $embeddingService
    ) {}

    public function addDocument(
        string $title,
        string $content,
        string $sourceType = 'manual',
        array $metadata = [],
        ?int $sourceId = null
    ): KnowledgeDocument {
        $doc = KnowledgeDocument::create([
            'title' => $title,
            'content' => $content,
            'source_type' => $sourceType,
            'metadata' => $metadata,
            'source_id' => $sourceId,
            'last_synced_at' => now(),
        ]);

        $this->chunkAndStore($doc, $content);

        return $doc;
    }

    public function updateDocument(KnowledgeDocument $doc, string $content): void
    {
        $doc->chunks()->delete();

        $doc->update([
            'content' => $content,
            'last_synced_at' => now(),
        ]);

        $this->chunkAndStore($doc, $content);
    }

    public function deleteDocument(KnowledgeDocument $doc): void
    {
        $doc->delete();
    }

    /** @return array<string> */
    public function chunkText(string $text, int $maxTokens = 500, int $overlap = 50): array
    {
        $text = trim($text);
        if (empty($text)) {
            return [];
        }

        $maxChars = $maxTokens * 4;
        $overlapChars = $overlap * 4;

        // Découpage en phrases
        $sentences = preg_split('/(?<=[.!?\n])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $chunks = [];
        $currentChunk = '';
        $currentLength = 0;
        $sentenceBuffer = [];

        foreach ($sentences as $sentence) {
            $sentenceLength = strlen($sentence);

            // Phrase trop longue : découpe brute
            if ($sentenceLength > $maxChars) {
                if (! empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                    $currentChunk = '';
                    $currentLength = 0;
                    $sentenceBuffer = [];
                }

                foreach (str_split($sentence, $maxChars - $overlapChars) as $part) {
                    $chunks[] = trim($part);
                }

                continue;
            }

            // Si dépasse la limite, on sauvegarde et on gère l'overlap
            if ($currentLength + $sentenceLength > $maxChars && ! empty($currentChunk)) {
                $chunks[] = trim($currentChunk);

                // Overlap : garder les dernières phrases
                $overlapText = '';
                $overlapLength = 0;
                $tempBuffer = [];

                foreach (array_reverse($sentenceBuffer) as $bufSentence) {
                    $bufLength = strlen($bufSentence);
                    if ($overlapLength + $bufLength <= $overlapChars) {
                        $overlapText = $bufSentence.' '.$overlapText;
                        $overlapLength += $bufLength;
                        array_unshift($tempBuffer, $bufSentence);
                    } else {
                        break;
                    }
                }

                $currentChunk = trim($overlapText);
                $currentLength = $overlapLength;
                $sentenceBuffer = $tempBuffer;
            }

            $currentChunk .= ($currentLength > 0 ? ' ' : '').$sentence;
            $currentLength += $sentenceLength + ($currentLength > 0 ? 1 : 0);
            $sentenceBuffer[] = $sentence;
        }

        if (! empty(trim($currentChunk))) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    private function chunkAndStore(KnowledgeDocument $doc, string $content): void
    {
        $cleanContent = strip_tags($content);
        $chunks = $this->chunkText($cleanContent);

        foreach ($chunks as $index => $chunkContent) {
            KnowledgeChunk::create([
                'document_id' => $doc->id,
                'chunk_index' => $index,
                'content' => $chunkContent,
                'token_count' => (int) ceil(strlen($chunkContent) / 4),
            ]);
        }
    }

    public function syncFromSource(string $type): int
    {
        $syncedCount = 0;

        $sources = [
            'faq' => 'Modules\Faq\Models\Faq',
            'page' => 'Modules\Pages\Models\Page',
            'article' => 'Modules\Blog\Models\Article',
        ];

        if (! isset($sources[$type]) || ! class_exists($sources[$type])) {
            return 0;
        }

        $modelClass = $sources[$type];

        $query = $type === 'article'
            ? $modelClass::where('published_at', '<=', now())
            : $modelClass::where('is_published', true);

        foreach ($query->get() as $item) {
            $this->syncItem($item, $type);
            $syncedCount++;
        }

        return $syncedCount;
    }

    private function syncItem(object $item, string $sourceType): void
    {
        $content = $item->content ?? $item->body ?? $item->answer ?? '';
        $title = $item->title ?? $item->question ?? $item->name ?? 'Sans titre';

        $metadata = [
            'slug' => $item->slug ?? null,
        ];

        $doc = KnowledgeDocument::updateOrCreate(
            ['source_type' => $sourceType, 'source_id' => $item->id],
            [
                'title' => $title,
                'content' => $content,
                'metadata' => $metadata,
                'last_synced_at' => now(),
            ]
        );

        // Re-chunk si nouveau ou contenu modifié
        if ($doc->wasRecentlyCreated || $doc->wasChanged('content')) {
            $doc->chunks()->delete();
            $this->chunkAndStore($doc, $content);
        }
    }

    /** @return array<int, array{content: string, title: string, source_type: string, similarity: float}> */
    public function search(string $query, int $limit = 5, ?int $tenantId = null): array
    {
        // 1. Tentative recherche par embeddings
        try {
            $queryEmbedding = $this->embeddingService->embed($query);

            if (! empty($queryEmbedding)) {
                $similar = $this->embeddingService->findSimilar($queryEmbedding, $limit, $tenantId);

                if ($similar->isNotEmpty()) {
                    return $similar->map(fn (array $item) => [
                        'content' => $item['chunk']->content,
                        'title' => $item['chunk']->document->title ?? '',
                        'source_type' => $item['chunk']->document->source_type ?? 'manual',
                        'similarity' => $item['similarity'],
                    ])->all();
                }
            }
        } catch (\Exception $e) {
            Log::warning('KB embedding search failed: '.$e->getMessage());
        }

        // 2. Fallback fulltext
        try {
            $fulltextResults = $this->embeddingService->findSimilarFulltext($query, $limit, $tenantId);

            if ($fulltextResults->isNotEmpty()) {
                return $fulltextResults->map(fn (KnowledgeChunk $chunk) => [
                    'content' => $chunk->content,
                    'title' => $chunk->document->title ?? '',
                    'source_type' => $chunk->document->source_type ?? 'manual',
                    'similarity' => 0.5,
                ])->all();
            }
        } catch (\Exception $e) {
            Log::warning('KB fulltext search failed: '.$e->getMessage());
        }

        return [];
    }
}

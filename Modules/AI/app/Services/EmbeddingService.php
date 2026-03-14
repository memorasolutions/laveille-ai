<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\AI\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AI\Models\KnowledgeChunk;
use Modules\Settings\Models\Setting;

class EmbeddingService
{
    private const ENDPOINT = 'https://openrouter.ai/api/v1/embeddings';

    private const MODEL = 'openai/text-embedding-3-small';

    private const MAX_BATCH_SIZE = 20;

    /** @return array<float> */
    public function embed(string $text): array
    {
        $apiKey = Setting::get('ai.openrouter_api_key');

        if (empty($text) || empty($apiKey)) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->post(self::ENDPOINT, [
                'model' => self::MODEL,
                'input' => $text,
            ]);

            if ($response->successful()) {
                return $response->json('data.0.embedding', []);
            }

            Log::error('EmbeddingService: API error', ['status' => $response->status()]);

            return [];
        } catch (\Exception $e) {
            Log::error('EmbeddingService: '.$e->getMessage());

            return [];
        }
    }

    /** @return array<array<float>> */
    public function embedBatch(array $texts): array
    {
        $apiKey = Setting::get('ai.openrouter_api_key');

        if (empty($texts) || empty($apiKey)) {
            return [];
        }

        $texts = array_slice($texts, 0, self::MAX_BATCH_SIZE);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->post(self::ENDPOINT, [
                'model' => self::MODEL,
                'input' => $texts,
            ]);

            if ($response->successful()) {
                return array_map(
                    fn (array $item) => $item['embedding'] ?? [],
                    $response->json('data', [])
                );
            }

            Log::error('EmbeddingService: batch API error', ['status' => $response->status()]);

            return [];
        } catch (\Exception $e) {
            Log::error('EmbeddingService: batch '.$e->getMessage());

            return [];
        }
    }

    public function cosineSimilarity(array $a, array $b): float
    {
        if (empty($a) || empty($b) || count($a) !== count($b)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $value) {
            $dotProduct += $value * $b[$i];
            $normA += $value * $value;
            $normB += $b[$i] * $b[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }

    /** @return Collection<int, array{chunk: KnowledgeChunk, similarity: float}> */
    public function findSimilar(array $queryEmbedding, int $limit = 5, ?int $tenantId = null): Collection
    {
        if (empty($queryEmbedding)) {
            return collect();
        }

        $query = KnowledgeChunk::whereNotNull('embedding')
            ->whereHas('document', fn ($q) => $q->where('is_active', true));

        if ($tenantId !== null) {
            $query->whereHas('document', fn ($q) => $q->where('tenant_id', $tenantId));
        }

        $chunks = $query->with('document')->get();

        return $chunks->map(function (KnowledgeChunk $chunk) use ($queryEmbedding) {
            $chunkEmbedding = $chunk->embedding_array;
            if (empty($chunkEmbedding)) {
                return null;
            }

            return [
                'chunk' => $chunk,
                'similarity' => $this->cosineSimilarity($queryEmbedding, $chunkEmbedding),
            ];
        })
            ->filter()
            ->sortByDesc('similarity')
            ->take($limit)
            ->values();
    }

    /** @return Collection<int, KnowledgeChunk> */
    public function findSimilarFulltext(string $query, int $limit = 5, ?int $tenantId = null): Collection
    {
        if (empty($query)) {
            return collect();
        }

        $sql = '
            SELECT kc.*, MATCH(kc.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
            FROM ai_knowledge_chunks kc
            INNER JOIN ai_knowledge_documents d ON kc.document_id = d.id
            WHERE d.is_active = 1
        ';
        $params = [$query];

        if ($tenantId !== null) {
            $sql .= ' AND d.tenant_id = ?';
            $params[] = $tenantId;
        }

        $sql .= ' HAVING relevance > 0 ORDER BY relevance DESC LIMIT ?';
        $params[] = $limit;

        $results = DB::select($sql, $params);

        return collect($results)->map(
            fn ($row) => KnowledgeChunk::hydrate([(array) $row])->first()
        );
    }
}

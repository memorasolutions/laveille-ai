<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\AI\Console;

use Illuminate\Console\Command;
use Modules\AI\Models\KnowledgeChunk;
use Modules\AI\Services\EmbeddingService;
use Modules\AI\Services\KnowledgeBaseService;

class SyncKnowledgeBaseCommand extends Command
{
    protected $signature = 'ai:sync-kb
                            {--type=all : Type to sync (all|faq|page|article)}
                            {--force : Force re-sync}
                            {--embed : Generate embeddings after sync}';

    protected $description = 'Synchronise la base de connaissances IA depuis FAQ, pages et articles';

    public function handle(KnowledgeBaseService $kbService, EmbeddingService $embeddingService): int
    {
        $type = $this->option('type');
        $generateEmbeddings = $this->option('embed');

        $this->info('Synchronisation KB en cours...');

        $syncTypes = $type === 'all' ? ['faq', 'page', 'article'] : [$type];
        $totalSynced = 0;

        foreach ($syncTypes as $syncType) {
            $this->line("  Sync {$syncType}...");
            $count = $kbService->syncFromSource($syncType);
            $totalSynced += $count;
            $this->info("  {$syncType}: {$count} documents synchronisés");
        }

        // Générer embeddings si demandé
        $embeddingsGenerated = 0;
        if ($generateEmbeddings) {
            $this->info('Génération des embeddings...');

            $chunks = KnowledgeChunk::whereNull('embedding')
                ->whereHas('document', fn ($q) => $q->where('is_active', true))
                ->get();

            $total = $chunks->count();
            if ($total === 0) {
                $this->line('  Aucun chunk sans embedding');
            } else {
                $this->line("  {$total} chunks à traiter");

                // Batch par 20 (limite API)
                foreach ($chunks->chunk(20) as $batch) {
                    $texts = $batch->pluck('content')->all();
                    $embeddings = $embeddingService->embedBatch($texts);

                    foreach ($batch->values() as $index => $chunk) {
                        if (isset($embeddings[$index]) && ! empty($embeddings[$index])) {
                            $chunk->update(['embedding' => json_encode($embeddings[$index])]);
                            $embeddingsGenerated++;
                        }
                    }
                }

                $this->info("  {$embeddingsGenerated} embeddings générés");
            }
        }

        // Résumé
        $totalChunks = KnowledgeChunk::count();
        $withEmbeddings = KnowledgeChunk::whereNotNull('embedding')->count();

        $this->newLine();
        $this->info('Résumé :');
        $this->line("  Documents synchronisés : {$totalSynced}");
        $this->line("  Chunks total : {$totalChunks}");
        $this->line("  Avec embeddings : {$withEmbeddings}");

        if ($generateEmbeddings) {
            $this->line("  Embeddings générés : {$embeddingsGenerated}");
        }

        $this->info('Terminé.');

        return self::SUCCESS;
    }
}

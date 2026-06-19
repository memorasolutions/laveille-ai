<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Console;

use Illuminate\Console\Command;
use Modules\Academy\Models\Course;
use Throwable;

/**
 * Commande `academy:reindex` — (ré)indexe les cours publiés/publics dans Scout.
 * Défensive : si Scout ou Meilisearch ne sont pas configurés, la commande est un no-op.
 */
class CourseReindexCommand extends Command
{
    protected $signature = 'academy:reindex';

    protected $description = 'Réindexe tous les cours publiés dans Scout/Meilisearch';

    public function handle(): void
    {
        // Garde-fou : Scout doit être disponible
        if (! class_exists('\Laravel\Scout\EngineManager')) {
            $this->info('Scout non disponible, indexation ignorée.');

            return;
        }

        try {
            // Indexer les cours publiés + publics
            $indexed = Course::published()->count();
            Course::published()->searchable();
            $this->info("Indexation : {$indexed} cours publiés et publics traités.");

            // Retirer de l'index les cours non-publiés ou privés (chunked pour la mémoire)
            $removed = 0;
            Course::where(function ($q) {
                $q->where('status', '!=', 'published')
                    ->orWhere('visibility', '!=', 'public');
            })->chunkById(200, function ($courses) use (&$removed) {
                $courses->unsearchable();
                $removed += $courses->count();
            });

            $this->info("Désindexation : {$removed} cours non-publiés/privés retirés de l'index.");
            $this->info('Réindexation terminée avec succès.');
        } catch (Throwable $e) {
            $this->error("Erreur lors de la réindexation : {$e->getMessage()}");
        }
    }
}

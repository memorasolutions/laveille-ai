<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: pose reviewed_at / reviewed_by sur les fiches REELLEMENT relues.
 * MCP: squelette genere par hermes (model_invoke code), corrige ici sur trois points.
 * RAISON: 313 fiches portent des extraits probants d'une vraie verification, mais aucune ne
 *         porte de date de relecture. La signature editoriale et le JSON-LD reviewedBy ne
 *         s'affichent donc nulle part - le site fait le travail sans le montrer.
 */

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsArticle;

class BackfillReviewedAtCommand extends Command
{
    protected $signature = 'news:backfill-reviewed-at
                            {--appliquer : Exécute réellement les écritures (sinon simulation)}
                            {--par= : Libellé du relecteur}
                            {--limit=0 : Plafond de fiches traitées (0 = aucun)}';

    protected $description = 'Pose reviewed_at (= content_updated_at) sur les fiches portant des paires de preuve';

    private const RELECTEUR_DEFAUT = 'la rédaction de laveille.ai';

    public function handle(): int
    {
        $relecteur = (string) ($this->option('par') ?: self::RELECTEUR_DEFAUT);
        $simulation = ! $this->option('appliquer');
        $limite = (int) $this->option('limit');

        $eligibles = 0;
        $ecrites = 0;
        $ignoreesSansDate = 0;

        // Comptage informatif : ce qui porte deja une date n'est jamais retouche.
        $dejaPourvues = NewsArticle::query()
            ->whereNotNull('editorial_proof_pairs')->where('editorial_proof_pairs', '!=', '')
            ->whereNotNull('reviewed_at')->count();

        $base = fn () => NewsArticle::query()
            ->whereNotNull('editorial_proof_pairs')
            ->where('editorial_proof_pairs', '!=', '')
            ->whereNull('reviewed_at');

        $total = $base()->count();
        if ($limite > 0) {
            $total = min($total, $limite);
        }

        if ($total === 0) {
            $this->info('Aucune fiche éligible : rien à faire.');
            $this->rapport(0, 0, 0, $dejaPourvues, $simulation);

            return self::SUCCESS;
        }

        $this->info($simulation
            ? "SIMULATION sur {$total} fiche(s). Rien ne sera écrit. Ajouter --appliquer pour exécuter."
            : "ÉCRITURE sur {$total} fiche(s), relecteur « {$relecteur} ».");

        $barre = $this->output->createProgressBar($total);
        $barre->start();

        // chunkById est sûr ici malgré le filtre sur reviewed_at : il pagine par `id > dernier`,
        // pas par décalage. Un chunk() ordinaire, lui, sauterait des lignes à chaque écriture.
        $base()->select('id', 'content_updated_at')->chunkById(200, function ($lot) use (
            &$eligibles, &$ecrites, &$ignoreesSansDate, $barre, $simulation, $relecteur, $limite
        ) {
            $ecritures = [];

            foreach ($lot as $fiche) {
                if ($limite > 0 && $eligibles >= $limite) {
                    break;
                }
                $eligibles++;
                $barre->advance();

                // Jamais de date devinée : sans content_updated_at, on n'invente rien.
                if (empty($fiche->content_updated_at)) {
                    $ignoreesSansDate++;

                    continue;
                }

                $ecritures[] = ['id' => $fiche->id, 'date' => $fiche->content_updated_at];
            }

            if (! $simulation && $ecritures !== []) {
                // Écriture DIRECTE plutôt que save() : save() toucherait `updated_at` et
                // déclencherait les observers, alors qu'on ne modifie pas le contenu.
                DB::transaction(function () use ($ecritures, $relecteur, &$ecrites) {
                    foreach ($ecritures as $e) {
                        DB::table('news_articles')->where('id', $e['id'])->update([
                            'reviewed_at' => $e['date'],
                            'reviewed_by' => $relecteur,
                        ]);
                        $ecrites++;
                    }
                });
            } elseif ($simulation) {
                $ecrites += count($ecritures);
            }

            if ($limite > 0 && $eligibles >= $limite) {
                return false;
            }

            return true;
        });

        $barre->finish();
        $this->newLine(2);
        $this->rapport($eligibles, $ecrites, $ignoreesSansDate, $dejaPourvues, $simulation);

        if (! $simulation && $ecrites > 0) {
            Log::info('news:backfill-reviewed-at', ['fiches_datees' => $ecrites, 'relecteur' => $relecteur]);
        }

        return self::SUCCESS;
    }

    private function rapport(int $eligibles, int $ecrites, int $sansDate, int $deja, bool $simulation): void
    {
        $this->table(
            ['Éligibles', $simulation ? 'Seraient datées' : 'Datées', 'Ignorées (aucune date)', 'Déjà pourvues'],
            [[$eligibles, $ecrites, $sansDate, $deja]]
        );
    }
}

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

/**
 * Second passage du nettoyage rétroactif de meta_description figée (2026-08-30, tâche #1942,
 * corrige la portée trop étroite de la mesure de
 * 2026_08_30_000000_reset_stale_meta_description_after_correction.php - JAMAIS réédité : une
 * migration déjà exécutée en production ne se modifie pas après coup, elle se complète par une
 * suivante).
 *
 * CE QUI ÉTAIT TROP ÉTROIT : la première mesure ne retenait que les lignes `payload appliqué`
 * dont `keys_applied` contient la clé EXACTE `summary`. Or le déclencheur RÉEL de l'invalidation
 * automatique ajoutée à NewsApplyCommand::applyPayload() (`$remplaceLeResumeAffiche`) est
 * `summary` OU `structured_summary` - cette seconde clé apparaît aussi quand SEUL
 * `composed_summary` est fourni (il écrit directement `structured_summary`, jamais une clé
 * `composed_summary` littérale dans keys_applied). La première mesure a donc raté toute
 * correction passée par `composed_summary` seul, sans jamais toucher `summary`.
 *
 * PREUVE CONCRÈTE que ce n'était pas qu'une nuance théorique : la fiche #2327 (« Meta licencie
 * ... »), très antérieure à /actu2, a reçu le 2026-08-27 une correction de titre via
 * `news:apply --enrich` (`keys_applied: ["title","seo_title","structured_summary"]` - PAS
 * `summary`, absente donc de la première liste). Vérifiée en production le jour même de ce
 * correctif (`news:brief 2327`) : le TITRE affiche désormais « 8 000 employés », alors que
 * `meta_description` affichait ENCORE « jusqu'à 16 000 licenciements » - exactement le défaut
 * signalé par le fondateur (tâche #1942), toujours en ligne pour Google au moment de cette
 * mesure, et absent de la première migration.
 *
 * MESURE CORRIGÉE, même fenêtre et mêmes fichiers (`storage/logs/composition-2026-08-{22..29}.log`,
 * canal `composition`), filtre élargi à `summary` OU `structured_summary` dans `keys_applied` :
 * 106 fiches distinctes (au lieu de 94). IDS_A_VERIFIER_LOT_2 ci-dessous est le DELTA exact - les
 * 12 IDs présents dans cette mesure élargie mais absents de la liste déjà couverte par le premier
 * passage (les 94 déjà vérifiées non concernées restent hors de ce second lot ; les revérifier ne
 * changerait rien, whereNotNull() y trouverait de nouveau zéro résultat).
 *
 * ACTION, DOWN() : identiques au premier passage - seuls les IDs du lot dont meta_description est
 * ENCORE non NULL à l'exécution sont touchés, remis à `null` (cascade automatique), ancienne
 * valeur journalisée avant écrasement, down() est un NO-OP délibéré pour la même raison.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsArticle;

return new class extends Migration
{
    /**
     * Delta exact du second passage (voir le bloc de commentaires en tête de fichier) - 11
     * fiches très antérieures à /actu2 (443 à 22925) et une seule fiche récente ratée par la
     * première mesure (34670, corrigée le 2026-08-22 via `structured_summary` seul).
     */
    private const IDS_A_VERIFIER_LOT_2 = [
        443, 1426, 1659, 1936, 2327, 2365, 8333, 19419, 20762, 21663, 22925, 34670,
    ];

    public function up(): void
    {
        $articles = NewsArticle::whereIn('id', self::IDS_A_VERIFIER_LOT_2)
            ->whereNotNull('meta_description')
            ->get(['id', 'slug', 'meta_description']);

        if ($articles->isEmpty()) {
            Log::channel('composition')->info('news:migration - reset meta_description perimee (lot 2) : aucune fiche concernee.', [
                'candidats_verifies' => count(self::IDS_A_VERIFIER_LOT_2),
            ]);

            return;
        }

        DB::transaction(function () use ($articles): void {
            foreach ($articles as $article) {
                Log::channel('composition')->info('news:migration - meta_description perimee remise a la cascade automatique (lot 2)', [
                    'article_id' => $article->id,
                    'slug' => $article->slug,
                    'meta_description_avant' => $article->meta_description,
                ]);

                $article->update(['meta_description' => null]);
            }
        });

        Log::channel('composition')->info('news:migration - reset meta_description perimee (lot 2) : termine.', [
            'candidats_verifies' => count(self::IDS_A_VERIFIER_LOT_2),
            'fiches_corrigees' => $articles->count(),
            'ids_corriges' => $articles->pluck('id')->all(),
        ]);
    }

    /**
     * NO-OP volontaire, même raison que le premier passage : restaurer l'ancienne valeur
     * réintroduirait la description périmée que cette migration corrige. Valeurs d'origine
     * journalisées dans storage/logs/composition-*.log (entrée "... (lot 2)").
     */
    public function down(): void {}
};

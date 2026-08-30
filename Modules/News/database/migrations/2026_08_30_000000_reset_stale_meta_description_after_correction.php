<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

/**
 * Nettoyage rétroactif de meta_description figée (2026-08-30, tâche #1942 - angle mort signalé
 * par le fondateur).
 *
 * CONSTAT : la balise publique <meta name="description">/og:description d'une fiche affiche
 * meta_description si elle est renseignée, sinon NewsArticle::displayExcerpt() calculé depuis le
 * résumé COURANT (Modules\News\resources\views\public\show.blade.php, ligne @section('meta_description', ...)).
 * Or meta_description n'a jamais été dans la liste blanche de NewsApplyCommand
 * (ALLOWED_PAYLOAD_KEYS) avant le correctif accompagnant cette migration : la porte de correction
 * éditoriale (`news:apply --enrich --payload=...`) pouvait corriger `summary`/`title` d'une fiche
 * DÉJÀ PUBLIÉE sans jamais pouvoir toucher sa description. Une fiche dont meta_description avait
 * été posée une fois - à l'ingestion RSS par FetchNewsCommand, seul écrivain de ce champ avant ce
 * jour avec ReprocessArticlesCommand et le formulaire admin manuel - gardait donc l'ANCIENNE
 * description en ligne pour Google après une correction de fond (chiffre faux, affirmation
 * déformée). Les fiches composées par /actu2 (news:create-draft) n'écrivent JAMAIS ce champ à la
 * création : seules les fiches d'AVANT /actu2, rattrapées par une correction récente, sont donc
 * concernées.
 *
 * MESURE, reproductible : grep de `storage/logs/composition-2026-08-{22..29}.log` (rétention de
 * 14 jours, canal `composition`, config/logging.php) sur les lignes
 * `news:apply - payload appliqué` dont `keys_applied` contient la clé EXACTE `summary` (jamais un
 * simple sous-texte : `structured_summary`/`composed_summary` contiennent aussi la sous-chaîne
 * "summary" et ne comptent pas). 94 fiches DISTINCTES ressortent de cette fenêtre. IDS_A_VERIFIER
 * ci-dessous est cette liste EXACTE, figée à la date de la mesure - ni plus ni moins.
 *
 * ACTION DE CETTE MIGRATION : parmi ces 94 IDs, seuls ceux dont meta_description est ENCORE non
 * NULL au moment où la migration s'exécute sont touchés (whereNotNull - le nombre réel de fiches
 * concernées, répondant à la tâche #1942, est donc le compte que cette migration journalise
 * elle-même à l'exécution, pas un chiffre supposé à l'avance). Ceux-là sont remis à `null` : la
 * cascade automatique de show.blade.php reprend alors la main avec une description calculée
 * depuis le résumé ACTUEL, donc forcément à jour. Rien d'autre n'est modifié (titre, résumé,
 * slug... hors périmètre).
 *
 * down() est un NO-OP délibéré, pas un oubli : l'ancienne valeur de meta_description qu'il
 * faudrait restaurer est EXACTEMENT la valeur périmée que cette migration corrige - la
 * réintroduire serait annuler le correctif, pas annuler la migration. L'ancienne valeur de
 * chaque ligne touchée reste néanmoins consultable : journalisée intégralement ci-dessous (canal
 * `composition`, même canal que NewsApplyCommand) avant l'écrasement, jamais perdue en silence.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsArticle;

return new class extends Migration
{
    /**
     * Fiches ayant reçu une correction de `summary` via news:apply entre le 2026-08-22 et le
     * 2026-08-29 (mesure ci-dessus). Liste figée : NE PAS enrichir cette constante au fil de
     * futures corrections - le garde-fou permanent contre la récidive vit désormais dans
     * NewsApplyCommand::applyPayload() (invalidation automatique de meta_description sur toute
     * correction future de summary/composed_summary), pas dans une migration qui se rouvrirait
     * indéfiniment.
     */
    private const IDS_A_VERIFIER = [
        34673, 35168, 35176, 35178, 35185, 35289, 35303, 35312, 35313, 35314, 35315, 35316, 35330,
        35337, 35341, 35349, 35872, 35958, 35961, 35964, 36011, 36037, 36071, 36089, 36090, 36543,
        36603, 36604, 36618, 36620, 36626, 36661, 36663, 36667, 36676, 37451, 37455, 37483, 37498,
        37508, 37511, 37517, 37527, 37535, 37546, 37547, 37549, 37552, 37554, 37556, 37572, 37573,
        37574, 37575, 38145, 38214, 38217, 38222, 38251, 38256, 38258, 38264, 38269, 38292, 38293,
        38295, 38296, 38303, 38304, 38305, 38306, 38307, 38308, 38900, 38921, 38933, 38942, 38948,
        38959, 38960, 38961, 39357, 39451, 39460, 39461, 39464, 39471, 39472, 39476, 39486, 39524,
        39526, 39527, 39528,
    ];

    public function up(): void
    {
        $articles = NewsArticle::whereIn('id', self::IDS_A_VERIFIER)
            ->whereNotNull('meta_description')
            ->get(['id', 'slug', 'meta_description']);

        if ($articles->isEmpty()) {
            Log::channel('composition')->info('news:migration - reset meta_description perimee : aucune fiche concernee.', [
                'candidats_verifies' => count(self::IDS_A_VERIFIER),
            ]);

            return;
        }

        DB::transaction(function () use ($articles): void {
            foreach ($articles as $article) {
                Log::channel('composition')->info('news:migration - meta_description perimee remise a la cascade automatique', [
                    'article_id' => $article->id,
                    'slug' => $article->slug,
                    'meta_description_avant' => $article->meta_description,
                ]);

                $article->update(['meta_description' => null]);
            }
        });

        Log::channel('composition')->info('news:migration - reset meta_description perimee : termine.', [
            'candidats_verifies' => count(self::IDS_A_VERIFIER),
            'fiches_corrigees' => $articles->count(),
            'ids_corriges' => $articles->pluck('id')->all(),
        ]);
    }

    /**
     * NO-OP volontaire - voir le bloc de commentaires en tête de fichier : restaurer l'ancienne
     * valeur reviendrait à réintroduire la description périmée que cette migration corrige.
     * L'ancienne valeur de chaque fiche touchée reste lisible dans storage/logs/composition-*.log
     * (entrée "meta_description perimee remise a la cascade automatique", horodatée à
     * l'exécution de up()).
     */
    public function down(): void {}
};

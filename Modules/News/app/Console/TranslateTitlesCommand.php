<?php

declare(strict_types=1);

namespace Modules\News\Console;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Traduction des titres PRÉCALCULÉE, hors du chemin synchrone de l'écran de composition (demande
 * du propriétaire, 2026-08-24). L'écran /admin/news/composition bornait sa liste à 200 lignes
 * pour ne jamais dépasser le budget de 8 secondes de la traduction en lot synchrone
 * (Modules\Core\Services\TranslationService::translateBatch) : 452 des 652 actualités collectées
 * le 23 août restaient invisibles. Cette commande retire la traduction du chemin de l'écran en
 * l'écrivant à l'avance, en base, sur la colonne 'title_fr' (voir la migration
 * 2026_08_24_090000_add_title_fr_to_news_articles) - l'écran ne fait plus que LIRE cette colonne
 * (Modules\News\Http\Controllers\Admin\NewsCompositionController::titresTraduits()), sauf pour un
 * rattrapage synchrone borné à 40 titres sur ce qui n'a pas encore été traduit.
 *
 * Même critère de sélection que titresTraduits() ci-dessus (DRY volontaire, decrit deux fois par
 * nécessité technique - une requête SQL, l'autre un filtre PHP sur une collection déjà chargée -
 * mais UNE seule règle métier) : source non francophone (`language` non vide et ne commençant pas
 * par « fr ») et 'seo_title' vide (un titre déjà réécrit éditorialement n'est jamais traduit).
 *
 * MÉCANISME DE REPRISE, volontaire et sans boucle de tentative : chaque lot de BATCH_SIZE titres
 * est envoyé à TranslationService::translateBatch(), qui rejette le lot ENTIER si le compte de
 * lignes rendues diverge. Un lot rejeté laisse simplement ses articles avec 'title_fr' à NULL -
 * ils seront de nouveau candidats à la PROCHAINE exécution planifiée (toutes les heures, voir
 * Modules\News\Providers\NewsServiceProvider::registerCommandSchedules()). Retenter en boucle
 * dans la même exécution referait échouer le même appel pour la même raison (budget, format) sans
 * bénéfice, et retarderait les lots suivants.
 *
 * IDEMPOTENTE par construction : la sélection porte sur 'title_fr' IS NULL, donc un article déjà
 * traduit ne redevient jamais candidat, quel que soit le nombre de fois où la commande tourne.
 *
 * RÉVISION 2026-08-24 (mesure en PRODUCTION, même jour que la mise en service) : le budget de
 * config (services.openrouter.translation_budget_seconds, 15 s) protège le chemin SYNCHRONE de
 * l'écran (Cloudflare coupe vers 100 s) - il n'a AUCUNE raison de s'appliquer à cette commande,
 * qui tourne en arrière-plan. Résultat mesuré : un lot RÉEL de 40 titres a pris 36,6 secondes
 * pour une réponse au format parfaitement conforme (compte de lignes concordant) - rejeté par le
 * budget de 15 s AVANT même d'avoir reçu la réponse. La taille du lot passe donc de 40 à
 * BATCH_SIZE = 20 titres (mesure : 20 ≈ 18,3 s, moitié de 36,6 s), et translateBatch() reçoit
 * désormais un budget explicite de BUDGET_SECONDES = 120 s (voir
 * Modules\Core\Services\TranslationService::translateBatch(), paramètre $budgetSecondes ajouté ce
 * même jour) - large marge sous les 45 s par modèle déjà consentis, pour laisser la cascade de
 * modèles retenter au besoin sans jamais couper une réponse conforme en cours de route.
 *
 * MCP: SELF (<5 lignes utiles par branche)
 * RAISON: porte serveur unique de la traduction précalculée, jamais d'écriture ailleurs.
 */

use Illuminate\Console\Command;
use Modules\Core\Services\TranslationService;
use Modules\News\Models\NewsArticle;

class TranslateTitlesCommand extends Command
{
    protected $signature = 'news:translate-titles
        {--limit=200 : Nombre maximum de titres traités en une exécution}
        {--dry-run : Compte et liste les candidats SANS traduire ni écrire}';

    protected $description = "Précalcule la traduction française des titres d'actualités (title_fr), hors du chemin synchrone de l'écran de composition";

    /**
     * Taille des lots envoyés à TranslationService::translateBatch() - même contrainte de format
     * que l'écran (une réponse numérotée ligne à ligne). Ramenée de 40 à 20 le 2026-08-24 :
     * mesure en production, un lot de 40 titres prend 36,6 secondes ; 20 titres ≈ 18 secondes,
     * confortablement sous BUDGET_SECONDES.
     */
    private const BATCH_SIZE = 20;

    /**
     * Budget (en secondes) passé explicitement à TranslationService::translateBatch() pour CETTE
     * commande - distinct du budget de config (services.openrouter.translation_budget_seconds,
     * 15 s), qui protège le chemin synchrone de l'écran de composition (Cloudflare) et n'a aucune
     * raison de s'appliquer ici. Mesure en production, 2026-08-24 : un lot de 40 titres a pris
     * 36,6 secondes pour une réponse conforme, rejetée à tort par les 15 s du budget de l'écran.
     * 120 s laisse une large marge à la cascade de modèles (jusqu'à 45 s par modèle déjà
     * consentis ailleurs) sans jamais couper une réponse en cours de route.
     */
    private const BUDGET_SECONDES = 120;

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $isDryRun = (bool) $this->option('dry-run');

        $candidats = $this->candidatesQuery()->orderBy('id')->limit($limit)->get();

        if ($isDryRun) {
            $this->table(['Mesure', 'Valeur'], [
                ['Candidats sélectionnés (limite '.$limit.')', $candidats->count()],
                ['Total restant à traduire (toutes exécutions)', $this->candidatesQuery()->count()],
            ]);
            $this->info('Mode --dry-run : aucune traduction effectuée, aucune écriture.');

            return self::SUCCESS;
        }

        $processed = 0;
        $succeeded = 0;
        $failed = 0;

        foreach ($candidats->chunk(self::BATCH_SIZE) as $lot) {
            $processed += $lot->count();

            $resultat = TranslationService::translateBatch(
                $lot->map(static fn (NewsArticle $a): string => (string) $a->title)->values()->all(),
                budgetSecondes: self::BUDGET_SECONDES,
            );

            if ($resultat['statut'] !== 'ok') {
                // Mécanisme de reprise : rien n'est écrit, tout le lot reste à 'title_fr' NULL et
                // sera de nouveau candidat à la prochaine exécution planifiée.
                $failed += $lot->count();

                continue;
            }

            foreach ($lot->values() as $i => $article) {
                $traduit = $resultat['titres'][$i] ?? null;

                if (! is_string($traduit) || trim($traduit) === '') {
                    $failed++;

                    continue;
                }

                $article->title_fr = $traduit;
                $article->title_fr_at = now('America/Toronto');
                $article->saveQuietly();
                $succeeded++;
            }
        }

        $restant = $this->candidatesQuery()->count();

        $this->table(['Mesure', 'Valeur'], [
            ['Traités', $processed],
            ['Réussis', $succeeded],
            ['Échoués (repris à la prochaine exécution)', $failed],
            ['Restants (toutes exécutions)', $restant],
        ]);

        return self::SUCCESS;
    }

    /**
     * Même critère que NewsCompositionController::titresTraduits() : source non francophone et
     * 'seo_title' vide, ici en SQL puisque la sélection porte sur l'ensemble de la table plutôt
     * que sur une page déjà chargée en mémoire.
     */
    private function candidatesQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return NewsArticle::query()
            ->whereNull('title_fr')
            ->where(function ($q) {
                $q->whereNull('seo_title')->orWhere('seo_title', '');
            })
            ->whereHas('source', function ($q) {
                $q->whereNotNull('language')
                    ->where('language', '<>', '')
                    ->whereRaw('LOWER(language) NOT LIKE ?', ['fr%']);
            });
    }
}

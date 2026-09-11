<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Compteur de vues/consultations partagé, réutilisable par tout module
 * (Tools, News, Authors, Dictionary...). Un seul point d'appel plutôt
 * qu'une copie du même `increment()` par contrôleur.
 *
 * Corrige l'incident 2026-08-13 : un compteur incrémenté sans filtre sur
 * chaque requête HTTP (aucun tri robots, aucune déduplication) a produit
 * un rapport vues/clics réels de 8 à 487x selon la fiche - inutilisable
 * comme critère de tri ou d'élagage SEO.
 *
 * Usage : ViewCounterService::record($article, 'views_count');
 */
final class ViewCounterService
{
    /**
     * Incrémente $column sur $model si la requête courante est jugée
     * légitime : pas un robot connu (config('view_counter.bot_patterns')),
     * pas une répétition rapprochée de la même visite. Incrémente aussi,
     * si elle existe, une colonne "compteur propre" repartie de zéro
     * (voir config('view_counter.verified_suffix')) - la colonne historique
     * n'est jamais réinitialisée ni supprimée, seulement filtrée à partir
     * de maintenant.
     *
     * Ne lève JAMAIS d'exception : un compteur qui échoue ne doit jamais
     * faire tomber une page publique (garde-fou zéro casse).
     */
    public static function record(Model $model, string $column = 'views_count'): void
    {
        try {
            $request = request();

            if (! $request instanceof Request || $request->isMethod('HEAD') || $request->isMethod('OPTIONS')) {
                return;
            }

            $table = $model->getTable();
            if (! Schema::hasColumn($table, $column)) {
                return;
            }

            if (self::isKnownBot($request->userAgent())) {
                return;
            }

            if (self::isDuplicateVisit($model, $column, $request)) {
                return;
            }

            $columns = [$column];
            $verifiedColumn = $column.(string) config('view_counter.verified_suffix', '_verified');
            if (Schema::hasColumn($table, $verifiedColumn)) {
                $columns[] = $verifiedColumn;
            }

            // ACTION : lire une page n'est pas la modifier - `Builder::increment()` pose sinon
            // `updated_at` à chaque vue (vendor addUpdatedAtColumn()), que ce compteur alimente
            // ensuite une date de modification publiée (JSON-LD/sitemap/texte visible) à tort.
            // `addUpdatedAtColumn()` n'écrase QUE les clés absentes de $extra : y placer déjà
            // `updated_at` figé sur lui-même (colonne = colonne, échappée par la grammaire du
            // connecteur - jamais une concaténation de chaîne) neutralise l'écriture sans toucher
            // au reste de la ligne. Vaut pour les DEUX incréments ci-dessous (compteur historique
            // et compteur "propre").
            // RAISON: docs/specs/2026-09-11-mesure-visibilite-et-fraicheur.md (MESURE B) - 78 des
            // 80 termes du glossaire mesurables portaient déjà plus de 24h de dérive, jusqu'à ~50
            // jours, entre updated_at et leur dernière vraie édition.
            $pinnedUpdatedAt = self::pinUpdatedAtExtra($model, $table);

            $model::query()->whereKey($model->getKey())->increment($columns[0], 1, $pinnedUpdatedAt);
            if (isset($columns[1])) {
                $model::query()->whereKey($model->getKey())->increment($columns[1], 1, $pinnedUpdatedAt);
            }
        } catch (Throwable $e) {
            // Silence volontaire (garde-fou zéro casse) : un échec de comptage ne doit
            // jamais empêcher l'affichage d'une page publique.
        }
    }

    /**
     * Construit le tableau `$extra` à passer à `increment()` pour figer `updated_at` sur
     * lui-même, plutôt que de laisser `addUpdatedAtColumn()` (vendor) le réécrire à `now()`.
     * Ne pose la clé QUE si le modèle utilise réellement les horodatages ET que la colonne
     * existe sur la table : un modèle sans horodatages (`usesTimestamps() === false`) ou dont
     * la colonne `updated_at` n'existe pas recevrait sinon une écriture sur une colonne absente.
     * Le nom de colonne est échappé par la grammaire du connecteur courant (jamais par
     * concaténation de chaîne) avant d'être posé dans l'expression brute.
     */
    private static function pinUpdatedAtExtra(Model $model, string $table): array
    {
        if (! $model->usesTimestamps()) {
            return [];
        }

        $updatedAtColumn = $model->getUpdatedAtColumn();
        if ($updatedAtColumn === null || ! Schema::hasColumn($table, $updatedAtColumn)) {
            return [];
        }

        $grammar = $model->getConnection()->getQueryGrammar();

        return [$updatedAtColumn => new Expression($grammar->wrap($updatedAtColumn))];
    }

    /**
     * Comparaison insensible à la casse du User-Agent contre la liste de
     * motifs configurée. Un User-Agent absent/vide n'est PAS traité comme
     * un robot par défaut (ambigu - clients légitimes minimalistes, tests) ;
     * seuls les motifs connus excluent explicitement.
     */
    private static function isKnownBot(?string $userAgent): bool
    {
        if (! $userAgent || trim($userAgent) === '') {
            return false;
        }

        $userAgent = mb_strtolower($userAgent);

        foreach ((array) config('view_counter.bot_patterns', []) as $pattern) {
            $pattern = mb_strtolower((string) $pattern);
            if ($pattern !== '' && str_contains($userAgent, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Déduplication courte fenêtre SANS aucune donnée personnelle durable
     * (Loi 25) : la clé combine l'identifiant de session déjà utilisé par
     * le site (cookie de session standard, aucune collecte supplémentaire)
     * ou, à défaut de session démarrée, un hachage SHA-256 IP+UA. Rien
     * n'est écrit en base ni journalisé : la clé vit uniquement dans le
     * cache applicatif et expire automatiquement après la fenêtre
     * configurée (30 minutes par défaut) - empreinte non identifiante
     * (hachée, jamais recoupée avec un compte) et éphémère (auto-expirée,
     * jamais conservée après la fenêtre).
     */
    private static function isDuplicateVisit(Model $model, string $column, Request $request): bool
    {
        $identity = $request->hasSession() && $request->session()->isStarted()
            ? 'sess:'.$request->session()->getId()
            : 'anon:'.$request->ip().'|'.mb_substr((string) $request->userAgent(), 0, 80);

        $modelKey = $model::class.':'.$model->getKey().':'.$column;
        $cacheKey = 'view_counter:'.hash('sha256', $identity.'|'.$modelKey);

        if (Cache::has($cacheKey)) {
            return true;
        }

        $windowMinutes = max(1, (int) config('view_counter.dedup_window_minutes', 30));
        Cache::put($cacheKey, true, now()->addMinutes($windowMinutes));

        return false;
    }
}

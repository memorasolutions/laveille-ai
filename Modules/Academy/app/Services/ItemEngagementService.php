<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F18 - SOURCE UNIQUE (DRY) de l'activité « notes + commentaires » d'un item de leçon
 * (parité Moodle ratings/comments). Lue côté SERVEUR par le contrôleur d'actions et
 * par le lecteur (lesson.blade). Les bornes, le nom du honeypot et le PRÉCHARGEMENT
 * anti N+1 (moyennes des notes, note de l'utilisateur, commentaires par item) ne
 * vivent qu'ICI.
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Academy\Models\ItemComment;
use Modules\Academy\Models\ItemRating;

final class ItemEngagementService
{
    /** Longueur maximale d'un commentaire (anti-abus). */
    public const COMMENT_MAX = 2000;

    /** Note minimale / maximale (étoiles). */
    public const RATING_MIN = 1;
    public const RATING_MAX = 5;

    /** Nom du champ honeypot anti-spam (caché, doit rester vide). */
    public const HONEYPOT = 'hp_url';

    // ─────────────────────────────────────────────────────────────────────────────
    // PRÉCHARGEMENT (anti N+1) - calculé une fois par leçon dans le contrôleur
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Statistiques de note par item : [lesson_item_id => {votes_count, avg_value}].
     * UNE requête agrégée (GROUP BY) pour TOUS les items de la leçon. Un item sans
     * note est simplement absent de la map (moyenne 0 / 0 note côté vue).
     *
     * @param  iterable<mixed>  $items  items ou ids
     */
    public static function preloadRatingStats(iterable $items): Collection
    {
        $itemIds = self::ids($items);

        if ($itemIds === []) {
            return collect();
        }

        return ItemRating::whereIn('lesson_item_id', $itemIds)
            ->selectRaw('lesson_item_id, COUNT(*) as votes_count, AVG(value) as avg_value')
            ->groupBy('lesson_item_id')
            ->get()
            ->keyBy('lesson_item_id');
    }

    /**
     * Note de l'utilisateur courant par item : [lesson_item_id => value]. UNE requête
     * pour tous les items. Vide si anonyme.
     *
     * @param  iterable<mixed>  $items  items ou ids
     */
    public static function preloadUserRatings(iterable $items, ?User $user): Collection
    {
        if ($user === null) {
            return collect();
        }

        $itemIds = self::ids($items);

        if ($itemIds === []) {
            return collect();
        }

        return ItemRating::where('user_id', $user->id)
            ->whereIn('lesson_item_id', $itemIds)
            ->pluck('value', 'lesson_item_id');
    }

    /**
     * Commentaires (non supprimés) groupés par item : [lesson_item_id => Collection].
     * UNE requête pour tous les items, auteur chargé en lot (anti N+1), du plus ancien
     * au plus récent (lecture naturelle du fil).
     *
     * @param  iterable<mixed>  $items  items ou ids
     */
    public static function preloadComments(iterable $items): Collection
    {
        $itemIds = self::ids($items);

        if ($itemIds === []) {
            return collect();
        }

        return ItemComment::whereIn('lesson_item_id', $itemIds)
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get()
            ->groupBy('lesson_item_id');
    }

    /**
     * Normalise une liste d'items (modèles ou ids) en tableau d'ids entiers uniques.
     *
     * @param  iterable<mixed>  $items
     * @return list<int>
     */
    private static function ids(iterable $items): array
    {
        $itemIds = [];
        foreach ($items as $it) {
            $itemIds[] = (int) (is_object($it) ? $it->id : $it);
        }

        return array_values(array_unique($itemIds));
    }
}

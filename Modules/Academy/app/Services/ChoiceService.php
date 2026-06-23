<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * CHOICE - SOURCE UNIQUE (DRY) de lecture/agrégation d'un item de leçon « choice »
 * (sondage/vote simple, non noté ; type Moodle « Choice »). Lue côté SERVEUR par le
 * contrôleur de vote ET le lecteur (lesson.blade) : la configuration, les options,
 * l'agrégat des votes et les règles de VISIBILITÉ des résultats ne vivent qu'ici.
 *
 * Le payload de l'item porte (aucune nouvelle colonne, comme quiz/document) :
 *   - question            : énoncé du sondage ;
 *   - options             : tableau de libellés (>= 2) ;
 *   - allow_multiple      : autorise plusieurs choix (défaut false) ;
 *   - anonymous           : ne jamais révéler QUI a voté (défaut false) ;
 *   - results_visibility  : after_vote | always | never (défaut after_vote).
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Modules\Academy\Models\ChoiceResponse;
use Modules\Academy\Models\LessonItem;

final class ChoiceService
{
    /** Modes de visibilité des résultats (liste blanche). */
    public const VISIBILITIES = ['after_vote', 'always', 'never'];

    public const DEFAULT_VISIBILITY = 'after_vote';

    /** Nombre maximal d'options d'un sondage (garde-fou anti-abus). */
    public const MAX_OPTIONS = 20;

    /**
     * Libellés d'options NORMALISÉS d'un item (réindexés depuis 0). Source de vérité
     * des index valides d'un vote. Jamais de HTML : l'échappement est fait au rendu.
     *
     * @return array<int, string>
     */
    public static function options(LessonItem $item): array
    {
        $raw = is_array($item->payload ?? null) ? ($item->payload['options'] ?? []) : [];

        $clean = [];
        foreach ((array) $raw as $label) {
            if (is_string($label) && trim($label) !== '') {
                $clean[] = trim($label);
            }
        }

        return array_slice(array_values($clean), 0, self::MAX_OPTIONS);
    }

    public static function question(LessonItem $item): string
    {
        $q = is_array($item->payload ?? null) ? ($item->payload['question'] ?? '') : '';

        return is_string($q) ? $q : '';
    }

    public static function allowsMultiple(LessonItem $item): bool
    {
        return (bool) (is_array($item->payload ?? null) ? ($item->payload['allow_multiple'] ?? false) : false);
    }

    public static function isAnonymous(LessonItem $item): bool
    {
        return (bool) (is_array($item->payload ?? null) ? ($item->payload['anonymous'] ?? false) : false);
    }

    /** Mode de visibilité EFFECTIF (liste blanche, défaut after_vote). */
    public static function visibility(LessonItem $item): string
    {
        $raw = is_array($item->payload ?? null) ? ($item->payload['results_visibility'] ?? null) : null;

        return in_array($raw, self::VISIBILITIES, true) ? (string) $raw : self::DEFAULT_VISIBILITY;
    }

    /**
     * Le vote (indices choisis) d'un utilisateur pour cet item, ou null s'il n'a pas voté.
     *
     * Perf (C3 - anti N+1) : si une leçon contient PLUSIEURS items « choice », le lecteur
     * appellerait une requête par item. On accepte un `$preloaded` (votes de l'utilisateur
     * pour TOUS les items de la leçon, chargés en UNE requête via {@see preloadUserVotes})
     * indexé par `lesson_item_id`. Quand il est fourni il fait AUTORITÉ (aucune requête) :
     * item absent = pas de vote. Sans `$preloaded`, comportement INCHANGÉ (1 requête).
     *
     * @param  \Illuminate\Support\Collection<int, ChoiceResponse>|null  $preloaded
     * @return array<int, int>|null
     */
    public static function userVote(LessonItem $item, ?User $user, $preloaded = null): ?array
    {
        if ($user === null) {
            return null;
        }

        if ($preloaded !== null) {
            $response = $preloaded->get($item->id);

            return $response === null
                ? null
                : array_values(array_map('intval', (array) $response->choices));
        }

        $response = ChoiceResponse::where('lesson_item_id', $item->id)
            ->where('user_id', $user->id)
            ->first();

        if ($response === null) {
            return null;
        }

        return array_values(array_map('intval', (array) $response->choices));
    }

    /**
     * Précharge en UNE requête les votes d'un utilisateur pour un lot d'items « choice »
     * (anti N+1, C3). Retourne une collection de {@see ChoiceResponse} indexée par
     * `lesson_item_id`, à passer en `$preloaded` de {@see userVote}. Vide si pas
     * d'utilisateur ou pas d'item.
     *
     * @param  iterable<int, LessonItem|int>  $items
     * @return \Illuminate\Support\Collection<int, ChoiceResponse>
     */
    public static function preloadUserVotes(iterable $items, ?User $user): \Illuminate\Support\Collection
    {
        if ($user === null) {
            return collect();
        }

        $itemIds = [];
        foreach ($items as $it) {
            $itemIds[] = (int) (is_object($it) ? $it->id : $it);
        }
        $itemIds = array_values(array_unique($itemIds));

        if ($itemIds === []) {
            return collect();
        }

        return ChoiceResponse::where('user_id', $user->id)
            ->whereIn('lesson_item_id', $itemIds)
            ->get(['lesson_item_id', 'choices'])
            ->keyBy('lesson_item_id');
    }

    public static function hasVoted(LessonItem $item, ?User $user, $preloaded = null): bool
    {
        return self::userVote($item, $user, $preloaded) !== null;
    }

    /**
     * Agrégat des votes : pour chaque option, son libellé, le nombre de votes et le
     * pourcentage (sur le nombre total de VOTANTS, jamais sur le nb de coches). On ne
     * révèle JAMAIS l'identité des votants ici (juste des comptes anonymisés).
     *
     * @return array{total_voters: int, options: array<int, array{index:int,label:string,count:int,percent:int}>}
     */
    public static function tally(LessonItem $item): array
    {
        $options = self::options($item);

        $counts = array_fill(0, count($options), 0);
        $voters = 0;

        // Perf (C3) : on ne sélectionne QUE la colonne `choices` et on parcourt en
        // flux (lazy / curseur paginé) plutôt que de charger toutes les lignes et
        // toutes les colonnes en mémoire. Résultat identique, empreinte mémoire bornée.
        foreach (ChoiceResponse::where('lesson_item_id', $item->id)->select('choices')->lazy() as $response) {
            $voters++;
            foreach ((array) $response->choices as $idx) {
                $idx = (int) $idx;
                if (array_key_exists($idx, $counts)) {
                    $counts[$idx]++;
                }
            }
        }

        $rows = [];
        foreach ($options as $i => $label) {
            $rows[] = [
                'index'   => $i,
                'label'   => $label,
                'count'   => $counts[$i],
                'percent' => $voters > 0 ? (int) round(($counts[$i] / $voters) * 100) : 0,
            ];
        }

        return ['total_voters' => $voters, 'options' => $rows];
    }

    /**
     * Les résultats sont-ils visibles à un ÉTUDIANT ? (le formateur les voit toujours,
     * géré côté vue). Règles : always = oui ; never = non ; after_vote = oui seulement
     * une fois que l'étudiant a voté.
     */
    public static function resultsVisibleToStudent(LessonItem $item, bool $hasVoted): bool
    {
        return match (self::visibility($item)) {
            'always'    => true,
            'never'     => false,
            default     => $hasVoted, // after_vote
        };
    }

    /**
     * Liste des utilisateurs ayant voté (pour l'affichage formateur UNIQUEMENT, et
     * SEULEMENT si le sondage n'est pas anonyme). Vide si anonyme : aucune fuite
     * d'identité possible. Réservé au contexte gérant côté vue.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function voters(LessonItem $item): \Illuminate\Support\Collection
    {
        if (self::isAnonymous($item)) {
            return collect();
        }

        return ChoiceResponse::where('lesson_item_id', $item->id)
            ->with('user')
            ->get()
            ->map(fn (ChoiceResponse $r) => $r->user)
            ->filter()
            ->values();
    }
}

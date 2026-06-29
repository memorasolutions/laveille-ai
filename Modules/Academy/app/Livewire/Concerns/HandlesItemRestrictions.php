<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseEditor — restrictions d'accès par item
 * (parité Moodle « Restrict access », V5-d) : chargement, annulation, ajout/retrait
 * de condition et enregistrement sanitisé via AccessRestrictionService.
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse() et
 * ré-autorise 'manageStructure' (anti-IDOR). La résolution anti-IDOR de l'item
 * passe par $this->resolveItemFor() avant toute écriture. AccessRestrictionService
 * applique une liste blanche de types et vérifie que les item_id référencés
 * appartiennent bien au cours courant. Aucun comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Modules\Academy\Services\AccessRestrictionService;

trait HandlesItemRestrictions
{
    // ─────────────────────────────────────────────────────────────────────────────
    // V5-d : RESTRICTIONS D'ACCÈS PAR ITEM (parité Moodle « Restrict access »)
    //
    // Sécurité : resolveCourse() → authorize('manageStructure') → resolveItemFor()
    // (anti-IDOR item→leçon→cours) → AccessRestrictionService::sanitizeConditions()
    // (liste blanche type + bornes % + anti-IDOR item_id ∈ cours) → payload.
    //
    // Le tampon $editRestrictions[itemId] est chargé par loadItemRestrictions() et
    // enregistré par saveItemRestrictions(). Un tableau null = panneau fermé.
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Charge le tampon d'édition des restrictions d'un item depuis son payload.
     * Ouvre le panneau de restrictions dans l'UI. Anti-IDOR : l'item doit
     * appartenir à CE cours.
     */
    public function loadItemRestrictions(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item   = $this->resolveItemFor($course, $itemId);
        $config = is_array($item->payload['access_restrictions'] ?? null)
            ? $item->payload['access_restrictions']
            : [];

        $this->editRestrictions[$itemId] = [
            'match'      => ($config['match'] ?? 'all') === 'any' ? 'any' : 'all',
            'conditions' => is_array($config['conditions'] ?? null) ? $config['conditions'] : [],
        ];
    }

    /** Ferme le panneau (vide le tampon) sans enregistrer. */
    public function cancelItemRestrictions(int $itemId): void
    {
        unset($this->editRestrictions[$itemId]);
    }

    /**
     * Ajoute une condition vierge au tampon de restrictions d'un item.
     * L'item doit exister dans le tampon (sinon : appeler loadItemRestrictions d'abord).
     */
    public function addRestrictionCondition(int $itemId, string $type = 'completion'): void
    {
        if (! isset($this->editRestrictions[$itemId])) {
            return;
        }

        if (! in_array($type, AccessRestrictionService::TYPES, true)) {
            $type = 'completion';
        }

        $blank = match ($type) {
            'date'       => ['type' => 'date', 'from' => '', 'until' => '', 'hide' => false],
            'grade'      => ['type' => 'grade', 'item_id' => 0, 'min_percent' => 50, 'hide' => false],
            'group'      => ['type' => 'group', 'group_id' => 0, 'hide' => false],
            default      => ['type' => 'completion', 'item_id' => 0, 'hide' => false],
        };

        $this->editRestrictions[$itemId]['conditions'][] = $blank;
    }

    /** Retire la condition d'index donné du tampon (réindexée). */
    public function removeRestrictionCondition(int $itemId, int $index): void
    {
        if (! isset($this->editRestrictions[$itemId]['conditions'][$index])) {
            return;
        }

        unset($this->editRestrictions[$itemId]['conditions'][$index]);
        $this->editRestrictions[$itemId]['conditions'] = array_values(
            $this->editRestrictions[$itemId]['conditions']
        );
    }

    /**
     * Enregistre les restrictions d'un item depuis le tampon.
     *
     * Gardes (DRY, identiques aux autres méthodes item) :
     *   resolveCourse() → authorize('manageStructure') → resolveItemFor() (anti-IDOR)
     *   → AccessRestrictionService::sanitizeConditions() (liste blanche + anti-IDOR).
     *
     * Une liste vide de conditions RETIRE la clé du payload (rétrocompat stricte :
     * item sans 'access_restrictions' = toujours accessible).
     */
    public function saveItemRestrictions(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        $tampon = $this->editRestrictions[$itemId] ?? null;
        if (! is_array($tampon)) {
            return;
        }

        $match    = ($tampon['match'] ?? 'all') === 'any' ? 'any' : 'all';
        $rawConds = is_array($tampon['conditions'] ?? null) ? $tampon['conditions'] : [];

        // Anti-IDOR : seuls les items du cours courant sont acceptés comme référence.
        // On exclut aussi l'item courant lui-même (anti-auto-référence = deadlock).
        $validItemIds = AccessRestrictionService::courseItemIds($course);
        $cleanConds   = AccessRestrictionService::sanitizeConditions(
            $rawConds,
            $validItemIds,
            $course->id,
            $item->id
        );

        $payload = is_array($item->payload) ? $item->payload : [];

        if (count($cleanConds) === 0) {
            // Aucune condition valide : retire la clé (rétrocompat).
            unset($payload['access_restrictions']);
        } else {
            $payload['access_restrictions'] = [
                'match'      => $match,
                'conditions' => $cleanConds,
            ];
        }

        $item->update(['payload' => $payload]);

        // Reflète l'état sanitisé dans l'UI.
        $this->editRestrictions[$itemId] = [
            'match'      => $match,
            'conditions' => $cleanConds,
        ];

        $this->flashSaved('Restrictions d\'accès enregistrées.');
    }

    /**
     * Liste des items du cours utilisables comme référence dans une restriction
     * grade/completion (sauf l'item courant lui-même - on ne peut pas s'auto-bloquer).
     * Sert à alimenter le sélecteur de l'UI éditeur.
     *
     * @param  int  $currentItemId  item en cours d'édition (exclu de la liste)
     * @return array<int, array{id: int, title: string, type: string}>
     */
    public function restrictionRefItems(int $currentItemId): array
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $course->loadMissing(['chapters.lessons.lessonItems']);

        $items = [];
        foreach ($course->chapters as $chapter) {
            foreach ($chapter->lessons as $lesson) {
                foreach ($lesson->lessonItems as $lessonItem) {
                    if ((int) $lessonItem->id === $currentItemId) {
                        continue;  // ne pas s'auto-référencer
                    }
                    $items[] = [
                        'id'    => (int) $lessonItem->id,
                        'title' => (string) ($lessonItem->title ?? 'Sans titre'),
                        'type'  => (string) $lessonItem->type,
                    ];
                }
            }
        }

        return $items;
    }
}

<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component QuestionBankManager — RÉPÉTEURS de formulaire
 * par type de question : ajout/retrait de choix (mcq), réponses acceptées (short),
 * paires (matching), éléments ordonnés (ordering), trous (cloze) et mots du pool
 * glisser-déposer (ddwtos).
 *
 * Aucune règle de sécurité directe (les mutations portent sur l'état Livewire du
 * formulaire, pas sur la DB). Les invariants de cardinalité minimale sont conservés
 * exactement comme dans le composant d'origine (minimum 2 choix, 1 réponse, 2 paires,
 * 2 éléments, 1 trou, 2 mots du pool). Aucun comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

trait HandlesQbPayloadRepeaters
{
    // ─────────────────────────────────────────────────────────────────────────
    // QCM — choix
    // ─────────────────────────────────────────────────────────────────────────

    /** Ajoute un choix (mcq) - borné côté serveur au build du payload. */
    public function addChoice(): void
    {
        $this->qChoices[]        = '';
        $this->qChoiceFeedback[] = '';
    }

    public function removeChoice(int $index): void
    {
        if (count($this->qChoices) <= 2) {
            return; // minimum 2 choix.
        }

        unset($this->qChoices[$index]);
        $this->qChoices = array_values($this->qChoices);

        // V1-a : retirer la rétroaction du même index et ré-indexer.
        unset($this->qChoiceFeedback[$index]);
        $this->qChoiceFeedback = array_values($this->qChoiceFeedback);

        if ($this->qCorrect >= count($this->qChoices)) {
            $this->qCorrect = 0;
        }

        // V1-e : garder $qCorrectSet cohérent après ré-indexation.
        if ($this->qMultiple) {
            $newSet = [];
            foreach (array_map('intval', $this->qCorrectSet) as $sel) {
                if ($sel === $index) {
                    continue;
                }
                $newSet[] = $sel > $index ? $sel - 1 : $sel;
            }
            $this->qCorrectSet = array_values(array_unique($newSet));
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Réponse courte — réponses acceptées
    // ─────────────────────────────────────────────────────────────────────────

    public function addAccepted(): void
    {
        $this->qAccepted[] = '';
    }

    public function removeAccepted(int $index): void
    {
        if (count($this->qAccepted) <= 1) {
            return; // minimum 1 réponse acceptée.
        }

        unset($this->qAccepted[$index]);
        $this->qAccepted = array_values($this->qAccepted);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Appariement — paires terme/définition
    // ─────────────────────────────────────────────────────────────────────────

    public function addPair(): void
    {
        $this->qPairs[] = ['term' => '', 'def' => ''];
    }

    public function removePair(int $index): void
    {
        if (count($this->qPairs) <= 2) {
            return; // minimum 2 paires.
        }

        unset($this->qPairs[$index]);
        $this->qPairs = array_values($this->qPairs);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ordonnancement — éléments
    // ─────────────────────────────────────────────────────────────────────────

    /** Ajoute un élément d'ordonnancement (à la fin de l'ordre attendu). */
    public function addOrderingItem(): void
    {
        $this->qOrderingItems[] = '';
    }

    public function removeOrderingItem(int $index): void
    {
        if (count($this->qOrderingItems) <= 2) {
            return; // minimum 2 éléments.
        }

        unset($this->qOrderingItems[$index]);
        $this->qOrderingItems = array_values($this->qOrderingItems);
    }

    /**
     * Réordonne un élément d'ordonnancement (l'ordre saisi EST la bonne réponse).
     * $direction : 'up' (remonter) ou 'down' (descendre). Bornes respectées (no-op
     * en dehors). Échange simple avec l'élément voisin.
     */
    public function moveOrderingItem(int $index, string $direction): void
    {
        $items  = array_values($this->qOrderingItems);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index < 0 || $index >= count($items) || $target < 0 || $target >= count($items)) {
            return;
        }

        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];
        $this->qOrderingItems = $items;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cloze — trous
    // ─────────────────────────────────────────────────────────────────────────

    /** Ajoute un trou de cloze (à la suite ; son numéro de marqueur = position + 1). */
    public function addClozeBlank(): void
    {
        $this->qClozeBlanks[] = ['kind' => 'short', 'accepted' => '', 'display' => '', 'choices' => '', 'correct' => 0];
    }

    public function removeClozeBlank(int $index): void
    {
        if (count($this->qClozeBlanks) <= 1) {
            return; // minimum 1 trou.
        }

        unset($this->qClozeBlanks[$index]);
        $this->qClozeBlanks = array_values($this->qClozeBlanks);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Glisser-déposer sur texte (ddwtos) — pool de mots
    // ─────────────────────────────────────────────────────────────────────────

    /** Ajoute un mot au pool ddwtos (glisser-déposer sur texte). */
    public function addDdwtosWord(): void
    {
        $this->qDdwtosWords[] = '';
    }

    /**
     * Retire un mot du pool ddwtos et ré-indexe. Met à jour $qDdwtosAnswers : un trou
     * qui pointait le mot retiré perd sa désignation ; les index supérieurs reculent d'un
     * cran (cohérence avec la ré-indexation du pool).
     */
    public function removeDdwtosWord(int $index): void
    {
        if (count($this->qDdwtosWords) <= 2) {
            return; // pool minimal de 2 mots.
        }

        unset($this->qDdwtosWords[$index]);
        $this->qDdwtosWords = array_values($this->qDdwtosWords);

        $newAnswers = [];
        foreach ($this->qDdwtosAnswers as $blankIdx => $wordIdx) {
            $wordIdx = (int) $wordIdx;
            if ($wordIdx === $index) {
                continue; // ce trou perd sa désignation (mot retiré).
            }
            $newAnswers[(int) $blankIdx] = $wordIdx > $index ? $wordIdx - 1 : $wordIdx;
        }
        $this->qDdwtosAnswers = $newAnswers;
    }
}

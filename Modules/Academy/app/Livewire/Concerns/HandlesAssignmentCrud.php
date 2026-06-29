<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseAssignments — CRUD des devoirs d'un cours :
 * création, édition, publication, dépublication, suppression, et confirmations
 * inline à 2 temps (jamais de popup native). Inclut la résolution sécurisée de
 * l'échelle sélectionnable (anti-IDOR F14).
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse()
 * (anti-IDOR/anti-escalade), puis re-autorise via $this->authorize(). Les
 * identifiants du DOM (lessonId, scaleId) sont re-résolus serveur avant écriture.
 * Aucun comportement modifié par rapport au composant d'origine.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Scale;

trait HandlesAssignmentCrud
{
    // ─────────────────────────────────────────────────────────────────────────
    // CRÉER / ÉDITER / PUBLIER / SUPPRIMER un devoir - gâté manageStructure
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Enregistre un devoir (création ou édition selon $editingAssignment).
     * $publish=true → is_published=true (visible des inscrits) ; false = brouillon.
     */
    public function saveAssignment(bool $publish = false): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // SECURITY : paramètres du DOM = chaînes. On normalise '' → null AVANT
        // validation (jamais de cast ?int strict = TypeError/500).
        $lessonIdRaw = trim($this->lessonId) === '' ? null : (int) $this->lessonId;
        $dueAtRaw    = trim($this->dueAt) === '' ? null : $this->dueAt;
        // F14 : échelle optionnelle re-résolue (échelle de l'actor OU système, OU
        // n'importe laquelle si admin) ; une échelle étrangère → null (anti-IDOR).
        $scaleIdRaw      = trim($this->scaleId) === '' ? null : (int) $this->scaleId;
        $resolvedScaleId = $scaleIdRaw !== null ? $this->resolveSelectableScaleId($scaleIdRaw) : null;

        $data = $this->validate([
            'title'        => 'required|string|max:200',
            'instructions' => 'nullable|string|max:20000',
            'maxPoints'    => 'required|integer|min:1|max:100000',
            'dueAt'        => 'nullable|date',
        ]);

        // Si une leçon est choisie, elle DOIT appartenir à CE cours (anti-IDOR).
        $resolvedLessonId = null;
        if ($lessonIdRaw !== null) {
            $resolvedLessonId = $this->resolveLessonFor($course, $lessonIdRaw)->id;
        }

        if ($this->editingAssignment !== null) {
            // ÉDITION : devoir re-résolu et scopé à CE cours (anti-IDOR).
            $assignment = $this->resolveAssignmentFor($course, (int) $this->editingAssignment);

            $assignment->title        = trim($data['title']);
            $assignment->instructions = $data['instructions'] !== '' ? $data['instructions'] : null;
            $assignment->max_points   = (int) $data['maxPoints'];
            $assignment->scale_id     = $resolvedScaleId;
            $assignment->due_at       = $dueAtRaw;
            $assignment->lesson_id    = $resolvedLessonId;

            if ($publish) {
                $assignment->is_published = true;
            }

            $assignment->save();
            $message = $publish ? 'Devoir publié.' : 'Devoir enregistré.';
        } else {
            // CRÉATION : position = fin de liste pour CE cours.
            $position = (int) Assignment::where('course_id', $course->id)->max('position') + 1;

            Assignment::create([
                'course_id'    => $course->id,
                'lesson_id'    => $resolvedLessonId,
                'title'        => trim($data['title']),
                'instructions' => $data['instructions'] !== '' ? $data['instructions'] : null,
                'max_points'   => (int) $data['maxPoints'],
                'scale_id'     => $resolvedScaleId,
                'due_at'       => $dueAtRaw,
                'is_published' => $publish,
                'position'     => $position,
            ]);
            $message = $publish ? 'Devoir publié.' : 'Brouillon de devoir enregistré.';
        }

        $this->resetAssignmentForm();
        $this->flashSaved($message);
    }

    /** Charge un devoir de CE cours dans le formulaire (édition, anti-IDOR). */
    public function editAssignment(int $assignmentId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $assignment = $this->resolveAssignmentFor($course, $assignmentId);

        $this->editingAssignment = $assignment->id;
        $this->title             = $assignment->title;
        $this->instructions      = (string) $assignment->instructions;
        $this->maxPoints         = $assignment->max_points;
        $this->scaleId           = $assignment->scale_id !== null ? (string) $assignment->scale_id : '';
        $this->dueAt             = $assignment->due_at?->format('Y-m-d\TH:i') ?? '';
        $this->lessonId          = $assignment->lesson_id !== null ? (string) $assignment->lesson_id : '';
        $this->resetErrorBag();
    }

    /** Publie un devoir existant (brouillon → publié) de CE cours (anti-IDOR). */
    public function publishAssignment(int $assignmentId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $assignment = $this->resolveAssignmentFor($course, $assignmentId);
        $assignment->update(['is_published' => true]);

        $this->flashSaved('Devoir publié.');
    }

    /** Repasse un devoir publié en brouillon (retire des inscrits), anti-IDOR. */
    public function unpublishAssignment(int $assignmentId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $assignment = $this->resolveAssignmentFor($course, $assignmentId);
        $assignment->update(['is_published' => false]);

        $this->flashSaved('Devoir repassé en brouillon.');
    }

    /** Supprime un devoir de CE cours (re-résolu, anti-IDOR). Remises en cascade. */
    public function deleteAssignment(int $assignmentId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $assignment = $this->resolveAssignmentFor($course, $assignmentId);
        $assignment->delete();

        if ($this->editingAssignment === $assignmentId) {
            $this->resetAssignmentForm();
        }
        if ($this->reviewingAssignment === $assignmentId) {
            $this->reviewingAssignment = null;
        }
        $this->confirmingAssignmentRemoval = null;
        $this->flashSaved('Devoir supprimé.');
    }

    /** Réinitialise le formulaire de devoir (annule l'édition en cours). */
    public function resetAssignmentForm(): void
    {
        $this->reset('editingAssignment', 'title', 'instructions', 'dueAt', 'lessonId', 'scaleId');
        $this->maxPoints = 100;
        $this->resetErrorBag();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Confirmations inline à 2 temps (jamais de popup native) — CRUD devoir
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmAssignmentRemoval(int $assignmentId): void
    {
        $this->confirmingAssignmentRemoval = $assignmentId;
    }

    public function cancelAssignmentRemoval(): void
    {
        $this->confirmingAssignmentRemoval = null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Résolution privée : échelle sélectionnable (F14, anti-IDOR)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * F14 - Re-résout l'identifiant d'une échelle SÉLECTIONNABLE par l'utilisateur
     * courant : une échelle qu'il POSSÈDE (owner_id = auth), une échelle SYSTÈME
     * (owner_id null), OU n'importe laquelle s'il est admin (academy.manage). Toute
     * autre échelle (d'un autre formateur) → null (anti-IDOR, jamais rattachée).
     */
    private function resolveSelectableScaleId(int $scaleId): ?int
    {
        $query = Scale::where('id', $scaleId);

        if (! (bool) auth()->user()?->can('academy.manage')) {
            $uid = (int) auth()->id();
            $query->where(fn ($q) => $q->where('owner_id', $uid)->orWhereNull('owner_id'));
        }

        return $query->value('id') !== null ? $scaleId : null;
    }
}

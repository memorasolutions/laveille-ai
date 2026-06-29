<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Trait extrait du God-component CourseEditor — réglages et règles du cours :
 * prérequis (C4), personnalisation du certificat (E3), achèvement configurable.
 *
 * SÉCURITÉ : chaque mutation re-résout le cours via $this->resolveCourse() et
 * ré-autorise 'manageStructure' (anti-IDOR), exactement comme dans le composant
 * d'origine. Aucun comportement modifié.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Modules\Academy\Models\Course;
use Modules\Academy\Services\CourseCompletionService;

trait HandlesCourseSettings
{
    // ─────────────────────────────────────────────────────────────────────────────
    // PRÉREQUIS DE COURS (C4) - gâtés manageStructure
    //
    // SÉCURITÉ : resolveCourse() → authorize('manageStructure') → on RE-RÉSOUT côté
    // serveur l'ensemble des cours candidats (visibles par l'utilisateur, hors cours
    // courant) et on n'écrit QUE l'intersection avec les ids reçus (anti-IDOR : un id
    // forgé d'un cours non visible / du cours lui-même est ignoré). Auto-référence et
    // doublons impossibles par construction.
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Cours candidats aux prérequis : ceux que l'utilisateur peut VOIR (la même
     * requête scoped que le tableau de bord), EXCLUANT le cours courant. Sert à la
     * fois à l'affichage (cases à cocher) et de liste blanche serveur (anti-IDOR).
     *
     * @return \Illuminate\Support\Collection<int, Course>
     */
    #[Computed]
    public function availableCourses(): \Illuminate\Support\Collection
    {
        $user = Auth::user();

        $query = Course::query()
            ->where('id', '!=', $this->courseId)
            ->orderBy('title');

        // Admin (academy.manage) voit tout ; sinon, uniquement les cours dont il est
        // gérant (course_roles), via la même logique d'ownership que la Policy.
        if (! ($user?->can('academy.manage'))) {
            $query->whereHas('courseRoles', fn ($q) => $q->where('user_id', $user?->id));
        }

        return $query->get();
    }

    /**
     * Synchronise les prérequis du cours avec la sélection de cases à cocher.
     * Seuls les ids appartenant à availableCourses() (cours visibles, hors courant)
     * sont conservés : un id forgé (cours non visible ou auto-référence) est écarté.
     */
    public function savePrerequisites(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Liste blanche serveur : ids des cours réellement visibles par l'utilisateur.
        $allowed = $this->availableCourses->pluck('id')->map(fn ($id) => (int) $id)->all();

        // Intersection : on n'attache QUE des prérequis légitimes (anti-IDOR + anti
        // auto-référence, le cours courant étant déjà exclu de availableCourses()).
        $clean = array_values(array_unique(array_intersect(
            array_map(static fn ($id) => (int) $id, $this->prerequisiteIds),
            $allowed
        )));

        $course->prerequisites()->sync($clean);

        $this->prerequisiteIds = $clean;
        $this->flashSaved('Prérequis du cours enregistrés.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // PERSONNALISATION DU CERTIFICAT (E3) - gâtée manageStructure
    //
    // SÉCURITÉ : resolveCourse() → authorize('manageStructure') → validate → écrire.
    // Un gérant ne personnalise que SES cours (le cours est re-résolu serveur). Les
    // valeurs vides ('') sont normalisées en null → le gabarit retombe sur ses défauts
    // (rétrocompatibilité totale). La couleur d'accent est validée comme hex #RGB/#RRGGBB ;
    // l'anti-XSS final est assuré au RENDU (e() pour titre/signature, markdown nettoyé
    // pour le message), cf. public/certificate.blade.php.
    // ─────────────────────────────────────────────────────────────────────────────

    public function saveCertificate(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Normalisation '' → null AVANT validation : un champ vidé efface la personnalisation
        // (retour au défaut), il ne doit jamais déclencher une règle sur chaîne vide.
        foreach (['certificate_title', 'certificate_message', 'certificate_signature_name', 'certificate_accent_color'] as $field) {
            $value = $this->{$field};
            $this->{$field} = (is_string($value) && trim($value) === '') ? null : (is_string($value) ? trim($value) : $value);
        }

        $validated = $this->validate([
            'certificate_title'          => 'nullable|string|max:120',
            'certificate_message'        => 'nullable|string|max:2000',
            'certificate_signature_name' => 'nullable|string|max:120',
            // Couleur d'accent : hexadécimal #RGB ou #RRGGBB uniquement (sinon rejet).
            'certificate_accent_color'   => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
        ], [
            'certificate_accent_color.regex' => 'La couleur doit être un code hexadécimal valide (ex. #064E5A).',
        ]);

        $course->update([
            'certificate_title'          => $validated['certificate_title'] ?? null,
            'certificate_message'        => $validated['certificate_message'] ?? null,
            'certificate_signature_name' => $validated['certificate_signature_name'] ?? null,
            'certificate_accent_color'   => $validated['certificate_accent_color'] ?? null,
            'updated_by'                 => Auth::id(),
        ]);

        $this->flashSaved('Certificat du cours enregistré.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ACHÈVEMENT DU COURS (course completion configurable) - gâté manageStructure
    //
    // SÉCURITÉ : resolveCourse() → authorize('manageStructure') → validate → écrire.
    // Le critère décide quand le cours est COMPLÉTÉ (certificat + badges, via
    // CourseCompletionService côté serveur). Défaut « all_required » = comportement
    // historique. Pour selected_activities, les ids reçus sont RE-FILTRÉS aux items
    // appartenant réellement à CE cours (anti-IDOR), jamais de confiance au client.
    // ─────────────────────────────────────────────────────────────────────────────

    public function saveCompletion(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $needsValue    = in_array($this->completion_type, [
            CourseCompletionService::TYPE_PERCENT,
            CourseCompletionService::TYPE_MIN_GRADE,
        ], true);
        $needsSelected = $this->completion_type === CourseCompletionService::TYPE_SELECTED;

        $this->validate([
            'completion_type'  => ['required', 'string', Rule::in(CourseCompletionService::TYPES)],
            'completion_value' => [$needsValue ? 'required' : 'nullable', 'integer', 'min:1', 'max:100'],
            'completion_selected'   => [$needsSelected ? 'required' : 'nullable', 'array'],
            'completion_selected.*' => ['integer', 'min:1'],
        ], [
            'completion_value.required'    => 'Indiquez un seuil entre 1 et 100.',
            'completion_selected.required' => 'Sélectionnez au moins une activité.',
        ]);

        // Construction du critère normalisé à stocker (forme minimale par type).
        $criteria = ['type' => $this->completion_type];

        if ($needsValue) {
            $criteria['value'] = max(1, min(100, (int) $this->completion_value));
        }

        if ($needsSelected) {
            // Anti-IDOR : ne garder que les items appartenant réellement à ce cours.
            $valid = (new CourseCompletionService())
                ->validItemIdsForCourse($course, $this->completion_selected);

            if ($valid === []) {
                $this->addError('completion_selected', 'Sélectionnez au moins une activité valide de ce cours.');

                return;
            }

            $criteria['items']         = $valid;
            $this->completion_selected = $valid;
        }

        $course->update([
            'completion_criteria' => $criteria,
            'updated_by'          => Auth::id(),
        ]);

        $this->flashSaved('Critère d\'achèvement enregistré.');
    }

    /**
     * Liste APLATIE des items du cours (pour la sélection « activités désignées »).
     * Source serveur, scopée à CE cours. Chaque entrée : {id, label}.
     *
     * @return array<int, array{id: int, label: string}>
     */
    #[Computed]
    public function completionItems(): array
    {
        $course = $this->resolveCourse();
        $course->loadMissing([
            'chapters'                     => fn ($q) => $q->orderBy('position'),
            'chapters.lessons'             => fn ($q) => $q->orderBy('position'),
            'chapters.lessons.lessonItems' => fn ($q) => $q->orderBy('position'),
        ]);

        $rows = [];
        foreach ($course->chapters as $chapter) {
            foreach ($chapter->lessons as $lesson) {
                foreach ($lesson->lessonItems as $item) {
                    $rows[] = [
                        'id'    => (int) $item->id,
                        'label' => trim(($lesson->title ?? 'Leçon').' - '.($item->title ?? 'Activité')),
                    ];
                }
            }
        }

        return $rows;
    }
}

<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@php($course = $this->course)
<div style="display: flex; flex-direction: column; gap: 28px;">

    {{-- ───────────────────────── Message de succès ───────────────────────── --}}
    @if (session('academy_assignments_status'))
        <div role="status" aria-live="polite"
             style="border: 1px solid #BBF7D0; background: #F0FDF4; color: #166534; border-radius: var(--sys-radius-md, 0.75rem); padding: 12px 16px; font-weight: 600;">
            {{ session('academy_assignments_status') }}
        </div>
    @endif

    {{-- ───────────────────────── Créer / éditer un devoir ───────────────────────── --}}
    <section aria-labelledby="assignments-compose"
             style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
        <h3 id="assignments-compose" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 6px; font-size: 1.1rem;">
            {{ $editingAssignment ? 'Modifier le devoir' : 'Nouveau devoir' }}
        </h3>
        <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 18px;">
            Les devoirs publiés s'affichent dans l'espace des personnes inscrites à ce cours. Consignes en markdown (HTML brut retiré par sécurité).
        </p>

        <form wire:submit="saveAssignment(false)" style="display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="assignment-title" style="font-weight: 600; font-size: 0.85rem;">Titre</label>
                <input id="assignment-title" type="text" wire:model="title" maxlength="200" autocomplete="off" placeholder="Ex. : analyse de cas n°1"
                       style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                @error('title') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="assignment-instructions" style="font-weight: 600; font-size: 0.85rem;">Consignes</label>
                <textarea id="assignment-instructions" wire:model="instructions" rows="5" maxlength="20000" placeholder="Décrivez le travail attendu…"
                          style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-family: inherit;"></textarea>
                @error('instructions') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
            </div>

            <div class="d-flex flex-wrap gap-3">
                <div style="display: flex; flex-direction: column; gap: 6px; flex: 0 0 140px;">
                    <label for="assignment-max" style="font-weight: 600; font-size: 0.85rem;">Points maximum</label>
                    <input id="assignment-max" type="number" min="1" max="100000" wire:model="maxPoints"
                           style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                    @error('maxPoints') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px; flex: 0 0 220px;">
                    <label for="assignment-due" style="font-weight: 600; font-size: 0.85rem;">Échéance (optionnel)</label>
                    <input id="assignment-due" type="datetime-local" wire:model="dueAt"
                           style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                    @error('dueAt') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px; flex: 1 1 240px;">
                    <label for="assignment-lesson" style="font-weight: 600; font-size: 0.85rem;">Rattacher à une leçon (optionnel)</label>
                    <select id="assignment-lesson" wire:model="lessonId"
                            style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <option value="">Cours entier</option>
                        @foreach ($this->lessons as $lesson)
                            <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <x-core::button type="submit" variant="secondary" size="sm">
                    {{ $editingAssignment ? 'Enregistrer' : 'Enregistrer comme brouillon' }}
                </x-core::button>
                <x-core::button type="button" wire:click="saveAssignment(true)" variant="primary" size="sm">
                    Publier maintenant
                </x-core::button>
                @if ($editingAssignment)
                    <x-core::button type="button" wire:click="resetAssignmentForm" variant="ghost" size="sm">Annuler l'édition</x-core::button>
                @endif
            </div>
        </form>
    </section>

    {{-- ───────────────────────── Liste des devoirs ───────────────────────── --}}
    <section aria-labelledby="assignments-list">
        <h3 id="assignments-list" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 12px; font-size: 1.1rem;">
            Devoirs du cours
        </h3>

        @if ($this->assignments->isNotEmpty())
            <ul class="list-unstyled d-flex flex-column gap-3" style="margin: 0;">
                @foreach ($this->assignments as $assignment)
                    <li wire:key="assignment-{{ $assignment->id }}"
                        style="border: 1px solid #F3F4F6; border-radius: var(--sys-radius-md, 0.5rem); padding: 14px 16px;">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div style="flex: 1 1 280px; min-width: 200px;">
                                <h4 style="font-family: var(--f-heading); font-size: 1rem; color: var(--sys-text-default, #1A1D23); margin: 0 0 4px;">
                                    {{ $assignment->title }}
                                </h4>
                                <div class="d-flex flex-wrap align-items-center gap-2" style="font-size: 0.72rem;">
                                    @if ($assignment->is_published)
                                        <span style="display: inline-block; font-weight: 700; color: #166534; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 999px; padding: 2px 10px;">Publié</span>
                                    @else
                                        <span style="display: inline-block; font-weight: 700; color: #92400E; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 999px; padding: 2px 10px;">Brouillon</span>
                                    @endif
                                    <span style="color: var(--sys-text-muted, #6B7280);">Sur {{ $assignment->max_points }} pts</span>
                                    @if ($assignment->lesson)
                                        <span style="color: var(--sys-text-muted, #6B7280);">· Leçon : {{ $assignment->lesson->title }}</span>
                                    @endif
                                    @if ($assignment->due_at)
                                        <span style="color: var(--sys-text-muted, #6B7280);">· Échéance {{ $assignment->due_at->timezone('America/Toronto')->format('Y-m-d H\hi') }}</span>
                                    @endif
                                    <span style="color: var(--sys-text-muted, #6B7280);">· {{ $assignment->submissions_count }} remise(s), {{ $assignment->graded_count }} corrigée(s)</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                @if ($this->canGrade)
                                    <x-core::button type="button" wire:click="reviewAssignment({{ $assignment->id }})" variant="secondary" size="sm">Corriger</x-core::button>
                                @endif
                                <x-core::button type="button" wire:click="editAssignment({{ $assignment->id }})" variant="ghost" size="sm">Modifier</x-core::button>
                                @if ($assignment->is_published)
                                    <x-core::button type="button" wire:click="unpublishAssignment({{ $assignment->id }})" variant="ghost" size="sm">Repasser en brouillon</x-core::button>
                                @else
                                    <x-core::button type="button" wire:click="publishAssignment({{ $assignment->id }})" variant="secondary" size="sm">Publier</x-core::button>
                                @endif
                                @if ($confirmingAssignmentRemoval === $assignment->id)
                                    <span style="font-size: 0.82rem; font-weight: 600;">Supprimer ?</span>
                                    <x-core::button type="button" wire:click="deleteAssignment({{ $assignment->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                    <x-core::button type="button" wire:click="cancelAssignmentRemoval" variant="ghost" size="sm">Annuler</x-core::button>
                                @else
                                    <x-core::button type="button" wire:click="confirmAssignmentRemoval({{ $assignment->id }})" variant="ghost" size="sm"
                                                    aria-label="Supprimer le devoir « {{ $assignment->title }} »">Supprimer</x-core::button>
                                @endif
                            </div>
                        </div>

                        {{-- Liste des remises (revue) du devoir sélectionné --}}
                        @if ($this->canGrade && $reviewingAssignment === $assignment->id)
                            <div style="margin-top: 14px; border-top: 1px dashed #E5E7EB; padding-top: 14px;">
                                <div class="d-flex justify-content-between align-items-center" style="margin-bottom: 10px;">
                                    <strong style="font-size: 0.9rem;">Remises à corriger</strong>
                                    <x-core::button type="button" wire:click="closeReview" variant="ghost" size="sm">Fermer</x-core::button>
                                </div>

                                @if ($this->reviewSubmissions->isNotEmpty())
                                    <ul class="list-unstyled d-flex flex-column gap-3" style="margin: 0;">
                                        @foreach ($this->reviewSubmissions as $submission)
                                            <li wire:key="submission-{{ $submission->id }}"
                                                style="border: 1px solid #F3F4F6; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px 14px; background: #FAFAFA;">
                                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                                    <div style="flex: 1 1 240px;">
                                                        <strong style="font-size: 0.9rem;">{{ $submission->user?->name ?? 'Inconnu' }}</strong>
                                                        <span style="color: var(--sys-text-muted, #6B7280); font-size: 0.8rem;">· remis le {{ $submission->submitted_at?->timezone('America/Toronto')->format('Y-m-d H\hi') }}</span>
                                                    </div>
                                                    <div>
                                                        @if ($submission->isGraded())
                                                            <span style="font-size: 0.78rem; font-weight: 700; color: #166534;">Corrigé : {{ $submission->score }} / {{ $assignment->max_points }}</span>
                                                        @else
                                                            <span style="font-size: 0.78rem; font-weight: 700; color: #92400E;">Non corrigé</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div style="font-size: 0.88rem; color: var(--sys-text-default, #1A1D23); margin-top: 8px;">
                                                    {!! $submission->renderedBody() !!}
                                                </div>

                                                @if ($submission->attachmentUrl())
                                                    <p style="margin: 8px 0 0;">
                                                        <a href="{{ $submission->attachmentUrl() }}" target="_blank" rel="noopener" style="color: var(--sys-action-primary, #064E5A); font-size: 0.85rem;">Pièce jointe</a>
                                                    </p>
                                                @endif

                                                @if ($gradingSubmission === $submission->id)
                                                    <form wire:submit="gradeSubmission" style="margin-top: 12px; display: flex; flex-direction: column; gap: 10px;">
                                                        <div style="display: flex; flex-direction: column; gap: 6px; max-width: 200px;">
                                                            <label for="grade-score-{{ $submission->id }}" style="font-weight: 600; font-size: 0.82rem;">Note (0 à {{ $assignment->max_points }})</label>
                                                            <input id="grade-score-{{ $submission->id }}" type="number" min="0" max="{{ $assignment->max_points }}" wire:model="gradeScore"
                                                                   style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                            @error('gradeScore') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div style="display: flex; flex-direction: column; gap: 6px;">
                                                            <label for="grade-feedback-{{ $submission->id }}" style="font-weight: 600; font-size: 0.82rem;">Rétroaction (markdown)</label>
                                                            <textarea id="grade-feedback-{{ $submission->id }}" wire:model="gradeFeedback" rows="3" maxlength="20000"
                                                                      style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-family: inherit;"></textarea>
                                                            @error('gradeFeedback') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div class="d-flex gap-2">
                                                            <x-core::button type="submit" variant="primary" size="sm">Enregistrer la note</x-core::button>
                                                            <x-core::button type="button" wire:click="cancelGrading" variant="ghost" size="sm">Annuler</x-core::button>
                                                        </div>
                                                    </form>
                                                @else
                                                    <div style="margin-top: 10px;">
                                                        @if ($submission->isGraded() && $submission->feedback)
                                                            <div style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin-bottom: 8px;">
                                                                <strong>Rétroaction :</strong> {!! $submission->renderedFeedback() !!}
                                                            </div>
                                                        @endif
                                                        <x-core::button type="button" wire:click="startGrading({{ $submission->id }})" variant="secondary" size="sm">
                                                            {{ $submission->isGraded() ? 'Modifier la note' : 'Noter' }}
                                                        </x-core::button>
                                                    </div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0;">Aucune remise pour ce devoir.</p>
                                @endif
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p style="font-size: 0.9rem; color: var(--sys-text-muted, #6B7280);">Aucun devoir pour l'instant.</p>
        @endif
    </section>

    {{-- ───────────────────────── Carnet de notes (gradebook) ───────────────────────── --}}
    @if ($this->canGrade)
        <section aria-labelledby="assignments-gradebook"
                 style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2" style="margin-bottom: 12px;">
                <h3 id="assignments-gradebook" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0; font-size: 1.1rem;">
                    Carnet de notes
                </h3>
                <x-core::button type="button" wire:click="toggleGradebook" variant="ghost" size="sm">
                    {{ $showGradebook ? 'Masquer' : 'Afficher' }}
                </x-core::button>
            </div>

            @if ($showGradebook)
                @php($gb = $this->gradebook)
                @if (count($gb['students']) > 0)
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                            <thead>
                                <tr style="text-align: left; border-bottom: 2px solid #E5E7EB;">
                                    <th scope="col" style="padding: 8px 10px;">Personne inscrite</th>
                                    @foreach ($gb['assignments'] as $a)
                                        <th scope="col" style="padding: 8px 10px; white-space: nowrap;">{{ $a->title }} <span style="color: var(--sys-text-muted, #6B7280);">/ {{ $a->max_points }}</span></th>
                                    @endforeach
                                    @if ($gb['quizTotal'] > 0)
                                        <th scope="col" style="padding: 8px 10px; white-space: nowrap;">Quiz</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($gb['students'] as $student)
                                    <tr style="border-bottom: 1px solid #F3F4F6;">
                                        <th scope="row" style="padding: 8px 10px; font-weight: 600; text-align: left;">{{ $student['user']?->name ?? 'Inconnu' }}</th>
                                        @foreach ($student['cells'] as $cell)
                                            <td style="padding: 8px 10px;">
                                                @if ($cell !== null)
                                                    {{ $cell }}
                                                @else
                                                    <span style="color: var(--sys-text-muted, #9CA3AF);">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        @if ($gb['quizTotal'] > 0)
                                            <td style="padding: 8px 10px;">{{ $student['quizScore'] }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0;">Aucune personne inscrite active.</p>
                @endif
            @endif
        </section>
    @endif

</div>

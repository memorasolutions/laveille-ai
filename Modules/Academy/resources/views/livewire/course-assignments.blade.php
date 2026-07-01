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
                <div style="display: flex; flex-direction: column; gap: 6px; flex: 1 1 240px;">
                    <label for="assignment-scale" style="font-weight: 600; font-size: 0.85rem;">Mode de notation</label>
                    <select id="assignment-scale" wire:model="scaleId"
                            style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        <option value="">Note numérique (sur les points maximum)</option>
                        @foreach ($this->selectableScales as $scale)
                            <option value="{{ $scale->id }}">Échelle : {{ $scale->name }}</option>
                        @endforeach
                    </select>
                    <span style="font-size: 0.74rem; color: var(--sys-text-muted, #6B7280);">Une échelle convertit le niveau choisi en points sur le maximum. Gérez vos échelles dans « Pondération et lettres ».</span>
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
                                        <span style="display: inline-block; font-weight: 700; color: var(--sys-status-success-alt-text, #134E2A); background: var(--sys-status-success-alt-bg, #F0FDF4); border: 1px solid #BBF7D0; border-radius: 999px; padding: 2px 10px;">Publié</span>
                                    @else
                                        <span style="display: inline-block; font-weight: 700; color: var(--sys-status-caution-text, #7A3406); background: var(--sys-status-caution-bg, #FFFBEB); border: 1px solid #FDE68A; border-radius: 999px; padding: 2px 10px;">Brouillon</span>
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
                                @if ($rubricAssignment === $assignment->id)
                                    <x-core::button type="button" wire:click="closeRubric" variant="secondary" size="sm">Fermer la grille</x-core::button>
                                @else
                                    <x-core::button type="button" wire:click="openRubric({{ $assignment->id }})" variant="ghost" size="sm">Grille d'évaluation</x-core::button>
                                @endif
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

                        {{-- ─── V2-a : construction de la grille d'évaluation (rubric) ─── --}}
                        @if ($rubricAssignment === $assignment->id)
                            <div style="margin-top: 14px; border-top: 1px dashed #E5E7EB; padding-top: 14px;">
                                <div class="d-flex justify-content-between align-items-center" style="margin-bottom: 6px;">
                                    <strong style="font-size: 0.9rem;">Grille d'évaluation</strong>
                                    <x-core::button type="button" wire:click="closeRubric" variant="ghost" size="sm">Fermer</x-core::button>
                                </div>
                                <p style="font-size: 0.8rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 12px;">
                                    Ajoutez des critères, puis des niveaux (libellé + points) par critère. À la correction, vous choisirez un niveau par critère et la note sera calculée puis ramenée sur {{ $assignment->max_points }} points. Un devoir sans critère reste noté à la main.
                                </p>

                                @if ($this->rubricCriteria->isNotEmpty())
                                    <ul class="list-unstyled d-flex flex-column gap-3" style="margin: 0 0 14px;">
                                        @foreach ($this->rubricCriteria as $criterion)
                                            <li wire:key="criterion-{{ $criterion->id }}"
                                                style="border: 1px solid #F3F4F6; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px 14px; background: #FAFAFA;">
                                                {{-- En-tête du critère (édition inline) --}}
                                                @if ($editingCriterion === $criterion->id)
                                                    <form wire:submit="saveCriterion" class="d-flex flex-wrap align-items-start gap-2">
                                                        <div style="flex: 1 1 240px; display: flex; flex-direction: column; gap: 4px;">
                                                            <label for="criterion-edit-{{ $criterion->id }}" class="visually-hidden">Libellé du critère</label>
                                                            <input id="criterion-edit-{{ $criterion->id }}" type="text" wire:model="criterionDescription" maxlength="500"
                                                                   style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                            @error('criterionDescription') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.8rem;">{{ $message }}</span> @enderror
                                                        </div>
                                                        <x-core::button type="submit" variant="primary" size="sm">Enregistrer</x-core::button>
                                                        <x-core::button type="button" wire:click="cancelCriterionEdit" variant="ghost" size="sm">Annuler</x-core::button>
                                                    </form>
                                                @else
                                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                                        <strong style="font-size: 0.9rem; flex: 1 1 240px;">{{ $criterion->description }}</strong>
                                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                                            <x-core::button type="button" wire:click="editCriterion({{ $criterion->id }})" variant="ghost" size="sm">Modifier</x-core::button>
                                                            @if ($confirmingCriterionRemoval === $criterion->id)
                                                                <span style="font-size: 0.8rem; font-weight: 600;">Supprimer ce critère ?</span>
                                                                <x-core::button type="button" wire:click="deleteCriterion({{ $criterion->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                                                <x-core::button type="button" wire:click="cancelCriterionRemoval" variant="ghost" size="sm">Annuler</x-core::button>
                                                            @else
                                                                <x-core::button type="button" wire:click="confirmCriterionRemoval({{ $criterion->id }})" variant="ghost" size="sm"
                                                                                aria-label="Supprimer le critère « {{ $criterion->description }} »">Supprimer</x-core::button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- Niveaux du critère --}}
                                                <ul class="list-unstyled d-flex flex-column gap-1" style="margin: 10px 0 0;">
                                                    @foreach ($criterion->levels as $level)
                                                        <li wire:key="level-{{ $level->id }}" style="font-size: 0.85rem;">
                                                            @if ($editingLevel === $level->id)
                                                                <form wire:submit="saveLevel" class="d-flex flex-wrap align-items-start gap-2">
                                                                    <div style="flex: 1 1 200px; display: flex; flex-direction: column; gap: 4px;">
                                                                        <label for="level-desc-{{ $level->id }}" class="visually-hidden">Libellé du niveau</label>
                                                                        <input id="level-desc-{{ $level->id }}" type="text" wire:model="levelDescription" maxlength="500"
                                                                               style="width: 100%; padding: 6px 8px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                        @error('levelDescription') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.78rem;">{{ $message }}</span> @enderror
                                                                    </div>
                                                                    <div style="flex: 0 0 90px; display: flex; flex-direction: column; gap: 4px;">
                                                                        <label for="level-pts-{{ $level->id }}" class="visually-hidden">Points</label>
                                                                        <input id="level-pts-{{ $level->id }}" type="number" min="0" max="100000" wire:model="levelPoints"
                                                                               style="width: 100%; padding: 6px 8px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                        @error('levelPoints') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.78rem;">{{ $message }}</span> @enderror
                                                                    </div>
                                                                    <x-core::button type="submit" variant="primary" size="sm">Enregistrer</x-core::button>
                                                                    <x-core::button type="button" wire:click="cancelLevelEdit" variant="ghost" size="sm">Annuler</x-core::button>
                                                                </form>
                                                            @else
                                                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2"
                                                                     style="border: 1px solid #F3F4F6; border-radius: var(--sys-radius-md, 0.4rem); padding: 6px 10px; background: #FFFFFF;">
                                                                    <span>{{ $level->description }} <span style="color: var(--sys-text-muted, #6B7280);">· {{ $level->points }} pt(s)</span></span>
                                                                    <span class="d-flex flex-wrap align-items-center gap-2">
                                                                        <x-core::button type="button" wire:click="editLevel({{ $level->id }})" variant="ghost" size="sm">Modifier</x-core::button>
                                                                        @if ($confirmingLevelRemoval === $level->id)
                                                                            <span style="font-size: 0.78rem; font-weight: 600;">Supprimer ?</span>
                                                                            <x-core::button type="button" wire:click="deleteLevel({{ $level->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                                                            <x-core::button type="button" wire:click="cancelLevelRemoval" variant="ghost" size="sm">Annuler</x-core::button>
                                                                        @else
                                                                            <x-core::button type="button" wire:click="confirmLevelRemoval({{ $level->id }})" variant="ghost" size="sm"
                                                                                            aria-label="Supprimer le niveau « {{ $level->description }} »">Supprimer</x-core::button>
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                    @if ($criterion->levels->isEmpty())
                                                        <li style="font-size: 0.8rem; color: var(--sys-text-muted, #92400E);">Aucun niveau : ajoutez-en au moins un pour rendre ce critère notable.</li>
                                                    @endif
                                                </ul>

                                                {{-- Ajouter un niveau à ce critère --}}
                                                @if ($addingLevelTo === $criterion->id)
                                                    <form wire:submit="addLevel" class="d-flex flex-wrap align-items-start gap-2" style="margin-top: 10px;">
                                                        <div style="flex: 1 1 200px; display: flex; flex-direction: column; gap: 4px;">
                                                            <label for="level-new-desc-{{ $criterion->id }}" style="font-size: 0.78rem; font-weight: 600;">Libellé du niveau</label>
                                                            <input id="level-new-desc-{{ $criterion->id }}" type="text" wire:model="levelDescription" maxlength="500" placeholder="Ex. : Excellent"
                                                                   style="width: 100%; padding: 6px 8px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                            @error('levelDescription') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.78rem;">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div style="flex: 0 0 90px; display: flex; flex-direction: column; gap: 4px;">
                                                            <label for="level-new-pts-{{ $criterion->id }}" style="font-size: 0.78rem; font-weight: 600;">Points</label>
                                                            <input id="level-new-pts-{{ $criterion->id }}" type="number" min="0" max="100000" wire:model="levelPoints" placeholder="0"
                                                                   style="width: 100%; padding: 6px 8px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                            @error('levelPoints') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.78rem;">{{ $message }}</span> @enderror
                                                        </div>
                                                        <x-core::button type="submit" variant="primary" size="sm">Ajouter</x-core::button>
                                                        <x-core::button type="button" wire:click="cancelAddLevel" variant="ghost" size="sm">Annuler</x-core::button>
                                                    </form>
                                                @else
                                                    <div style="margin-top: 10px;">
                                                        <x-core::button type="button" wire:click="startAddLevel({{ $criterion->id }})" variant="secondary" size="sm">Ajouter un niveau</x-core::button>
                                                    </div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 12px;">Aucun critère pour l'instant.</p>
                                @endif

                                {{-- Ajouter un critère --}}
                                <form wire:submit="addCriterion" class="d-flex flex-wrap align-items-start gap-2">
                                    <div style="flex: 1 1 260px; display: flex; flex-direction: column; gap: 4px;">
                                        <label for="criterion-new-{{ $assignment->id }}" style="font-size: 0.82rem; font-weight: 600;">Nouveau critère</label>
                                        <input id="criterion-new-{{ $assignment->id }}" type="text" wire:model="newCriterion" maxlength="500" placeholder="Ex. : qualité de l'argumentation"
                                               style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                        @error('newCriterion') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.8rem;">{{ $message }}</span> @enderror
                                    </div>
                                    <x-core::button type="submit" variant="secondary" size="sm">Ajouter le critère</x-core::button>
                                </form>
                            </div>
                        @endif

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
                                                    @php($gradingCriteria = $this->gradingCriteria->filter(fn ($c) => $c->levels->isNotEmpty()))
                                                    <form wire:submit="gradeSubmission" style="margin-top: 12px; display: flex; flex-direction: column; gap: 10px;">
                                                        @if ($gradingCriteria->isNotEmpty())
                                                            {{-- V2-a : correction PAR GRILLE (un niveau par critère, note auto-calculée) --}}
                                                            <fieldset style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.5rem); padding: 10px 12px; margin: 0;">
                                                                <legend style="font-weight: 600; font-size: 0.82rem; padding: 0 6px;">Grille d'évaluation (la note sera ramenée sur {{ $assignment->max_points }} points)</legend>
                                                                @foreach ($gradingCriteria as $criterion)
                                                                    <div wire:key="grade-criterion-{{ $criterion->id }}" style="margin-bottom: 8px;">
                                                                        <p style="font-weight: 600; font-size: 0.82rem; margin: 0 0 4px;">{{ $criterion->description }}</p>
                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            @foreach ($criterion->levels as $level)
                                                                                <label wire:key="grade-level-{{ $level->id }}" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; border: 1px solid #E5E7EB; border-radius: 999px; padding: 4px 12px; min-height: 24px; cursor: pointer;">
                                                                                    <input type="radio" wire:model="rubricSelection.{{ $criterion->id }}" value="{{ $level->id }}">
                                                                                    {{ $level->description }} <span style="color: var(--sys-text-muted, #6B7280);">({{ $level->points }} pt)</span>
                                                                                </label>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                                @error('rubricSelection') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                                            </fieldset>
                                                        @elseif ($assignment->hasScale())
                                                        {{-- F14 : correction PAR ÉCHELLE (un niveau ; converti en points sur max_points) --}}
                                                        <div style="display: flex; flex-direction: column; gap: 6px; max-width: 320px;">
                                                            <label for="grade-scale-{{ $submission->id }}" style="font-weight: 600; font-size: 0.82rem;">Niveau de l'échelle « {{ $assignment->scale?->name }} » (ramené sur {{ $assignment->max_points }} points)</label>
                                                            <select id="grade-scale-{{ $submission->id }}" wire:model="gradeScaleLevel"
                                                                    style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                <option value="">Choisir un niveau</option>
                                                                {{-- scaleLevelsWithPoints pré-calculé dans startGrading() : {label, value, points} --}}
                                                                @foreach ($scaleLevelsWithPoints as $i => $lvl)
                                                                    <option value="{{ $i }}">{{ $lvl['label'] }} ({{ $lvl['points'] }} / {{ $assignment->max_points }} pts)</option>
                                                                @endforeach
                                                            </select>
                                                            @error('gradeScaleLevel') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                                        </div>
                                                        @else
                                                        <div style="display: flex; flex-direction: column; gap: 6px; max-width: 200px;">
                                                            <label for="grade-score-{{ $submission->id }}" style="font-weight: 600; font-size: 0.82rem;">Note (0 à {{ $assignment->max_points }})</label>
                                                            <input id="grade-score-{{ $submission->id }}" type="number" min="0" max="{{ $assignment->max_points }}" wire:model="gradeScore"
                                                                   style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                            @error('gradeScore') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span> @enderror
                                                        </div>
                                                        @endif
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

                                                        {{-- Feedback IA sur réponses ouvertes (LMS 2026) : PROPOSITION seulement.
                                                             Gâté par le drapeau academy.ai_feedback_enabled + service IA présent.
                                                             L'IA ne note JAMAIS : le formateur garde le dernier mot. --}}
                                                        @if ($this->aiFeedbackAvailable && $aiFeedbackSubmission === $submission->id)
                                                            <div style="margin: 8px 0 10px; border: 1px solid #064E5A; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px; background: rgba(6,78,90,0.04);">
                                                                <p style="margin: 0 0 8px; font-weight: 600; font-size: 0.82rem; color: #064E5A;">
                                                                    ✨ Proposition de feedback IA
                                                                    <span style="font-weight: 400; color: var(--sys-text-muted, #4B5563);">(brouillon — à réviser, jamais appliqué automatiquement)</span>
                                                                </p>
                                                                <div style="display: flex; flex-direction: column; gap: 6px; max-width: 220px; margin-bottom: 8px;">
                                                                    <label for="ai-score-{{ $submission->id }}" style="font-weight: 600; font-size: 0.82rem;">Note suggérée (sur {{ $assignment->max_points }})</label>
                                                                    <input id="ai-score-{{ $submission->id }}" type="number" min="0" max="{{ $assignment->max_points }}" wire:model.live.blur="aiSuggestedScoreDraft"
                                                                           style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                                </div>
                                                                <div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 8px;">
                                                                    <label for="ai-fb-{{ $submission->id }}" style="font-weight: 600; font-size: 0.82rem;">Feedback suggéré (markdown, éditable)</label>
                                                                    <textarea id="ai-fb-{{ $submission->id }}" wire:model.live.blur="aiFeedbackDraft" rows="6" maxlength="20000"
                                                                              style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-family: inherit;"></textarea>
                                                                </div>
                                                                <div class="d-flex flex-wrap gap-2">
                                                                    <x-core::button type="button" wire:click="applyAiFeedbackToGrading({{ $submission->id }})" variant="primary" size="sm">
                                                                        Utiliser dans la correction
                                                                    </x-core::button>
                                                                    <x-core::button type="button" wire:click="dismissAiFeedback" variant="ghost" size="sm">
                                                                        Rejeter
                                                                    </x-core::button>
                                                                </div>
                                                                <p style="margin: 8px 0 0; font-size: 0.78rem; color: var(--sys-text-muted, #4B5563);">
                                                                    « Utiliser dans la correction » pré-remplit le formulaire ; rien n'est officiel tant que vous n'avez pas cliqué « Enregistrer la note ».
                                                                </p>
                                                            </div>
                                                        @elseif ($this->aiFeedbackAvailable && $aiFeedbackError !== '' && $aiFeedbackSubmission === null)
                                                            <p style="margin: 0 0 8px; font-size: 0.82rem; color: var(--sys-action-danger, #DC2626);" wire:key="ai-err-{{ $submission->id }}">{{ $aiFeedbackError }}</p>
                                                        @endif

                                                        <div class="d-flex flex-wrap gap-2">
                                                            <x-core::button type="button" wire:click="startGrading({{ $submission->id }})" variant="secondary" size="sm">
                                                                {{ $submission->isGraded() ? 'Modifier la note' : 'Noter' }}
                                                            </x-core::button>
                                                            @if ($this->aiFeedbackAvailable && $aiFeedbackSubmission !== $submission->id)
                                                                <x-core::button type="button" wire:click="proposeAiFeedback({{ $submission->id }})" variant="ghost" size="sm"
                                                                                wire:loading.attr="disabled" wire:target="proposeAiFeedback({{ $submission->id }})">
                                                                    <span wire:loading.remove wire:target="proposeAiFeedback({{ $submission->id }})">✨ Proposer un feedback IA</span>
                                                                    <span wire:loading wire:target="proposeAiFeedback({{ $submission->id }})">Génération…</span>
                                                                </x-core::button>
                                                            @endif
                                                        </div>
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

    {{-- ───────────────────────── V2-b : pondération du carnet ───────────────────────── --}}
    <section aria-labelledby="grade-structure"
             style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 22px 24px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2" style="margin-bottom: 8px;">
            <h3 id="grade-structure" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0; font-size: 1.1rem;">
                Pondération et lettres
            </h3>
            <x-core::button type="button" wire:click="toggleGradeStructure" variant="ghost" size="sm">
                {{ $showGradeStructure ? 'Masquer' : 'Configurer' }}
            </x-core::button>
        </div>
        <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0;">
            Créez des catégories pondérées (ex. Quiz 40 %, Devoirs 60 %), classez-y chaque évaluation, puis personnalisez le barème de lettres. Sans aucune catégorie, le carnet reste en agrégation simple (inchangé).
        </p>

        @if ($showGradeStructure)
            {{-- Catégories de notes --}}
            <div style="margin-top: 18px; border-top: 1px dashed #E5E7EB; padding-top: 16px;">
                <strong style="font-size: 0.95rem;">Catégories de notes</strong>

                @if ($this->gradeCategories->isNotEmpty())
                    <ul class="list-unstyled d-flex flex-column gap-2" style="margin: 10px 0 14px;">
                        @foreach ($this->gradeCategories as $cat)
                            <li wire:key="cat-{{ $cat->id }}"
                                style="border: 1px solid #F3F4F6; border-radius: var(--sys-radius-md, 0.5rem); padding: 10px 12px; background: #FAFAFA;">
                                @if ($editingCategory === $cat->id)
                                    <form wire:submit="saveCategory" class="d-flex flex-wrap align-items-end gap-2">
                                        <div style="flex: 1 1 200px; display: flex; flex-direction: column; gap: 4px;">
                                            <label for="cat-edit-name-{{ $cat->id }}" style="font-size: 0.78rem; font-weight: 600;">Nom</label>
                                            <input id="cat-edit-name-{{ $cat->id }}" type="text" wire:model="editCategoryName" maxlength="120"
                                                   style="width: 100%; padding: 7px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                            @error('editCategoryName') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.78rem;">{{ $message }}</span> @enderror
                                        </div>
                                        <div style="flex: 0 0 110px; display: flex; flex-direction: column; gap: 4px;">
                                            <label for="cat-edit-weight-{{ $cat->id }}" style="font-size: 0.78rem; font-weight: 600;">Poids (%)</label>
                                            <input id="cat-edit-weight-{{ $cat->id }}" type="number" min="0" max="100" step="0.01" wire:model="editCategoryWeight"
                                                   style="width: 100%; padding: 7px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                            @error('editCategoryWeight') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.78rem;">{{ $message }}</span> @enderror
                                        </div>
                                        <div style="flex: 1 1 240px; display: flex; flex-direction: column; gap: 4px;">
                                            <label for="cat-edit-method-{{ $cat->id }}" style="font-size: 0.78rem; font-weight: 600;">Méthode d'agrégation</label>
                                            <select id="cat-edit-method-{{ $cat->id }}" wire:model="editCategoryMethod"
                                                    style="width: 100%; padding: 7px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                @foreach (\Modules\Academy\Models\GradeCategory::aggregationLabels() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('editCategoryMethod') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.78rem;">{{ $message }}</span> @enderror
                                        </div>
                                        <x-core::button type="submit" variant="primary" size="sm">Enregistrer</x-core::button>
                                        <x-core::button type="button" wire:click="cancelCategoryEdit" variant="ghost" size="sm">Annuler</x-core::button>
                                    </form>
                                @else
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <span style="font-size: 0.9rem;"><strong>{{ $cat->name }}</strong> <span style="color: var(--sys-text-muted, #6B7280);">· {{ rtrim(rtrim(number_format($cat->weight, 2, '.', ''), '0'), '.') }} % · {{ \Modules\Academy\Models\GradeCategory::aggregationLabels()[$cat->effectiveAggregationMethod()] ?? '' }}</span></span>
                                        <span class="d-flex flex-wrap align-items-center gap-2">
                                            <x-core::button type="button" wire:click="editCategory({{ $cat->id }})" variant="ghost" size="sm">Modifier</x-core::button>
                                            @if ($confirmingCategoryRemoval === $cat->id)
                                                <span style="font-size: 0.8rem; font-weight: 600;">Supprimer ?</span>
                                                <x-core::button type="button" wire:click="deleteCategory({{ $cat->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                                <x-core::button type="button" wire:click="cancelCategoryRemoval" variant="ghost" size="sm">Annuler</x-core::button>
                                            @else
                                                <x-core::button type="button" wire:click="confirmCategoryRemoval({{ $cat->id }})" variant="ghost" size="sm"
                                                                aria-label="Supprimer la catégorie « {{ $cat->name }} »">Supprimer</x-core::button>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 10px 0 14px;">Aucune catégorie : le carnet reste en agrégation simple.</p>
                @endif

                <form wire:submit="addCategory" class="d-flex flex-wrap align-items-end gap-2">
                    <div style="flex: 1 1 200px; display: flex; flex-direction: column; gap: 4px;">
                        <label for="cat-new-name" style="font-size: 0.8rem; font-weight: 600;">Nouvelle catégorie</label>
                        <input id="cat-new-name" type="text" wire:model="newCategoryName" maxlength="120" placeholder="Ex. : Quiz"
                               style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        @error('newCategoryName') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.8rem;">{{ $message }}</span> @enderror
                    </div>
                    <div style="flex: 0 0 110px; display: flex; flex-direction: column; gap: 4px;">
                        <label for="cat-new-weight" style="font-size: 0.8rem; font-weight: 600;">Poids (%)</label>
                        <input id="cat-new-weight" type="number" min="0" max="100" step="0.01" wire:model="newCategoryWeight" placeholder="40"
                               style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                        @error('newCategoryWeight') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.8rem;">{{ $message }}</span> @enderror
                    </div>
                    <div style="flex: 1 1 240px; display: flex; flex-direction: column; gap: 4px;">
                        <label for="cat-new-method" style="font-size: 0.8rem; font-weight: 600;">Méthode d'agrégation</label>
                        <select id="cat-new-method" wire:model="newCategoryMethod"
                                style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                            @foreach (\Modules\Academy\Models\GradeCategory::aggregationLabels() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('newCategoryMethod') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.8rem;">{{ $message }}</span> @enderror
                    </div>
                    <x-core::button type="submit" variant="secondary" size="sm">Ajouter la catégorie</x-core::button>
                </form>
            </div>

            {{-- Affectation des items à une catégorie + poids --}}
            @if ($this->gradeCategories->isNotEmpty() && count($this->gradableItems) > 0)
                <div style="margin-top: 18px; border-top: 1px dashed #E5E7EB; padding-top: 16px;">
                    <strong style="font-size: 0.95rem;">Classer les évaluations</strong>
                    <p style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 12px;">
                        Choisissez la catégorie et le poids relatif de chaque évaluation au sein de sa catégorie. « Aucune » retire l'évaluation de la pondération.
                    </p>
                    <form wire:submit="saveItemAssignments">
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                                <thead>
                                    <tr style="text-align: left; border-bottom: 2px solid #E5E7EB;">
                                        <th scope="col" style="padding: 8px 10px;">Évaluation</th>
                                        <th scope="col" style="padding: 8px 10px;">Type</th>
                                        <th scope="col" style="padding: 8px 10px;">Catégorie</th>
                                        <th scope="col" style="padding: 8px 10px;">Poids</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->gradableItems as $item)
                                        @php($key = $item['type'].'_'.$item['id'])
                                        <tr wire:key="gi-{{ $key }}" style="border-bottom: 1px solid #F3F4F6;">
                                            <td style="padding: 8px 10px;">{{ $item['title'] }}</td>
                                            <td style="padding: 8px 10px; color: var(--sys-text-muted, #6B7280);">{{ $item['type'] === 'quiz' ? 'Quiz' : 'Devoir' }}</td>
                                            <td style="padding: 8px 10px;">
                                                <label for="gi-cat-{{ $key }}" class="visually-hidden">Catégorie de « {{ $item['title'] }} »</label>
                                                <select id="gi-cat-{{ $key }}" wire:model="itemCategoryMap.{{ $key }}"
                                                        style="padding: 6px 8px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                    <option value="">Aucune</option>
                                                    @foreach ($this->gradeCategories as $cat)
                                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td style="padding: 8px 10px;">
                                                <label for="gi-w-{{ $key }}" class="visually-hidden">Poids de « {{ $item['title'] }} »</label>
                                                <input id="gi-w-{{ $key }}" type="number" min="0" step="0.01" wire:model="itemWeightMap.{{ $key }}"
                                                       style="width: 90px; padding: 6px 8px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 12px;">
                            <x-core::button type="submit" variant="primary" size="sm">Enregistrer les affectations</x-core::button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Barème de lettres --}}
            <div style="margin-top: 18px; border-top: 1px dashed #E5E7EB; padding-top: 16px;">
                <strong style="font-size: 0.95rem;">Barème de lettres</strong>
                <p style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 12px;">
                    Chaque bande attribue une lettre à partir d'un seuil minimal (en %). Laissez vide pour revenir au barème par défaut.
                </p>
                <form wire:submit="saveLetterScheme" style="display: flex; flex-direction: column; gap: 8px;">
                    @foreach ($letterBands as $i => $band)
                        <div wire:key="band-{{ $i }}" class="d-flex flex-wrap align-items-end gap-2">
                            <div style="flex: 0 0 120px; display: flex; flex-direction: column; gap: 4px;">
                                <label for="band-letter-{{ $i }}" style="font-size: 0.78rem; font-weight: 600;">Lettre</label>
                                <input id="band-letter-{{ $i }}" type="text" wire:model="letterBands.{{ $i }}.letter" maxlength="4"
                                       style="width: 100%; padding: 6px 8px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                            </div>
                            <div style="flex: 0 0 120px; display: flex; flex-direction: column; gap: 4px;">
                                <label for="band-min-{{ $i }}" style="font-size: 0.78rem; font-weight: 600;">Seuil min (%)</label>
                                <input id="band-min-{{ $i }}" type="number" min="0" max="100" step="0.1" wire:model="letterBands.{{ $i }}.min"
                                       style="width: 100%; padding: 6px 8px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                            </div>
                            <x-core::button type="button" wire:click="removeLetterBand({{ $i }})" variant="ghost" size="sm">Retirer</x-core::button>
                        </div>
                    @endforeach
                    <div class="d-flex flex-wrap gap-2" style="margin-top: 4px;">
                        <x-core::button type="button" wire:click="addLetterBand" variant="ghost" size="sm">Ajouter une bande</x-core::button>
                        <x-core::button type="submit" variant="secondary" size="sm">Enregistrer le barème</x-core::button>
                    </div>
                </form>
            </div>

            {{-- F14 : Échelles personnalisées (CRUD owner-scopé) --}}
            <div style="margin-top: 18px; border-top: 1px dashed #E5E7EB; padding-top: 16px;">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <strong style="font-size: 0.95rem;">Échelles personnalisées</strong>
                    <x-core::button type="button" wire:click="toggleScales" variant="ghost" size="sm">
                        {{ $showScales ? 'Masquer' : 'Gérer mes échelles' }}
                    </x-core::button>
                </div>
                <p style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280); margin: 4px 0 12px;">
                    Une échelle (ex. « Insuffisant, Acceptable, Maîtrisé ») peut noter un devoir : à la correction, le niveau choisi est converti en points sur le maximum du devoir.
                </p>

                @if ($showScales)
                    @if ($this->selectableScales->isNotEmpty())
                        <ul class="list-unstyled d-flex flex-column gap-2" style="margin: 0 0 14px;">
                            @foreach ($this->selectableScales as $scale)
                                <li wire:key="scale-{{ $scale->id }}"
                                    style="border: 1px solid #F3F4F6; border-radius: var(--sys-radius-md, 0.5rem); padding: 10px 12px; background: #FAFAFA;">
                                    @if ($editingScale === $scale->id)
                                        <form wire:submit="saveScale" style="display: flex; flex-direction: column; gap: 8px;">
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <label for="scale-edit-name-{{ $scale->id }}" style="font-size: 0.78rem; font-weight: 600;">Nom</label>
                                                <input id="scale-edit-name-{{ $scale->id }}" type="text" wire:model="editScaleName" maxlength="120"
                                                       style="width: 100%; padding: 7px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                                                @error('editScaleName') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.78rem;">{{ $message }}</span> @enderror
                                            </div>
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <label for="scale-edit-items-{{ $scale->id }}" style="font-size: 0.78rem; font-weight: 600;">Niveaux (un par ligne : « libellé | valeur », du plus faible au plus fort)</label>
                                                <textarea id="scale-edit-items-{{ $scale->id }}" wire:model="editScaleItems" rows="4" maxlength="5000"
                                                          style="width: 100%; padding: 7px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-family: inherit;"></textarea>
                                                @error('editScaleItems') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.78rem;">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <x-core::button type="submit" variant="primary" size="sm">Enregistrer</x-core::button>
                                                <x-core::button type="button" wire:click="cancelScaleEdit" variant="ghost" size="sm">Annuler</x-core::button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <span style="font-size: 0.9rem;">
                                                <strong>{{ $scale->name }}</strong>
                                                <span style="color: var(--sys-text-muted, #6B7280);">· {{ collect($scale->levels())->pluck('label')->implode(', ') }}</span>
                                                @if ($scale->owner_id === null)<span style="color: var(--sys-text-muted, #6B7280);"> · système</span>@endif
                                            </span>
                                            <span class="d-flex flex-wrap align-items-center gap-2">
                                                <x-core::button type="button" wire:click="editScale({{ $scale->id }})" variant="ghost" size="sm">Modifier</x-core::button>
                                                @if ($confirmingScaleRemoval === $scale->id)
                                                    <span style="font-size: 0.8rem; font-weight: 600;">Supprimer ?</span>
                                                    <x-core::button type="button" wire:click="deleteScale({{ $scale->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                                                    <x-core::button type="button" wire:click="cancelScaleRemoval" variant="ghost" size="sm">Annuler</x-core::button>
                                                @else
                                                    <x-core::button type="button" wire:click="confirmScaleRemoval({{ $scale->id }})" variant="ghost" size="sm"
                                                                    aria-label="Supprimer l'échelle « {{ $scale->name }} »">Supprimer</x-core::button>
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 0 0 14px;">Aucune échelle pour l'instant.</p>
                    @endif

                    <form wire:submit="addScale" style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <label for="scale-new-name" style="font-size: 0.8rem; font-weight: 600;">Nouvelle échelle</label>
                            <input id="scale-new-name" type="text" wire:model="newScaleName" maxlength="120" placeholder="Ex. : Maîtrise"
                                   style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
                            @error('newScaleName') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.8rem;">{{ $message }}</span> @enderror
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <label for="scale-new-items" style="font-size: 0.8rem; font-weight: 600;">Niveaux (un par ligne : « libellé | valeur », du plus faible au plus fort)</label>
                            <textarea id="scale-new-items" wire:model="newScaleItems" rows="4" maxlength="5000" placeholder="Insuffisant | 0&#10;Acceptable | 1&#10;Maîtrisé | 2"
                                      style="width: 100%; padding: 8px 10px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-family: inherit;"></textarea>
                            @error('newScaleItems') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.8rem;">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <x-core::button type="submit" variant="secondary" size="sm">Créer l'échelle</x-core::button>
                        </div>
                    </form>
                @endif
            </div>
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
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <x-core::button type="button" wire:click="exportGradebookCsv" variant="secondary" size="sm">
                        Exporter en CSV
                    </x-core::button>
                    <x-core::button type="button" wire:click="toggleGradebook" variant="ghost" size="sm">
                        {{ $showGradebook ? 'Masquer' : 'Afficher' }}
                    </x-core::button>
                </div>
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
                                        <th scope="col" style="padding: 8px 10px; white-space: nowrap;">Quiz <span style="color: var(--sys-text-muted, #6B7280);">/ 100</span></th>
                                    @endif
                                    @if ($gb['weighted'])
                                        @foreach ($gb['categories'] as $cat)
                                            <th scope="col" style="padding: 8px 10px; white-space: nowrap;">{{ $cat->name }} <span style="color: var(--sys-text-muted, #6B7280);">(pond.)</span></th>
                                        @endforeach
                                        <th scope="col" style="padding: 8px 10px; white-space: nowrap;">Note finale</th>
                                        <th scope="col" style="padding: 8px 10px; white-space: nowrap;">Lettre</th>
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
                                                    <span style="color: var(--sys-text-muted, #9CA3AF);">–</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        @if ($gb['quizTotal'] > 0)
                                            <td style="padding: 8px 10px;">{{ $student['quizScore'] }}%</td>
                                        @endif
                                        @if ($gb['weighted'])
                                            @php($catById = collect($student['catScores'])->keyBy('id'))
                                            @foreach ($gb['categories'] as $cat)
                                                @php($cs = $catById->get($cat->id))
                                                <td style="padding: 8px 10px;">
                                                    @if ($cs && $cs['hasData'])
                                                        {{ round($cs['score'], 1) }}%
                                                    @else
                                                        <span style="color: var(--sys-text-muted, #9CA3AF);">–</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td style="padding: 8px 10px; font-weight: 700;">{{ $student['final'] }}%</td>
                                            <td style="padding: 8px 10px; font-weight: 700;">{{ $student['letter'] }}</td>
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

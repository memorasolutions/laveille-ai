{{-- Author: MEMORA solutions, https://memora.solutions --}}
{{--
    ESSAI - Correction manuelle des tentatives de quiz contenant un essai.
    Gâté manageEnrollments (mount + chaque action). Tentative re-résolue scopée
    au cours (anti-IDOR). Réponse de l'étudiant + consignes rendues anti-XSS
    (LessonItem::renderRichText). Confirmations/saisies inline, zéro popup natif.
--}}
<div>
    @if (session()->has('academy_essays_status'))
        <div role="status" class="mb-3 p-3 rounded"
             style="background: #DCFCE7; border: 1px solid #86EFAC; color: #166534; font-size: 0.9rem;">
            {{ session('academy_essays_status') }}
        </div>
    @endif

    @php($pending = $this->pendingAttempts)

    @if ($pending->isEmpty())
        <p class="text-muted" style="font-size: 0.92rem;">
            Aucun essai en attente de correction pour ce cours.
        </p>
    @else
        <p class="text-muted" style="font-size: 0.92rem;">
            {{ $pending->count() }} {{ $pending->count() > 1 ? 'tentatives sont' : 'tentative est' }} en attente de correction.
        </p>

        <ul style="list-style: none; padding: 0; margin: 0;">
            @foreach ($pending as $attempt)
                <li wire:key="pending-{{ $attempt->id }}"
                    class="mb-2 p-3 rounded d-flex flex-wrap align-items-center justify-content-between gap-2"
                    style="background: #FFFBEB; border: 1px solid #FDE68A;">
                    <span style="font-size: 0.92rem; color: #1A1D23;">
                        <strong>{{ $attempt->user?->name ?? 'Étudiant' }}</strong>
                        <span class="text-muted">·</span>
                        {{ $attempt->lessonItem?->title ?? 'Quiz' }}
                        <span class="text-muted">·</span>
                        remis le {{ optional($attempt->submitted_at)->timezone('America/Toronto')?->format('Y-m-d H:i') }}
                    </span>
                    <button type="button" wire:click="startGrading({{ $attempt->id }})"
                            class="btn ct-btn ct-btn-primary btn-sm">
                        Corriger
                    </button>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- ── Panneau de correction d'une tentative ── --}}
    @php($attempt = $this->gradedAttempt)
    @if ($attempt !== null)
        @php($essays = $this->gradingEssays)
        <div class="mt-3 p-3 rounded" style="background: #fff; border: 1px solid #E2E8F0;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h3 style="font-family: var(--f-heading); font-size: 1.05rem; margin: 0; color: #1A1D23;">
                    Correction : {{ $attempt->user?->name ?? 'Étudiant' }}
                </h3>
                <button type="button" wire:click="cancelGrading" class="btn btn-outline-secondary btn-sm">
                    Fermer
                </button>
            </div>

            <form wire:submit.prevent="gradeAttempt">
                @foreach ($essays as $essay)
                    @php($idx = $essay['index'])
                    <fieldset wire:key="essay-{{ $attempt->id }}-{{ $idx }}"
                              class="mb-3 p-3 rounded" style="border: 1px solid #E2E8F0;">
                        <legend style="font-size: 0.95rem; font-weight: 600; color: #1A1D23; float: none; width: auto; padding: 0 6px;">
                            Essai {{ $idx + 1 }}
                            <span class="text-muted" style="font-weight: 400;">(sur {{ $essay['max'] }} {{ $essay['max'] >= 2 ? 'points' : 'point' }})</span>
                        </legend>

                        {{-- Énoncé --}}
                        <p class="mb-2" style="font-size: 0.92rem; color: #1A1D23;">
                            <span style="font-weight: 600;">Énoncé :</span>
                            {!! \Modules\Academy\Models\LessonItem::renderRichText($essay['prompt']) !!}
                        </p>

                        {{-- Consignes de correction (visibles SEULEMENT au formateur) --}}
                        @if (trim($essay['grader_info']) !== '')
                            <div class="mb-2 p-2 rounded" style="background: #F0F9FF; border: 1px solid #BAE6FD; font-size: 0.85rem;">
                                <span style="font-weight: 600;">Consignes de correction :</span>
                                {!! \Modules\Academy\Models\LessonItem::renderRichText($essay['grader_info']) !!}
                            </div>
                        @endif

                        {{-- Réponse de l'étudiant (rendu anti-XSS) --}}
                        <div class="mb-2 p-2 rounded" style="background: #F8FAFC; border: 1px solid #E2E8F0; font-size: 0.9rem;">
                            <span style="font-weight: 600;">Réponse de l'étudiant :</span>
                            @if (trim($essay['answer']) !== '')
                                <div class="mt-1">{!! \Modules\Academy\Models\LessonItem::renderRichText($essay['answer']) !!}</div>
                            @else
                                <span class="text-muted">Aucune réponse rédigée.</span>
                            @endif
                        </div>

                        {{-- Note (bornée serveur [0..max]) --}}
                        <div class="mb-2">
                            <label for="essay-score-{{ $idx }}" style="font-size: 0.85rem; font-weight: 600;">
                                Note (0 à {{ $essay['max'] }})
                            </label>
                            <input type="number" id="essay-score-{{ $idx }}"
                                   wire:model="essayScores.{{ $idx }}"
                                   min="0" max="{{ $essay['max'] }}" step="1" inputmode="numeric"
                                   class="form-control form-control-sm" style="max-width: 140px;">
                            @error("essayScores.{$idx}")
                                <span role="alert" style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Feedback (optionnel) --}}
                        <div>
                            <label for="essay-feedback-{{ $idx }}" style="font-size: 0.85rem; font-weight: 600;">
                                Rétroaction (facultatif)
                            </label>
                            <textarea id="essay-feedback-{{ $idx }}"
                                      wire:model="essayFeedback.{{ $idx }}"
                                      rows="2" class="form-control form-control-sm"
                                      maxlength="20000"
                                      placeholder="Commentaire pour l'étudiant…"></textarea>
                            @error("essayFeedback.{$idx}")
                                <span role="alert" style="color: var(--sys-action-danger, #DC2626); font-size: 0.82rem;">{{ $message }}</span>
                            @enderror
                        </div>
                    </fieldset>
                @endforeach

                <button type="submit" class="btn ct-btn ct-btn-primary">
                    Enregistrer la correction
                </button>
            </form>
        </div>
    @endif
</div>

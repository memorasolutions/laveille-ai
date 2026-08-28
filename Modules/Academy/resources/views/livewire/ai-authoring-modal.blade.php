{{-- Author: MEMORA solutions <info@memora.ca> (https://memora.solutions) --}}
{{-- Composant Livewire : Authoring IA — génère plan de cours et questions quiz en brouillon. --}}
{{-- Jamais de publication automatique : le formateur valide avant chaque écriture.          --}}

<div class="academy-ai-authoring">

    {{-- ─── Section : Générer un plan de cours ─────────────────────────────────── --}}
    <section class="mb-5">
        <h3 class="mb-3" style="color: var(--sys-action-primary, #064E5A);">
            ✨ Générer un plan de cours
        </h3>

        <div class="mb-3">
            <label for="ai-outline-prompt" class="form-label fw-medium">
                Décris le cours à créer <span aria-hidden="true" class="text-danger">*</span>
            </label>
            <textarea
                id="ai-outline-prompt"
                wire:model.live.blur="outlinePrompt"
                class="form-control"
                rows="3"
                placeholder="Ex. : Introduction à l'intelligence artificielle pour les gestionnaires québécois…"
                aria-describedby="ai-outline-hint"
            ></textarea>
            <div id="ai-outline-hint" class="form-text">
                Sois précis (public cible, niveau, thèmes). L'IA générera un plan avec chapitres et leçons.
            </div>
            @error('outlinePrompt')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button
            type="button"
            wire:click="generateOutline"
            wire:loading.attr="disabled"
            wire:target="generateOutline"
            class="btn btn-primary d-inline-flex align-items-center gap-2"
            style="background-color: var(--sys-action-primary, #064E5A); border-color: var(--sys-action-primary, #064E5A);"
        >
            <span wire:loading.remove wire:target="generateOutline">✨ Générer un plan</span>
            <span wire:loading wire:target="generateOutline" aria-live="polite">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Génération en cours…
            </span>
        </button>

        {{-- Aperçu du plan généré (avant insertion) --}}
        @if (!empty($generatedOutline['chapters']))
            <div class="mt-4 p-3 border rounded-2 bg-light" aria-label="Aperçu du plan généré">
                <p class="fw-semibold mb-2">Aperçu du plan proposé :</p>
                <ol class="mb-2">
                    @foreach ($generatedOutline['chapters'] as $chIdx => $chapter)
                        <li class="mb-2">
                            <strong>{{ $chapter['title'] ?? '' }}</strong>
                            @if (!empty($chapter['lessons']))
                                <ul class="mt-1">
                                    @foreach ($chapter['lessons'] as $lesson)
                                        <li>
                                            {{ $lesson['title'] ?? '' }}
                                            @if (!empty($lesson['objective']))
                                                <span class="text-muted small"> – {{ $lesson['objective'] }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ol>

                <button
                    type="button"
                    wire:click="confirmInsertOutline"
                    wire:loading.attr="disabled"
                    wire:target="confirmInsertOutline"
                    class="btn btn-success btn-sm d-inline-flex align-items-center gap-2"
                >
                    <span wire:loading.remove wire:target="confirmInsertOutline">Insérer en brouillon</span>
                    <span wire:loading wire:target="confirmInsertOutline">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Insertion…
                    </span>
                </button>
            </div>
        @endif

        @if ($outlineInserted)
            <div class="alert alert-success mt-3 py-2" role="alert">
                Plan inséré en brouillon – visible et éditable dans la structure du cours ci-dessous.
            </div>
        @endif
    </section>

    <hr class="my-4">

    {{-- ─── Section : Générer des questions de quiz ─────────────────────────────── --}}
    <section class="mb-5">
        <h3 class="mb-3" style="color: var(--sys-action-primary, #064E5A);">
            ✨ Générer des questions de quiz
        </h3>

        <div class="mb-3">
            <label for="ai-questions-prompt" class="form-label fw-medium">
                Sujet ou contenu de la leçon <span aria-hidden="true" class="text-danger">*</span>
            </label>
            <textarea
                id="ai-questions-prompt"
                wire:model.live.blur="questionsPrompt"
                class="form-control"
                rows="3"
                placeholder="Ex. : Les 5 étapes de la transformation numérique selon le cadre québécois…"
            ></textarea>
            @error('questionsPrompt')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3" style="max-width: 12rem;">
            <label for="ai-questions-count" class="form-label fw-medium">Nombre de questions</label>
            <input
                type="number"
                id="ai-questions-count"
                wire:model.live.blur="questionsCount"
                class="form-control"
                min="1"
                max="20"
            >
            @error('questionsCount')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button
            type="button"
            wire:click="generateQuestions"
            wire:loading.attr="disabled"
            wire:target="generateQuestions"
            class="btn btn-primary d-inline-flex align-items-center gap-2"
            style="background-color: var(--sys-action-primary, #064E5A); border-color: var(--sys-action-primary, #064E5A);"
        >
            <span wire:loading.remove wire:target="generateQuestions">✨ Générer des questions</span>
            <span wire:loading wire:target="generateQuestions" aria-live="polite">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Génération en cours…
            </span>
        </button>

        {{-- Aperçu des questions générées (avant insertion) --}}
        @if (!empty($generatedQuestions))
            <div class="mt-4 p-3 border rounded-2 bg-light" aria-label="Aperçu des questions générées">
                <p class="fw-semibold mb-2">{{ count($generatedQuestions) }} question(s) proposée(s) :</p>
                <ol class="mb-2">
                    @foreach ($generatedQuestions as $q)
                        <li class="mb-3">
                            <p class="mb-1 fw-medium">{{ $q['prompt'] ?? '' }}</p>
                            @if (!empty($q['options']))
                                <ul class="list-unstyled ps-3">
                                    @foreach ($q['options'] as $oIdx => $option)
                                        <li class="{{ ($oIdx === ($q['correct_index'] ?? -1)) ? 'text-success fw-semibold' : '' }}">
                                            {{ chr(65 + $oIdx) }}. {{ $option }}
                                            @if ($oIdx === ($q['correct_index'] ?? -1))
                                                <span aria-label="bonne réponse"> ✓</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @if (!empty($q['explanation']))
                                <p class="text-muted small mb-0 mt-1">
                                    <em>Explication :</em> {{ $q['explanation'] }}
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ol>

                <button
                    type="button"
                    wire:click="confirmInsertQuestions"
                    wire:loading.attr="disabled"
                    wire:target="confirmInsertQuestions"
                    class="btn btn-success btn-sm d-inline-flex align-items-center gap-2"
                >
                    <span wire:loading.remove wire:target="confirmInsertQuestions">Insérer en brouillon</span>
                    <span wire:loading wire:target="confirmInsertQuestions">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Insertion…
                    </span>
                </button>
            </div>
        @endif

        @if ($questionsInserted)
            <div class="alert alert-success mt-3 py-2" role="alert">
                Questions insérées en brouillon (is_active=false) dans la catégorie « IA – À réviser ». À relire avant activation.
            </div>
        @endif
    </section>

    {{-- ─── Zone d'erreur globale ──────────────────────────────────────────────── --}}
    @if ($errorMessage)
        <div class="alert alert-danger mt-3" role="alert" aria-live="assertive">
            {{ $errorMessage }}
        </div>
    @endif

</div>

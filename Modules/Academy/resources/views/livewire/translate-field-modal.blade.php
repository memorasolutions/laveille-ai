{{-- Author: MEMORA solutions <info@memora.ca> (https://memora.solutions) --}}
{{-- Composant Livewire : Traduction IA — traduit un texte du cours en aperçu éditable.       --}}
{{-- AUCUNE écriture automatique en base (Course/Lesson/Chapter ne sont pas Translatable ici) --}}
{{-- le formateur relit, VALIDE l'aperçu, puis copie le texte où il en a besoin.               --}}

<div class="academy-ai-translation">

    <div class="mb-3">
        <label for="translate-source-text" class="form-label fw-medium">
            Texte à traduire <span aria-hidden="true" class="text-danger">*</span>
        </label>
        <textarea
            id="translate-source-text"
            wire:model.live.blur="sourceText"
            class="form-control"
            rows="4"
            placeholder="Colle ici le texte du cours à traduire (description, résumé, contenu…)"
            aria-describedby="translate-source-hint"
        ></textarea>
        <div id="translate-source-hint" class="form-text">
            Aucune donnée du cours n'est modifiée automatiquement : le résultat est un brouillon à copier toi-même.
        </div>
        @error('sourceText')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="row g-3 mb-3">
        <div class="col-sm-6">
            <label for="translate-source-locale" class="form-label fw-medium">Langue source</label>
            <select id="translate-source-locale" wire:model.live="sourceLocale" class="form-select">
                <option value="fr">Français</option>
                <option value="en">Anglais</option>
            </select>
            @error('sourceLocale')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-sm-6">
            <label for="translate-target-locale" class="form-label fw-medium">Langue cible</label>
            <select id="translate-target-locale" wire:model.live="targetLocale" class="form-select">
                <option value="en">Anglais</option>
                <option value="fr">Français</option>
            </select>
            @error('targetLocale')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <button
        type="button"
        wire:click="translate"
        wire:loading.attr="disabled"
        wire:target="translate"
        class="btn btn-primary d-inline-flex align-items-center gap-2"
        style="background-color: var(--sys-action-primary, #064E5A); border-color: var(--sys-action-primary, #064E5A);"
    >
        <span wire:loading.remove wire:target="translate">🌐 Traduire en {{ $targetLocale === 'en' ? 'anglais' : 'français' }}</span>
        <span wire:loading wire:target="translate" aria-live="polite">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Traduction en cours…
        </span>
    </button>

    {{-- Aperçu ÉDITABLE de la traduction (avant confirmation) --}}
    @if ($translatedText !== '')
        <div class="mt-4 p-3 border rounded-2 bg-light" aria-label="Aperçu de la traduction">
            <label for="translate-preview" class="form-label fw-semibold">
                Aperçu (modifiable avant de confirmer) :
            </label>
            <textarea
                id="translate-preview"
                wire:model.live.blur="translatedText"
                class="form-control"
                rows="4"
            ></textarea>

            <div class="mt-3 d-flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="confirmTranslation"
                    wire:loading.attr="disabled"
                    wire:target="confirmTranslation"
                    class="btn btn-success btn-sm d-inline-flex align-items-center gap-2"
                >
                    ✅ Valider cet aperçu
                </button>
                <button
                    type="button"
                    wire:click="resetTranslation"
                    class="btn btn-outline-secondary btn-sm"
                >
                    Recommencer
                </button>
            </div>

            @if ($isConfirmed)
                <div class="alert alert-success mt-3 py-2 mb-0" role="alert">
                    Aperçu validé – copie ce texte où tu en as besoin (aucune sauvegarde automatique n'est effectuée : ce cours n'a pas de champ multilingue).
                </div>
            @endif
        </div>
    @endif

    {{-- ─── Zone d'erreur ───────────────────────────────────────────────────────── --}}
    @if ($errorMessage)
        <div class="alert alert-danger mt-3" role="alert" aria-live="assertive">
            {{ $errorMessage }}
        </div>
    @endif

</div>

<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    <div class="d-flex flex-column gap-4" style="max-width: 680px;">

        {{-- Message d'erreur global (fichier invalide / non reconnu). Aucune popup
             native : affichage inline accessible (role=alert). --}}
        @if($importError)
            <div role="alert"
                 style="border: 1px solid #FCA5A5; background: #FEF2F2; color: #991B1B; padding: 12px 14px; border-radius: var(--sys-radius-md, 0.5rem);">
                {{ $importError }}
            </div>
        @endif

        <div>
            <label for="course-moodle-import-file"
                   style="display: block; font-weight: 600; color: var(--sys-text-default, #1A1D23); margin-bottom: 6px;">
                Sauvegarde Moodle (.mbz) <span aria-hidden="true" style="color: #B91C1C;">*</span>
                <span class="visually-hidden">(obligatoire)</span>
            </label>
            <input type="file"
                   id="course-moodle-import-file"
                   accept=".mbz,.zip,application/zip"
                   wire:model="mbzFile"
                   @error('mbzFile') aria-invalid="true" aria-describedby="course-moodle-import-file-error" @enderror
                   style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 1rem;">
            @error('mbzFile')
                <p id="course-moodle-import-file-error" role="alert" style="color: #B91C1C; font-size: 0.85rem; margin: 6px 0 0;">{{ $message }}</p>
            @enderror
            <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 6px 0 0;">
                Taille maximale : {{ (int) (config('academy.moodle_import.max_kb', 204800) / 1024) }} Mo. Seules les
                sections et les activités simples (page, fichier/ressource, étiquette) sont importées ; les autres
                activités (quiz, devoirs, forums, SCORM, H5P...) sont ignorées et listées dans le résumé.
            </p>

            <div wire:loading wire:target="import" style="margin-top: 10px; font-size: 0.9rem; color: var(--sys-text-muted, #6B7280);">
                Import en cours (cela peut prendre un moment pour une grosse sauvegarde)...
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <x-core::button type="button" variant="primary" wire:click="import" wire:loading.attr="disabled" wire:target="import">
                <span wire:loading.remove wire:target="import">Importer ce cours Moodle</span>
                <span wire:loading wire:target="import">Import en cours...</span>
            </x-core::button>
        </div>

    </div>
</div>

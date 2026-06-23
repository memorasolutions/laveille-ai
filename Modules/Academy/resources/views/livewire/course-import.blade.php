<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    <div class="d-flex flex-column gap-4" style="max-width: 680px;">

        {{-- Message d'erreur global (fichier invalide / non pris en charge). Aucune
             popup native : affichage inline accessible (role=alert). --}}
        @if($importError)
            <div role="alert"
                 style="border: 1px solid #FCA5A5; background: #FEF2F2; color: #991B1B; padding: 12px 14px; border-radius: var(--sys-radius-md, 0.5rem);">
                {{ $importError }}
            </div>
        @endif

        @if($preview === null)
            {{-- Étape 1 : choix du fichier --}}
            <div>
                <label for="course-import-file"
                       style="display: block; font-weight: 600; color: var(--sys-text-default, #1A1D23); margin-bottom: 6px;">
                    Fichier de sauvegarde (.json) <span aria-hidden="true" style="color: #B91C1C;">*</span>
                    <span class="visually-hidden">(obligatoire)</span>
                </label>
                <input type="file"
                       id="course-import-file"
                       accept=".json,application/json"
                       wire:model="backupFile"
                       @error('backupFile') aria-invalid="true" aria-describedby="course-import-file-error" @enderror
                       style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 1rem;">
                @error('backupFile')
                    <p id="course-import-file-error" role="alert" style="color: #B91C1C; font-size: 0.85rem; margin: 6px 0 0;">{{ $message }}</p>
                @enderror
                <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 6px 0 0;">
                    Taille maximale : 5 Mo. Le fichier doit provenir d'un export de l'Académie.
                </p>

                <div wire:loading wire:target="backupFile" style="margin-top: 10px; font-size: 0.9rem; color: var(--sys-text-muted, #6B7280);">
                    Analyse du fichier en cours...
                </div>
            </div>
        @else
            {{-- Étape 2 : aperçu + confirmation (inline, sans popup native) --}}
            <div style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 18px 20px; background: #F9FAFB;">
                <h2 style="font-family: var(--f-heading); font-size: 1.15rem; color: var(--sys-text-default, #1A1D23); margin: 0 0 10px;">
                    Aperçu de la sauvegarde
                </h2>

                <p style="margin: 0 0 12px;">
                    <strong>{{ $preview['title'] }}</strong>
                </p>

                <ul style="list-style: none; padding: 0; margin: 0 0 4px; display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 8px 16px;">
                    <li><span aria-hidden="true">📚</span> {{ $preview['chapters'] }} chapitre(s)</li>
                    <li><span aria-hidden="true">📄</span> {{ $preview['lessons'] }} leçon(s)</li>
                    <li><span aria-hidden="true">🧩</span> {{ $preview['items'] }} contenu(s)</li>
                    <li><span aria-hidden="true">📝</span> {{ $preview['assignments'] }} devoir(s)</li>
                    <li><span aria-hidden="true">🎯</span> {{ $preview['grade_items'] }} élément(s) de note</li>
                    <li><span aria-hidden="true">❓</span> {{ $preview['bank_questions'] }} question(s) de banque</li>
                </ul>

                <p style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280); margin: 10px 0 0;">
                    Format {{ $preview['format_version'] }}@if($preview['exported_at']) - exporté le {{ $preview['exported_at'] }}@endif.
                    Le cours sera créé en brouillon ; aucune donnée d'étudiant n'est importée.
                </p>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <x-core::button type="button" variant="primary" wire:click="import" wire:loading.attr="disabled" wire:target="import">
                    <span wire:loading.remove wire:target="import">Importer ce cours</span>
                    <span wire:loading wire:target="import">Import en cours...</span>
                </x-core::button>
                <x-core::button type="button" variant="ghost" wire:click="cancelPreview">
                    Choisir un autre fichier
                </x-core::button>
            </div>
        @endif

    </div>
</div>

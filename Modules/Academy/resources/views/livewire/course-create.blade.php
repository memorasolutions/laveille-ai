<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    <form wire:submit="create" novalidate>
        <div class="d-flex flex-column gap-4" style="max-width: 640px;">

            {{-- Titre (requis) --}}
            <div>
                <label for="course-create-title"
                       style="display: block; font-weight: 600; color: var(--sys-text-default, #1A1D23); margin-bottom: 6px;">
                    Titre du cours <span aria-hidden="true" style="color: #B91C1C;">*</span>
                    <span class="visually-hidden">(obligatoire)</span>
                </label>
                <input type="text"
                       id="course-create-title"
                       wire:model="title"
                       autocomplete="off"
                       required
                       aria-required="true"
                       @error('title') aria-invalid="true" aria-describedby="course-create-title-error" @enderror
                       style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 1rem;">
                @error('title')
                    <p id="course-create-title-error" role="alert" style="color: #B91C1C; font-size: 0.85rem; margin: 6px 0 0;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Niveau --}}
            <div>
                <label for="course-create-level"
                       style="display: block; font-weight: 600; color: var(--sys-text-default, #1A1D23); margin-bottom: 6px;">
                    Niveau
                </label>
                <select id="course-create-level"
                        wire:model="level"
                        style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 1rem;">
                    <option value="intro">Débutant</option>
                    <option value="inter">Intermédiaire</option>
                    <option value="avance">Avancé</option>
                </select>
            </div>

            {{-- Langue --}}
            <div>
                <label for="course-create-language"
                       style="display: block; font-weight: 600; color: var(--sys-text-default, #1A1D23); margin-bottom: 6px;">
                    Langue
                </label>
                <select id="course-create-language"
                        wire:model="language"
                        style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 1rem;">
                    <option value="fr-CA">Français (Canada)</option>
                    <option value="en-CA">Anglais (Canada)</option>
                </select>
            </div>

            {{-- Visibilité --}}
            <div>
                <label for="course-create-visibility"
                       style="display: block; font-weight: 600; color: var(--sys-text-default, #1A1D23); margin-bottom: 6px;">
                    Visibilité
                </label>
                <select id="course-create-visibility"
                        wire:model="visibility"
                        aria-describedby="course-create-visibility-help"
                        style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 1rem;">
                    <option value="private">Privé</option>
                    <option value="unlisted">Non répertorié</option>
                    <option value="public">Public</option>
                </select>
                <p id="course-create-visibility-help" style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 6px 0 0;">
                    Le cours démarre en brouillon privé. Vous le publierez depuis l'éditeur quand il sera prêt.
                </p>
            </div>

            {{-- Type d'accès --}}
            <div>
                <label for="course-create-access"
                       style="display: block; font-weight: 600; color: var(--sys-text-default, #1A1D23); margin-bottom: 6px;">
                    Type d'accès
                </label>
                <select id="course-create-access"
                        wire:model="access_type"
                        aria-describedby="course-create-access-help"
                        style="width: 100%; padding: 10px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-size: 1rem;">
                    <option value="free">Gratuit</option>
                    <option value="paid_one_time">Payant (achat unique)</option>
                    <option value="paid_subscription">Payant (abonnement)</option>
                </select>
                <p id="course-create-access-help" style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 6px 0 0;">
                    Pour un cours payant, le prix se règle ensuite dans l'éditeur.
                </p>
            </div>

            {{-- Actions --}}
            <div class="d-flex flex-wrap align-items-center gap-2" style="margin-top: 8px;">
                <x-core::button type="submit" variant="primary">
                    Créer le cours
                </x-core::button>
                <x-core::button :href="route('academy.dashboard')" variant="ghost">
                    Annuler
                </x-core::button>
            </div>

        </div>
    </form>
</div>

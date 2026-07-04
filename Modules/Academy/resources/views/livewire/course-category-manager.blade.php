{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Gestion des catégories de cours - Vague 4 (CourseCategoryManager Livewire).
    Réservé à academy.manage. Confirmation inline à 2 temps (jamais confirm() natif).
--}}
<div style="font-family: var(--f-body, system-ui); color: var(--sys-text-default, #1A1D23);">

    {{-- Flash statut --}}
    @if(session('academy_categories_status'))
        <div role="status" aria-live="polite"
             style="border: 1px solid #BBF7D0; background: #F0FDF4; color: #166534;
                    border-radius: var(--sys-radius-md, 0.75rem); padding: 12px 16px;
                    margin-bottom: 18px; font-size: 0.9rem;">
            {{ session('academy_categories_status') }}
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3" style="margin-bottom: 20px;">
        <h2 style="font-family: var(--f-heading); font-size: 1.35rem;
                   color: var(--sys-text-default, #1A1D23); margin: 0;">
            Catégories de cours
        </h2>

        @if(!$showForm)
            <x-core::button type="button" variant="primary" size="sm" wire:click="create">
                Ajouter une catégorie
            </x-core::button>
        @endif
    </div>

    {{-- Formulaire de création/édition --}}
    @if($showForm)
        <div role="region" aria-labelledby="cat-form-title"
             style="border: 1px solid #D1FAE5; border-radius: var(--sys-radius-md, 0.75rem);
                    padding: 20px 22px; margin-bottom: 24px; background: #F0FDF4;">

            <h3 id="cat-form-title"
                style="font-family: var(--f-heading); font-size: 1rem;
                       color: var(--sys-text-default, #1A1D23); margin: 0 0 16px;">
                {{ $editingId ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}
            </h3>

            <div class="d-flex flex-wrap gap-4" style="margin-bottom: 14px;">
                <div style="flex: 2 1 220px;">
                    <label for="cat-name"
                           style="display: block; font-size: 0.88rem; font-weight: 600;
                                  color: var(--sys-text-default, #374151); margin-bottom: 4px;">
                        Nom <span aria-hidden="true" style="color:#B91C1C;">*</span>
                    </label>
                    <input id="cat-name" type="text" wire:model.blur="name"
                           maxlength="120" required
                           style="width: 100%; border: 1px solid #D1D5DB;
                                  border-radius: var(--sys-radius-sm, 0.5rem);
                                  padding: 8px 12px; font-size: 0.92rem; color: #1A1D23;"
                           placeholder="Ex. : Développement web">
                    @error('name')
                        <p role="alert" style="font-size: 0.8rem; color: #B91C1C; margin: 4px 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="flex: 1 1 140px;">
                    <label for="cat-color"
                           style="display: block; font-size: 0.88rem; font-weight: 600;
                                  color: var(--sys-text-default, #374151); margin-bottom: 4px;">
                        Couleur (optionnel)
                    </label>
                    <input id="cat-color" type="text" wire:model.blur="color"
                           maxlength="7" placeholder="#064E5A"
                           style="width: 100%; border: 1px solid #D1D5DB;
                                  border-radius: var(--sys-radius-sm, 0.5rem);
                                  padding: 8px 12px; font-size: 0.92rem; color: #1A1D23;">
                    @error('color')
                        <p role="alert" style="font-size: 0.8rem; color: #B91C1C; margin: 4px 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="flex: 1 1 100px;">
                    <label for="cat-icon"
                           style="display: block; font-size: 0.88rem; font-weight: 600;
                                  color: var(--sys-text-default, #374151); margin-bottom: 4px;">
                        Icône (optionnel)
                    </label>
                    <input id="cat-icon" type="text" wire:model.blur="icon"
                           maxlength="10" placeholder="💻"
                           style="width: 100%; border: 1px solid #D1D5DB;
                                  border-radius: var(--sys-radius-sm, 0.5rem);
                                  padding: 8px 12px; font-size: 0.92rem; color: #1A1D23;">
                    @error('icon')
                        <p role="alert" style="font-size: 0.8rem; color: #B91C1C; margin: 4px 0 0;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <x-core::button type="button" variant="primary" size="sm"
                                wire:click="save" wire:loading.attr="disabled" wire:target="save">
                    Enregistrer
                </x-core::button>
                <x-core::button type="button" variant="ghost" size="sm" wire:click="cancelForm">
                    Annuler
                </x-core::button>
            </div>
        </div>
    @endif

    {{-- Liste des catégories --}}
    @if($this->categories->isNotEmpty())
        <ul class="list-unstyled d-flex flex-column gap-2" role="list" style="margin: 0;">
            @foreach($this->categories as $cat)
                <li wire:key="cat-{{ $cat->id }}" role="listitem"
                    style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem);
                           padding: 12px 16px; background: #FFFFFF;
                           display: flex; flex-wrap: wrap; align-items: center;
                           justify-content: space-between; gap: 12px;">

                    <div class="d-flex align-items-center gap-2" style="min-width: 200px;">
                        <span aria-hidden="true"
                              style="display: inline-block; width: 14px; height: 14px;
                                     border-radius: 999px; background: {{ $cat->color ?: '#9CA3AF' }};">
                        </span>
                        @if($cat->icon)
                            <span aria-hidden="true">{{ $cat->icon }}</span>
                        @endif
                        <strong>{{ $cat->name }}</strong>
                        <span style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280);">
                            ({{ $cat->courses_count }} cours)
                        </span>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" wire:click="moveUp({{ $cat->id }})"
                                aria-label="Monter « {{ $cat->name }} »"
                                style="min-width: 36px; min-height: 36px; border: 1px solid #D1D5DB;
                                       border-radius: var(--sys-radius-sm, 0.5rem); background: #fff; cursor: pointer;">
                            ↑
                        </button>
                        <button type="button" wire:click="moveDown({{ $cat->id }})"
                                aria-label="Descendre « {{ $cat->name }} »"
                                style="min-width: 36px; min-height: 36px; border: 1px solid #D1D5DB;
                                       border-radius: var(--sys-radius-sm, 0.5rem); background: #fff; cursor: pointer;">
                            ↓
                        </button>

                        <x-core::button type="button" variant="secondary" size="sm"
                                        wire:click="edit({{ $cat->id }})">
                            Modifier
                        </x-core::button>

                        @if($confirmingDeleteId === $cat->id)
                            <span style="font-size: 0.82rem; color: var(--sys-text-muted, #6B7280);">
                                Supprimer ?
                                @if($cat->courses_count > 0)
                                    ({{ $cat->courses_count }} cours redeviendront sans catégorie)
                                @endif
                            </span>
                            <x-core::button type="button" variant="primary" size="sm"
                                            wire:click="remove({{ $cat->id }})"
                                            wire:loading.attr="disabled" wire:target="remove">
                                Oui, supprimer
                            </x-core::button>
                            <x-core::button type="button" variant="ghost" size="sm" wire:click="cancelDelete">
                                Annuler
                            </x-core::button>
                        @else
                            <x-core::button type="button" variant="ghost" size="sm"
                                            wire:click="confirmDelete({{ $cat->id }})">
                                Supprimer
                            </x-core::button>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <div style="border: 1px dashed #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem);
                    padding: 28px; text-align: center;">
            <p style="color: var(--sys-text-muted, #6B7280); margin: 0;">
                Aucune catégorie pour le moment. Utilisez le bouton « Ajouter une catégorie » pour en créer une.
            </p>
        </div>
    @endif
</div>

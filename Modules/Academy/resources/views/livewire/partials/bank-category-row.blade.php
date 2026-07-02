{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Ligne d'une catégorie dans l'arbre de la banque (sélection + renommage inline +
    suppression à 2 temps). $category est une QuestionCategory avec questions_count
    et children_count chargés. Toutes les actions sont gardées serveur.
--}}
@php($isSelected = $selectedCategoryId === $category->id)

@if ($renamingCategory === $category->id)
    {{-- Mode renommage inline --}}
    <form wire:submit="renameCategory" style="display: flex; align-items: center; gap: 6px;">
        <label class="visually-hidden" for="rename-cat-{{ $category->id }}">Nouveau nom de la catégorie</label>
        <input id="rename-cat-{{ $category->id }}" type="text" wire:model="renameCategoryName" maxlength="160"
               style="flex: 1; padding: 6px 10px; min-height: 36px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem);">
        <x-core::button type="submit" variant="primary" size="sm">OK</x-core::button>
        <x-core::button type="button" wire:click="cancelRenameCategory" variant="ghost" size="sm">Annuler</x-core::button>
    </form>
    @error('renameCategoryName') <span role="alert" style="color: var(--sys-action-danger, #DC2626); font-size: 0.8rem;">{{ $message }}</span> @enderror
@else
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;
                padding: 6px 8px; border-radius: var(--sys-radius-md, 0.5rem);
                background: {{ $isSelected ? '#ECFEFF' : 'transparent' }};">
        <button type="button" wire:click="selectCategory({{ $category->id }})"
                @if ($isSelected) aria-current="true" @endif
                style="flex: 1; min-height: 36px; text-align: left; background: none; border: none; cursor: pointer;
                       font-weight: {{ $isSelected ? 700 : 600 }}; font-size: 0.9rem;
                       color: {{ $isSelected ? 'var(--sys-action-primary, #064E5A)' : 'var(--sys-text-default, #1A1D23)' }};">
            {{ $category->name }}
            <span style="font-weight: 400; font-size: 0.78rem; color: var(--sys-text-muted, #6B7280);">
                ({{ $category->questions_count }} question{{ $category->questions_count > 1 ? 's' : '' }})
            </span>
        </button>

        <span style="display: inline-flex; gap: 4px; flex: 0 0 auto;">
            @if ($confirmingCategoryDeletion === $category->id)
                <x-core::button type="button" wire:click="deleteCategory({{ $category->id }})" variant="danger" size="sm">Confirmer</x-core::button>
                <x-core::button type="button" wire:click="cancelCategoryDeletion" variant="ghost" size="sm">Annuler</x-core::button>
            @else
                @include('core::components.admin-action-menu', ['actions' => [
                    ['label' => 'Renommer', 'icon' => 'pencil', 'wireClick' => "startRenameCategory({$category->id})"],
                    ['label' => 'Supprimer', 'icon' => 'trash-2', 'wireClick' => "confirmCategoryDeletion({$category->id})", 'danger' => true],
                ]])
            @endif
        </span>
    </div>
@endif

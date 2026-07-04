@once
@push('styles')
<style>
    .nw-tool-opt {
        display: block !important;
        width: 100% !important;
        text-align: left !important;
        padding: 9px 14px !important;
        margin: 0 !important;
        background: transparent !important;
        border: none !important;
        border-bottom: 1px solid #eef2f7 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        font-size: 0.875rem !important;
        color: #111827 !important;
        font-weight: 400 !important;
    }
    .nw-tool-opt:not(:disabled):hover { background: #ecfeff !important; }
    li:last-child > .nw-tool-opt { border-bottom: none !important; }
    .nw-tool-results { list-style: none !important; padding: 0 !important; margin: 0 0 12px !important; border: 1px solid #e5e7eb !important; border-radius: 6px !important; max-height: 220px !important; overflow-y: auto !important; background: #fff !important; }
    .nw-tool-results li { margin: 0 !important; padding: 0 !important; }
</style>
@endpush
@endonce
<div
    x-data="{
        search: '',
        get filteredTools() {
            if (this.search.trim() === '') return [];
            const q = this.search.trim().toLowerCase();
            return $wire.allTools
                .filter(t => t.name.toLowerCase().includes(q))
                .slice(0, 30);
        },
        isSelected(id) {
            return $wire.selectedToolIds.includes(id);
        }
    }"
    style="padding: 4px 16px 16px;"
>

    {{-- Message de statut --}}
    @if(session('news_tools_editor_status'))
        <div role="status" aria-live="polite" style="padding: 8px 12px; background: #d1fae5; border: 1px solid #6ee7b7; border-radius: 6px; font-size: 0.875rem; color: #065f46; margin-bottom: 12px;">
            {{ session('news_tools_editor_status') }}
        </div>
    @endif

    {{-- Pills des outils sélectionnés --}}
    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; min-height: 36px;">
        @forelse($selectedToolIds as $selId)
            @php
                $selTool = collect($allTools)->firstWhere('id', $selId);
            @endphp
            @if($selTool)
                <span class="ct-pill" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #e0f2fe; border: 1px solid #7dd3fc; border-radius: 99px; font-size: 0.82rem; font-weight: 600; color: #0369a1;">
                    {{ $selTool['name'] }}
                    <button
                        type="button"
                        wire:click="removeTool({{ $selId }})"
                        aria-label="{{ __('Retirer') }} {{ $selTool['name'] }}"
                        style="background: none; border: none; cursor: pointer; color: #0369a1; padding: 0; line-height: 1; font-size: 1rem;"
                    >&times;</button>
                </span>
            @endif
        @empty
            <span style="font-size: 0.85rem; color: #6b7280; font-style: italic;">{{ __('Aucun outil lié.') }}</span>
        @endforelse
    </div>

    {{-- Recherche --}}
    <div style="margin-bottom: 10px;">
        <label for="nw-tools-search" style="display: block; font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 4px;">
            {{ __('Ajouter un outil') }}
        </label>
        <input
            id="nw-tools-search"
            type="search"
            x-model="search"
            placeholder="{{ __('Rechercher un outil...') }}"
            autocomplete="off"
            style="width: 100%; padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; outline: none;"
            aria-label="{{ __('Rechercher un outil à ajouter') }}"
        >
    </div>

    {{-- Résultats de recherche (filtrés côté client) --}}
    <ul
        x-show="filteredTools.length > 0"
        style="list-style: none; padding: 0; margin: 0 0 12px; border: 1px solid #e5e7eb; border-radius: 6px; max-height: 200px; overflow-y: auto; background: #fff;"
        role="listbox"
        aria-label="{{ __('Résultats de recherche') }}"
    >
        <template x-for="tool in filteredTools" :key="tool.id">
            <li role="option" :aria-selected="isSelected(tool.id)">
                <button
                    type="button"
                    class="nw-tool-opt"
                    x-on:click="$wire.addTool(tool.id).then(() => { document.getElementById('nw-tools-search')?.focus() })"
                    :disabled="isSelected(tool.id)"
                    :style="{ opacity: isSelected(tool.id) ? '0.5' : '1', cursor: isSelected(tool.id) ? 'not-allowed' : 'pointer' }"
                    style="display: block; width: 100%; text-align: left; padding: 9px 14px; background: transparent; border: none; border-bottom: 1px solid #eef2f7; font-size: 0.875rem; color: #111827;"
                    :aria-label="isSelected(tool.id) ? `${tool.name} (déjà sélectionné)` : `Ajouter ${tool.name}`"
                >
                    <span x-text="tool.name"></span>
                    <span x-show="isSelected(tool.id)" style="margin-left: 6px; color: #6b7280; font-size: 0.75rem;">✓</span>
                </button>
            </li>
        </template>
    </ul>

    {{-- Actions (chaque ajout/retrait s'enregistre déjà immédiatement, aucun bouton "Enregistrer" séparé requis) --}}
    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <button
            type="button"
            wire:click="suggestTools"
            wire:loading.attr="disabled"
            style="padding: 7px 18px; background: #fff; color: var(--c-primary, #064E5A); border: 1.5px solid var(--c-primary, #064E5A); border-radius: 6px; font-size: 0.875rem; font-weight: 600; cursor: pointer;"
            aria-label="{{ __('Suggérer les outils détectés dans le contenu') }}"
        >
            <span wire:loading.remove wire:target="suggestTools">🔍 {{ __('Suggérer les outils détectés') }}</span>
            <span wire:loading wire:target="suggestTools">{{ __('Analyse...') }}</span>
        </button>
    </div>
</div>

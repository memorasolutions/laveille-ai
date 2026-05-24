@props(['authorProfileId'])

<div x-data="{ open: false }"
     @keydown.window.cmd.k.prevent="open = true; setTimeout(() => $refs.searchPanel?.querySelector('input')?.focus(), 50)"
     @keydown.window.ctrl.k.prevent="open = true; setTimeout(() => $refs.searchPanel?.querySelector('input')?.focus(), 50)"
     @keydown.escape.window="open = false"
     class="lv-search-toggle"
     style="position: relative; display: inline-block;">
    <button
        type="button"
        @click="open = !open; if(open) setTimeout(() => $refs.searchPanel?.querySelector('input')?.focus(), 50)"
        :aria-expanded="open"
        aria-label="Rechercher dans les articles (Ctrl+K)"
        title="Rechercher (Ctrl+K)"
        style="min-width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; border:2px solid var(--c-primary,#064E5A); border-radius:50%; background:transparent; color:var(--c-primary,#064E5A); cursor:pointer; transition:background 200ms, color 200ms;"
        onmouseover="this.style.background='var(--c-primary,#064E5A)'; this.style.color='white';"
        onmouseout="this.style.background='transparent'; this.style.color='var(--c-primary,#064E5A)';"
        onfocus="this.style.outline='3px solid var(--c-accent,#9A2A06)'; this.style.outlineOffset='2px';"
        onblur="this.style.outline='none';"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.35-4.35"/>
        </svg>
    </button>

    <div x-show="open"
         x-ref="searchPanel"
         x-transition.opacity.duration.200ms
         @click.outside="open = false"
         role="dialog"
         aria-modal="false"
         aria-label="Recherche d'articles"
         x-cloak
         style="position:absolute; top:calc(100% + 8px); right:0; z-index:50; min-width:320px; background:var(--c-cream,#F8FAFB); padding:1rem; border-radius:0.5rem; box-shadow:0 10px 30px rgba(6,78,90,0.18); border:1px solid rgba(6,78,90,0.12);"
         class="lv-search-panel">
        @if(\Illuminate\Support\Facades\Route::has('home') || class_exists(\Modules\Authors\Livewire\AuthorSearch::class))
            @livewire('authors.author-search', ['authorProfileId' => $authorProfileId])
        @endif
    </div>
</div>

<style>
    @media (max-width: 768px) {
        .lv-search-panel {
            position: fixed !important;
            top: 60px !important;
            left: 0 !important;
            right: 0 !important;
            margin: 0 0.5rem !important;
            min-width: auto !important;
        }
    }
</style>

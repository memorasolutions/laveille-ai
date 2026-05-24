@props(['author'])

<div x-data="{ open: false }"
     @keydown.window.cmd.k.prevent="open=true; $nextTick(() => $refs.searchInput?.focus())"
     @keydown.window.ctrl.k.prevent="open=true; $nextTick(() => $refs.searchInput?.focus())"
     @keydown.escape.window="open=false"
     @click.outside="open=false"
     class="lv-search-toggle"
     style="position:relative; display:inline-block;">
    <button type="button"
            @click="open = !open; if(open) $nextTick(() => $refs.searchInput?.focus())"
            :aria-expanded="open"
            aria-label="Rechercher dans les articles (Ctrl+K)"
            title="Rechercher (Ctrl+K)"
            style="min-width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; border:2px solid var(--c-primary,#064E5A); border-radius:50%; background:transparent; color:var(--c-primary,#064E5A); cursor:pointer; transition:background 200ms, color 200ms;"
            onmouseover="this.style.background='#064E5A'; this.style.color='white';"
            onmouseout="this.style.background='transparent'; this.style.color='#064E5A';"
            onfocus="this.style.outline='3px solid #9A2A06'; this.style.outlineOffset='2px';"
            onblur="this.style.outline='none';">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.35-4.35"/>
        </svg>
    </button>

    <div x-show="open"
         x-cloak
         x-transition.opacity.duration.150ms
         role="search"
         aria-label="Recherche d'articles"
         class="lv-search-panel"
         style="position:absolute; top:calc(100% + 8px); right:0; z-index:50; min-width:320px; background:var(--c-cream,#F8FAFB); padding:1rem; border-radius:10px; box-shadow:0 10px 30px rgba(6,78,90,0.18); border:1px solid rgba(6,78,90,0.12);">
        <form action="/@{{ $author->slug }}" method="GET" role="search" style="display:flex; gap:8px; align-items:stretch;">
            <label for="lv-search-input" class="lv-sr-only">Rechercher dans les articles</label>
            <input id="lv-search-input"
                   x-ref="searchInput"
                   type="search"
                   name="q"
                   autocomplete="off"
                   placeholder="Rechercher..."
                   aria-label="Rechercher dans les articles"
                   style="flex:1; min-height:44px; padding:10px 12px; border:2px solid rgba(6,78,90,0.2); border-radius:6px; font-size:14px; color:#064E5A; background:white;"
                   onfocus="this.style.borderColor='#9A2A06';"
                   onblur="this.style.borderColor='rgba(6,78,90,0.2)';">
            <button type="submit"
                    aria-label="Lancer la recherche"
                    style="min-height:44px; padding:8px 16px; background:var(--c-primary,#064E5A); color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px;"
                    onfocus="this.style.outline='3px solid #9A2A06'; this.style.outlineOffset='2px';"
                    onblur="this.style.outline='none';">
                Rechercher
            </button>
        </form>
        <p style="margin:10px 0 0; font-size:11px; color:#5A6270;">💡 Astuce : <kbd style="background:rgba(6,78,90,0.08); padding:2px 6px; border-radius:4px; font-family:monospace; font-size:11px;">Ctrl</kbd>+<kbd style="background:rgba(6,78,90,0.08); padding:2px 6px; border-radius:4px; font-family:monospace; font-size:11px;">K</kbd></p>
    </div>
</div>

<style>
    .lv-sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
    @media (max-width: 768px) {
        .lv-search-panel {
            position:fixed !important;
            top:60px !important;
            left:0 !important;
            right:0 !important;
            margin:0 0.5rem !important;
            min-width:auto !important;
        }
    }
</style>

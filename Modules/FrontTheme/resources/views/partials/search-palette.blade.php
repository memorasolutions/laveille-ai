{{--
    Cmd+K command palette — recherche globale cross-module.
    Source : SearchRegistry + SearchService::searchFront() → JSON via route('search.palette')
    Raccourci : Cmd+K (macOS) / Ctrl+K (Windows/Linux)
--}}
<div
    x-data="searchPalette()"
    x-init="init()"
    x-cloak
    x-show="open"
    x-on:keydown.window.meta.k.prevent="toggle()"
    x-on:keydown.window.ctrl.k.prevent="toggle()"
    x-on:keydown.escape.window="open && close()"
    x-on:open-search-palette.window="openNow()"
    role="dialog"
    aria-modal="true"
    aria-labelledby="search-palette-title"
    class="sp-overlay"
    x-on:click.self="close()"
>
    <div class="sp-modal" role="document">
        <div class="sp-header">
            <label for="search-palette-input" class="sp-visually-hidden" id="search-palette-title">
                {{ __('Rechercher sur le site') }}
            </label>
            <span class="sp-icon" aria-hidden="true">🔎</span>
            <input
                id="search-palette-input"
                type="search"
                x-model="query"
                x-ref="input"
                x-on:input.debounce.250ms="fetch()"
                x-on:keydown.arrow-down.prevent="moveActive(1)"
                x-on:keydown.arrow-up.prevent="moveActive(-1)"
                x-on:keydown.enter.prevent="goActive()"
                autocomplete="off"
                spellcheck="false"
                inputmode="search"
                placeholder="{{ __('Rechercher outils, glossaire, articles…') }}"
                aria-controls="search-palette-results"
                aria-describedby="search-palette-hint"
                class="sp-input"
            >
            <kbd class="sp-kbd" aria-hidden="true">ESC</kbd>
            <button
                type="button"
                x-on:click="close()"
                class="sp-close"
                aria-label="{{ __('Fermer la recherche') }}"
            >×</button>
        </div>

        <div id="search-palette-results" class="sp-results" aria-live="polite" aria-busy="false" x-bind:aria-busy="loading.toString()">
            {{-- Empty state — invite à taper, avec Octopus loved (« je cherche pour toi ») --}}
            <template x-if="!query || query.length < 2">
                <div class="sp-empty">
                    <div class="sp-empty__mascot" aria-hidden="true">
                        <x-tools::octopus variant="loved" :size="96" />
                    </div>
                    <p class="sp-empty__title">{{ __('Commencez à taper') }}</p>
                    <p class="sp-empty__hint">{{ __('Octopus cherche pour vous parmi les outils, le glossaire techno, les articles et plus.') }}</p>
                    <ul class="sp-shortcuts">
                        <li><kbd>↑</kbd><kbd>↓</kbd> {{ __('naviguer') }}</li>
                        <li><kbd>⏎</kbd> {{ __('ouvrir') }}</li>
                        <li><kbd>ESC</kbd> {{ __('fermer') }}</li>
                    </ul>
                </div>
            </template>

            {{-- Loading --}}
            <template x-if="query && query.length >= 2 && loading">
                <div class="sp-loading" role="status">
                    <span class="sp-spinner" aria-hidden="true"></span>
                    <span>{{ __('Recherche en cours…') }}</span>
                </div>
            </template>

            {{-- No results — Octopus confused (« je n'ai rien trouvé ») --}}
            <template x-if="query && query.length >= 2 && !loading && total === 0">
                <div class="sp-empty">
                    <div class="sp-empty__mascot" aria-hidden="true">
                        <x-tools::octopus variant="confused" :size="96" />
                    </div>
                    <p class="sp-empty__title">{{ __('Aucun résultat pour') }} « <span x-text="query"></span> »</p>
                    <p class="sp-empty__hint">{{ __('Octopus n\'a rien trouvé. Essayez avec un synonyme ou un terme plus court.') }}</p>
                </div>
            </template>

            {{-- Sections --}}
            <template x-if="query && query.length >= 2 && !loading && total > 0">
                <div>
                    <template x-for="(section, sIdx) in sections" :key="section.key">
                        <section class="sp-section">
                            <header class="sp-section__head">
                                <span class="sp-section__icon" aria-hidden="true" x-text="section.icon"></span>
                                <h3 class="sp-section__label" x-text="section.label"></h3>
                                <span class="sp-section__count" x-text="section.total"></span>
                            </header>
                            <ul class="sp-list" role="listbox">
                                <template x-for="(item, iIdx) in section.items" :key="section.key + '-' + iIdx">
                                    <li
                                        role="option"
                                        x-bind:id="'sp-item-' + flatIndex(sIdx, iIdx)"
                                        x-bind:aria-selected="(activeIndex === flatIndex(sIdx, iIdx)).toString()"
                                        x-bind:class="{ 'is-active': activeIndex === flatIndex(sIdx, iIdx) }"
                                        x-on:mousemove="activeIndex = flatIndex(sIdx, iIdx)"
                                        class="sp-item"
                                    >
                                        <a x-bind:href="item.url" class="sp-item__link">
                                            <span class="sp-item__title" x-text="item.title"></span>
                                            <span class="sp-item__excerpt" x-text="item.excerpt"></span>
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </section>
                    </template>

                    <a x-bind:href="seeAllUrl" class="sp-see-all">
                        {{ __('Voir tous les résultats') }} <span x-text="'(' + total + (total >= 6 ? '+' : '') + ')'"></span>
                    </a>
                </div>
            </template>
        </div>

        <div id="search-palette-hint" class="sp-footer">
            <span>{{ __('Astuce') }} :</span>
            <kbd>{{ __('Ctrl') }}</kbd><kbd>K</kbd>
            <span>{{ __('ou') }}</span>
            <kbd>⌘</kbd><kbd>K</kbd>
            <span>{{ __('pour rouvrir') }}</span>
        </div>
    </div>
</div>

<style>
    .sp-overlay {
        position: fixed; inset: 0;
        background: rgba(11, 14, 20, 0.55);
        backdrop-filter: blur(4px);
        z-index: 100000;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 8vh 16px 16px;
    }
    .sp-modal {
        width: 100%; max-width: 640px;
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 18px 48px rgba(0,0,0,0.28);
        overflow: hidden;
        display: flex; flex-direction: column;
        max-height: 80vh;
    }
    .sp-header {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 14px;
        border-bottom: 1px solid #e5e7eb;
    }
    .sp-icon { font-size: 18px; line-height: 1; }
    .sp-input {
        flex: 1; min-width: 0;
        border: 0; outline: 0; background: transparent;
        font-size: 17px; line-height: 1.4;
        color: #0b1220; padding: 6px 4px;
    }
    .sp-input::placeholder { color: #6b7280; }
    .sp-kbd, .sp-footer kbd {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 11px;
        background: #f3f4f6; color: #374151;
        border: 1px solid #e5e7eb; border-bottom-width: 2px;
        border-radius: 6px;
        padding: 2px 6px;
    }
    .sp-close {
        border: 0; background: transparent; cursor: pointer;
        font-size: 24px; line-height: 1; color: #6b7280;
        width: 32px; height: 32px;
        border-radius: 8px;
    }
    .sp-close:hover, .sp-close:focus-visible {
        background: #f3f4f6; color: #111827;
        outline: 2px solid #ea580c; outline-offset: 2px;
    }
    .sp-results { overflow-y: auto; padding: 8px 6px 12px; }
    .sp-empty { padding: 28px 22px; text-align: center; }
    .sp-empty__mascot { display: block; margin: 0 auto 12px; line-height: 1; }
    .sp-empty__mascot .octopus-mascot { display: block; margin: 0 auto; }
    .sp-empty__title { margin: 0 0 4px; font-weight: 700; color: #0b1220; font-size: 15px; }
    .sp-empty__hint { margin: 0; color: #4b5563; font-size: 14px; line-height: 1.45; }
    .sp-shortcuts {
        list-style: none; padding: 16px 0 0; margin: 0;
        display: flex; gap: 18px; justify-content: center; flex-wrap: wrap;
        font-size: 13px; color: #4b5563;
    }
    .sp-shortcuts li { display: inline-flex; align-items: center; gap: 6px; }
    .sp-loading {
        display: flex; align-items: center; gap: 10px;
        padding: 18px 22px; color: #4b5563;
    }
    .sp-spinner {
        width: 18px; height: 18px; border-radius: 50%;
        border: 2px solid #e5e7eb; border-top-color: var(--c-primary, #064E5A);
        animation: sp-spin .8s linear infinite;
    }
    @keyframes sp-spin { to { transform: rotate(360deg); } }
    .sp-section { padding: 6px 4px; }
    .sp-section__head {
        display: flex; align-items: center; gap: 8px;
        padding: 8px 10px 4px;
    }
    .sp-section__icon { font-size: 16px; }
    .sp-section__label {
        margin: 0; font-size: 12px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .04em;
        color: #4b5563;
    }
    .sp-section__count {
        margin-left: auto;
        background: #f3f4f6; color: #374151;
        font-size: 11px; font-weight: 700;
        padding: 2px 8px; border-radius: 999px;
    }
    .sp-list { list-style: none; padding: 0; margin: 0; }
    .sp-item { border-radius: 10px; }
    .sp-item.is-active { background: #f3f4f6; }
    .sp-item__link {
        display: flex; flex-direction: column; gap: 2px;
        padding: 10px 12px; min-height: 44px;
        color: #0b1220; text-decoration: none;
    }
    .sp-item.is-active .sp-item__link { color: #064E5A; }
    .sp-item__title { font-weight: 600; font-size: 15px; line-height: 1.3; }
    .sp-item__excerpt {
        font-size: 13px; line-height: 1.4; color: #4b5563;
        display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .sp-item__link:focus-visible {
        outline: 2px solid #ea580c; outline-offset: 2px;
        background: #fff7ed;
    }
    .sp-see-all {
        display: block; text-align: center;
        margin: 10px 8px 4px;
        padding: 10px;
        background: #f3f4f6;
        border-radius: 10px;
        font-weight: 600; font-size: 13px;
        color: var(--c-primary, #064E5A);
        text-decoration: none;
    }
    .sp-see-all:hover, .sp-see-all:focus-visible {
        background: var(--c-primary, #064E5A); color: #fff;
        outline: 2px solid #ea580c; outline-offset: 2px;
    }
    .sp-footer {
        display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
        padding: 10px 14px;
        border-top: 1px solid #e5e7eb;
        font-size: 12px; color: #6b7280;
    }
    .sp-visually-hidden {
        position: absolute; width: 1px; height: 1px;
        padding: 0; margin: -1px; overflow: hidden;
        clip: rect(0,0,0,0); white-space: nowrap; border: 0;
    }
    @media (max-width: 480px) {
        .sp-overlay { padding: 0; }
        .sp-modal { max-height: 100vh; height: 100vh; border-radius: 0; max-width: 100%; }
    }
    body.sp-open { overflow: hidden; }
</style>

<script>
    function searchPalette() {
        return {
            open: false,
            query: '',
            sections: [],
            total: 0,
            seeAllUrl: '#',
            loading: false,
            activeIndex: 0,
            _abort: null,

            init() {
                window.openSearchPalette = () => this.openNow();
            },
            openNow() {
                if (this.open) return;
                this.open = true;
                document.body.classList.add('sp-open');
                this.$nextTick(() => { try { this.$refs.input.focus(); } catch (e) {} });
            },
            close() {
                this.open = false;
                document.body.classList.remove('sp-open');
                this.query = '';
                this.sections = [];
                this.total = 0;
                this.activeIndex = 0;
            },
            toggle() {
                this.open ? this.close() : this.openNow();
            },
            async fetch() {
                if (!this.query || this.query.length < 2) {
                    this.sections = []; this.total = 0; this.activeIndex = 0;
                    return;
                }
                if (this._abort) { try { this._abort.abort(); } catch (e) {} }
                this._abort = new AbortController();
                this.loading = true;
                try {
                    const url = '{{ route('search.palette') }}?q=' + encodeURIComponent(this.query);
                    const r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: this._abort.signal });
                    if (!r.ok) throw new Error('http ' + r.status);
                    const data = await r.json();
                    this.sections = data.sections || [];
                    this.total = data.total || 0;
                    this.seeAllUrl = data.see_all_url || '#';
                    this.activeIndex = 0;
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        this.sections = []; this.total = 0;
                    }
                } finally {
                    this.loading = false;
                }
            },
            flatItems() {
                const out = [];
                this.sections.forEach((s, sIdx) => s.items.forEach((it, iIdx) => out.push({ s: sIdx, i: iIdx, url: it.url })));
                return out;
            },
            flatIndex(sIdx, iIdx) {
                let n = 0;
                for (let s = 0; s < sIdx; s++) n += this.sections[s].items.length;
                return n + iIdx;
            },
            moveActive(delta) {
                const flat = this.flatItems();
                if (flat.length === 0) return;
                this.activeIndex = (this.activeIndex + delta + flat.length) % flat.length;
                this.$nextTick(() => {
                    const el = document.getElementById('sp-item-' + this.activeIndex);
                    if (el) el.scrollIntoView({ block: 'nearest' });
                });
            },
            goActive() {
                const flat = this.flatItems();
                if (flat.length === 0) {
                    if (this.query && this.query.length >= 2 && this.seeAllUrl) {
                        window.location.href = this.seeAllUrl;
                    }
                    return;
                }
                const t = flat[this.activeIndex];
                if (t && t.url) window.location.href = t.url;
            },
        };
    }
</script>

{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Sticky compare bar (DRY) — composant unique inclus dans index/show/compare.
    Déclare Alpine.store('compare') global utilisé par <x-directory::compare-toggle>.
    Persistance localStorage 'laveille_compare_ids', max 4 outils.
--}}
@once
<style>
    .lv-compare-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1040;
        background: #fff;
        border-top: 3px solid var(--c-primary, #064E5A);
        box-shadow: 0 -4px 16px rgba(0,0,0,0.08);
        padding: 12px 16px;
        transform: translateY(100%);
        transition: transform 0.25s ease;
    }
    .lv-compare-bar.is-open { transform: translateY(0); }
    .lv-compare-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .lv-compare-label {
        font-weight: 700;
        color: var(--c-dark, #1a1d23);
        font-size: 14px;
        white-space: nowrap;
    }
    .lv-compare-chips {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        flex: 1;
        min-width: 0;
    }
    .lv-compare-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #F0F4F8;
        color: var(--c-dark, #1a1d23);
        padding: 6px 10px 6px 12px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        max-width: 200px;
    }
    .lv-compare-chip span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .lv-compare-chip button {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--c-text-muted, #52586a);
        font-size: 18px;
        line-height: 1;
        padding: 0 2px;
        border-radius: 50%;
        min-width: 22px;
        min-height: 22px;
    }
    .lv-compare-chip button:hover { color: var(--c-accent, #9A2A06); }
    .lv-compare-chip button:focus-visible { outline: 2px solid var(--c-accent, #9A2A06); outline-offset: 2px; }
    .lv-compare-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-shrink: 0;
    }
    .lv-compare-cta {
        background: var(--c-primary, #064E5A);
        color: #fff !important;
        text-decoration: none !important;
        padding: 10px 18px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 14px;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: opacity 0.15s;
    }
    .lv-compare-cta:hover { opacity: 0.9; color: #fff; }
    .lv-compare-cta:focus-visible { outline: 3px solid var(--c-accent, #9A2A06); outline-offset: 3px; }
    .lv-compare-cta[aria-disabled="true"] { background: #cbd5e1; cursor: not-allowed; pointer-events: none; }
    .lv-compare-clear {
        background: none;
        border: 1px solid var(--c-border, #E5E7EB);
        color: var(--c-text-muted, #52586a);
        padding: 8px 14px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        min-height: 36px;
    }
    .lv-compare-clear:hover { background: #F3F4F6; }
    .lv-compare-clear:focus-visible { outline: 2px solid var(--c-primary, #064E5A); outline-offset: 2px; }
    @media (max-width: 640px) {
        .lv-compare-inner { gap: 8px; }
        .lv-compare-label { width: 100%; font-size: 13px; }
        .lv-compare-chip { max-width: 140px; font-size: 12px; }
        .lv-compare-cta { padding: 8px 14px; font-size: 13px; }
    }
</style>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('compare', {
        ids: [],
        names: {},
        max: 4,
        storageKey: 'laveille_compare_v1',

        init() {
            try {
                const raw = localStorage.getItem(this.storageKey);
                if (raw) {
                    const data = JSON.parse(raw);
                    this.ids = Array.isArray(data.ids) ? data.ids.slice(0, this.max) : [];
                    this.names = (data.names && typeof data.names === 'object') ? data.names : {};
                }
            } catch (e) { this.ids = []; this.names = {}; }
        },

        save() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify({ ids: this.ids, names: this.names }));
            } catch (e) {}
        },

        has(id) {
            return this.ids.includes(parseInt(id, 10));
        },

        toggle(id, name) {
            const intId = parseInt(id, 10);
            if (!intId) return;
            if (this.has(intId)) {
                this.ids = this.ids.filter(x => x !== intId);
                delete this.names[intId];
            } else {
                if (this.ids.length >= this.max) {
                    window.dispatchEvent(new CustomEvent('toast-show', {
                        detail: { message: `Maximum ${this.max} outils. Retirez-en un pour en ajouter un autre.`, variant: 'warning', duration: 3500 }
                    }));
                    return;
                }
                this.ids.push(intId);
                if (name) this.names[intId] = String(name).slice(0, 80);
            }
            this.save();
        },

        remove(id) {
            const intId = parseInt(id, 10);
            this.ids = this.ids.filter(x => x !== intId);
            delete this.names[intId];
            this.save();
        },

        clear() {
            this.ids = [];
            this.names = {};
            this.save();
        },

        get count() { return this.ids.length; },

        get compareUrl() {
            return '/annuaire/comparer?ids=' + this.ids.join(',');
        }
    });
});
</script>
@endonce

<div class="lv-compare-bar"
     :class="{ 'is-open': $store.compare.count > 0 }"
     x-data
     x-cloak
     role="region"
     aria-label="{{ __('Comparateur d\'outils') }}">
    <div class="lv-compare-inner">
        <span class="lv-compare-label">
            <span x-text="$store.compare.count"></span>
            <span x-text="$store.compare.count <= 1 ? '{{ __('outil sélectionné') }}' : '{{ __('outils sélectionnés') }}'"></span>
        </span>
        <div class="lv-compare-chips" aria-live="polite">
            <template x-for="id in $store.compare.ids" :key="id">
                <span class="lv-compare-chip">
                    <span x-text="$store.compare.names[id] || ('Outil #' + id)"></span>
                    <button type="button"
                            @click="$store.compare.remove(id)"
                            :aria-label="'{{ __('Retirer') }} ' + ($store.compare.names[id] || ('Outil #' + id))">×</button>
                </span>
            </template>
        </div>
        <div class="lv-compare-actions">
            <button type="button" class="lv-compare-clear" @click="$store.compare.clear()">{{ __('Vider') }}</button>
            <a :href="$store.compare.compareUrl"
               class="lv-compare-cta"
               :aria-disabled="$store.compare.count < 2 ? 'true' : 'false'"
               @click="if ($store.compare.count < 2) { $event.preventDefault(); window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: '{{ __('Sélectionnez au moins 2 outils pour comparer.') }}', variant: 'info', duration: 3000 } })); }">
                📊 {{ __('Comparer') }}
            </a>
        </div>
    </div>
</div>

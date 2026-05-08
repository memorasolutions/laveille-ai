{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Sticky compare bar (DRY) — composant unique inclus dans index/show/compare.
    Déclare Alpine.store('compare') global utilisé par <x-directory::compare-toggle>.
    S88 bonifs : thumbnails dans chips, mode sélection, slide-in, fillFromCategory.
--}}
@once
<style>
    /* ─────────── Sticky compare bar ─────────── */
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
        gap: 8px;
        background: #F0F4F8;
        color: var(--c-dark, #1a1d23);
        padding: 4px 6px 4px 6px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        max-width: 220px;
        animation: lvChipSlideIn 0.32s cubic-bezier(.34,1.56,.64,1);
    }
    .lv-compare-chip-thumb {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        object-fit: cover;
        background: #fff;
        flex-shrink: 0;
        border: 1px solid var(--c-border, #E5E7EB);
    }
    .lv-compare-chip-thumb-placeholder {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--c-primary, #064E5A), #0a6a7c);
        color: #fff;
        font-weight: 800;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .lv-compare-chip-name {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding-right: 4px;
    }
    .lv-compare-chip-remove {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--c-text-muted, #52586a);
        font-size: 18px;
        line-height: 1;
        min-width: 32px;
        min-height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .lv-compare-chip-remove:hover { color: var(--c-accent, #9A2A06); background: rgba(154,42,6,0.08); }
    .lv-compare-chip-remove:focus-visible { outline: 2px solid var(--c-accent, #9A2A06); outline-offset: 2px; }

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
        padding: 12px 22px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 14px;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: opacity 0.15s, transform 0.15s;
    }
    .lv-compare-cta:hover { opacity: 0.9; color: #fff; transform: translateY(-1px); }
    .lv-compare-cta:focus-visible { outline: 3px solid var(--c-accent, #9A2A06); outline-offset: 3px; }
    .lv-compare-cta[aria-disabled="true"] { background: #cbd5e1; cursor: not-allowed; pointer-events: none; }
    .lv-compare-clear {
        background: none;
        border: 1px solid var(--c-border, #E5E7EB);
        color: var(--c-text-muted, #52586a);
        padding: 10px 16px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        min-height: 44px;
    }
    .lv-compare-clear:hover { background: #F3F4F6; }
    .lv-compare-clear:focus-visible { outline: 2px solid var(--c-primary, #064E5A); outline-offset: 2px; }

    /* ─────────── Animations ─────────── */
    @keyframes lvChipSlideIn {
        from { opacity: 0; transform: translateX(20px) scale(0.85); }
        to { opacity: 1; transform: translateX(0) scale(1); }
    }
    @keyframes lvBounce {
        0%, 100% { transform: scale(1); }
        45% { transform: scale(1.04); }
    }
    .lv-bounce { animation: lvBounce 0.36s ease-out; }

    @media (prefers-reduced-motion: reduce) {
        .lv-compare-chip { animation: none; }
        .lv-bounce { animation: none; }
        .lv-compare-bar { transition: none; }
    }

    @media (max-width: 640px) {
        .lv-compare-inner { gap: 8px; }
        .lv-compare-label { width: 100%; font-size: 13px; }
        .lv-compare-chip { max-width: 160px; font-size: 12px; }
        .lv-compare-chip-thumb, .lv-compare-chip-thumb-placeholder { width: 24px; height: 24px; font-size: 11px; }
        .lv-compare-cta { padding: 10px 16px; font-size: 13px; min-height: 44px; }
    }

    /* ─────────── Mode sélection (active sur body.lv-selection-mode) ─────────── */
    body.lv-selection-mode .rt-card,
    body.lv-selection-mode .lv-cmp-card-row {
        background: rgba(6, 78, 90, 0.025);
    }
    body.lv-selection-mode .lv-cmp-toggle--icon {
        width: 56px !important;
        height: 56px !important;
        font-size: 22px !important;
        border-width: 2.5px !important;
    }
    .lv-selection-help {
        background: linear-gradient(135deg, #F0F9FA 0%, #E0F4F7 100%);
        border: 1.5px solid #B2E0E6;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 18px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
    }
    .lv-selection-help-text {
        flex: 1;
        min-width: 200px;
        font-size: 14px;
        color: var(--c-dark, #1a1d23);
        font-weight: 600;
    }
    .lv-selection-help-text .count {
        color: var(--c-primary, #064E5A);
        font-weight: 800;
    }
    .lv-selection-help .btn {
        background: var(--c-primary, #064E5A);
        color: #fff;
        text-decoration: none !important;
        padding: 10px 18px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 13px;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .lv-selection-help .btn:hover { opacity: 0.9; color: #fff; }
    .lv-selection-help .btn[aria-disabled="true"] { background: #cbd5e1; pointer-events: none; }
    .lv-selection-help .btn-secondary {
        background: #fff;
        color: var(--c-primary, #064E5A);
        border: 1.5px solid var(--c-primary, #064E5A);
    }
    .lv-selection-help .btn-secondary:hover { background: #F0F4F8; color: var(--c-primary, #064E5A); }
</style>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('compare', {
        ids: [],
        names: {},
        thumbs: {},
        max: 4,
        selectionMode: false,
        storageKey: 'laveille_compare_v1',
        modeKey: 'laveille_compare_mode_v1',

        init() {
            try {
                const raw = localStorage.getItem(this.storageKey);
                if (raw) {
                    const data = JSON.parse(raw);
                    this.ids = Array.isArray(data.ids) ? data.ids.slice(0, this.max) : [];
                    this.names = (data.names && typeof data.names === 'object') ? data.names : {};
                    this.thumbs = (data.thumbs && typeof data.thumbs === 'object') ? data.thumbs : {};
                }
                this.selectionMode = localStorage.getItem(this.modeKey) === '1';
                this.applyMode();
            } catch (e) { this.ids = []; this.names = {}; this.thumbs = {}; }
        },

        save() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify({
                    ids: this.ids, names: this.names, thumbs: this.thumbs,
                }));
            } catch (e) {}
        },

        applyMode() {
            if (this.selectionMode) {
                document.body.classList.add('lv-selection-mode');
            } else {
                document.body.classList.remove('lv-selection-mode');
            }
        },

        toggleMode() {
            this.selectionMode = !this.selectionMode;
            try { localStorage.setItem(this.modeKey, this.selectionMode ? '1' : '0'); } catch (e) {}
            this.applyMode();
            window.dispatchEvent(new CustomEvent('toast-show', {
                detail: {
                    message: this.selectionMode
                        ? 'Mode sélection activé. Cochez 2 à 4 outils, puis cliquez Comparer.'
                        : 'Mode sélection désactivé.',
                    variant: 'info',
                    duration: 2500,
                },
            }));
        },

        has(id) {
            return this.ids.includes(parseInt(id, 10));
        },

        toggle(id, name, thumb) {
            const intId = parseInt(id, 10);
            if (!intId) return;
            if (this.has(intId)) {
                this.ids = this.ids.filter(x => x !== intId);
                delete this.names[intId];
                delete this.thumbs[intId];
            } else {
                if (this.ids.length >= this.max) {
                    window.dispatchEvent(new CustomEvent('toast-show', {
                        detail: { message: `Maximum ${this.max} outils. Retirez-en un pour en ajouter un autre.`, variant: 'warning', duration: 3500 }
                    }));
                    return;
                }
                this.ids.push(intId);
                if (name) this.names[intId] = String(name).slice(0, 80);
                if (thumb) this.thumbs[intId] = String(thumb).slice(0, 500);
            }
            this.save();
        },

        remove(id) {
            const intId = parseInt(id, 10);
            this.ids = this.ids.filter(x => x !== intId);
            delete this.names[intId];
            delete this.thumbs[intId];
            this.save();
        },

        clear() {
            this.ids = [];
            this.names = {};
            this.thumbs = {};
            this.save();
        },

        bounce(id) {
            const btn = document.querySelector('[data-cmp-card-id="' + id + '"]');
            if (!btn) return;
            const target = btn.closest('.rt-card, tr, [data-bounce-target]') || btn;
            target.classList.remove('lv-bounce');
            void target.offsetWidth;
            target.classList.add('lv-bounce');
            setTimeout(() => target.classList.remove('lv-bounce'), 400);
        },

        fillFromCategory(slug) {
            // Set ?fillCategory=slug et navigation reload pour laisser le serveur retourner top 4 outils.
            const url = new URL(window.location.href);
            url.searchParams.set('fillCategory', slug);
            window.location.href = url.toString();
        },

        get count() { return this.ids.length; },
        get reachedMax() { return this.ids.length >= this.max; },
        get canCompare() { return this.ids.length >= 2; },

        get compareUrl() {
            return '/annuaire/comparer?ids=' + this.ids.join(',');
        },

        initial(name) {
            const s = String(name || '').trim();
            return s ? s.charAt(0).toUpperCase() : '?';
        },
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
            <span x-text="$store.compare.count"></span>/<span x-text="$store.compare.max"></span>
            <span x-text="$store.compare.count <= 1 ? '{{ __('outil') }}' : '{{ __('outils') }}'"></span>
        </span>
        <div class="lv-compare-chips" aria-live="polite">
            <template x-for="id in $store.compare.ids" :key="id">
                <span class="lv-compare-chip">
                    <template x-if="$store.compare.thumbs[id]">
                        <img :src="$store.compare.thumbs[id]" alt="" class="lv-compare-chip-thumb" loading="lazy" onerror="this.style.display='none'">
                    </template>
                    <template x-if="!$store.compare.thumbs[id]">
                        <span class="lv-compare-chip-thumb-placeholder" aria-hidden="true" x-text="$store.compare.initial($store.compare.names[id])"></span>
                    </template>
                    <span class="lv-compare-chip-name" x-text="$store.compare.names[id] || ('Outil #' + id)"></span>
                    <button type="button"
                            class="lv-compare-chip-remove"
                            @click="$store.compare.remove(id)"
                            :aria-label="'{{ __('Retirer') }} ' + ($store.compare.names[id] || ('Outil #' + id))">×</button>
                </span>
            </template>
        </div>
        <div class="lv-compare-actions">
            <button type="button" class="lv-compare-clear" @click="$store.compare.clear()">{{ __('Vider') }}</button>
            <a :href="$store.compare.compareUrl"
               class="lv-compare-cta"
               :aria-disabled="$store.compare.canCompare ? 'false' : 'true'"
               @click="if (!$store.compare.canCompare) { $event.preventDefault(); window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: '{{ __('Sélectionnez au moins 2 outils pour comparer.') }}', variant: 'info', duration: 3000 } })); }">
                📊 {{ __('Comparer') }}
            </a>
        </div>
    </div>
</div>

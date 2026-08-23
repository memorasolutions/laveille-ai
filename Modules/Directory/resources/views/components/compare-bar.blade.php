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
    /* 2026-08-23 WCAG 2.2 AAA : le texte restait en #fff sur ce gris, soit 1,48:1 (AAA exige 7:1,
       AA 4,5:1). L'exemption des « composants inactifs » ne s'applique PAS ici : `compareUrl`
       renvoie toujours une chaîne, donc le lien garde son href et reste atteignable au clavier
       (`pointer-events:none` ne bloque que la souris). Un libellé qu'on peut cibler doit se lire.
       Le fond pâle est conservé - c'est lui qui dit « indisponible » - et seul le texte est foncé :
       #1F2937 sur #cbd5e1 = 9,89:1, mesuré.
       `!important` est requis ici, et seulement ici : la règle de base pose `color: #fff !important`
       (ligne 111, pour battre la couleur de lien du thème sur un <a>). Une déclaration simple, même
       plus spécifique, ne peut pas la battre - mesuré : après un premier déploiement sans lui, la
       page rendue affichait toujours 1,48:1 alors que la feuille de style semblait corrigée. Entre
       deux `!important`, c'est la spécificité qui tranche, et [aria-disabled] l'emporte. */
    .lv-compare-cta[aria-disabled="true"] { background: #cbd5e1; color: #1F2937 !important; cursor: not-allowed; pointer-events: none; }
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
    /* Même défaut, même correction que .lv-compare-cta ci-dessus (9,89:1 mesuré). `cursor` ajouté
       pour que les deux états désactivés du même écran se comportent pareil. */
    .lv-selection-help .btn[aria-disabled="true"] { background: #cbd5e1; color: #1F2937; cursor: not-allowed; pointer-events: none; }
    .lv-selection-help .btn-secondary {
        background: #fff;
        color: var(--c-primary, #064E5A);
        border: 1.5px solid var(--c-primary, #064E5A);
    }
    .lv-selection-help .btn-secondary:hover { background: #F0F4F8; color: var(--c-primary, #064E5A); }

    /* ─── Variant icon : circle 32x32 absolute coin haut-droit (Material Design 3) ─── */
    .lv-cmp-toggle--icon {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 4;
        width: 32px;
        height: 32px;
        min-width: 32px;
        border: 2px solid var(--c-primary, #064E5A);
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        color: transparent;
        cursor: pointer;
        font-weight: 800;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        line-height: 1;
        padding: 0;
        transition: background 0.18s ease, border-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
        text-decoration: none !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }
    .lv-cmp-toggle--icon:hover {
        background: #fff;
        transform: scale(1.06);
        box-shadow: 0 4px 12px rgba(6, 78, 90, 0.25);
    }
    .lv-cmp-toggle--icon:focus-visible {
        outline: 3px solid var(--c-accent, #9A2A06);
        outline-offset: 3px;
    }
    .lv-cmp-toggle--icon.is-active {
        background: var(--c-primary, #064E5A);
        border-color: var(--c-primary, #064E5A);
        color: #fff;
    }
    .lv-cmp-toggle--icon.is-active:hover {
        background: #053f49;
        border-color: #053f49;
    }

    /* ─── Variant pill (show.blade.php) ─── */
    .lv-cmp-toggle--pill {
        background: #fff;
        border: 1.5px solid var(--c-border, #E5E7EB);
        color: var(--c-text-muted, #52586a);
        cursor: pointer;
        font-weight: 700;
        transition: all 0.15s, transform 0.2s;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        min-height: 44px;
        border-radius: 50px;
        font-size: 13px;
    }
    .lv-cmp-toggle--pill:hover { border-color: var(--c-primary, #064E5A); color: var(--c-primary, #064E5A); transform: translateY(-1px); }
    .lv-cmp-toggle--pill:focus-visible { outline: 2px solid var(--c-primary, #064E5A); outline-offset: 2px; }
    .lv-cmp-toggle--pill.is-active { background: var(--c-primary, #064E5A); border-color: var(--c-primary, #064E5A); color: #fff; }
    .lv-cmp-toggle--pill.is-active:hover { background: #053f49; color: #fff; }

    /* ─── Card selectable state holistique (overlay non-bloquant) ─── */
    .rt-card { position: relative; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
    .rt-card.is-selected {
        border-color: var(--c-primary, #064E5A) !important;
        box-shadow: 0 0 0 4px rgba(6, 78, 90, 0.08), 0 6px 18px rgba(0, 0, 0, 0.08) !important;
    }
    .rt-card.is-selected::before {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(6, 78, 90, 0.06);
        border-radius: inherit;
        pointer-events: none;
        z-index: 2;
    }
    .rt-card:focus-within .lv-cmp-toggle--icon { box-shadow: 0 4px 12px rgba(6, 78, 90, 0.25); }

    /* ─── Variant card : bouton textuel pleine largeur dans .rt-actions (Option C ultra-intuitive) ─── */
    .lv-cmp-toggle--card {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 14px;
        min-height: 36px;
        border-radius: 8px;
        border: 2px solid var(--c-primary, #064E5A);
        background: #fff;
        color: var(--c-primary, #064E5A);
        cursor: pointer;
        font-weight: 700;
        font-size: 12px;
        text-decoration: none !important;
        transition: background 0.15s, transform 0.15s, box-shadow 0.15s;
        white-space: nowrap;
    }
    .lv-cmp-toggle--card:hover {
        background: rgba(6, 78, 90, 0.06);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(6, 78, 90, 0.12);
    }
    .lv-cmp-toggle--card:focus-visible {
        outline: 3px solid var(--c-accent, #9A2A06);
        outline-offset: 2px;
    }
    .lv-cmp-toggle--card.is-active {
        background: var(--c-primary, #064E5A);
        color: #fff;
    }
    .lv-cmp-toggle--card.is-active:hover {
        background: #053f49;
        color: #fff;
    }

    /* ─── Onboarding tooltip pulse 1ère visite ─── */
    .lv-cmp-onboarding {
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translate(-50%, -100%);
        background: var(--c-dark, #1a1d23);
        color: #fff;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.45;
        max-width: 280px;
        text-align: center;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
        z-index: 50;
        animation: lvCmpPulse 2.4s ease-in-out infinite;
    }
    .lv-cmp-onboarding::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 0;
        border-left: 8px solid transparent;
        border-right: 8px solid transparent;
        border-top: 8px solid var(--c-dark, #1a1d23);
    }
    .lv-cmp-onboarding-dismiss {
        background: var(--c-accent, #9A2A06);
        color: #fff;
        border: none;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        margin-top: 8px;
        min-height: 32px;
        display: inline-flex;
        align-items: center;
    }
    .lv-cmp-onboarding-dismiss:hover { background: #B65D04; }
    @keyframes lvCmpPulse {
        0%, 100% { transform: translate(-50%, -100%) scale(1); }
        50% { transform: translate(-50%, -100%) scale(1.04); }
    }
    @media (prefers-reduced-motion: reduce) {
        .lv-cmp-onboarding { animation: none; }
    }

    /* ─── Popover 2e sélection ─── */
    .lv-cmp-popover-overlay {
        position: fixed;
        inset: 0;
        background: rgba(26, 29, 35, 0.45);
        z-index: 1050;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: lvCmpFadeIn 0.18s ease-out;
    }
    .lv-cmp-popover {
        background: #fff;
        border-radius: 16px;
        padding: 28px 24px 22px;
        max-width: 420px;
        width: 100%;
        text-align: center;
        box-shadow: 0 24px 48px rgba(0, 0, 0, 0.22);
        border-top: 5px solid var(--c-primary, #064E5A);
    }
    .lv-cmp-popover h3 {
        margin: 0 0 8px;
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--c-dark, #1a1d23);
        font-family: var(--f-heading, sans-serif);
    }
    .lv-cmp-popover p {
        margin: 0 0 18px;
        color: var(--c-text-muted, #52586a);
        font-size: 0.95rem;
        line-height: 1.5;
    }
    .lv-cmp-popover-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .lv-cmp-popover-cta {
        background: var(--c-primary, #064E5A);
        color: #fff !important;
        text-decoration: none !important;
        padding: 12px 24px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 14px;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .lv-cmp-popover-cta:hover { opacity: 0.9; color: #fff; }
    .lv-cmp-popover-secondary {
        background: #F0F4F8;
        color: var(--c-dark, #1a1d23);
        border: none;
        padding: 12px 22px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 14px;
        min-height: 44px;
        cursor: pointer;
    }
    @keyframes lvCmpFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @media (prefers-reduced-motion: reduce) {
        .lv-cmp-popover-overlay { animation: none; }
    }

    /* ─── Toggle compact dans table list view ─── */
    .lv-cmp-toggle-row {
        width: 28px;
        height: 28px;
        min-width: 28px;
        border-radius: 50%;
        border: 2px solid var(--c-primary, #064E5A);
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        margin-right: 6px;
        vertical-align: middle;
        transition: background 0.15s, transform 0.15s;
        font-weight: 800;
        color: #fff;
        font-size: 14px;
        line-height: 1;
        padding: 0;
    }
    .lv-cmp-toggle-row:hover { transform: scale(1.08); }
    .lv-cmp-toggle-row.is-active { background: var(--c-primary, #064E5A); }

    @media (prefers-reduced-motion: reduce) {
        .rt-card { transition: none; }
        .lv-cmp-toggle--icon, .lv-cmp-toggle--pill, .lv-cmp-toggle-row { transition: none; }
    }
</style>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('compare', {
        ids: [],
        names: {},
        thumbs: {},
        max: 6,
        selectionMode: false,
        showFirstPairPopover: false,
        firstPairSeen: false,
        onboardingShown: false,
        storageKey: 'laveille_compare_v1',
        modeKey: 'laveille_compare_mode_v1',
        firstPairKey: 'laveille_compare_first_pair_seen_v1',
        onboardingKey: 'laveille_compare_onboarded_v1',

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
                this.firstPairSeen = localStorage.getItem(this.firstPairKey) === '1';
                this.onboardingShown = localStorage.getItem(this.onboardingKey) === '1';
                this.applyMode();
            } catch (e) { this.ids = []; this.names = {}; this.thumbs = {}; }
        },

        markOnboarded() {
            this.onboardingShown = true;
            try { localStorage.setItem(this.onboardingKey, '1'); } catch (e) {}
        },

        dismissFirstPairPopover() {
            this.showFirstPairPopover = false;
            this.firstPairSeen = true;
            try { localStorage.setItem(this.firstPairKey, '1'); } catch (e) {}
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
                // Première fois qu'on atteint 2 sélections : popover progress feedback
                if (this.ids.length === 2 && !this.firstPairSeen) {
                    this.showFirstPairPopover = true;
                    // Auto-dismiss après 7s
                    setTimeout(() => { if (this.showFirstPairPopover) this.dismissFirstPairPopover(); }, 7000);
                }
                // Premier ajout : marquer onboarding done
                if (!this.onboardingShown) this.markOnboarded();
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
     :class="{ 'is-open': $store.compare.count >= 1 }"
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
               @click="if (!$store.compare.canCompare) { $event.preventDefault(); }">
                <template x-if="$store.compare.canCompare">
                    <span>📊 {{ __('Comparer') }} (<span x-text="$store.compare.count"></span>)</span>
                </template>
                <template x-if="!$store.compare.canCompare">
                    <span>{{ __('Sélectionnez au moins 2 outils') }}</span>
                </template>
            </a>
        </div>
    </div>
</div>

{{-- Popover progress feedback : 1ère fois count atteint 2 --}}
<template x-data x-if="$store.compare.showFirstPairPopover">
    <div class="lv-cmp-popover-overlay"
         role="dialog"
         aria-modal="true"
         aria-labelledby="lvCmpPopoverTitle"
         @click.self="$store.compare.dismissFirstPairPopover()"
         @keydown.escape.window="$store.compare.dismissFirstPairPopover()">
        <div class="lv-cmp-popover">
            <div style="font-size: 2.6rem; line-height: 1; margin-bottom: 8px;" aria-hidden="true">🎉</div>
            <h3 id="lvCmpPopoverTitle">{{ __('2 outils sélectionnés !') }}</h3>
            <p>{{ __('Tu peux maintenant les comparer côte à côte. Ajoute jusqu\'à 6 outils ou compare directement.') }}</p>
            <div class="lv-cmp-popover-actions">
                <a :href="$store.compare.compareUrl"
                   class="lv-cmp-popover-cta"
                   @click="$store.compare.dismissFirstPairPopover()">
                    📊 {{ __('Comparer maintenant') }}
                </a>
                <button type="button"
                        class="lv-cmp-popover-secondary"
                        @click="$store.compare.dismissFirstPairPopover()">
                    {{ __('Continuer la sélection') }}
                </button>
            </div>
        </div>
    </div>
</template>

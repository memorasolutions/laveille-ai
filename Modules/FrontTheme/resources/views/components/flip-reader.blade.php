{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Lecteur générique « page qui tourne » (effet Flipboard) réutilisable pour
    n'importe quelle série d'images de pages - le composant ne connaît aucune
    notion de « livre » ni de « chapitre », uniquement une liste de pages.
    Moteur : StPageFlip (page-flip, MIT), vendorisé localement (aucun CDN,
    conforme à la CSP script-src 'self') dans public/vendor/page-flip/.

    Fallback accessible obligatoire : si prefers-reduced-motion est actif OU si
    la librairie ne charge pas, bascule sur un mode « simple » (une seule page
    visible à la fois, changement instantané ou fondu léger, mêmes éléments DOM
    - aucune image n'est jamais retirée du DOM, seulement masquée via x-show).

    Usage :
        <x-fronttheme::flip-reader
            :pages="[['image' => '...', 'alt' => '...', 'width' => 1000, 'height' => 1400], ...]"
            title="Extrait du livre"
            trigger-label="Feuilleter l'extrait"
            mode="modal"
            :downloadable="false"
        />
--}}
@props([
    'pages' => [],
    'triggerLabel' => null,
    'title' => null,
    'mode' => 'modal',
    'downloadable' => false,
])

@php
    $mode = in_array($mode, ['modal', 'inline'], true) ? $mode : 'modal';
    $triggerLabel = $triggerLabel ?: __('Feuilleter');
    $firstPage = $pages[0] ?? [];
    $baseWidth = (int) ($firstPage['width'] ?? 480);
    $baseHeight = (int) ($firstPage['height'] ?? 640);
    $baseWidth = $baseWidth > 0 ? $baseWidth : 480;
    $baseHeight = $baseHeight > 0 ? $baseHeight : 640;
@endphp

@if(count($pages))
@once
@push('styles')
<style>
    .fpr-block { margin: 0 0 22px; }
    .fpr-trigger {
        display: flex; gap: 14px; align-items: center; width: 100%;
        background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 100%);
        border: 1px solid color-mix(in srgb, var(--c-primary) 28%, transparent);
        border-left: 4px solid var(--c-primary);
        border-radius: 10px; padding: 14px 18px; cursor: pointer;
        text-align: left; transition: box-shadow .18s ease, transform .18s ease;
        min-height: 44px;
    }
    .fpr-trigger:hover, .fpr-trigger:focus-visible {
        box-shadow: 0 6px 18px rgba(11, 114, 133, .16); transform: translateY(-1px);
    }
    .fpr-trigger:focus-visible { outline: 3px solid var(--c-primary); outline-offset: 2px; }
    .fpr-trigger-icon { font-size: 1.6rem; line-height: 1; flex: 0 0 auto; }
    .fpr-trigger-text { flex: 1 1 auto; min-width: 0; }
    .fpr-trigger-text strong { display: block; color: var(--c-dark, #1a1d23); font-size: 1.02rem; }
    .fpr-trigger-text span { color: var(--c-text-secondary, #4b5563); font-size: .9rem; }

    .fpr-sr-only {
        position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
        overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
    }

    .fpr-reader--modal {
        position: fixed; inset: 0; z-index: 2000;
        background: rgba(10, 20, 24, .88); backdrop-filter: blur(2px);
        display: flex; flex-direction: column; overscroll-behavior: contain;
    }
    .fpr-reader--inline {
        position: relative; z-index: 0; display: flex; flex-direction: column;
        background: var(--c-surface, #f8fafb); border-radius: var(--r-base, .75rem);
        border: 1px solid color-mix(in srgb, var(--c-primary) 20%, transparent);
        overflow: hidden;
    }
    .fpr-reader:focus-visible { outline: none; }

    /* z-index explicite : la barre de navigation doit TOUJOURS rester au-dessus
       de .fpr-stage (et de tout ce que StPageFlip y injecte), même si un futur
       calcul de taille venait à faire déborder le livre - filet de sécurité en
       plus de la contrainte de hauteur posée sur .fpr-book ci-dessous. */
    .fpr-bar {
        position: relative; z-index: 2;
        flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between;
        gap: 10px 14px; padding: 10px 14px; flex-wrap: wrap;
    }
    .fpr-reader--modal .fpr-bar { color: #fff; }
    .fpr-reader--inline .fpr-bar {
        color: var(--c-dark, #1a1d23);
        border-bottom: 1px solid color-mix(in srgb, var(--c-primary) 16%, transparent);
        background: #fff;
    }
    .fpr-caption { font-weight: 600; font-size: 1rem; line-height: 1.3; flex: 1 1 160px; min-width: 0; }

    .fpr-nav { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; }
    .fpr-actions { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; flex-wrap: wrap; }

    .fpr-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        border-radius: 8px; padding: 8px 12px; font-size: .9rem; cursor: pointer;
        text-decoration: none; min-height: 44px; min-width: 44px; line-height: 1;
    }
    .fpr-reader--modal .fpr-btn {
        background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.28);
    }
    .fpr-reader--modal .fpr-btn:hover, .fpr-reader--modal .fpr-btn:focus-visible { background: rgba(255,255,255,.22); color: #fff; }
    .fpr-reader--inline .fpr-btn {
        background: var(--c-primary-light, #f0fafb); color: var(--c-primary); border: 1px solid color-mix(in srgb, var(--c-primary) 30%, transparent);
    }
    .fpr-reader--inline .fpr-btn:hover, .fpr-reader--inline .fpr-btn:focus-visible { background: var(--c-primary-badge, #ddf4f8); }
    .fpr-btn:focus-visible { outline: 3px solid var(--c-primary); outline-offset: 2px; }
    .fpr-btn:disabled { opacity: .35; cursor: not-allowed; }
    .fpr-btn-icon { padding: 8px 10px; font-size: 1.15rem; }
    .fpr-level { min-width: 56px; text-align: center; font-variant-numeric: tabular-nums; font-size: .9rem; font-weight: 600; user-select: none; }
    .fpr-reader--modal .fpr-level { color: #fff; }
    .fpr-reader--inline .fpr-level { color: var(--c-dark, #1a1d23); }

    .fpr-stage {
        flex: 1 1 auto; position: relative; z-index: 1; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        padding: 12px; min-height: 0;
    }
    .fpr-reader--modal .fpr-stage { min-height: 60vh; }
    .fpr-reader--inline .fpr-stage { min-height: 320px; }

    /* Cause racine du chevauchement du bouton "Page suivante" : sans max-height,
       .fpr-book n'était contraint qu'en largeur (aspect-ratio dérive la hauteur
       sans jamais tenir compte de la hauteur DISPONIBLE dans .fpr-stage). Pour
       des pages au format portrait dans une modale (hauteur de scène fixée par
       le viewport), le livre calculait une hauteur supérieure à la scène et
       débordait symétriquement (centré par le flex du parent) par-dessus
       .fpr-bar. max-height: 100% force le navigateur à réduire la largeur en
       proportion (algorithme de « transferred size » CSS aspect-ratio) pour que
       le livre tienne toujours entièrement dans .fpr-stage, comme un
       object-fit: contain - qu'il soit rendu en <img> simple ou via StPageFlip
       (qui lit les dimensions déjà contraintes de .fpr-book au montage). */
    .fpr-book {
        position: relative; width: 100%; max-width: 900px; max-height: 100%; margin: 0 auto;
        aspect-ratio: {{ $baseWidth }} / {{ $baseHeight }};
    }
    .fpr-page {
        background: #fff; border-radius: 2px; overflow: hidden;
        box-shadow: 0 8px 30px rgba(0,0,0,.35);
    }
    .fpr-page img {
        display: block; width: 100%; height: 100%; object-fit: cover;
        -webkit-user-select: none; user-select: none; -webkit-user-drag: none;
    }
    .fpr-book--simple .fpr-page {
        position: absolute; inset: 0; width: 100%; height: 100%;
        transition: opacity .15s ease;
    }
    @media (prefers-reduced-motion: reduce) {
        .fpr-trigger, .fpr-book--simple .fpr-page { transition: none; }
    }
    @media (max-width: 560px) {
        .fpr-caption { flex-basis: 100%; }
        .fpr-btn span { display: none; }
    }
</style>
@endpush
@endonce

@once
@push('scripts')
{{-- Vendorisé localement (aucun CDN) - conforme à la CSP script-src 'self'. MIT, StPageFlip. --}}
<script src="{{ asset('vendor/page-flip/page-flip.browser.js') }}"></script>
<script>
    // Fabrique de données Alpine réutilisée par toutes les instances du composant sur la page.
    // L'instance PageFlip (pf) vit dans la fermeture (closure), PAS dans l'objet retourné,
    // pour éviter que le Proxy réactif d'Alpine n'interfère avec l'état interne de la librairie.
    window.fprReaderData = function (cfg) {
        let pf = null;
        const mq = window.matchMedia('(prefers-reduced-motion: reduce)');

        return {
            mode: cfg.mode,
            pageCount: cfg.pageCount,
            images: cfg.images,
            width: cfg.width,
            height: cfg.height,
            open: cfg.mode !== 'modal',
            useSimple: false,
            current: 1,
            liveMsg: '',
            lastFocus: null,
            scrollY: 0,

            init() {
                this.useSimple = mq.matches || typeof window.St === 'undefined' || !window.St.PageFlip;
                mq.addEventListener('change', (e) => {
                    this.useSimple = e.matches || typeof window.St === 'undefined' || !window.St.PageFlip;
                });
                if (this.mode !== 'modal') {
                    this.$nextTick(() => this.mount());
                }
            },

            mount() {
                if (pf || this.useSimple) { return; }
                const bookEl = this.$refs.book;
                if (!bookEl) { return; }
                try {
                    pf = new window.St.PageFlip(bookEl, {
                        width: this.width,
                        height: this.height,
                        size: 'stretch',
                        minWidth: Math.max(120, Math.round(this.width * 0.4)),
                        maxWidth: Math.round(this.width * 2.2),
                        minHeight: Math.max(160, Math.round(this.height * 0.4)),
                        maxHeight: Math.round(this.height * 2.2),
                        showCover: true,
                        maxShadowOpacity: 0.5,
                        flippingTime: 700,
                        mobileScrollSupport: false,
                        useMouseEvents: true,
                    });
                    pf.loadFromHtml(bookEl.querySelectorAll('.fpr-page'));
                    pf.on('flip', (e) => {
                        this.current = e.data + 1;
                        this.announce();
                    });
                } catch (err) {
                    this.useSimple = true;
                }
            },

            announce() {
                this.liveMsg = this.current + ' / ' + this.pageCount;
            },

            next() {
                if (this.useSimple) {
                    if (this.current < this.pageCount) { this.current++; this.announce(); }
                } else if (pf) { pf.flipNext(); }
            },
            prev() {
                if (this.useSimple) {
                    if (this.current > 1) { this.current--; this.announce(); }
                } else if (pf) { pf.flipPrev(); }
            },
            goFirst() {
                if (this.useSimple) { this.current = 1; this.announce(); }
                else if (pf) { pf.turnToPage(0); }
            },
            goLast() {
                if (this.useSimple) { this.current = this.pageCount; this.announce(); }
                else if (pf) { pf.turnToPage(this.pageCount - 1); }
            },

            show() {
                if (this.mode !== 'modal') { return; }
                this.lastFocus = document.activeElement;
                this.lockBody();
                this.open = true;
                // Double tick : x-teleport déplace le contenu dans <body> après le premier
                // $nextTick, les $refs teleportés ne sont fiables qu'au tick suivant.
                this.$nextTick(() => {
                    this.$nextTick(() => {
                        this.mount();
                        if (this.$refs.closeBtn) { this.$refs.closeBtn.focus(); }
                    });
                });
            },
            hide() {
                if (this.mode !== 'modal') { return; }
                this.open = false;
                this.unlockBody();
                if (this.lastFocus && this.lastFocus.focus) { this.lastFocus.focus(); }
            },

            lockBody() {
                this.scrollY = window.scrollY || window.pageYOffset || 0;
                const b = document.body;
                b.style.position = 'fixed';
                b.style.top = '-' + this.scrollY + 'px';
                b.style.left = '0'; b.style.right = '0'; b.style.width = '100%';
                b.style.overflow = 'hidden';
            },
            unlockBody() {
                const b = document.body;
                b.style.position = ''; b.style.top = ''; b.style.left = '';
                b.style.right = ''; b.style.width = ''; b.style.overflow = '';
                window.scrollTo(0, this.scrollY);
            },

            onKey(e) {
                const k = e.key;
                if (k === 'ArrowRight' || k === 'PageDown') { e.preventDefault(); this.next(); }
                else if (k === 'ArrowLeft' || k === 'PageUp') { e.preventDefault(); this.prev(); }
                else if (k === 'Home') { e.preventDefault(); this.goFirst(); }
                else if (k === 'End') { e.preventDefault(); this.goLast(); }
            },
            trapTab(e) {
                if (this.mode !== 'modal') { return; }
                const root = this.$refs.overlay;
                if (!root) { return; }
                const list = Array.from(
                    root.querySelectorAll('button, a[href], [tabindex]:not([tabindex="-1"])')
                ).filter((el) => !el.disabled && el.offsetParent !== null);
                if (!list.length) { return; }
                const first = list[0], last = list[list.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            },
        };
    };
</script>
@endpush
@endonce

<div class="fpr-block"
     x-data="fprReaderData({
        mode: '{{ $mode }}',
        pageCount: {{ count($pages) }},
        images: @js(array_column($pages, 'image')),
        width: {{ $baseWidth }},
        height: {{ $baseHeight }},
     })"
     @keydown.escape.window="mode === 'modal' && open && hide()">

    @if($mode === 'modal')
    <button type="button" class="fpr-trigger" @click="show()" aria-haspopup="dialog"
            aria-label="{{ $triggerLabel }}{{ $title ? ' : '.$title : '' }}">
        <span class="fpr-trigger-icon" aria-hidden="true">📖</span>
        <span class="fpr-trigger-text">
            <strong>{{ $triggerLabel }}</strong>
            @if($title)<span>{{ $title }}</span>@endif
        </span>
    </button>

    <template x-teleport="body">
        @include('fronttheme::components.partials.flip-reader-body', [
            'pages' => $pages,
            'title' => $title,
            'mode' => $mode,
            'downloadable' => $downloadable,
        ])
    </template>
    @else
        @include('fronttheme::components.partials.flip-reader-body', [
            'pages' => $pages,
            'title' => $title,
            'mode' => $mode,
            'downloadable' => $downloadable,
        ])
    @endif
</div>
@endif

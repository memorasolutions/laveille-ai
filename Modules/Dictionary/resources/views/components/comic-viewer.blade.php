{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Visionneur de planche BD réutilisable (standard « bibliothèque de BD »).
    Lightbox Alpine maison (zéro dépendance JS) : <picture> AVIF→WebP→JPEG.
    Zoom continu (molette+Ctrl, pincement, double-clic), modes d'ajustement
    (page/largeur/hauteur/100 %), pan au glisser, focus-trap, verrou de défilement
    robuste iOS (anti scroll-chaining), raccourcis clavier, télécharger. a11y AA.
    Navigation multi-planches (précédent/suivant/compteur) si plusieurs pages.

    Usage : <x-dictionary::comic-viewer :comic="$comic" />
    où $comic = Modules\Dictionary\Support\ComicLibrary::forSlug($slug)
--}}
@props(['comic' => null])

@php
    $planche = $comic['planches'][0] ?? null;
@endphp

@if($comic && $planche)
@once
@push('styles')
<style>
    .cbd-block { margin: 0 0 22px; }
    .cbd-trigger {
        display: flex; gap: 16px; align-items: center; width: 100%;
        background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 100%);
        border: 1px solid color-mix(in srgb, var(--c-primary) 28%, transparent);
        border-left: 4px solid var(--c-primary);
        border-radius: 10px; padding: 14px 18px; cursor: pointer;
        text-align: left; transition: box-shadow .18s ease, transform .18s ease;
    }
    .cbd-trigger:hover, .cbd-trigger:focus-visible {
        box-shadow: 0 6px 18px rgba(11, 114, 133, .16); transform: translateY(-1px);
    }
    .cbd-trigger:focus-visible { outline: 3px solid var(--c-primary); outline-offset: 2px; }
    .cbd-thumb {
        flex: 0 0 auto; width: 74px; height: 110px; object-fit: cover;
        border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,.18); background: #fff;
    }
    .cbd-trigger-text { flex: 1 1 auto; min-width: 0; }
    .cbd-trigger-text strong { display: block; color: var(--c-dark, #1a1d23); font-size: 1.02rem; }
    .cbd-trigger-text span { color: #4b5563; font-size: .9rem; }
    .cbd-cta {
        display: inline-flex; align-items: center; gap: 6px; margin-top: 6px;
        color: var(--c-primary); font-weight: 600; font-size: .92rem;
    }

    .cbd-overlay {
        position: fixed; inset: 0; z-index: 2000;
        background: rgba(10, 20, 24, .88); backdrop-filter: blur(2px);
        display: flex; flex-direction: column;
        overscroll-behavior: contain;
    }
    .cbd-bar {
        flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between;
        gap: 10px 14px; padding: 10px 14px; color: #fff; flex-wrap: wrap;
    }
    .cbd-caption { font-weight: 600; font-size: 1rem; line-height: 1.3; flex: 1 1 160px; min-width: 0; }
    .cbd-actions { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; flex-wrap: wrap; }
    .cbd-nav {
        display: flex; align-items: center; gap: 4px; flex-wrap: nowrap;
        background: rgba(255,255,255,.08); border-radius: 10px; padding: 4px;
    }
    .cbd-zoom {
        display: flex; align-items: center; gap: 4px; flex-wrap: wrap;
        background: rgba(255,255,255,.08); border-radius: 10px; padding: 4px;
    }
    .cbd-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.28);
        border-radius: 8px; padding: 8px 12px; font-size: .9rem; cursor: pointer;
        text-decoration: none; min-height: 40px; min-width: 40px; line-height: 1;
    }
    .cbd-btn:hover, .cbd-btn:focus-visible { background: rgba(255,255,255,.22); color: #fff; }
    .cbd-btn:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
    .cbd-btn[aria-pressed="true"] {
        background: var(--c-primary, #0b7285); border-color: #fff;
    }
    .cbd-btn-icon { padding: 8px 10px; font-size: 1.05rem; }
    .cbd-btn:disabled { opacity: .35; cursor: not-allowed; }
    .cbd-level {
        min-width: 56px; text-align: center; font-variant-numeric: tabular-nums;
        font-size: .9rem; font-weight: 600; padding: 0 4px; user-select: none;
    }
    .cbd-stage {
        flex: 1 1 auto; position: relative; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        padding: 0 8px 12px;
        overscroll-behavior: none; touch-action: none;
    }
    .cbd-stage picture { display: block; line-height: 0; }
    .cbd-stage img {
        display: block; width: auto; height: auto; max-width: none; max-height: none;
        border-radius: 4px; box-shadow: 0 10px 40px rgba(0,0,0,.5);
        transform-origin: center center; transition: transform .18s ease;
        cursor: grab; -webkit-user-select: none; user-select: none; -webkit-user-drag: none;
        touch-action: none;
    }
    .cbd-stage img.is-grab { cursor: grab; }
    .cbd-stage img.is-grabbing { cursor: grabbing; }
    .cbd-stage img.is-static { cursor: default; }
    .cbd-stage img.no-anim { transition: none; }
    [x-cloak] { display: none !important; }
    @media (prefers-reduced-motion: reduce) {
        .cbd-trigger, .cbd-stage img { transition: none; }
    }
    @media (max-width: 560px) {
        .cbd-caption { flex-basis: 100%; }
        .cbd-zoom .cbd-btn span { display: none; }
    }
</style>
@endpush
@endonce

<div class="cbd-block"
     x-data="{
        open: false,
        ready: false,
        planches: @js($comic['planches']),
        pageIndex: 0,
        imgW: 0,
        imgH: 0,
        scale: 1,
        fitScale: 1,
        tx: 0, ty: 0,
        mode: 'page',
        minScale: 0.25,
        maxScale: 4,
        dragging: false,
        startX: 0, startY: 0, startTx: 0, startTy: 0,
        pinchDist: 0, pinchScale: 1,
        _ptrs: {},
        lastFocus: null,
        scrollY: 0,

        get current() {
            return this.planches[this.pageIndex] || this.planches[0];
        },

        show() {
            this.lastFocus = document.activeElement;
            this.lockBody();
            this.open = true;
            this.$nextTick(() => {
                this.measure();
                this.fit('page');
                if (this.$refs.closeBtn) this.$refs.closeBtn.focus();
            });
        },
        hide() {
            this.open = false;
            this.unlockBody();
            if (this.lastFocus && this.lastFocus.focus) this.lastFocus.focus();
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

        get stageEl() { return this.$refs.stage; },
        get imgEl() { return this.$refs.img; },

        measure() {
            if (this.imgEl && this.imgEl.naturalWidth) {
                this.imgW = this.imgEl.naturalWidth;
                this.imgH = this.imgEl.naturalHeight;
            }
        },
        baseScaleFor(mode) {
            const s = this.stageEl;
            if (!s || !this.imgW || !this.imgH) return 1;
            const sw = s.clientWidth / this.imgW;
            const sh = s.clientHeight / this.imgH;
            if (mode === 'width')  return sw;
            if (mode === 'height') return sh;
            if (mode === 'real')   return 1;
            return Math.min(sw, sh);
        },
        clampScale(s) { return Math.min(this.maxScale, Math.max(this.minScale, s)); },

        fit(mode) {
            this.measure();
            this.mode = mode;
            this.fitScale = this.baseScaleFor('page');
            this.scale = this.clampScale(this.baseScaleFor(mode));
            this.tx = 0; this.ty = 0;
            this.clampPan();
            this.ready = true;
        },
        refit() {
            if (!this.open) return;
            if (this.mode === 'free') { this.clampPan(); }
            else { this.fit(this.mode); }
        },

        setScale(next, ox, oy) {
            const old = this.scale || 1;
            const ns = this.clampScale(next);
            if (ox !== undefined && oy !== undefined) {
                const k = ns / old;
                this.tx = ox - (ox - this.tx) * k;
                this.ty = oy - (oy - this.ty) * k;
            }
            this.scale = ns;
            this.mode = 'free';
            this.clampPan();
        },
        zoomBy(factor, ox, oy) { this.setScale(this.scale * factor, ox, oy); },

        clampPan() {
            const s = this.stageEl;
            if (!s) return;
            const dw = this.imgW * this.scale, dh = this.imgH * this.scale;
            const maxX = Math.max(0, (dw - s.clientWidth) / 2);
            const maxY = Math.max(0, (dh - s.clientHeight) / 2);
            this.tx = Math.min(maxX, Math.max(-maxX, this.tx));
            this.ty = Math.min(maxY, Math.max(-maxY, this.ty));
        },
        get pct() { return Math.round(this.scale * 100); },
        get canPan() {
            const s = this.stageEl;
            if (!s) return false;
            return (this.imgW * this.scale > s.clientWidth + 1)
                || (this.imgH * this.scale > s.clientHeight + 1);
        },
        get transformStyle() {
            return 'transform: translate(' + this.tx + 'px,' + this.ty + 'px) scale(' + this.scale + ');'
                 + 'visibility:' + (this.ready ? 'visible' : 'hidden') + ';';
        },
        _evOrigin(e) {
            const r = this.stageEl.getBoundingClientRect();
            return { ox: e.clientX - r.left - r.width / 2, oy: e.clientY - r.top - r.height / 2 };
        },

        onWheel(e) {
            if (e.ctrlKey || e.metaKey) {
                const o = this._evOrigin(e);
                this.zoomBy(e.deltaY < 0 ? 1.12 : 0.892, o.ox, o.oy);
            } else {
                this.tx -= e.deltaX; this.ty -= e.deltaY; this.mode = 'free'; this.clampPan();
            }
        },
        onDblClick(e) {
            const o = this._evOrigin(e);
            if (this.pct >= 100) { this.fit('page'); }
            else { this.setScale(1, o.ox, o.oy); }
        },

        onDown(e) {
            if (this.imgEl && this.imgEl.setPointerCapture) {
                try { this.imgEl.setPointerCapture(e.pointerId); } catch (err) {}
            }
            this._ptrs[e.pointerId] = { x: e.clientX, y: e.clientY };
            const ids = Object.keys(this._ptrs);
            if (ids.length === 1) {
                this.dragging = true;
                this.startX = e.clientX; this.startY = e.clientY;
                this.startTx = this.tx; this.startTy = this.ty;
            } else if (ids.length === 2) {
                this.dragging = false;
                this.pinchDist = this._dist();
                this.pinchScale = this.scale;
            }
        },
        onMove(e) {
            if (!(e.pointerId in this._ptrs)) return;
            this._ptrs[e.pointerId] = { x: e.clientX, y: e.clientY };
            const ids = Object.keys(this._ptrs);
            if (ids.length >= 2) {
                e.preventDefault();
                const d = this._dist();
                if (this.pinchDist > 0) {
                    const c = this._center();
                    const r = this.stageEl.getBoundingClientRect();
                    this.setScale(this.pinchScale * (d / this.pinchDist),
                                  c.x - r.left - r.width / 2, c.y - r.top - r.height / 2);
                }
            } else if (this.dragging) {
                if (!this.canPan) return;
                e.preventDefault();
                this.tx = this.startTx + (e.clientX - this.startX);
                this.ty = this.startTy + (e.clientY - this.startY);
                this.clampPan();
            }
        },
        onUp(e) {
            delete this._ptrs[e.pointerId];
            const n = Object.keys(this._ptrs).length;
            if (n < 2) this.pinchDist = 0;
            if (n === 0) this.dragging = false;
        },

        onKey(e) {
            const k = e.key;
            if (k === '+' || k === '=') { e.preventDefault(); this.zoomBy(1.25); }
            else if (k === '-' || k === '_') { e.preventDefault(); this.zoomBy(0.8); }
            else if (k === '0') { e.preventDefault(); this.fit('page'); }
            else if (k === 'ArrowUp')    { e.preventDefault(); this.ty += 60; this.mode='free'; this.clampPan(); }
            else if (k === 'ArrowDown')  { e.preventDefault(); this.ty -= 60; this.mode='free'; this.clampPan(); }
            else if (k === 'ArrowLeft')  { e.preventDefault(); this.tx += 60; this.mode='free'; this.clampPan(); }
            else if (k === 'ArrowRight') { e.preventDefault(); this.tx -= 60; this.mode='free'; this.clampPan(); }
            else if (k === 'PageUp' || k === ',') { e.preventDefault(); this.prev(); }
            else if (k === 'PageDown' || k === '.') { e.preventDefault(); this.next(); }
        },
        trapTab(e) {
            const root = this.$refs.overlay;
            if (!root) return;
            const list = Array.from(
                root.querySelectorAll('button, a[href], [tabindex]:not([tabindex=\'-1\'])')
            ).filter(el => !el.disabled && el.offsetParent !== null);
            if (!list.length) return;
            const first = list[0], last = list[list.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        },

        goTo(index) {
            const clamped = Math.max(0, Math.min(this.planches.length - 1, index));
            if (clamped === this.pageIndex) return;
            this.pageIndex = clamped;
            this.$nextTick(() => {
                this.fit('page');
            });
        },
        next() {
            if (this.pageIndex < this.planches.length - 1) {
                this.goTo(this.pageIndex + 1);
            }
        },
        prev() {
            if (this.pageIndex > 0) {
                this.goTo(this.pageIndex - 1);
            }
        },

        _dist() { const p = Object.values(this._ptrs); return Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y); },
        _center() { const p = Object.values(this._ptrs); return { x: (p[0].x + p[1].x) / 2, y: (p[0].y + p[1].y) / 2 }; }
     }"
     @keydown.escape.window="open && hide()"
     @resize.window="refit()">

    {{-- Déclencheur : vignette + bouton « Lire la BD » --}}
    <button type="button" class="cbd-trigger" @click="show()"
            aria-haspopup="dialog"
            aria-label="{{ __('Ouvrir la bande dessinée') }}{{ $comic['title'] ? ' : '.$comic['title'] : '' }}">
        <img class="cbd-thumb" src="{{ $planche['thumb'] ?? $planche['jpg'] }}"
             alt="" loading="lazy" width="74" height="110" aria-hidden="true">
        <span class="cbd-trigger-text">
            <strong>🐙 {{ __('Lire la BD') }}@if($comic['title']) : {{ $comic['title'] }}@endif</strong>
            <span>{{ __('Une planche pour comprendre en un coup d’œil.') }}</span>
            <span class="cbd-cta">{{ __('Ouvrir le visionneur') }} →</span>
        </span>
    </button>

    {{-- Lightbox --}}
    <template x-teleport="body">
        <div class="cbd-overlay" x-show="open" x-cloak
             x-ref="overlay"
             x-transition.opacity
             role="dialog" aria-modal="true"
             aria-label="{{ $comic['title'] ?: __('Bande dessinée') }}"
             @keydown.tab="trapTab($event)"
             @keydown="onKey($event)">

            <div class="cbd-bar">
                <span class="cbd-caption">{{ $comic['title'] ?: __('Bande dessinée') }}</span>

                <div x-show="planches.length > 1" class="cbd-nav" role="group" aria-label="{{ __('Navigation entre planches') }}">
                    <button type="button" class="cbd-btn cbd-btn-icon" @click="prev()" :disabled="pageIndex === 0"
                            aria-label="{{ __('Planche précédente') }}">‹</button>
                    <span class="cbd-level" aria-live="polite"
                          aria-label="{{ __('Planche courante sur total') }}" x-text="(pageIndex + 1) + ' / ' + planches.length"></span>
                    <button type="button" class="cbd-btn cbd-btn-icon" @click="next()" :disabled="pageIndex === planches.length - 1"
                            aria-label="{{ __('Planche suivante') }}">›</button>
                </div>

                <div class="cbd-zoom" role="group" aria-label="{{ __('Contrôles de zoom') }}">
                    <button type="button" class="cbd-btn cbd-btn-icon" @click="zoomBy(0.8)"
                            aria-label="{{ __('Zoom arrière') }}">−</button>
                    <span class="cbd-level" aria-live="polite"
                          aria-label="{{ __('Niveau de zoom') }}" x-text="pct + ' %'">100 %</span>
                    <button type="button" class="cbd-btn cbd-btn-icon" @click="zoomBy(1.25)"
                            aria-label="{{ __('Zoom avant') }}">+</button>
                    <button type="button" class="cbd-btn" @click="fit('page')"
                            :aria-pressed="mode === 'page' ? 'true' : 'false'"
                            aria-label="{{ __('Ajuster à la page') }}">{{ __('Page') }}</button>
                    <button type="button" class="cbd-btn" @click="fit('width')"
                            :aria-pressed="mode === 'width' ? 'true' : 'false'"
                            aria-label="{{ __('Ajuster à la largeur') }}">{{ __('Largeur') }}</button>
                    <button type="button" class="cbd-btn" @click="fit('height')"
                            :aria-pressed="mode === 'height' ? 'true' : 'false'"
                            aria-label="{{ __('Ajuster à la hauteur') }}">{{ __('Hauteur') }}</button>
                    <button type="button" class="cbd-btn" @click="fit('real')"
                            :aria-pressed="mode === 'real' ? 'true' : 'false'"
                            aria-label="{{ __('Taille réelle 100 %') }}">100&nbsp;%</button>
                    <button type="button" class="cbd-btn cbd-btn-icon" @click="fit('page')"
                            aria-label="{{ __('Réinitialiser le zoom') }}"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg></button>
                </div>

                <div class="cbd-actions">
                    <a class="cbd-btn" :href="current.jpg || current.webp || current.avif" download
                       aria-label="{{ __('Télécharger la planche courante') }}">
                        ⬇ <span>{{ __('Télécharger') }}</span>
                    </a>
                    <button type="button" class="cbd-btn" x-ref="closeBtn" @click="hide()"
                            aria-label="{{ __('Fermer le visionneur') }}">
                        ✕ <span>{{ __('Fermer') }}</span>
                    </button>
                </div>
            </div>

            <div class="cbd-stage" x-ref="stage"
                 @wheel.prevent="onWheel($event)"
                 @click.self="hide()">
                <picture>
                    <template x-if="current.avif">
                        <source :srcset="current.avif" type="image/avif">
                    </template>
                    <template x-if="current.webp">
                        <source :srcset="current.webp" type="image/webp">
                    </template>
                    <img x-ref="img"
                         :src="current.jpg"
                         alt="{{ $comic['alt'] }}"
                         :width="current.width"
                         :height="current.height"
                         loading="lazy"
                         tabindex="0"
                         :style="transformStyle"
                         :class="{ 'is-grabbing': dragging, 'is-grab': !dragging && canPan, 'is-static': !canPan, 'no-anim': dragging || pinchDist }"
                         @load="measure(); if (open) fit(mode);"
                         @dblclick.prevent="onDblClick($event)"
                         @pointerdown="onDown($event)"
                         @pointermove="onMove($event)"
                         @pointerup="onUp($event)"
                         @pointercancel="onUp($event)"
                         aria-label="{{ $comic['alt'] }} {{ __('(double-cliquez pour zoomer, glissez pour déplacer)') }}">
                </picture>
            </div>
        </div>
    </template>
</div>
@endif

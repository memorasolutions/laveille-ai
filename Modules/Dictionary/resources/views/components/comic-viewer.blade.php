{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Visionneur de planche BD réutilisable (standard « bibliothèque de BD »).
    Lightbox Alpine maison (zéro dépendance JS) : <picture> AVIF→WebP→JPEG,
    zoom/pan léger, focus-trap, fermeture Échap + clic fond, télécharger. a11y AA.

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
    }
    .cbd-bar {
        flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between;
        gap: 12px; padding: 12px 16px; color: #fff;
    }
    .cbd-caption { font-weight: 600; font-size: 1rem; line-height: 1.3; }
    .cbd-actions { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; }
    .cbd-btn {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.28);
        border-radius: 8px; padding: 8px 12px; font-size: .9rem; cursor: pointer;
        text-decoration: none; min-height: 40px;
    }
    .cbd-btn:hover, .cbd-btn:focus-visible { background: rgba(255,255,255,.22); color: #fff; }
    .cbd-btn:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
    .cbd-stage {
        flex: 1 1 auto; overflow: auto; display: flex; align-items: flex-start;
        justify-content: center; padding: 0 16px 24px;
    }
    .cbd-stage img {
        max-width: 100%; height: auto; display: block; border-radius: 6px;
        box-shadow: 0 10px 40px rgba(0,0,0,.5); transform-origin: top center;
        transition: transform .2s ease; cursor: zoom-in;
    }
    .cbd-stage.is-zoomed { align-items: flex-start; }
    .cbd-stage.is-zoomed img { cursor: zoom-out; max-width: none; }
    [x-cloak] { display: none !important; }
    @media (prefers-reduced-motion: reduce) {
        .cbd-trigger, .cbd-stage img { transition: none; }
    }
</style>
@endpush
@endonce

<div class="cbd-block"
     x-data="{
        open: false,
        zoomed: false,
        lastFocus: null,
        show() {
            this.lastFocus = document.activeElement;
            this.open = true; this.zoomed = false;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => this.$refs.closeBtn && this.$refs.closeBtn.focus());
        },
        hide() {
            this.open = false; this.zoomed = false;
            document.body.style.overflow = '';
            if (this.lastFocus) this.lastFocus.focus();
        },
        toggleZoom() { this.zoomed = !this.zoomed; }
     }"
     @keydown.escape.window="open && hide()">

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
             x-transition.opacity
             role="dialog" aria-modal="true"
             aria-label="{{ $comic['title'] ?: __('Bande dessinée') }}"
             @click.self="hide()">

            <div class="cbd-bar">
                <span class="cbd-caption">{{ $comic['title'] ?: __('Bande dessinée') }}</span>
                <div class="cbd-actions">
                    @if($comic['download_url'])
                        <a class="cbd-btn" href="{{ $comic['download_url'] }}" download
                           aria-label="{{ __('Télécharger la BD') }}">
                            ⬇ <span>{{ __('Télécharger') }}</span>
                        </a>
                    @endif
                    <button type="button" class="cbd-btn" x-ref="closeBtn" @click="hide()"
                            aria-label="{{ __('Fermer le visionneur') }}">
                        ✕ <span>{{ __('Fermer') }}</span>
                    </button>
                </div>
            </div>

            <div class="cbd-stage" :class="zoomed ? 'is-zoomed' : ''" @click.self="hide()">
                <picture @click="toggleZoom()"
                         @keydown.enter.prevent="toggleZoom()"
                         @keydown.space.prevent="toggleZoom()"
                         tabindex="0" role="button"
                         :aria-label="zoomed ? '{{ __('Dézoomer') }}' : '{{ __('Zoomer') }}'">
                    @if($planche['avif'])<source srcset="{{ $planche['avif'] }}" type="image/avif">@endif
                    @if($planche['webp'])<source srcset="{{ $planche['webp'] }}" type="image/webp">@endif
                    <img src="{{ $planche['jpg'] }}"
                         alt="{{ $comic['alt'] }}"
                         @if($planche['width']) width="{{ $planche['width'] }}" @endif
                         @if($planche['height']) height="{{ $planche['height'] }}" @endif
                         loading="lazy">
                </picture>
            </div>
        </div>
    </template>
</div>
@endif

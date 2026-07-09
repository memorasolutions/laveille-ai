{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Corps partagé du lecteur « page qui tourne » (x-fronttheme::flip-reader).
    Inclus deux fois par le composant parent : une fois teleporté dans <body>
    (mode="modal"), une fois en place (mode="inline") - jamais dupliqué en dur,
    un seul gabarit source pour les deux rendus (DRY).

    Variables attendues : $pages, $title, $mode, $downloadable.
--}}
<div class="fpr-reader"
     :class="{ 'fpr-reader--modal': mode === 'modal', 'fpr-reader--inline': mode !== 'modal' }"
     x-show="mode !== 'modal' || open"
     x-cloak
     x-ref="overlay"
     role="{{ $mode === 'modal' ? 'dialog' : 'region' }}"
     @if($mode === 'modal') aria-modal="true" @endif
     aria-label="{{ $title ?: __('Lecteur de pages') }}"
     @keydown.tab="trapTab($event)"
     @keydown="onKey($event)"
     @click.self="mode === 'modal' && hide()"
     tabindex="-1">

    <div class="fpr-bar">
        @if($title)
            <span class="fpr-caption">{{ $title }}</span>
        @endif

        <div class="fpr-nav" role="group" aria-label="{{ __('Navigation entre les pages') }}">
            <button type="button" class="fpr-btn fpr-btn-icon" @click="prev()" :disabled="current === 1"
                    aria-label="{{ __('Page précédente') }}">‹</button>
            <span class="fpr-level" aria-hidden="true" x-text="current + ' / ' + pageCount">1 / {{ count($pages) }}</span>
            <button type="button" class="fpr-btn fpr-btn-icon" @click="next()" :disabled="current === pageCount"
                    aria-label="{{ __('Page suivante') }}">›</button>
        </div>

        <div class="fpr-actions">
            @if($downloadable)
            <a class="fpr-btn" :href="images[current - 1]" download
               aria-label="{{ __('Télécharger la page courante') }}">
                ⬇ <span>{{ __('Télécharger') }}</span>
            </a>
            @endif
            @if($mode === 'modal')
            <button type="button" class="fpr-btn" x-ref="closeBtn" @click="hide()"
                    aria-label="{{ __('Fermer le lecteur') }}">
                ✕ <span>{{ __('Fermer') }}</span>
            </button>
            @endif
        </div>
    </div>

    <div class="fpr-stage">
        <div class="fpr-book" x-ref="book" :class="{ 'fpr-book--simple': useSimple }">
            @foreach($pages as $i => $p)
                <div class="fpr-page"
                     x-show="!useSimple || current === {{ $i + 1 }}"
                     x-cloak
                     @if($i === 0 || $i === count($pages) - 1) data-density="hard" @endif>
                    {{-- Squelette « papier » + blur-up (LQIP) pendant le chargement -
                         aria-busy et classe --loaded pilotés par pageLoaded[i] (Alpine),
                         mis à jour au @load de l'image pleine résolution. --}}
                    <div class="fpr-page-media"
                         :class="{ 'fpr-page-media--loaded': pageLoaded[{{ $i }}] }"
                         :aria-busy="pageLoaded[{{ $i }}] ? 'false' : 'true'">
                        @if(!empty($p['lqip']))
                        <img class="fpr-page-lqip"
                             src="{{ $p['lqip'] }}"
                             alt="" aria-hidden="true" loading="lazy" decoding="async">
                        @endif
                        <img class="fpr-page-full"
                             src="{{ $p['image'] }}"
                             alt="{{ $p['alt'] ?? '' }}"
                             @if(!empty($p['width'])) width="{{ (int) $p['width'] }}" @endif
                             @if(!empty($p['height'])) height="{{ (int) $p['height'] }}" @endif
                             @if($i === 0)
                             fetchpriority="high"
                             @else
                             loading="lazy"
                             @endif
                             decoding="async"
                             @load="markLoaded({{ $i }})">
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Annonce sobre du changement de page (pas à chaque frame d'animation) --}}
    <div class="fpr-sr-only" aria-live="polite" x-text="liveMsg"></div>
</div>

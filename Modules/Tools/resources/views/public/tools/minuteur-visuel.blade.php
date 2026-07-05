<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@php
    $shareData = $tool->getShareData();

    // Sources de vérité serveur (SEO/AEO + tests Pest) — l'Alpine côté client reprend les mêmes
    // clés/valeurs pour piloter la réactivité (palette/presets/styles), sans dupliquer la logique
    // de rendu SVG dynamique qui reste 100 % côté client (deadline/rAF).
    $mvStyles = [
        ['key' => 'disk', 'label' => 'Disque', 'icon' => '⏱️'],
        ['key' => 'hourglass', 'label' => 'Sablier', 'icon' => '⏳'],
        ['key' => 'ring', 'label' => 'Anneau', 'icon' => '⭕'],
        ['key' => 'flip', 'label' => 'Chiffres', 'icon' => '🔢'],
        ['key' => 'traffic', 'label' => 'Feu de circulation', 'icon' => '🚦'],
    ];
    $mvColors = [
        ['key' => 'red', 'label' => 'Rouge classique', 'hex' => '#991B1B'],
        ['key' => 'teal', 'label' => 'Teal', 'hex' => '#064E5A'],
        ['key' => 'orange', 'label' => 'Orange', 'hex' => '#9A2A06'],
        ['key' => 'violet', 'label' => 'Violet', 'hex' => '#6B21A8'],
        ['key' => 'blue', 'label' => 'Bleu', 'hex' => '#1E40AF'],
    ];
    $mvPresets = [
        ['key' => 'p5', 'label' => '5 min'],
        ['key' => 'p10', 'label' => '10 min'],
        ['key' => 'p15', 'label' => '15 min'],
        ['key' => 'p25', 'label' => '25 min'],
        ['key' => 'p45', 'label' => '45 min'],
        ['key' => 'pomodoro-focus', 'label' => 'Pomodoro 25 min'],
        ['key' => 'pomodoro-break', 'label' => 'Pause 5 min'],
    ];

    // 60 graduations du disque TimeTimer — statiques, calculées une fois côté serveur (DRY,
    // évite un recalcul JS à chaque frame pour un élément qui ne bouge jamais).
    $mvTicks = [];
    for ($i = 0; $i < 60; $i++) {
        $angle = deg2rad($i * 6);
        $isMajor = $i % 5 === 0;
        $outer = 88;
        $inner = $isMajor ? 74 : 80;
        $mvTicks[] = [
            'x1' => round(100 + $outer * sin($angle), 2),
            'y1' => round(100 - $outer * cos($angle), 2),
            'x2' => round(100 + $inner * sin($angle), 2),
            'y2' => round(100 - $inner * cos($angle), 2),
            'major' => $isMajor,
        ];
    }
@endphp

@section('title', $tool->name . ' - ' . config('app.name'))
@section('meta_description', $shareData['meta_description'] ?? 'Minuteur visuel gratuit : disque TimeTimer, sablier, anneau, chiffres ou feu de circulation. Presets, alerte sonore, plein écran. 100 % gratuit, sans compte.')
@section('og_type', $shareData['og_type'] ?? 'website')
@section('og_image', $shareData['og_image'] ?? '')
@section('share_text', $shareData['share_text'] ?? '')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection

@push('head')
<link rel="stylesheet" href="{{ asset('assets/tools/minuteur-visuel/minuteur-visuel.css') }}?v={{ config('version.semver') }}">
<link rel="canonical" href="{{ url('/outils/minuteur-visuel') }}">
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    {{-- #708 : container-fluid (pas .container) — DEUXIÈME EXCEPTION DÉLIBÉRÉE pour cet outil
         visuel. `.container` Bootstrap plafonne LUI-MÊME la largeur (960/1140/1320px selon le
         breakpoint) indépendamment du viewport réel : sur un grand écran (1920px+), col-lg-10
         restait bloqué bien avant d'utiliser l'espace disponible, laissant deux bandes grises
         vides de chaque côté de la carte. `.container-fluid` n'a pas ce plafond (juste un
         padding latéral) ; le plafond raisonnable est repris nous-mêmes via `.mv-outer-container`
         (minuteur-visuel.css, max-width: 1600px) pour éviter une carte démesurée en 4K/ultra-wide.
         Le contenu à l'intérieur de la carte (.mv-wrap, minuteur-visuel.css) reste plafonné à
         640px : élargir le conteneur externe élargit la CARTE (fond/bordure blanche), pas les
         contrôles, qui restent centrés et lisibles — pas d'étirement disgracieux. --}}
    <div class="container-fluid mv-outer-container">
        <div class="row justify-content-center">
            {{-- col-lg-10 (pas col-lg-8 comme generateur-mots-passe/tirage-presentations) — EXCEPTION
                 DÉLIBÉRÉE : le minuteur est un outil visuel (grand cadran central, usage salle de
                 classe/plein écran), pas un outil texte/formulaire où une colonne étroite sert la
                 lisibilité. Voir aussi .mv-dial-zone (minuteur-visuel.css) dont le plafond a été
                 relevé en conséquence, sinon élargir la carte seule ne change rien à la taille
                 visible du cadran. --}}
            <div class="col-lg-10 col-12">
                {{-- #707 : tool-geo (JSON-LD + answer-box) déplacé DANS le conteneur pour respecter
                     la largeur du contenu (auparavant plein-largeur hors .container, cf. blog/show.blade.php). --}}
                @include('tools::public.partials.tool-geo')
                <div class="card shadow-sm tool-fullscreen-target" id="mv-fullscreen-target" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5 mv-wrap"
                         x-data="minuteurVisuel()"
                         x-init="init()"
                         @keydown.space.window="handleSpaceKey($event)"
                         :class="{ 'mv-reduced-motion': reducedMotion }">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->name }}</h1>
                            <div class="d-flex gap-1 align-items-center" style="flex-shrink:0;">
                                @include('tools::partials.fullscreen-btn', ['targetId' => '#mv-fullscreen-target'])
                                @include('tools::partials.share-btn', ['tool' => $tool])
                            </div>
                        </div>
                        <p class="text-muted mb-3">{{ $tool->description }}</p>

                        {{-- Sélecteur de style visuel (5 styles) — classes charte ct-btn (pas de bordure ad hoc) --}}
                        <div role="radiogroup" aria-label="{{ __('Style visuel du minuteur') }}" class="mv-style-selector">
                            @foreach($mvStyles as $s)
                                <button type="button"
                                        role="radio"
                                        class="ct-btn ct-btn-sm"
                                        :class="style === '{{ $s['key'] }}' ? 'ct-btn-primary' : 'ct-btn-outline'"
                                        :aria-checked="(style === '{{ $s['key'] }}').toString()"
                                        @click="setStyle('{{ $s['key'] }}')">
                                    <span aria-hidden="true">{{ $s['icon'] }}</span>
                                    <span>{{ $s['label'] }}</span>
                                </button>
                            @endforeach
                        </div>

                        {{-- Sélecteur de couleur (palette curatée, 5 tons WCAG AAA) — disque et anneau seulement --}}
                        <div role="radiogroup" aria-label="{{ __('Couleur du minuteur') }}" class="mv-color-selector" x-show="supportsColorPalette" x-cloak>
                            @foreach($mvColors as $c)
                                <button type="button"
                                        role="radio"
                                        class="mv-color-btn"
                                        style="background: {{ $c['hex'] }};"
                                        :class="{ 'active': accentColor === '{{ $c['key'] }}' }"
                                        :aria-checked="(accentColor === '{{ $c['key'] }}').toString()"
                                        aria-label="{{ $c['label'] }}"
                                        title="{{ $c['label'] }}"
                                        @click="setColor('{{ $c['key'] }}')"></button>
                            @endforeach
                        </div>

                        {{-- Zone visuelle du cadran actif --}}
                        <div class="mv-dial-zone" :class="'mv-style-' + style">

                            {{-- Anneau de progression --}}
                            <template x-if="style === 'ring'">
                                <div style="position:relative;width:100%;height:100%;">
                                    <svg viewBox="0 0 200 200" role="img" aria-label="{{ __('Anneau de progression du minuteur') }}">
                                        <circle cx="100" cy="100" r="90" class="mv-ring-track"></circle>
                                        <circle cx="100" cy="100" r="90" class="mv-ring-progress"
                                                transform="rotate(-90 100 100)"
                                                :style="'stroke:' + dialColorHex + '; stroke-dasharray:' + ringCircumference + '; stroke-dashoffset:' + ringOffset + ';'"></circle>
                                    </svg>
                                    <div class="mv-center-display" x-text="display" aria-hidden="true"></div>
                                </div>
                            </template>

                            {{-- Disque TimeTimer --}}
                            <template x-if="style === 'disk'">
                                <div style="position:relative;width:100%;height:100%;">
                                    <svg viewBox="0 0 200 200" role="img" aria-label="{{ __('Disque TimeTimer du minuteur') }}">
                                        <circle cx="100" cy="100" r="90" class="mv-disk-face"></circle>
                                        @foreach($mvTicks as $t)
                                            <line x1="{{ $t['x1'] }}" y1="{{ $t['y1'] }}" x2="{{ $t['x2'] }}" y2="{{ $t['y2'] }}" class="{{ $t['major'] ? 'mv-tick mv-tick-major' : 'mv-tick' }}"></line>
                                        @endforeach
                                        <path class="mv-disk-slice" :d="diskPathD" :fill="dialColorHex"></path>
                                        <circle cx="100" cy="100" r="5" class="mv-disk-knob"></circle>
                                    </svg>
                                    <div class="mv-center-display" x-text="display" aria-hidden="true"></div>
                                </div>
                            </template>

                            {{-- Sablier stylisé — sable en dégradé + grain (feTurbulence) + ligne de surface ombrée + verre dégradé --}}
                            <template x-if="style === 'hourglass'">
                                <div style="position:relative;width:100%;height:100%;">
                                    <svg viewBox="0 0 200 260" role="img" aria-label="{{ __('Sablier du minuteur') }}">
                                        <defs>
                                            <linearGradient id="mvSandGradient" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stop-color="#E8C393"></stop>
                                                <stop offset="55%" stop-color="#D4A574"></stop>
                                                <stop offset="100%" stop-color="#B8875A"></stop>
                                            </linearGradient>
                                            <linearGradient id="mvGlassGradient" x1="0" y1="0" x2="1" y2="1">
                                                <stop offset="0%" stop-color="#E5C687"></stop>
                                                <stop offset="100%" stop-color="#8B6914"></stop>
                                            </linearGradient>
                                            <filter id="mvSandGrain" x="-20%" y="-20%" width="140%" height="140%">
                                                <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" result="noise"></feTurbulence>
                                                <feColorMatrix in="noise" type="matrix" values="0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 0.08 0"></feColorMatrix>
                                            </filter>
                                            <clipPath id="mvTopSandClip">
                                                <rect x="0" :y="topSandY" width="200" :height="topSandHeight"></rect>
                                            </clipPath>
                                            <clipPath id="mvBottomSandClip">
                                                <rect x="0" :y="bottomSandY" width="200" :height="bottomSandHeight"></rect>
                                            </clipPath>
                                            {{-- Clips triangulaires — contiennent la texture de grain + la ligne de surface DANS le sablier (sinon un <rect> plein-largeur déborderait des flancs obliques du verre). --}}
                                            <clipPath id="mvTopTriangleClip">
                                                <path d="M 40 20 L 160 20 L 100 130 Z"></path>
                                            </clipPath>
                                            <clipPath id="mvBottomTriangleClip">
                                                <path d="M 100 130 L 40 240 L 160 240 Z"></path>
                                            </clipPath>
                                        </defs>

                                        {{-- Sable haut : dégradé vertical clair→foncé (relief), même hauteur que le clip d'origine --}}
                                        <path d="M 40 20 L 160 20 L 100 130 Z" class="mv-hourglass-sand" clip-path="url(#mvTopSandClip)"></path>
                                        <g clip-path="url(#mvTopSandClip)">
                                            <rect x="0" :y="topSandY" width="200" :height="topSandHeight" class="mv-hourglass-sand-grain" clip-path="url(#mvTopTriangleClip)"></rect>
                                            <rect x="0" :y="topSandY" width="200" height="4" class="mv-hourglass-sand-surface" clip-path="url(#mvTopTriangleClip)"></rect>
                                        </g>

                                        {{-- Sable bas --}}
                                        <path d="M 100 130 L 40 240 L 160 240 Z" class="mv-hourglass-sand" clip-path="url(#mvBottomSandClip)"></path>
                                        <g clip-path="url(#mvBottomSandClip)">
                                            <rect x="0" :y="bottomSandY" width="200" :height="bottomSandHeight" class="mv-hourglass-sand-grain" clip-path="url(#mvBottomTriangleClip)"></rect>
                                            <rect x="0" :y="bottomSandY" width="200" height="4" class="mv-hourglass-sand-surface" clip-path="url(#mvBottomTriangleClip)"></rect>
                                        </g>

                                        <line x1="100" y1="124" x2="100" y2="136" class="mv-hourglass-stream" :class="{ 'is-running': state === 'running' }"></line>
                                        <path d="M 40 20 L 160 20 L 100 130 Z" class="mv-hourglass-frame"></path>
                                        <path d="M 100 130 L 40 240 L 160 240 Z" class="mv-hourglass-frame"></path>
                                        <line x1="30" y1="20" x2="170" y2="20" class="mv-hourglass-frame"></line>
                                        <line x1="30" y1="240" x2="170" y2="240" class="mv-hourglass-frame"></line>

                                        {{-- 3 grains qui tombent visiblement à travers le goulot pendant le décompte —
                                             peints APRÈS le cadre (sinon les traits du cadre, qui convergent exactement
                                             au même point 100,130, recouvraient les particules). --}}
                                        <g class="mv-sand-stream" :class="{ 'is-running': state === 'running' }">
                                            <circle cx="100" cy="130" r="2.2" class="mv-sand-grain-particle mv-sand-grain-particle-1"></circle>
                                            <circle cx="100" cy="130" r="2.2" class="mv-sand-grain-particle mv-sand-grain-particle-2"></circle>
                                            <circle cx="100" cy="130" r="2.2" class="mv-sand-grain-particle mv-sand-grain-particle-3"></circle>
                                        </g>
                                    </svg>
                                    <div class="mv-center-display" x-text="display" aria-hidden="true"></div>
                                </div>
                            </template>

                            {{-- Feu de circulation — #709 : r=28 (pas 34) sur les 3 cercles, cy inchangés
                                 (65/130/195). Avec r=34, le diamètre (68) dépassait l'espacement centre-à-centre
                                 (65) : les cercles se chevauchaient structurellement entre eux, et le chiffre
                                 central (superposé pile sur le cercle du milieu) débordait visuellement sur
                                 la jonction avec le cercle vert. r=28 (diamètre 56) laisse un vrai collier de
                                 fond sombre #374151 visible entre chaque cercle. Voir aussi .mv-style-traffic
                                 .mv-center-display (minuteur-visuel.css) dont le plafond de taille a été
                                 réduit en cohérence, sinon le texte déborderait toujours du cercle rétréci. --}}
                            <template x-if="style === 'traffic'">
                                <div style="position:relative;width:100%;height:100%;">
                                    <svg viewBox="0 0 120 260" role="img" aria-label="{{ __('Feu de circulation du minuteur') }}">
                                        <rect x="20" y="10" width="80" height="240" rx="24" fill="#374151"></rect>
                                        <circle cx="60" cy="65" r="28" class="mv-traffic-circle mv-green" :class="trafficPhase === 'green' ? 'mv-on' : 'mv-off'"></circle>
                                        <circle cx="60" cy="130" r="28" class="mv-traffic-circle mv-yellow" :class="trafficPhase === 'yellow' ? 'mv-on' : 'mv-off'"></circle>
                                        <circle cx="60" cy="195" r="28" class="mv-traffic-circle mv-red" :class="trafficPhase === 'red' ? 'mv-on' : 'mv-off'"></circle>
                                    </svg>
                                    <div class="mv-center-display" x-text="display" aria-hidden="true"></div>
                                </div>
                            </template>

                            {{-- Chiffres / flip --}}
                            <template x-if="style === 'flip'">
                                <div class="mv-flip-display" :class="{ 'mv-flip-pulse': flipPulse }" x-text="display" role="img" aria-label="{{ __('Affichage numérique du minuteur') }}"></div>
                            </template>
                        </div>

                        {{-- Légende du feu de circulation — seuils réels dérivés de la durée choisie
                             (une ligne par couleur, recalculée si l'utilisateur change de durée). --}}
                        <div class="mv-traffic-legend" x-show="style === 'traffic'" x-cloak>
                            <p class="mv-traffic-legend__line">🟢 {{ __('Plus de la moitié du temps') }} — <span x-text="'de ' + trafficTotalFormatted + ' à ' + trafficGreenThreshold"></span></p>
                            <p class="mv-traffic-legend__line">🟡 {{ __('Bientôt fini') }} — <span x-text="'de ' + trafficGreenThreshold + ' à ' + trafficYellowThreshold"></span></p>
                            <p class="mv-traffic-legend__line">🔴 {{ __('Presque terminé') }} — <span x-text="'{{ __('moins de') }} ' + trafficYellowThreshold"></span></p>
                            <p class="mv-traffic-legend__note">{{ __('Le chiffre au centre reste le temps exact restant.') }}</p>
                        </div>

                        {{-- Annonces ARIA sobres (jamais à chaque seconde) --}}
                        <div class="mv-live" aria-live="polite" role="status" x-text="ariaMessage"></div>

                        {{-- Contrôles principaux — classes charte ct-btn (aucune bordure ad hoc) --}}
                        <div class="mv-controls">
                            <button type="button" class="ct-btn ct-btn-outline ct-btn-sm" @click="adjustMinutes(-1)" aria-label="{{ __('Réduire de 1 minute') }}" title="{{ __('-1 min') }}">−1</button>

                            <button type="button" class="ct-btn ct-btn-accent" @click="toggleStartPause()" x-show="state === 'idle' || state === 'paused'">
                                <span aria-hidden="true">▶</span> <span x-text="state === 'paused' ? '{{ __('Reprendre') }}' : '{{ __('Démarrer') }}'"></span>
                            </button>
                            <button type="button" class="ct-btn ct-btn-outline" @click="toggleStartPause()" x-show="state === 'running'">
                                <span aria-hidden="true">⏸</span> {{ __('Pause') }}
                            </button>
                            <button type="button" class="ct-btn ct-btn-accent" @click="toggleStartPause()" x-show="state === 'finished'">
                                <span aria-hidden="true">↻</span> {{ __('Recommencer') }}
                            </button>
                            <button type="button" class="ct-btn ct-btn-outline" @click="reset()" x-show="state !== 'idle'">
                                {{ __('Réinitialiser') }}
                            </button>

                            <button type="button" class="ct-btn ct-btn-outline ct-btn-sm" @click="adjustMinutes(1)" aria-label="{{ __('Ajouter 1 minute') }}" title="{{ __('+1 min') }}">+1</button>

                            <button type="button" class="ct-btn ct-btn-ghost ct-btn-sm" @click="shareCurrentUrl()" aria-label="{{ __('Copier le lien de ce minuteur') }}" title="{{ __('Partager ce réglage') }}">
                                <span aria-hidden="true">🔗</span> <span x-text="shareCopied ? '{{ __('Copié !') }}' : '{{ __('Partager') }}'"></span>
                            </button>
                        </div>

                        {{-- Présélections nommées — classes charte ct-btn --}}
                        <div class="mv-presets" role="group" aria-label="{{ __('Présélections de durée') }}">
                            @foreach($mvPresets as $p)
                                <button type="button"
                                        class="ct-btn ct-btn-outline ct-btn-sm"
                                        :disabled="state === 'running'"
                                        @click="applyPreset('{{ $p['key'] }}')">{{ $p['label'] }}</button>
                            @endforeach
                        </div>

                        {{-- Durée personnalisée — saisie exacte en minutes, hors présélections --}}
                        <div class="mv-custom-time" role="group" aria-label="{{ __('Durée personnalisée') }}">
                            <label for="mvCustomMinutes">{{ __('Durée personnalisée (minutes)') }}</label>
                            <input type="number"
                                   id="mvCustomMinutes"
                                   min="1"
                                   max="180"
                                   x-model.number="customMinutes"
                                   :disabled="state === 'running'"
                                   @keydown.enter.prevent="applyCustomMinutes()">
                            <button type="button"
                                    class="ct-btn ct-btn-primary ct-btn-sm"
                                    :disabled="state === 'running'"
                                    @click="applyCustomMinutes()">{{ __('Définir') }}</button>
                        </div>

                        {{-- Réglages accessibilité --}}
                        <details class="mv-settings">
                            <summary>
                                <span>{{ __('Réglages') }}</span>
                                <span class="mv-settings__chevron" aria-hidden="true">▼</span>
                            </summary>
                            <div class="mv-settings__body">
                                <div class="mv-settings__row">
                                    <label for="mvSoundToggle">{{ __('Alerte sonore') }}</label>
                                    <input type="checkbox" id="mvSoundToggle" x-model="soundEnabled" @change="toggleSound()" style="display:inline-block !important; width:20px; height:20px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                </div>
                                <div class="mv-settings__row">
                                    <label for="mvReducedMotionToggle">{{ __('Réduire les animations') }}</label>
                                    <input type="checkbox" id="mvReducedMotionToggle" x-model="reducedMotion" @change="toggleReducedMotion()" style="display:inline-block !important; width:20px; height:20px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                </div>
                                <div class="mv-settings__row">
                                    <label for="mvWarningThreshold">{{ __('Alerte « bientôt fini » (secondes avant la fin)') }}</label>
                                    <span style="display:flex; align-items:center; gap:.4rem;">
                                        <input type="number" id="mvWarningThreshold" min="0" max="600" x-model.number="warningThresholdSec" @change="setWarningThreshold(warningThresholdSec)">
                                        <span aria-hidden="true" style="font-size:.85rem; color:var(--c-dark, #1A1D23); opacity:.75;">{{ __('s') }}</span>
                                    </span>
                                </div>
                            </div>
                        </details>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('fronttheme::partials.tools-newsletter-cta', ['toolSource' => 'minuteur-visuel'])
@endsection

@push('scripts')
<script src="{{ asset('assets/tools/minuteur-visuel/minuteur-visuel-core.js') }}?v={{ config('version.semver') }}" defer></script>
@endpush

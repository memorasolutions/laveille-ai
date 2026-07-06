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
    // #776-781 : « orange » (#9A2A06, une rouille très rouge) retiré - perçu par l'utilisateur
    // comme un second rouge redondant avec le vrai rouge classique TimeTimer. Remplacé par
    // « Rose poudré » + ajout de « Sable pâle » : 2 teintes pâles distinctes, confirmées
    // tendance 2026 par veille pp_search (pastel poudré + sable/beige), sans chevaucher les
    // familles de teintes déjà couvertes (teal/violet/bleu).
    $mvColors = [
        ['key' => 'red', 'label' => 'Rouge classique', 'hex' => '#991B1B'],
        ['key' => 'teal', 'label' => 'Teal', 'hex' => '#064E5A'],
        ['key' => 'violet', 'label' => 'Violet', 'hex' => '#6B21A8'],
        ['key' => 'blue', 'label' => 'Bleu', 'hex' => '#1E40AF'],
        ['key' => 'rose', 'label' => 'Rose poudré', 'hex' => '#E8A9AE'],
        ['key' => 'sable', 'label' => 'Sable pâle', 'hex' => '#DCC3A0'],
    ];
    // #751-758 : 45 min retiré (demande utilisateur) ; Pomodoro/Pause déplacés dans leur propre
    // groupe (mvPresetsPomodoro) - deux lignes DÉLIBÉRÉES plutôt qu'un retour à la ligne accidentel
    // dépendant de la largeur d'écran.
    $mvPresets = [
        ['key' => 'p5', 'label' => '5 min'],
        ['key' => 'p10', 'label' => '10 min'],
        ['key' => 'p15', 'label' => '15 min'],
        ['key' => 'p25', 'label' => '25 min'],
    ];
    $mvPresetsPomodoro = [
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

    // #765-770 : 9 grains de sable individuels (au lieu de 3 identiques synchronisés) — chute
    // VERTICALE nette (pas de dérive latérale, qui donnait un nuage de points dispersés en
    // diagonale plutôt qu'un filet cohérent, signalé par l'utilisateur) traversant tout le
    // goulot jusque dans le bulbe du bas (avant : seulement ~13px de course, invisible/inutile —
    // désormais ~55px, bien visible entrant réellement DANS le tas du bas). Décalage
    // vertical/temporel pré-calculé côté serveur (motif déterministe, même logique que les
    // graduations ci-dessus, pas de hasard JS) pour que plusieurs grains soient visibles à des
    // hauteurs différentes du même filet à tout instant, comme un vrai écoulement.
    $mvSandGrains = [];
    for ($i = 0; $i < 9; $i++) {
        $mvSandGrains[] = [
            'jitter' => round((($i % 3) - 1) * 0.35, 2),
            'radius' => round(1.9 + ($i % 3) * 0.3, 2),
            'delay' => round($i * 0.15, 2),
            'duration' => round(1.1 + ($i % 3) * 0.15, 2),
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
    {{-- #713/#714 (2026-07-05) : gabarit RÉALIGNÉ sur les autres outils gratuits (demande explicite
         de l'utilisateur, qui a rejeté les exceptions #706/#708/#711 ci-dessous). `.container` +
         col-lg-10 — même largeur que TOUS les outils "carte unique" du site (calculatrice-taxes,
         code-qr, constructeur-prompts, generateur-mots-passe, tirage-presentations, anonymiseur,
         generateur-equipes, liens-google, mots-croises, roue-tirage). #714 : l'espace vide restant
         à col-lg-8 était identique (au pixel près) sur generateur-mots-passe/tirage-presentations —
         pas un bug du minuteur mais le gabarit standard ; élargi SITE-WIDE (6 fichiers col-lg-8
         portés à col-lg-10) plutôt qu'une nouvelle exception isolée. --}}
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-12">
                {{-- #707 : tool-geo (JSON-LD + answer-box) déplacé DANS le conteneur pour respecter
                     la largeur du contenu (auparavant plein-largeur hors .container, cf. blog/show.blade.php). --}}
                @include('tools::public.partials.tool-geo')
                <div class="card shadow-sm tool-fullscreen-target" id="mv-fullscreen-target" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5 mv-wrap"
                         x-data="minuteurVisuel({{ auth()->check() ? 'true' : 'false' }})"
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

                        {{-- #746 : bandeau visible (pas caché dans le panneau "Réglages" replié) quand les
                             animations sont réduites - signalé par l'utilisateur (grains de sable "invisibles"
                             malgré plusieurs correctifs CSS confirmés déployés) : cause réelle trouvée, la case
                             "Réduire les animations" est cochée AUTOMATIQUEMENT si le système/navigateur
                             déclare `prefers-reduced-motion: reduce` (comportement d'accessibilité correct et
                             volontaire, cf. minuteur-visuel-core.js ligne ~125), ce qui coupe TOUTES les
                             animations (grains, anneau, disque, chiffres) via `!important` sans aucune
                             indication visuelle - l'utilisateur ne pouvait pas savoir pourquoi. On ne force
                             PAS l'animation à revenir (ça casserait l'accessibilité pour qui a réellement
                             besoin de moins de mouvement) : on rend seulement l'état visible et réversible en
                             un clic, sans creuser dans "Réglages". --}}
                        <div class="mv-motion-hint" x-show="reducedMotion" x-cloak>
                            <span aria-hidden="true">ⓘ</span>
                            {{ __('Animations réduites car votre système ou navigateur demande « moins de mouvement ». Les grains, l\'anneau, le disque et les chiffres ne bougent pas.') }}
                            <button type="button" @click="reducedMotion = false; toggleReducedMotion()">
                                {{ __('Réactiver les animations pour cet outil') }}
                            </button>
                        </div>

                        {{-- Sélecteur de couleur (palette curatée, 5 tons WCAG AAA) — disque et anneau seulement.
                             #742 : + 1 couleur personnalisée (n'importe quel hex) - le contraste du texte
                             se recalcule automatiquement (diskAccentTextColor), pas de risque WCAG même
                             si la couleur choisie est pâle. --}}
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
                            <label class="mv-color-btn mv-color-btn--custom"
                                   :class="{ 'active': accentColor === 'custom' }"
                                   :style="'background:' + customColorHex + ';'"
                                   title="{{ __('Couleur personnalisée') }}">
                                <input type="color"
                                       class="mv-color-custom-input"
                                       :value="customColorHex"
                                       @input="setCustomColor($event.target.value)"
                                       @change="persistCustomColorHistory($event.target.value)"
                                       aria-label="{{ __('Couleur personnalisée (choisir n\'importe quelle teinte)') }}">
                                <span class="mv-color-btn--custom-icon"
                                      aria-hidden="true"
                                      x-show="accentColor !== 'custom'"
                                      :style="'color:' + customSwatchTextColor">+</span>
                            </label>
                        </div>

                        {{-- #816-820 : favoris/défaut/récentes consolidés dans un repli COMPACT, replié par
                             défaut - empilés en rangées séparées, ils ajoutaient ~200px avant même d'arriver
                             au cadran (signalé "beaucoup trop haut" par l'utilisateur, capture à l'appui).
                             Choix retenu après veille pp_search : disclosure natif (cohérent avec le pattern
                             "Réglages" déjà utilisé plus bas sur cette page), pas un popover ancré - moins de
                             risque d'introduire une nouvelle classe de bugs d'interaction (positionnement,
                             clic extérieur, focus trap) après plusieurs rounds de correctifs CSS aujourd'hui. --}}
                        <details class="mv-color-manager" x-show="supportsColorPalette" x-cloak>
                            <summary>
                                <span aria-hidden="true">★</span>
                                {{ __('Favoris, couleur par défaut, récentes') }}
                                <span class="mv-color-manager__chevron" aria-hidden="true">▼</span>
                            </summary>
                            <div class="mv-color-manager__body">
                                {{-- #787-792 : étoile favoris couleur (max 2) - même bascule explicite que les
                                     durées épinglées (customDurations), distincte de l'historique roulant
                                     automatique des couleurs personnalisées récentes ci-dessous. --}}
                                <div class="mv-color-favorite-toggle" x-show="isAuthenticated" x-cloak>
                                    <button type="button"
                                            class="mv-pin-btn"
                                            :disabled="!isCurrentColorPinnable"
                                            @click="toggleFavoriteColor()"
                                            :aria-label="isCurrentColorFavorite ? '{{ __('Retirer cette couleur des favoris') }}' : '{{ __('Ajouter cette couleur aux favoris') }}'"
                                            :title="isCurrentColorFavorite ? '{{ __('Retirer cette couleur des favoris') }}' : '{{ __('Ajouter cette couleur aux favoris (2 maximum)') }}'"
                                            :class="{ 'active': isCurrentColorFavorite }">
                                        <span aria-hidden="true" x-text="isCurrentColorFavorite ? '★' : '☆'"></span>
                                    </button>
                                    <span class="mv-color-favorite-toggle__label">{{ __('Épingler cette couleur comme favorite') }}</span>
                                </div>

                                {{-- #806-811 : couleur par défaut du compte - action explicite distincte des
                                     favoris (qui servent à un accès rapide entre 2 teintes) : le défaut est LA
                                     teinte qui s'applique automatiquement sur tout nouvel appareil connecté. --}}
                                <div class="mv-color-favorite-toggle" x-show="isAuthenticated" x-cloak>
                                    <button type="button"
                                            class="ct-btn ct-btn-outline ct-btn-xs"
                                            :disabled="isCurrentColorDefault"
                                            @click="setDefaultColor()">
                                        <span aria-hidden="true" x-show="isCurrentColorDefault">✓</span>
                                        <span x-text="isCurrentColorDefault ? '{{ __('Couleur par défaut') }}' : '{{ __('Définir comme couleur par défaut') }}'"></span>
                                    </button>
                                </div>
                                <template x-if="isAuthenticated && favoriteColors.length > 0">
                                    <div class="mv-favorite-colors" aria-label="{{ __('Couleurs favorites') }}">
                                        <span class="mv-recent-colors-label">{{ __('Favoris :') }}</span>
                                        <template x-for="(hex, i) in favoriteColors" :key="'fav'+i">
                                            <span class="mv-favorite-color-chip">
                                                <button type="button"
                                                        class="mv-color-btn mv-color-btn-recent"
                                                        :style="'background:' + hex + ';'"
                                                        :class="{ 'active': accentHex.toLowerCase() === hex.toLowerCase() }"
                                                        :aria-label="hex"
                                                        :title="hex"
                                                        @click="applyFavoriteColor(hex)"></button>
                                                <button type="button"
                                                        class="mv-favorite-color-remove"
                                                        @click="removeFavoriteColor(hex)"
                                                        :aria-label="'{{ __('Retirer') }} ' + hex"
                                                        title="{{ __('Retirer') }}">&times;</button>
                                            </span>
                                        </template>
                                    </div>
                                </template>

                                {{-- #744-750 : couleurs personnalisées récentes (connectés) + incitation à se
                                     connecter (invités) - le rappel serveur (users.tool_preferences) évite de
                                     perdre ses teintes personnalisées d'un appareil/navigateur à l'autre. --}}
                                <template x-if="isAuthenticated && recentCustomColors.length > 0">
                                    <div class="mv-recent-colors" aria-label="{{ __('Couleurs personnalisées récentes') }}">
                                        <span class="mv-recent-colors-label">{{ __('Récentes :') }}</span>
                                        <template x-for="(hex, i) in recentCustomColors" :key="'recent'+i">
                                            <button type="button"
                                                    class="mv-color-btn mv-color-btn-recent"
                                                    :style="'background:' + hex + ';'"
                                                    :class="{ 'active': accentColor === 'custom' && customColorHex.toLowerCase() === hex.toLowerCase() }"
                                                    :aria-label="hex"
                                                    :title="hex"
                                                    @click="setCustomColor(hex)"></button>
                                        </template>
                                    </div>
                                </template>
                                <div class="mv-login-hint" x-show="!isAuthenticated" x-cloak>
                                    {{ __('Connectez-vous pour retrouver vos couleurs personnalisées, vos favoris et votre couleur par défaut sur tous vos appareils.') }}
                                    <a href="{{ route('login') }}">{{ __('Se connecter') }}</a>
                                </div>
                            </div>
                        </details>

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
                                        {{-- Graduations (couche 1, ton gris) — visibles sur la zone blanche restante,
                                             comme avant. Peintes AVANT le secteur : recouvertes là où il progresse. --}}
                                        @foreach($mvTicks as $t)
                                            <line x1="{{ $t['x1'] }}" y1="{{ $t['y1'] }}" x2="{{ $t['x2'] }}" y2="{{ $t['y2'] }}" class="{{ $t['major'] ? 'mv-tick mv-tick-major' : 'mv-tick' }}"></line>
                                        @endforeach
                                        <path class="mv-disk-slice" :d="diskPathD" :fill="dialColorHex"></path>
                                        {{-- #718 : graduations (couche 2, ton clair) peintes APRÈS le secteur — signalé
                                             par l'utilisateur (capture 2026-07-05_13-11-14.jpg, graduations invisibles
                                             sous la couleur qui progresse). Identiques en position à la couche 1, mais
                                             en ton clair (minuteur-visuel.css) : invisibles sur la face blanche (même
                                             teinte que le fond, aucune graduation dupliquée visible) et nettement
                                             visibles sur les 5 teintes sombres de la palette — sans logique JS par
                                             graduation ni mix-blend-mode (support navigateur incertain sur SVG). --}}
                                        @foreach($mvTicks as $t)
                                            <line x1="{{ $t['x1'] }}" y1="{{ $t['y1'] }}" x2="{{ $t['x2'] }}" y2="{{ $t['y2'] }}" class="{{ $t['major'] ? 'mv-tick-overlay mv-tick-overlay-major' : 'mv-tick-overlay' }}"></line>
                                        @endforeach
                                        <circle cx="100" cy="100" r="5" class="mv-disk-knob"></circle>
                                        {{-- #740 : clipPath objectBoundingBox (coordonnées 0-1) partagé par le calque de
                                             texte clair ci-dessous - un clip-path CSS classique interprète ses coordonnées
                                             en pixels réels du div ciblé, PAS relatif à ce viewBox 0-200 ; objectBoundingBox
                                             est le seul mécanisme natif qui recale automatiquement sur la taille réelle
                                             du cadran (fluide selon l'écran). --}}
                                        <defs>
                                            <clipPath id="mvDiskTextClip" clipPathUnits="objectBoundingBox">
                                                <path :d="diskPathDNormalized"></path>
                                            </clipPath>
                                        </defs>
                                    </svg>
                                    {{-- #740/#741 : double calque au lieu d'un mix-blend-mode (teinte inversée
                                         émergente, pas le "blanc sur couleur / noir sur blanc" demandé). Calque du bas
                                         TOUJOURS foncé (visible partout où le secteur coloré n'a pas encore progressé -
                                         la face blanche du cadran). Calque du haut découpé EXACTEMENT sur le secteur
                                         coloré (même tracé que .mv-disk-slice) : couleur choisie AUTOMATIQUEMENT selon
                                         le contraste réel contre l'accent (blanc par défaut, mais foncé si l'accent
                                         est trop pâle pour rester lisible en blanc - curatée ou personnalisée). --}}
                                    <div class="mv-center-display mv-center-display--base" x-text="display" aria-hidden="true"></div>
                                    <div class="mv-center-display mv-center-display--accent"
                                         x-text="display"
                                         aria-hidden="true"
                                         :style="'clip-path:url(#mvDiskTextClip); color:' + diskAccentTextColor"></div>
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

                                        {{-- #765-770 : 9 grains qui tombent à travers le goulot pendant le décompte,
                                             jitter/taille/vitesse variables (au lieu de 3 identiques synchronisés) —
                                             peints APRÈS le cadre (sinon les traits du cadre, qui convergent exactement
                                             au même point 100,130, recouvraient les particules). --}}
                                        <g class="mv-sand-stream" :class="{ 'is-running': state === 'running' }">
                                            @foreach($mvSandGrains as $g)
                                                <circle cx="100" cy="130" r="{{ $g['radius'] }}"
                                                        class="mv-sand-grain-particle"
                                                        style="--gx: {{ $g['jitter'] }}px; --gd: {{ $g['delay'] }}s; --gdur: {{ $g['duration'] }}s;"></circle>
                                            @endforeach
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

                            {{-- Chiffres / flip — #759-764 : fond et texte personnalisables (même palette et
                                 même fonction de contraste WCAG AAA automatique que le disque/anneau). --}}
                            <template x-if="style === 'flip'">
                                <div class="mv-flip-display"
                                     :style="'background:' + dialColorHex + '; color:' + flipTextColor + ';'"
                                     x-text="display"
                                     role="img"
                                     aria-label="{{ __('Affichage numérique du minuteur') }}"></div>
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

                        {{-- Séparateur discret entre le cadran (le "spectacle") et les contrôles (l'"outil") —
                             hiérarchie visuelle claire sans découpage en colonnes. --}}
                        <hr class="mv-divider">

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
                            {{-- #751-758 : jusqu'à 2 durées personnalisées épinglées (connectés) - bascule
                                 étoile (pas d'écrasement silencieux), même architecture que les couleurs
                                 récentes (users.tool_preferences, clé custom_durations). --}}
                            <template x-if="isAuthenticated">
                                <template x-for="minutes in customDurations" :key="'dur'+minutes">
                                    <span class="mv-preset-pinned">
                                        <button type="button"
                                                class="ct-btn ct-btn-outline ct-btn-sm"
                                                :disabled="state === 'running'"
                                                @click="applyCustomDuration(minutes)"
                                                x-text="minutes + ' {{ __('min') }}'"></button>
                                        <button type="button"
                                                class="mv-preset-pinned__remove"
                                                @click="removeCustomDuration(minutes)"
                                                :aria-label="'{{ __('Retirer') }} ' + minutes + ' {{ __('min') }}'"
                                                title="{{ __('Retirer') }}">&times;</button>
                                    </span>
                                </template>
                            </template>
                        </div>

                        {{-- Pomodoro/Pause — groupe déliberément distinct (pas un retour à la ligne
                             accidentel dépendant de la largeur d'écran, #751-758). --}}
                        <div class="mv-presets" role="group" aria-label="{{ __('Présélections Pomodoro') }}">
                            @foreach($mvPresetsPomodoro as $p)
                                <button type="button"
                                        class="ct-btn ct-btn-outline ct-btn-sm"
                                        :disabled="state === 'running'"
                                        @click="applyPreset('{{ $p['key'] }}')">
                                    @if($p['key'] === 'pomodoro-focus')
                                        <span x-text="'{{ __('Pomodoro') }} ' + pomodoroFocusMin + ' {{ __('min') }}'">{{ $p['label'] }}</span>
                                    @else
                                        <span x-text="'{{ __('Pause') }} ' + pomodoroBreakMin + ' {{ __('min') }}'">{{ $p['label'] }}</span>
                                    @endif
                                </button>
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
                            <button type="button"
                                    class="mv-pin-btn"
                                    x-show="isAuthenticated"
                                    x-cloak
                                    :disabled="!isCustomMinutesPinnable"
                                    @click="pinCurrentDuration()"
                                    :aria-label="isCurrentDurationPinned ? '{{ __('Désépingler cette durée') }}' : '{{ __('Épingler cette durée') }}'"
                                    :title="isCurrentDurationPinned ? '{{ __('Désépingler cette durée') }}' : '{{ __('Épingler cette durée (2 maximum)') }}'"
                                    :class="{ 'active': isCurrentDurationPinned }">
                                <span aria-hidden="true" x-text="isCurrentDurationPinned ? '★' : '☆'"></span>
                            </button>
                        </div>
                        <div class="mv-login-hint" x-show="!isAuthenticated" x-cloak>
                            {{ __('Connectez-vous pour épingler jusqu\'à 2 durées personnalisées et les retrouver sur tous vos appareils.') }}
                            <a href="{{ route('login') }}">{{ __('Se connecter') }}</a>
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
                                {{-- #712 : durées Pomodoro configurables — pilotent les presets "Pomodoro"/"Pause"
                                     ci-dessus (défaut 25/5 classique, persistées localStorage comme le reste
                                     de Réglages). --}}
                                <div class="mv-settings__row">
                                    <label for="mvPomodoroFocus">{{ __('Durée du focus Pomodoro (minutes)') }}</label>
                                    <input type="number" id="mvPomodoroFocus" min="1" max="120" x-model.number="pomodoroFocusMin" @change="setPomodoroFocus(pomodoroFocusMin)">
                                </div>
                                <div class="mv-settings__row">
                                    <label for="mvPomodoroBreak">{{ __('Durée de la pause Pomodoro (minutes)') }}</label>
                                    <input type="number" id="mvPomodoroBreak" min="1" max="30" x-model.number="pomodoroBreakMin" @change="setPomodoroBreak(pomodoroBreakMin)">
                                </div>

                                {{-- #797-801 : seuils du feu de circulation - profils préréglés en 1 clic
                                     (option la plus facile confirmée par veille pp_search 2026), repli
                                     "Personnalisé" avec 2 champs % pour qui veut de la précision. --}}
                                <div class="mv-settings__row mv-settings__row--stack" x-show="style === 'traffic' && isAuthenticated" x-cloak>
                                    <label id="mvTrafficProfilesLabel">{{ __('Seuils du feu de circulation') }}</label>
                                    <div class="mv-traffic-profiles" role="radiogroup" aria-labelledby="mvTrafficProfilesLabel">
                                        <template x-for="(profile, key) in trafficProfiles" :key="key">
                                            <button type="button"
                                                    role="radio"
                                                    class="ct-btn ct-btn-outline ct-btn-xs"
                                                    :class="{ 'ct-btn-primary': trafficProfile === key }"
                                                    :aria-checked="(trafficProfile === key).toString()"
                                                    @click="customTrafficOpen = false; setTrafficProfile(key)"
                                                    x-text="profile.label"></button>
                                        </template>
                                        {{-- Reflète uniquement l'état RÉEL (trafficProfile), jamais le fait que le
                                             panneau soit ouvert - sinon 2 boutons du groupe peuvent apparaître actifs
                                             en même temps tant qu'on édite sans avoir cliqué "Appliquer" (bug détecté
                                             en vérification visuelle #797-801). Le panneau ouvert est déjà signalé
                                             par sa propre visibilité (x-show ci-dessous), pas besoin de doubler l'état
                                             sur le bouton du segmented control. --}}
                                        <button type="button"
                                                role="radio"
                                                class="ct-btn ct-btn-outline ct-btn-xs"
                                                :class="{ 'ct-btn-primary': trafficProfile === 'custom' }"
                                                :aria-checked="(trafficProfile === 'custom').toString()"
                                                @click="openCustomTrafficFields()">{{ __('Personnalisé') }}</button>
                                    </div>
                                    <div class="mv-traffic-custom" x-show="customTrafficOpen || trafficProfile === 'custom'" x-cloak>
                                        <label for="mvTrafficGreen">{{ __('Vert au-dessus de (%)') }}</label>
                                        <input type="number" id="mvTrafficGreen" min="2" max="99" x-model.number="customTrafficGreen">
                                        <label for="mvTrafficYellow">{{ __('Jaune au-dessus de (%)') }}</label>
                                        <input type="number" id="mvTrafficYellow" min="1" max="98" x-model.number="customTrafficYellow">
                                        <button type="button"
                                                class="ct-btn ct-btn-primary ct-btn-xs"
                                                @click="setTrafficThresholdsCustom(customTrafficGreen, customTrafficYellow)">{{ __('Appliquer') }}</button>
                                    </div>
                                    {{-- #802-805 : confirmation immédiate colocalisée avec les boutons - le feu
                                         lui-même reste vert tant que le décompte n'a pas commencé (100% restant
                                         dépasse même le seuil vert le plus haut, 70%), donc cliquer un profil ne
                                         change RIEN visuellement au feu avant que le minuteur ne tourne. Sans cette
                                         ligne, l'utilisateur ne voit aucune preuve que le clic a fait quelque chose
                                         (signalé comme "les boutons ne fonctionnent pas" alors que le réglage était
                                         bien appliqué et persisté). --}}
                                    <p class="mv-traffic-applied-hint" aria-live="polite">
                                        <span aria-hidden="true">✓</span>
                                        <span x-text="'{{ __('Appliqué') }} : {{ __('vert au-dessus de') }} ' + trafficGreenThreshold + ', {{ __('jaune de') }} ' + trafficYellowThreshold + ' {{ __('à') }} ' + trafficGreenThreshold + ', {{ __('rouge en dessous de') }} ' + trafficYellowThreshold"></span>
                                    </p>
                                </div>
                                <div class="mv-login-hint" x-show="style === 'traffic' && !isAuthenticated" x-cloak>
                                    {{ __('Connectez-vous pour choisir vos propres seuils du feu de circulation.') }}
                                    <a href="{{ route('login') }}">{{ __('Se connecter') }}</a>
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

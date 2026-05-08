{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Bouton DRY : ajouter / retirer un outil du comparateur (sticky bar).
    Usage : <x-directory::compare-toggle :tool="$tool" :variant="'icon'|'pill'" />

    S89.5 refonte : circle 32x32 (M3 chip selectable) sans cadre square.
    variant icon : circle 32x32 vide (border 2px teal) -> rempli teal solid + ✓ blanc
    variant pill : pill 44 min-height pour show.blade.php
--}}
@props(['tool', 'variant' => 'icon'])

@php
    $thumbUrl = null;
    if (!empty($tool->screenshot)) {
        $thumbUrl = str_starts_with($tool->screenshot, 'http')
            ? $tool->screenshot
            : asset($tool->screenshot);
    } elseif (!empty($tool->url)) {
        $host = parse_url($tool->url, PHP_URL_HOST);
        if ($host) $thumbUrl = "https://www.google.com/s2/favicons?domain={$host}&sz=64";
    }
@endphp

{{-- CSS deplaces vers compare-bar.blade.php (@once) car ce composant
     n'est pas toujours inclus (index.blade utilise bouton inline avec
     les classes mais sans le composant). Compare-bar EST inclus partout
     via x-directory::compare-bar dans index/show/compare. --}}

<button type="button"
        x-data
        :class="{ 'is-active': $store.compare.has({{ (int) $tool->id }}) }"
        class="lv-cmp-toggle lv-cmp-toggle--{{ $variant }}"
        data-cmp-card-id="{{ (int) $tool->id }}"
        @click.stop.prevent="$store.compare.toggle({{ (int) $tool->id }}, @js($tool->name), @js($thumbUrl)); $store.compare.bounce({{ (int) $tool->id }})"
        :aria-pressed="$store.compare.has({{ (int) $tool->id }}) ? 'true' : 'false'"
        :aria-label="$store.compare.has({{ (int) $tool->id }}) ? '{{ __('Retirer du comparateur') }}' : '{{ __('Ajouter au comparateur') }}'"
        title="{{ __('Comparer cet outil') }}">
    @if($variant === 'pill')
        <span x-show="!$store.compare.has({{ (int) $tool->id }})">📊 {{ __('Comparer') }}</span>
        <span x-show="$store.compare.has({{ (int) $tool->id }})" x-cloak>✓ {{ __('Sélectionné') }}</span>
    @else
        <span x-show="$store.compare.has({{ (int) $tool->id }})" x-cloak aria-hidden="true" style="color:#fff;">✓</span>
    @endif
</button>

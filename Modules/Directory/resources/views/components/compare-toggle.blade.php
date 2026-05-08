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

@once
<style>
    /* ─── Variant icon : circle 32x32 ancrée coin haut-droit (Material Design 3 chip selectable) ─── */
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

    /* ─── Variant pill (show.blade.php fiche outil) ─── */
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

    /* ─── Card selectable state (état sélectionné holistique) ─── */
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
        pointer-events: none; /* CRITIQUE : préserve clics sur liens/boutons internes */
        z-index: 2;
    }
    .rt-card:focus-within .lv-cmp-toggle--icon { box-shadow: 0 4px 12px rgba(6, 78, 90, 0.25); }

    @media (prefers-reduced-motion: reduce) {
        .rt-card { transition: none; }
        .lv-cmp-toggle--icon, .lv-cmp-toggle--pill { transition: none; }
    }
</style>
@endonce

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

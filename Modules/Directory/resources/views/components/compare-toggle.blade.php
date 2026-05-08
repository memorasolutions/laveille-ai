{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Bouton DRY : ajouter / retirer un outil du comparateur (sticky bar).
    Usage : <x-directory::compare-toggle :tool="$tool" :variant="'icon'|'pill'" />

    S88 bonifs : 44×44 AAA target size, passe thumb (favicon ou screenshot) au store,
    bounce micro-anim sur la card source.
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
    .lv-cmp-toggle {
        background: #fff;
        border: 1.5px solid var(--c-border, #E5E7EB);
        color: var(--c-text-muted, #52586a);
        cursor: pointer;
        font-weight: 700;
        transition: all 0.15s, transform 0.2s;
        text-decoration: none !important;
    }
    .lv-cmp-toggle:hover { border-color: var(--c-primary, #064E5A); color: var(--c-primary, #064E5A); transform: translateY(-1px); }
    .lv-cmp-toggle:focus-visible { outline: 2px solid var(--c-primary, #064E5A); outline-offset: 2px; }
    .lv-cmp-toggle.is-active {
        background: var(--c-primary, #064E5A);
        border-color: var(--c-primary, #064E5A);
        color: #fff;
    }
    .lv-cmp-toggle.is-active:hover { background: #053f49; border-color: #053f49; color: #fff; }
    .lv-cmp-toggle--icon {
        width: 44px;
        min-width: 44px;
        height: 44px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        line-height: 1;
        padding: 0;
    }
    .lv-cmp-toggle--pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        min-height: 44px;
        border-radius: 50px;
        font-size: 13px;
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
        <span x-show="!$store.compare.has({{ (int) $tool->id }})" aria-hidden="true">☐</span>
        <span x-show="$store.compare.has({{ (int) $tool->id }})" x-cloak aria-hidden="true">☑</span>
    @endif
</button>

{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Bouton DRY : ajouter / retirer un outil du comparateur (sticky bar).
    Usage : <x-directory::compare-toggle :tool="$tool" :variant="'icon'|'pill'" />

    Variants :
    - icon : carré 36px, juste icône ☐/☑ (pour grilles compactes / cards)
    - pill : pill avec libellé "Comparer" / "Sélectionné" (pour show.blade)
--}}
@props(['tool', 'variant' => 'icon'])

@once
<style>
    .lv-cmp-toggle {
        background: #fff;
        border: 1.5px solid var(--c-border, #E5E7EB);
        color: var(--c-text-muted, #52586a);
        cursor: pointer;
        font-weight: 700;
        transition: all 0.15s;
        text-decoration: none !important;
    }
    .lv-cmp-toggle:hover { border-color: var(--c-primary, #064E5A); color: var(--c-primary, #064E5A); }
    .lv-cmp-toggle:focus-visible { outline: 2px solid var(--c-primary, #064E5A); outline-offset: 2px; }
    .lv-cmp-toggle.is-active {
        background: var(--c-primary, #064E5A);
        border-color: var(--c-primary, #064E5A);
        color: #fff;
    }
    .lv-cmp-toggle.is-active:hover { background: #053f49; border-color: #053f49; color: #fff; }
    .lv-cmp-toggle--icon {
        width: 36px;
        min-width: 36px;
        height: 36px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        line-height: 1;
        padding: 0;
    }
    .lv-cmp-toggle--pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        min-height: 40px;
        border-radius: 50px;
        font-size: 13px;
    }
</style>
@endonce

<button type="button"
        x-data
        :class="{ 'is-active': $store.compare.has({{ (int) $tool->id }}) }"
        class="lv-cmp-toggle lv-cmp-toggle--{{ $variant }}"
        @click.stop.prevent="$store.compare.toggle({{ (int) $tool->id }}, @js($tool->name))"
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

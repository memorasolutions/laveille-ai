@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'block' => false,
    'disabled' => false,
])

@once
<style>
.ct-btn {
    --ct-btn-padding-y: 0.92rem;
    --ct-btn-min-height: 44px;
    font-family: var(--f-heading, system-ui);
    border-radius: var(--sys-radius-md, 0.75rem);
    border-width: 1px;
    border-style: solid;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5em;
    transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease, opacity 150ms ease;
    text-decoration: none;
    font-weight: 600;
    box-sizing: border-box;
    min-height: var(--ct-btn-min-height);
    padding: var(--ct-btn-padding-y) 1.25rem;
}

.ct-btn--sm {
    --ct-btn-padding-y: 0.65rem;
    --ct-btn-min-height: 38px;
    font-size: 0.82rem;
}

.ct-btn--md {
    font-size: 0.92rem;
}

.ct-btn--lg {
    --ct-btn-padding-y: 1.05rem;
    --ct-btn-min-height: 46px;
    font-size: 1.05rem;
}

.ct-btn--block {
    width: 100%;
}

/* Variants */
.ct-btn--primary {
    background-color: var(--sys-action-primary, #064E5A);
    color: var(--sys-text-on-accent, #fff);
    border-color: var(--sys-action-primary, #064E5A);
}
.ct-btn--primary:hover:not(:disabled) {
    background-color: var(--sys-action-primary-hover, #032E36);
    border-color: var(--sys-action-primary-hover, #032E36);
}

.ct-btn--secondary {
    background-color: transparent;
    color: var(--sys-action-primary, #064E5A);
    border-color: var(--sys-action-primary, #064E5A);
}
.ct-btn--secondary:hover:not(:disabled) {
    background-color: var(--sys-action-primary, #064E5A);
    color: var(--sys-text-on-accent, #fff);
}

.ct-btn--accent {
    background-color: var(--sys-action-accent, #9A2A06);
    color: var(--sys-text-on-accent, #fff);
    border-color: var(--sys-action-accent, #9A2A06);
}
.ct-btn--accent:hover:not(:disabled) {
    background-color: var(--sys-action-accent-hover, #771E04);
    border-color: var(--sys-action-accent-hover, #771E04);
}

.ct-btn--danger {
    background-color: var(--sys-action-danger, #DC2626);
    color: var(--sys-text-on-accent, #fff);
    border-color: var(--sys-action-danger, #DC2626);
}
.ct-btn--danger:hover:not(:disabled) {
    background-color: var(--sys-action-danger-hover, #B91C1C);
    border-color: var(--sys-action-danger-hover, #B91C1C);
}

.ct-btn--ghost {
    background-color: transparent;
    color: var(--sys-action-primary, #064E5A);
    border-color: transparent;
}
.ct-btn--ghost:hover:not(:disabled) {
    background-color: var(--sys-surface-sunken, #F1F5F9);
}

/* Focus AAA — toutes variantes */
.ct-btn:focus-visible {
    outline: var(--sys-focus-ring-width, 3px) solid var(--sys-focus-ring, #9A2A06);
    outline-offset: var(--sys-focus-ring-offset, 2px);
}
.ct-btn:focus:not(:focus-visible) {
    outline: none;
}

/* Disabled */
.ct-btn:disabled,
.ct-btn[aria-disabled="true"] {
    opacity: 0.55;
    cursor: not-allowed;
}
</style>
@endonce

@php
$variants = ['primary', 'secondary', 'accent', 'danger', 'ghost'];
$sizes = ['sm', 'md', 'lg'];

$variant = in_array($variant, $variants) ? $variant : 'primary';
$size = in_array($size, $sizes) ? $size : 'md';

$classes = ['ct-btn', "ct-btn--{$variant}", "ct-btn--{$size}"];
if ($block) {
    $classes[] = 'ct-btn--block';
}

$isLink = filled($href);
$finalHref = $disabled && $isLink ? null : $href;
$ariaDisabled = $disabled ? 'true' : null;
$tabindex = $disabled && $isLink ? '-1' : null;
@endphp

@if ($isLink)
    <a {{ $attributes->merge([
        'class' => implode(' ', $classes),
        'href' => $finalHref,
        'aria-disabled' => $ariaDisabled,
        'tabindex' => $tabindex,
    ]) }}>
        @if ($icon)<span aria-hidden="true">{{ $icon }}</span>@endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge([
        'class' => implode(' ', $classes),
        'type' => $type,
        'disabled' => $disabled ? true : null,
        'aria-disabled' => $ariaDisabled,
    ]) }}>
        @if ($icon)<span aria-hidden="true">{{ $icon }}</span>@endif
        {{ $slot }}
    </button>
@endif

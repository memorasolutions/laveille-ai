@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'block' => false,
    'disabled' => false,
])

{{-- #840-843 : renommé de .ct-btn à .core-btn - collisionnait avec .ct-btn-outline/.ct-btn-primary
     de charte.css (même classe de base .ct-btn, convention de modificateur différente : simple
     tiret côté charte, double tiret BEM ici). Les deux définitions divergentes de la classe
     partagée (border-width 1px vs 2px, border-radius --sys-radius-md vs --r-btn) s'écrasaient
     silencieusement selon l'ordre d'injection dans le DOM, produisant des bordures/rayons
     incohérents dès que les deux systèmes coexistaient sur une même page (ex: minuteur-visuel +
     bandeau newsletter). Renommage à blast radius nul : ce composant est le SEUL point d'émission
     de ces classes (aucune référence directe à ct-btn-- ailleurs sur le site, vérifié par grep). --}}
@once
<style>
.core-btn {
    --core-btn-padding-y: 0.92rem;
    --core-btn-min-height: 44px;
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
    min-height: var(--core-btn-min-height);
    padding: var(--core-btn-padding-y) 1.25rem;
}

.core-btn--sm {
    --core-btn-padding-y: 0.65rem;
    --core-btn-min-height: 38px;
    font-size: 0.82rem;
}

.core-btn--md {
    font-size: 0.92rem;
}

.core-btn--lg {
    --core-btn-padding-y: 1.05rem;
    --core-btn-min-height: 46px;
    font-size: 1.05rem;
}

.core-btn--block {
    width: 100%;
}

/* Variants */
.core-btn--primary {
    background-color: var(--sys-action-primary, #064E5A);
    color: var(--sys-text-on-accent, #fff);
    border-color: var(--sys-action-primary, #064E5A);
}
.core-btn--primary:hover:not(:disabled) {
    background-color: var(--sys-action-primary-hover, #032E36);
    border-color: var(--sys-action-primary-hover, #032E36);
    /* ACTION: fix contraste survol (audit academy::public.show, 2026-07-01) — sans
       cette ligne, la règle globale `a:hover { color: var(--c-primary) }` (charte.css)
       est PLUS spécifique que « .core-btn--primary { color: white } » sur la propriété
       color (élément+pseudo-classe > classe seule) et repeint le texte en teal foncé
       sur fond teal quasi noir : ratio mesuré 1.55:1 (échec AA/AAA). Fixé à 14.5:1. */
    color: var(--sys-text-on-accent, #fff);
}

.core-btn--secondary {
    background-color: transparent;
    color: var(--sys-action-primary, #064E5A);
    border-color: var(--sys-action-primary, #064E5A);
}
.core-btn--secondary:hover:not(:disabled) {
    background-color: var(--sys-action-primary, #064E5A);
    color: var(--sys-text-on-accent, #fff);
}

.core-btn--accent {
    background-color: var(--sys-action-accent, #9A2A06);
    color: var(--sys-text-on-accent, #fff);
    border-color: var(--sys-action-accent, #9A2A06);
}
.core-btn--accent:hover:not(:disabled) {
    background-color: var(--sys-action-accent-hover, #771E04);
    border-color: var(--sys-action-accent-hover, #771E04);
    /* Même correctif de contraste que primary (cf. commentaire ci-dessus). */
    color: var(--sys-text-on-accent, #fff);
}

.core-btn--danger {
    background-color: var(--sys-action-danger, #DC2626);
    color: var(--sys-text-on-accent, #fff);
    border-color: var(--sys-action-danger, #DC2626);
}
.core-btn--danger:hover:not(:disabled) {
    background-color: var(--sys-action-danger-hover, #B91C1C);
    border-color: var(--sys-action-danger-hover, #B91C1C);
    /* Même correctif de contraste que primary (cf. commentaire ci-dessus). */
    color: var(--sys-text-on-accent, #fff);
}

.core-btn--ghost {
    background-color: transparent;
    color: var(--sys-action-primary, #064E5A);
    border-color: transparent;
}
.core-btn--ghost:hover:not(:disabled) {
    background-color: var(--sys-surface-sunken, #F1F5F9);
}

/* Focus AAA — toutes variantes */
.core-btn:focus-visible {
    outline: var(--sys-focus-ring-width, 3px) solid var(--sys-focus-ring, #9A2A06);
    outline-offset: var(--sys-focus-ring-offset, 2px);
}
.core-btn:focus:not(:focus-visible) {
    outline: none;
}

/* Disabled */
.core-btn:disabled,
.core-btn[aria-disabled="true"] {
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

$classes = ['core-btn', "core-btn--{$variant}", "core-btn--{$size}"];
if ($block) {
    $classes[] = 'core-btn--block';
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

@props([
    'source' => null,
    'layout' => 'stacked',
    'submitLabel' => null,
    'showNote' => true,
    'heading' => null,
    'intro' => null,
])

@if($heading)
    <h3>{{ $heading }}</h3>
@endif

@if($intro)
    <p>{{ $intro }}</p>
@endif

{{-- WCAG 2.2 AAA (1.4.3) : le placeholder de l'input courriel n'a jamais eu de
     couleur explicite - il héritait donc du gris par défaut du navigateur
     (#757575 sur Chromium), mesuré à 4,61:1 sur fond blanc (mcp__wcag-mcp,
     2026-08-30) : passe l'AA (4,5:1) mais échoue l'AAA (7:1) visé par le
     projet. opacity:1 est nécessaire en plus de color (Firefox applique par
     défaut une opacité réduite au texte de placeholder, ce qui rabaisserait
     le contraste même avec la bonne couleur). --}}
@once
<style>
.lv-newsletter-email::placeholder { color: var(--sys-text-muted-aaa, #4b5563); opacity: 1; }
</style>
@endonce

<form action="{{ route('newsletter.subscribe') }}" method="POST" {{ $attributes }}>
    @csrf
    <input type="text" name="hp_url" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute!important;left:-9999px!important;width:1px;height:1px;overflow:hidden;">{{-- anti-bot honeypot (ne pas remplir) --}}

    @if($source)
        <input type="hidden" name="source" value="{{ $source }}">
    @endif

    <div style="{{ $layout === 'inline' ? 'display:flex; gap:8px; flex-wrap:wrap; align-items:center;' : 'display:flex; flex-direction:column; gap:10px;' }}">
        <input
            type="email"
            name="email"
            required
            autocomplete="email"
            aria-label="{{ __('Votre adresse courriel') }}"
            placeholder="{{ __('Votre courriel') }}"
            class="lv-newsletter-email"
            style="padding:10px 12px; border:1px solid var(--sys-border-default,#D1D5DB); border-radius:var(--sys-radius-sm,6px); font-size:0.95rem; {{ $layout === 'inline' ? 'flex:1; min-width:200px;' : 'width:100%;' }}"
        >

        <x-core::button type="submit" variant="accent" :block="$layout === 'stacked'">
            {{ $submitLabel ?? __('S’inscrire') }}
        </x-core::button>
    </div>
</form>

@if($showNote)
    {{-- WCAG 2.2 AAA (1.4.6) : var(--sys-text-muted,#52586a) sur #F8FAFB = 6.77:1 (< 7:1) —
         --sys-text-muted-aaa (#4b5563) = 7.22:1 (déjà utilisé ailleurs sur le site pour ce
         même besoin ; jetonisé le 2026-08-30, cf. public/css/charte.css). --}}
    <p style="font-size:0.75rem; color:var(--sys-text-muted-aaa, #4b5563); margin-top:12px;">
        {{ __('Double opt-in. Loi 25 / RGPD. Désabonnement 1-clic.') }}
    </p>
@endif

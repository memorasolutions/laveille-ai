{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Composant DRY : bouton d'aide « ? » rond (tâche 1638, 2026-08-06 - le « ? » était
     décentré parce que chaque occurrence recopiait des styles inline centrés au
     line-height, fragile selon la police ; ici le centrage est en flexbox, exact
     dans les deux axes, et le style vit à UN seul endroit).
     Props : toggle (expression Alpine à basculer, ex. "showHelp.persona") pour un
     panneau dépliant, OU click (expression Alpine libre, ex. ouverture de modale) ;
     size (px, défaut 24). --}}
@props(['toggle' => null, 'click' => null, 'size' => 24])
<button type="button"
    {{ $attributes->merge(['class' => 'ct-help-btn']) }}
    @if($toggle) @click="{{ $toggle }} = !{{ $toggle }}" :aria-expanded="({{ $toggle }}).toString()" @elseif($click) @click="{{ $click }}" @endif
    style="display:inline-flex;align-items:center;justify-content:center;width:{{ $size }}px;height:{{ $size }}px;min-width:{{ $size }}px;padding:0;border-radius:50%;border:2px solid var(--c-primary);background:#fff;color:var(--c-primary);font-weight:700;font-size:{{ (int) round($size * 0.58) }}px;line-height:1;cursor:pointer;flex-shrink:0;"
    aria-label="{{ __('Aide') }}" title="{{ __('Aide') }}">?</button>

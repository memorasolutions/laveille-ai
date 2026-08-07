{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Composant DRY : bouton d'aide « ? » rond (tâche 1638, 2026-08-06 - le « ? » était
     décentré parce que chaque occurrence recopiait des styles inline centrés au
     line-height, fragile selon la police ; ici le centrage est en flexbox, exact
     dans les deux axes, et le style vit à UN seul endroit).
     Bonifié après avis du panel (tâche 1639, Codex + DeepSeek convergents) : zone
     cliquable invisible de 40 px (cercle visuel inchangé - 40 et non 44 pour limiter
     le chevauchement avec des voisins serrés, risque nommé par DeepSeek) + états
     survol et focus visibles. L'infobulle native vient du title déjà présent.
     Props : toggle (expression Alpine à basculer, ex. "showHelp.persona") pour un
     panneau dépliant, OU click (expression Alpine libre, ex. ouverture de modale) ;
     size (px du cercle visuel, défaut 24). --}}
@props(['toggle' => null, 'click' => null, 'size' => 24])
@once
<style>
.ct-help-btn{position:relative;display:inline-flex;align-items:center;justify-content:center;width:var(--hb-size,24px);height:var(--hb-size,24px);min-width:var(--hb-size,24px);padding:0;border-radius:50%;border:2px solid var(--c-primary);background:#fff;color:var(--c-primary);font-weight:700;font-size:calc(var(--hb-size,24px) * 0.58);line-height:1;cursor:pointer;flex-shrink:0;transition:background .15s ease;}
.ct-help-btn::after{content:"";position:absolute;top:50%;left:50%;width:40px;height:40px;transform:translate(-50%,-50%);border-radius:50%;}
.ct-help-btn:hover{background:var(--c-primary-light);}
.ct-help-btn:focus-visible{outline:2px solid var(--c-primary);outline-offset:2px;}
/* Correction OPTIQUE (tâche 1647) : le « ? » de DM Sans n'a pas de jambage, sa boîte de
   ligne garde ~20 % de vide sous la ligne de base - centré mathématiquement, le dessin
   paraît trop haut. On redescend le glyphe seul, proportionnellement à la taille. */
.ct-help-btn__glyphe{display:inline-block;transform:translateY(0.07em);}
</style>
@endonce
<button type="button"
    {{-- style est exclu du bag puis réinjecté dans l'attribut style unique ci-dessous,
         sinon Blade rendrait DEUX attributs style et le navigateur en ignorerait un. --}}
    {{ $attributes->except('style')->merge(['class' => 'ct-help-btn']) }}
    @if($toggle) @click="{{ $toggle }} = !{{ $toggle }}" :aria-expanded="({{ $toggle }}).toString()" @elseif($click) @click="{{ $click }}" @endif
    {{-- Dimensions en inline (gagnent sur tout sélecteur du thème - mesuré : un CSS
         global écrasait la hauteur du cercle à 22px quand elles vivaient en classe). --}}
    {{-- font-size/line-height aussi en inline (round 2, tâche 1647 - mesuré en prod :
         le thème écrasait le font-size de la classe à 11,25 px, glyphe trop petit). --}}
    style="--hb-size:{{ (int) $size }}px;width:{{ (int) $size }}px;height:{{ (int) $size }}px;min-width:{{ (int) $size }}px;font-size:{{ round($size * 0.58, 2) }}px;line-height:1;{{ $attributes->get('style') }}"
    aria-label="{{ __('Aide') }}" title="{{ __('Aide') }}"><span class="ct-help-btn__glyphe" aria-hidden="true">?</span></button>

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
     size (px du cercle visuel, défaut 24).
     CSS : la classe .ct-help-btn vit désormais en UN seul endroit, public/css/charte.css
     (fusion DRY 2026-08-11 - elle était dupliquée ici et dans Modules/Core/.../help-modal.blade.php,
     avec un risque de cascade divergente sur toute page combinant les deux). --}}
@props(['toggle' => null, 'click' => null, 'size' => 24])
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

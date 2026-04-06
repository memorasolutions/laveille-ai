{{--
    Composant réutilisable : sélecteur de pilules (toggle buttons)
    Utilise les classes .gl-pill du design plateforme (charte.css)

    Variables requises :
    - $items       : array de labels (ex: ['S', 'M', 'L', 'XL', '2XL'])
    - $alpineVar   : nom de la variable Alpine.js (ex: 'selectedSize')
    - $inputName   : nom du champ hidden (ex: 'variant_label')

    Variables optionnelles :
    - $label       : texte du label (défaut: null = pas de label)
    - $extraData   : array JSON pour données supplémentaires (ex: variants avec gelato_uid)
    - $extraInput  : nom du champ hidden pour données extra (ex: 'variant_gelato_uid')
    - $extraKey    : clé pour extraire la valeur extra (ex: 'gelato_uid')
    - $matchKey    : clé pour matcher les items extra (ex: 'label')
--}}

<div>
    @if(!empty($label))
        <label style="font-weight: 600; margin-bottom: 8px; display: block;">{{ $label }}</label>
    @endif

    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
        @foreach($items as $item)
            <button
                type="button"
                class="gl-pill"
                :class="{ 'active': {{ $alpineVar }} === '{{ $item }}' }"
                @click="{{ $alpineVar }} = '{{ $item }}'"
            >{{ $item }}</button>
        @endforeach
    </div>

    <input type="hidden" name="{{ $inputName }}" :value="{{ $alpineVar }}">

    @if(!empty($extraData) && !empty($extraInput) && !empty($extraKey) && !empty($matchKey))
        <input type="hidden" name="{{ $extraInput }}" :value="({{ json_encode($extraData) }}).find(v => v.{{ $matchKey }} === {{ $alpineVar }})?.{{ $extraKey }} || ''">
    @endif
</div>

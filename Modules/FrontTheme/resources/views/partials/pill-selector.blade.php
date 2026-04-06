{{--
    Composant réutilisable : sélecteur de pilules (toggle buttons)
    Design rectangle arrondi 2026 — inline styles, zéro dépendance CSS

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
        <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 6px;">{{ $label }}</label>
    @endif

    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
        @foreach($items as $item)
            <button
                type="button"
                :style="{{ $alpineVar }} === '{{ $item }}'
                    ? 'border-radius:10px; padding:10px 18px; min-width:48px; height:44px; font-size:15px; font-weight:600; cursor:pointer; text-align:center; transition:all 0.2s; border:1px solid #0B7285; background:#0B7285; color:#fff; outline:none;'
                    : 'border-radius:10px; padding:10px 18px; min-width:48px; height:44px; font-size:15px; font-weight:600; cursor:pointer; text-align:center; transition:all 0.2s; border:1px solid #cbd5e1; background:#f1f5f9; color:#374151; outline:none;'"
                @click="{{ $alpineVar }} = '{{ $item }}'"
            >{{ $item }}</button>
        @endforeach
    </div>

    <input type="hidden" name="{{ $inputName }}" :value="{{ $alpineVar }}">

    @if(!empty($extraData) && !empty($extraInput) && !empty($extraKey) && !empty($matchKey))
        <input type="hidden" name="{{ $extraInput }}" :value="({{ json_encode($extraData) }}).find(v => v.{{ $matchKey }} === {{ $alpineVar }})?.{{ $extraKey }} || ''">
    @endif
</div>

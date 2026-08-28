{{--
    Partial DRY (C3) — RENDU DES TROUS d'un cloze / texte à trous.

    Chaque trou est rendu INLINE dans le texte (input pour short, select pour mcq).
    Les bonnes réponses ne sont JAMAIS exposées (les `segments` seuls sont rendus).

    Le groupe est enrobé d'un <fieldset>/<legend> (WCAG 1.3.1, C2) — identique en mode
    différé ET immédiat ; seul le préfixe de `name` change (cf. $namePrefix) :
      - différé   : namePrefix = "answers[{i}]"  → champ "answers[{i}][{k}]" ;
      - immédiat  : namePrefix = "answer"         → champ "answer[{k}]".

    Paramètres :
      - $segments   : liste ordonnée de segments {type:'text'|'blank', …} ;
      - $namePrefix : préfixe du `name` HTML (sans l'index de trou) ;
      - $legend     : intitulé pour le <legend> (visually-hidden).
--}}
@php
    $segments   = $segments   ?? [];
    $namePrefix = $namePrefix ?? 'answer';
    $legend     = $legend     ?? 'Texte à trous';
@endphp
<fieldset style="border:0;padding:0;margin:0;">
    <legend class="visually-hidden">{{ $legend }}</legend>
    <p class="text-muted mb-2" style="font-size: 0.85rem;">Complétez chaque trou.</p>
    <p style="line-height: 2.4;">
        @foreach($segments as $seg)
            @if(($seg['type'] ?? '') === 'blank')
                @php $k = (int) ($seg['index'] ?? 0); $bk = $seg['kind'] ?? 'short'; @endphp
                @if($bk === 'mcq')
                    <select name="{{ $namePrefix }}[{{ $k }}]"
                            class="form-select form-select-sm d-inline-block"
                            style="width:auto; max-width: 240px; vertical-align: baseline;"
                            aria-label="Trou {{ $k + 1 }}" required>
                        <option value="">– Choisir –</option>
                        @foreach(($seg['choices'] ?? []) as $ci => $choice)
                            <option value="{{ $ci }}">{{ $choice }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="{{ $namePrefix }}[{{ $k }}]"
                           class="form-control form-control-sm d-inline-block"
                           style="width:auto; max-width: 200px; vertical-align: baseline;"
                           autocomplete="off" aria-label="Trou {{ $k + 1 }}" required>
                @endif
            @else
                {{ $seg['value'] ?? '' }}
            @endif
        @endforeach
    </p>
</fieldset>

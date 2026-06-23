{{--
    Partial DRY — RENDU des trous d'un GLISSER-DÉPOSER SUR TEXTE (ddwtos).

    Chaque trou est rendu INLINE dans le texte comme un <select> (alternative clavier
    WCAG = mécanisme principal a11y-first) listant TOUT le pool de mots MÉLANGÉ partagé.
    Les bonnes réponses (answers) ne sont JAMAIS exposées : seuls les `segments`
    d'affichage + le pool `options` sont rendus.

    Le groupe est enrobé d'un <fieldset>/<legend> (WCAG 1.3.1) — identique en mode différé
    ET immédiat ; seul le préfixe de `name` change (cf. $namePrefix) :
      - différé   : namePrefix = "answers[{i}]"  → champ "answers[{i}][{k}]" ;
      - immédiat  : namePrefix = "answer"         → champ "answer[{k}]".

    Paramètres :
      - $segments   : liste ordonnée de segments {type:'text'|'blank', …} ;
      - $options    : pool de mots MÉLANGÉ (valeur = index dans le pool) ;
      - $namePrefix : préfixe du `name` HTML (sans l'index de trou) ;
      - $legend     : intitulé pour le <legend> (visually-hidden).
--}}
@php
    $segments   = $segments   ?? [];
    $options    = $options    ?? [];
    $namePrefix = $namePrefix ?? 'answer';
    $legend     = $legend     ?? 'Glisser-déposer sur texte';
@endphp
<fieldset style="border:0;padding:0;margin:0;">
    <legend class="visually-hidden">{{ $legend }}</legend>
    <p class="text-muted mb-2" style="font-size: 0.85rem;">Choisissez le bon mot pour chaque trou.</p>
    <p style="line-height: 2.4;">
        @foreach($segments as $seg)
            @if(($seg['type'] ?? '') === 'blank')
                @php $k = (int) ($seg['index'] ?? 0); @endphp
                <select name="{{ $namePrefix }}[{{ $k }}]"
                        class="form-select form-select-sm d-inline-block"
                        style="width:auto; max-width: 240px; vertical-align: baseline;"
                        aria-label="Trou {{ $k + 1 }}" required>
                    <option value="">– choisir –</option>
                    @foreach($options as $oi => $word)
                        <option value="{{ $oi }}">{{ $word }}</option>
                    @endforeach
                </select>
            @else
                {{ $seg['value'] ?? '' }}
            @endif
        @endforeach
    </p>
</fieldset>

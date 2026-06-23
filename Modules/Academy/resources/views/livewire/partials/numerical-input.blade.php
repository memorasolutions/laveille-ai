{{--
    Partial DRY (C3, audit F3) — SAISIE D'UNE RÉPONSE NUMÉRIQUE (valeur + unité indicative).

    Rendu IDENTIQUE en mode différé ET immédiat : seul le `name` (et l'`id`) change.
    Les bonnes réponses (correct/tolerance) restent SERVEUR : elles ne sont jamais rendues.

    Paramètres :
      - $nameAttr : attribut `name` du champ (différé « answers[{i}] » ; immédiat « answer ») ;
      - $inputId  : attribut `id` du champ (lié à l'aria-label / label éventuel) ;
      - $unit     : unité indicative (vide = pas d'unité affichée) ;
      - $value    : valeur pré-remplie facultative (défaut vide).
--}}
@php
    $nameAttr = $nameAttr ?? 'answer';
    $inputId  = $inputId  ?? '';
    $unit     = isset($unit) && is_string($unit) ? trim($unit) : '';
    $value    = $value ?? '';
@endphp
<div class="mt-2 d-flex align-items-center gap-2" style="max-width: 320px;">
    <input type="text"
           inputmode="decimal"
           name="{{ $nameAttr }}"
           id="{{ $inputId }}"
           class="form-control"
           placeholder="Votre réponse…"
           autocomplete="off"
           required
           aria-label="Réponse numérique{{ $unit !== '' ? ' en '.$unit : '' }}"
           value="{{ $value }}"
           style="max-width: 220px;">
    @if($unit !== '')
        <span aria-hidden="true" style="font-weight: 600; color: #475569;">{{ $unit }}</span>
    @endif
</div>

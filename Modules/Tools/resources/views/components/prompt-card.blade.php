{{--
    Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

    Carte cliquable réutilisable (écran 3 du constructeur de prompts, round 152, 2026-08-02) :
    UN VRAI bouton radio ou une VRAIE case à cocher (jamais un <div> qui imite un bouton sans en
    avoir le comportement clavier/lecteur d'écran), cible tactile >= 44px, et l'état sélectionné
    est indiqué par une coche EN PLUS de la couleur - jamais la couleur seule (exigence explicite
    du panel Codex/Gemini/claude.ai, voir .outils/PLAN-FINAL-constructeur-2026-07-31.md section 10).

    Props :
    - type    : 'radio' | 'checkbox' (défaut 'checkbox')
    - name    : nom du groupe HTML natif (recommandé pour 'radio' - navigation flèches native)
    - value   : expression Alpine BRUTE pour :value (ex. "a.value" ou "'texte'"). OMIS pour une
                case à cocher BOOLÉENNE simple (ex. "Autre" qui ouvre un champ libre) : le modèle
                est alors directement un booléen, pas un tableau.
    - model   : expression Alpine BRUTE pour x-model (ex. "audiencePresets", "profile",
                "customOpen.tone")
    - selected: expression Alpine BRUTE optionnelle pour l'état "carte sélectionnée" (sinon
                dérivée automatiquement : includes() pour une case à valeur, égalité stricte pour
                un radio, le modèle lui-même pour une case booléenne sans "value")
    - onChange: expression Alpine BRUTE optionnelle exécutée au changement (ex. marquer un profil
                "touché" pour désactiver la pré-sélection automatique par mots-clés)
--}}
@props([
    'type' => 'checkbox',
    'name' => null,
    'value' => null,
    'model' => null,
    'selected' => null,
    'onChange' => null,
])
@php
    if ($selected !== null) {
        $selectedExpr = $selected;
    } elseif ($value === null) {
        $selectedExpr = "({$model})";
    } elseif ($type === 'checkbox') {
        $selectedExpr = "(({$model}) || []).includes({$value})";
    } else {
        $selectedExpr = "({$model}) === ({$value})";
    }
@endphp
<label {{ $attributes->class(['ct-card']) }} :class="{ 'ct-card--on': {{ $selectedExpr }} }">
    <input type="{{ $type }}"
           @if($name) name="{{ $name }}" @endif
           class="ct-card__input"
           @if($value !== null) :value="{{ $value }}" @endif
           x-model="{{ $model }}"
           @if($onChange) @change="{{ $onChange }}" @endif>
    <span class="ct-card__mark" aria-hidden="true">✓</span>
    <span class="ct-card__label">{{ $slot }}</span>
</label>

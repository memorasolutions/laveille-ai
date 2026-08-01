{{--
    Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

    Bloc « toujours visible » de l'écran 3 du constructeur de prompts (round 152, 2026-08-02) :
    remplace les accordéons « + Réglages avancés » - CINQ blocs affichés en même temps, aucun
    mécanisme d'ouverture/fermeture. Une question en titre, un exemple concret, la mention
    « Facultatif », le contenu (cartes/champs, dans le slot par défaut), puis la ligne
    « Ajouté : ... » qui explique ce que le DERNIER choix vient de produire (voir les getters
    feedback* de constructeur-prompts-core.js).

    Props :
    - question : la question posée (titre du bloc)
    - example  : exemple concret affiché sous le titre (facultatif)
    - added    : expression Alpine BRUTE (x-text) pour la ligne "Ajouté : ..." - masquée si vide
    - id       : id HTML du bloc (utilisé par openDiagnosticSection() pour faire défiler jusqu'ici)
--}}
@props([
    'question' => '',
    'example' => null,
    'added' => null,
    'id' => null,
])
<section class="ct-block" @if($id) id="{{ $id }}" @endif>
    <div class="ct-block__head">
        <h3 class="ct-block__title">{{ $question }}</h3>
        <span class="ct-block__optional">{{ __('Facultatif') }}</span>
    </div>
    @if($example)
        <p class="ct-block__example">{{ $example }}</p>
    @endif
    {{ $slot }}
    @if($added)
        <p class="ct-block__added" x-show="{{ $added }}" x-cloak role="status" aria-live="polite" x-text="{{ $added }}"></p>
    @endif
</section>

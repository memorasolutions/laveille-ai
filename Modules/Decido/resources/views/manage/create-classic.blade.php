{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())
@section('title', 'Sondage classique Décido · ' . config('app.name'))
@section('page_noindex', true)
@section('meta_description', "Crée un sondage classique Décido pour choisir entre plusieurs options avec ton équipe, ta famille ou ta communauté.")
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Sondage classique', 'breadcrumbItems' => [__('Outils'), 'Sondage classique']])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="h2 mb-2">Sondage classique</h1>
                <p class="text-muted mb-4">Choisis entre plusieurs options libres.</p>

                {{-- Option E (skill /100 hors gate, veille pp_search juillet 2026 validée
                     Perplexity+Codex+Gemini, 95/100) : formulaire dédié au type "classique",
                     séparé du type "date" (fini le rendu conditionnel x-show sur une seule page).
                     Essentiel visible d'emblée (titre, mode de vote, options) ; description sous
                     "Plus d'options" repliée par défaut. --}}
                @php
                    // Round 27 (revue adversariale) : x-data initialisait `options` en dur sans
                    // jamais relire old(), contrairement à tous les autres champs du formulaire -
                    // un échec de validation (ex. options dupliquées, DistinctNormalized) faisait
                    // perdre la totalité de la saisie au réaffichage. json_encode() interpolé via
                    // {{ }} (échappement Blade), jamais {!! !!}, pour rester sécuritaire dans
                    // l'attribut HTML x-data="...".
                    $decidoOldOptions = old('options');
                    if (!is_array($decidoOldOptions) || count($decidoOldOptions) < 2) {
                        $decidoOldOptions = ['', ''];
                    }
                    $decidoOldOptions = array_values($decidoOldOptions);
                @endphp
                <form x-data="{ options: {{ json_encode($decidoOldOptions) }}, voteMode: 'single_choice' }" method="POST" action="{{ route('decido.store') }}">
                    @csrf
                    <input type="hidden" name="type" value="classic">

                    @include('decido::manage.partials.title-field')

                    <div class="mb-3">
                        <label class="form-label d-block">Mode de vote</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="vote_mode" id="vote_mode_single" value="single_choice"
                                   x-model="voteMode">
                            <label class="form-check-label" for="vote_mode_single">Choix unique</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="vote_mode" id="vote_mode_approval" value="approval"
                                   x-model="voteMode">
                            <label class="form-check-label" for="vote_mode_approval">Approbation, plusieurs choix possibles</label>
                        </div>
                        @error('vote_mode')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-block">Options</label>
                        <template x-for="(option, index) in options" :key="index">
                            <div class="input-group mb-2">
                                <input type="text" name="options[]" class="form-control" x-model="options[index]">
                                <button type="button" class="ct-btn ct-btn-outline-danger ct-btn-sm"
                                        x-on:click="options.splice(index, 1)"
                                        x-show="options.length > 2">
                                    Retirer
                                </button>
                            </div>
                        </template>
                        <button type="button" class="ct-btn ct-btn-outline ct-btn-sm" x-on:click="options.push('')">
                            + Ajouter une option
                        </button>
                        @error('options')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <details class="mb-4 decido-advanced-options">
                        <summary class="fw-semibold">Plus d'options</summary>
                        <div class="mt-3">
                            @include('decido::manage.partials.description-timezone-fields')
                        </div>
                    </details>

                    <div class="d-grid">
                        <x-core::button type="submit" variant="primary">Créer le sondage</x-core::button>
                    </div>
                    {{-- Politique de rétention (2026-07-19) : mention discrète, non intrusive
                         (pas d'alerte/encadré). --}}
                    <p class="text-muted small text-center mt-2 mb-0">Ce sondage sera automatiquement supprimé après sa période d'expiration, pour protéger tes données.</p>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

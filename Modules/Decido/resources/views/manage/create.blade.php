{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends(fronttheme_layout())
@section('title', 'Créer un sondage Décido · ' . config('app.name'))
{{-- Round 10 (skill /100) : page privée (auth requis), jamais destinée à l'indexation - même
     politique noindex que le reste des vues privées Décido. --}}
@section('page_noindex', true)
@section('meta_description', "Crée un sondage Décido pour trouver le bon moment ou le bon choix avec ton équipe, ta famille ou ta communauté.")
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Créer un sondage Décido', 'breadcrumbItems' => [__('Outils'), 'Créer un sondage Décido']])
@endsection
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="h2 mb-2">Créer un sondage Décido</h1>
                {{-- Option E (veille pp_search juillet 2026, validée Perplexity + Codex + Gemini,
                     95/100) : cette page n'est plus un long formulaire avec sélecteur de type
                     interne (rendu conditionnel x-show) - c'est désormais un simple choix qui
                     mène directement au formulaire dédié et allégé du type choisi
                     (decido.create.date / decido.create.classic). Réduit le nombre de champs
                     visibles au strict nécessaire par type, sans jamais forcer de navigation par
                     étapes une fois sur le formulaire lui-même (l'assistant multi-étapes classique
                     a été noté 55-64/100 par la recherche : trop formel pour un outil rapide type
                     Doodle/Framadate). Simples liens `<a>`, nativement accessibles au clavier. --}}
                <p class="text-muted mb-4">Choisis le type de sondage pour commencer.</p>

                <div class="row g-3 decido-type-choices">
                    <div class="col-md-6">
                        <a href="{{ route('decido.create.date') }}" class="d-flex flex-column h-100 border rounded p-4 decido-type-card">
                            <strong class="h5 mb-2">Sondage de dates</strong>
                            <p class="text-muted small mb-0">Trouve la meilleure date parmi plusieurs propositions.</p>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ route('decido.create.classic') }}" class="d-flex flex-column h-100 border rounded p-4 decido-type-card">
                            <strong class="h5 mb-2">Sondage classique</strong>
                            <p class="text-muted small mb-0">Choisis entre plusieurs options libres.</p>
                        </a>
                    </div>
                </div>
                <style>
                    .decido-type-card {
                        color: inherit;
                        text-decoration: none;
                        cursor: pointer;
                        transition: border-color .15s, background-color .15s;
                    }
                    .decido-type-card:hover,
                    .decido-type-card:focus-visible {
                        border-color: var(--c-primary, #064E5A);
                        background-color: var(--c-primary-light, #F0FAFB);
                    }
                    .decido-type-card:focus-visible {
                        outline: 3px solid var(--c-primary, #064E5A);
                        outline-offset: 2px;
                    }
                </style>
            </div>
        </div>
    </div>
</section>
@endsection

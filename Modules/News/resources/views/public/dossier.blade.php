{{--
    Page de dossier thématique : toutes les actualités publiées qui portent sur une même entité.

    Variables : $entite, $slug, $articles (paginator), $total, $periode,
                $entitesVoisines.

    Le contrôleur garantit au moins 5 actualités avant de servir cette page - un dossier de deux
    fiches serait exactement la page mince qu'on cherche à éliminer.

    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai
--}}
@extends(fronttheme_layout())

@section('title', __('Tout sur :entite', ['entite' => $entite]).' - '.config('app.name'))
@section('meta_description', __(':total actualités sur :entite, réunies en un seul dossier : ce qui a été annoncé, ce qui a changé, et dans quel ordre.', ['total' => $total, 'entite' => $entite]))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => __('Tout sur :entite', ['entite' => $entite]),
        'breadcrumbItems' => [__('Actualités'), __('Dossiers thématiques'), __('Tout sur :entite', ['entite' => $entite])],
    ])
@endsection

@include('news::public.partials.dossier-styles')

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <h1 style="font-family: var(--f-heading); margin-bottom: 0.25rem;">{{ __('Tout sur :entite', ['entite' => $entite]) }}</h1>

        {{-- La période arrive déjà formatée du contrôleur, élision comprise (« d'avril », jamais
             « de avril ») : trois mois commencent par une voyelle, la faute serait visible. --}}
        <p class="nw-dossier-intro">
            {{ trans_choice(':count actualité publiée sur :entite|:count actualités publiées sur :entite', $total, ['count' => $total, 'entite' => $entite]) }}@if($periode), {{ $periode }}@endif.
        </p>

        <div class="row nw-articles-grid news-grid">
            @foreach($articles as $article)
            <div class="col-sm-6 col-md-4" style="margin-bottom: 1.25rem;">
                @include('news::public.partials.article-card', ['article' => $article])
            </div>
            @endforeach
        </div>

        <div style="margin-top: 1.5rem;">
            {{ $articles->links() }}
        </div>

        {{-- Dossiers voisins : ce qui transforme une liste isolée en réseau navigable. Sans eux,
             le lecteur arrivé par un moteur trouve une impasse au bas de la page. --}}
        @if($entitesVoisines->isNotEmpty())
        <div class="nw-dossier-voisins">
            <h2>{{ __('Dossiers liés') }}</h2>
            <div class="nw-dossier-voisins-liste">
                @foreach($entitesVoisines as $voisine)
                <a href="{{ route('news.dossier', $voisine->entity_slug) }}" class="nw-dossier-lien">{{ $voisine->entity_label }}</a>
                @endforeach
            </div>
        </div>
        @endif

        <a href="{{ route('news.dossiers') }}" class="nw-dossier-retour">{{ __('Tous les dossiers') }}</a>
    </div>
</section>
@endsection

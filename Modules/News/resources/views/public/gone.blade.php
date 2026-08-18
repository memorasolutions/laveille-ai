<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{--
    ACTION : page 410 UTILE (pas une erreur brute) - chantier AdSense « faible valeur »
    (2026-08-18). Servie par PublicNewsController::show() dès qu'une fiche porte un
    retired_at non nul (voir NewsArticle::isRetired()). Retrait SEO-sûr et RÉVERSIBLE :
    la fiche existe toujours en base (aucune suppression), seul son statut de service
    change - unretire() la restaure et la page redevient 200.
    MCP: SELF (<5 lignes utiles)
    RAISON: design doc du chantier - un 410 explicite (retrait volontaire au sens de
    Google) plutôt qu'un 404 générique (disparition non expliquée).
--}}
@extends(fronttheme_layout())

{{-- Meta noindex par sécurité (le layout master lit cette section, même mécanisme que
     l'élagage SEO noindex - DRY, voir public/show.blade.php). --}}
@section('page_noindex', '1')

@section('title', __('Actualité retirée') . ' - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Actualité retirée')])
@endsection

@section('content')
    <!-- start error-404-section -->
    <section class="error-404-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-xs-12">
                    <div class="content clearfix">
                        <div class="error">
                            <h2>410</h2>
                        </div>
                        <div class="error-message">
                            <h3>{{ __('Cette actualité a été retirée') }}</h3>
                            <p>{{ __('Le contenu que vous cherchez n\'est plus disponible sur laveille.ai. Ce retrait est volontaire et définitif.') }}</p>
                            <p>
                                <a href="{{ route('news.index') }}" class="theme-btn">{{ __('Voir les actualités') }}</a>
                                <a href="{{ route('home') }}" class="theme-btn">{{ __('Retour à l\'accueil') }}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- end error-404-section -->
@endsection

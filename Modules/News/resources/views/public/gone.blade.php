<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{--
    ACTION : page 410 UTILE (pas une erreur brute) - chantier AdSense « faible valeur »
    (2026-08-18, complétée 2026-08-20). Servie par PublicNewsController::show() dans DEUX cas
    distincts, tous deux réversibles (aucune suppression en base) : une fiche retirée
    (retired_at non nul, voir NewsArticle::isRetired()) OU une fiche publiée dont le résumé
    n'est pas exploitable (hasExploitableSummary() faux). Le texte reste générique par défaut
    et ne se spécialise en « retrait » que lorsque la fiche est réellement retirée - ne
    présuppose jamais retired_at non nul.
    MCP: SELF (<5 lignes utiles)
    RAISON: design doc du chantier - un 410 explicite (contenu non exploitable au sens de
    Google) plutôt qu'un 404 générique (disparition non expliquée), pour les deux causes.
--}}
@php
    $estRetiree = $article->isRetired();
@endphp
@extends(fronttheme_layout())

{{-- Meta noindex par sécurité (le layout master lit cette section, même mécanisme que
     l'élagage SEO noindex - DRY, voir public/show.blade.php). --}}
@section('page_noindex', '1')

@section('title', ($estRetiree ? __('Actualité retirée') : __('Actualité indisponible')) . ' - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $estRetiree ? __('Actualité retirée') : __('Actualité indisponible')])
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
                            <h3>{{ $estRetiree ? __('Cette actualité a été retirée') : __('Cette actualité n\'est plus disponible') }}</h3>
                            <p>
                                @if ($estRetiree)
                                    {{ __('Le contenu que vous cherchez n\'est plus disponible sur laveille.ai. Ce retrait est volontaire et définitif.') }}
                                @else
                                    {{ __('Le contenu de cette actualité n\'est pas accessible pour le moment sur laveille.ai.') }}
                                @endif
                            </p>
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

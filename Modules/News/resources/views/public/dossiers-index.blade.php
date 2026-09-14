{{--
    Index des dossiers thématiques.

    Variable : $dossiers (collection d'objets entity_slug, entity_label, total).

    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai
--}}
@extends(fronttheme_layout())

@section('title', __('Dossiers thématiques').' - '.config('app.name'))
@section('meta_description', __('Chaque dossier réunit les actualités qui portent sur une même entreprise ou un même produit, de la première annonce à la plus récente.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Dossiers thématiques')])
@endsection

@include('news::public.partials.dossier-styles')

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <h1 style="font-family: var(--f-heading); margin-bottom: 0.25rem;">{{ __('Dossiers thématiques') }}</h1>
        <p class="nw-dossier-intro">{{ __('Chaque dossier réunit les actualités qui portent sur une même entreprise ou un même produit, pour suivre l\'ensemble d\'un sujet plutôt qu\'une annonce isolée.') }}</p>

        @if($dossiers->isEmpty())
            <p>{{ __('Aucun dossier n\'est encore assez fourni pour être publié.') }}</p>
        @else
        <div class="row">
            @foreach($dossiers as $dossier)
            <div class="col-sm-6 col-md-4" style="margin-bottom: 1.25rem;">
                <div class="nw-dossier-card">
                    <a href="{{ route('news.dossier', $dossier->entity_slug) }}" class="nw-dossier-card-link">
                        <h2 class="nw-dossier-card-title">{{ $dossier->entity_label }}</h2>
                        <span class="nw-dossier-card-count">{{ trans_choice(':count actualité|:count actualités', $dossier->total, ['count' => $dossier->total]) }}</span>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endsection

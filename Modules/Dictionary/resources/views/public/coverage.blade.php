<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Ticket #2531 (plan glossaire, étape 4) - page de couverture d'un terme du lot pilote (12
     termes, Modules\Dictionary\Support\CoverageTerms) : nom, définition (évite une page mince),
     lien vers la fiche du terme, puis toutes ses actualités liées, paginées. --}}
@extends(fronttheme_layout())

@section('title', __('Actualités sur :terme', ['terme' => $term->name]) . ' - ' . __('Glossaire Techno') . ' - ' . config('app.name'))
@section('meta_description', safe_excerpt(strip_tags($term->definition ?? ''), 160))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => __('Actualités sur :terme', ['terme' => $term->name]),
        'breadcrumbItems' => [__('Glossaire Techno'), $term->name, __('Actualités')],
    ])
@endsection

{{-- Schema.org ItemList — même forme que Modules\Directory\Http\Controllers\CollectionController
     (collections/show.blade.php). Positions absolues (firstItem() + index) pour rester correctes
     d'une page de pagination à l'autre, jamais 1..N recommencé à chaque page. --}}
@push('head')
@php
    $_itemListJsonLd = json_encode([
        chr(64).'context' => 'https://schema.org',
        chr(64).'type' => 'ItemList',
        'name' => __('Actualités sur :terme', ['terme' => $term->name]),
        'description' => Illuminate\Support\Str::limit(strip_tags($term->definition ?? ''), 160),
        'url' => url()->current(),
        'numberOfItems' => $newsArticles->count(),
        'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
        'inLanguage' => 'fr-CA',
        'publisher' => [
            chr(64).'type' => 'Organization',
            'name' => 'La veille',
            'url' => url('/'),
        ],
        'itemListElement' => collect($newsArticles->items())->values()->map(function ($article, $idx) use ($newsArticles) {
            return [
                chr(64).'type' => 'ListItem',
                'position' => ($newsArticles->firstItem() ?? 1) + $idx,
                'url' => route('news.show', $article),
                'name' => $article->seo_title ?: $article->title,
            ];
        })->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
<script type="application/ld+json">{!! $_itemListJsonLd !!}</script>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <div class="wpo-blog-content">
                    <div class="post">
                        <h2>{{ __('Actualités sur :terme', ['terme' => $term->name]) }}</h2>

                        {{-- Définition du terme : évite une page mince (ticket #2531, ne surtout
                             pas omettre - c'est la raison d'être de cette exigence). --}}
                        @if($term->definition)
                            <p style="color: var(--c-text-secondary); line-height: 1.7;">{{ $term->definition }}</p>
                        @endif

                        <p>
                            <a href="{{ $term->getPublicUrl() }}" class="ct-btn ct-btn-primary" style="display:inline-block;">
                                {{ __('Voir la fiche complète de :terme', ['terme' => $term->name]) }}
                            </a>
                        </p>

                        <div class="entry-details" style="line-height: 1.8; margin-top: 30px;">
                            <h4 style="margin-top: 0;">
                                {{ trans_choice(':count actualité liée à :terme|:count actualités liées à :terme', $newsArticles->total(), ['count' => $newsArticles->total(), 'terme' => $term->name]) }}
                            </h4>

                            @if($newsArticles->isEmpty())
                                <p style="color: var(--c-text-muted);">{{ __("Aucune actualité liée pour l'instant.") }}</p>
                            @else
                                <ul style="list-style: none; padding: 0; margin: 0;">
                                    @foreach($newsArticles as $article)
                                        <li style="margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #e5e7eb; line-height: 1.5;">
                                            <a href="{{ route('news.show', $article) }}" style="font-weight: 600;">{{ $article->seo_title ?: $article->title }}</a><br>
                                            {{-- Couleur MESURÉE (déjà validée dans ce projet) : #4B5163 donne 7,91:1
                                                 sur blanc et 7,56:1 sur #F8FAFC, AAA quel que soit le fond dont cette
                                                 liste hérite - voir Modules\Dictionary\resources\views\public\show.blade.php,
                                                 section « Dans l'actualité ». --}}
                                            <span style="color: #4B5163; font-size: 0.9rem;">
                                                {{ $article->pub_date?->locale('fr_CA')->translatedFormat('d F Y') }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>

                                <div style="margin-top: 24px;">
                                    {{ $newsArticles->appends(request()->query())->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@can('view_admin_panel')
@include('core::components.admin-bar', [
    'label' => __('Terme admin'),
    'model' => $term,
    'editUrl' => Route::has('admin.dictionary.edit') ? route('admin.dictionary.edit', $term->id) : null,
])
@endcan

<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@include('fronttheme::partials.pagination-seo', ['paginator' => $articles])

@section('title', $category->name . ' - ' . config('app.name'))
@section('meta_description', __('Articles dans la categorie :name — veille technologique et intelligence artificielle.', ['name' => $category->name]))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $category->name,
        'breadcrumbItems' => [__('Blog'), $category->name]
    ])
@endsection

@section('content')
    <!-- start wpo-blog-pg-section -->
    <section class="wpo-blog-pg-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-lg-8 col-12">
                    <div class="wpo-blog-content">
                        @forelse($articles as $article)
                            <div class="post format-standard-image">
                                <div class="entry-media">
                                    @if($article->featured_image)
                                        <img src="{{ asset($article->featured_image) }}" alt="{{ $article->title }}" loading="lazy">
                                    @else
                                        <img src="{{ fronttheme_asset('images/blog/img-' . (($loop->index % 3) + 10) . '.jpg') }}" alt="{{ $article->title }}" loading="lazy">
                                    @endif
                                </div>
                                @php
                                    $words = str_word_count(strip_tags($article->content ?? $article->excerpt ?? ''));
                                    $minRead = max(1, (int) ceil($words / 200));
                                @endphp
                                {{-- S90 #82 byline retirée des cartes (Option D pp_search 2026 mono-auteur). EEAT préservé : section header + page article + Schema.org Person --}}
                                <div class="card-meta-compact">
                                    <time class="card-meta-compact-date" datetime="{{ $article->published_at?->toIso8601String() }}">{{ $article->published_at?->translatedFormat('d M Y') }}</time>
                                    <span class="card-meta-compact-sep">·</span>
                                    <span class="card-meta-compact-readtime">{{ $minRead }} {{ __('min lecture') }}</span>
                                </div>
                                <div class="entry-details">
                                    <h3><a href="{{ route('blog.show', $article->slug) }}">{{ $article->title }}</a></h3>
                                    <p>{{ Str::limit($article->excerpt ?? strip_tags($article->content), 200) }}</p>
                                    <a href="{{ route('blog.show', $article->slug) }}" class="read-more">{{ __('LIRE LA SUITE...') }}</a>
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-info">{{ __('Aucun article dans cette catégorie.') }}</div>
                        @endforelse

                        @if($articles->hasPages())
                            <div class="pagination-wrapper pagination-wrapper-left">
                                {{ $articles->links() }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col col-lg-4 col-12 d-none d-lg-block">
                    @include('fronttheme::partials.sidebar')
                </div>
            </div>
        </div>
    </section>
    <!-- end wpo-blog-pg-section -->
@endsection

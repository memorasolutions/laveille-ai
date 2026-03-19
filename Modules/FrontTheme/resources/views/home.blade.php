@extends(fronttheme_layout())

@section('title', config('app.name'))

@section('content')

    <!-- hero slider -->
    <section class="wpo-hero-slider">
        <div class="swiper-container">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="slide-inner slide-bg-image" data-background="{{ fronttheme_asset('images/slider/slide-1.jpg') }}">
                        <div class="gradient-overlay"></div>
                        <div class="container">
                            <div class="slide-content">
                                <div data-swiper-parallax="300" class="slide-title">
                                    <h2>{{ __('Bienvenue sur') }} {{ config('app.name') }}</h2>
                                </div>
                                <div data-swiper-parallax="400" class="slide-text">
                                    <p>{{ __('Découvrez nos derniers articles, actualités et conseils.') }}</p>
                                </div>
                                <div class="clearfix"></div>
                                <div data-swiper-parallax="500" class="slide-btns">
                                    <a href="{{ route('blog.index') }}" class="theme-btn">{{ __('Lire le blog') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- end hero slider -->

    <!-- start wpo-blog-section -->
    <section class="wpo-blog-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="wpo-section-title">
                        <span>{{ __('Dernières actualités') }}</span>
                        <h2>{{ __('Nos articles récents') }}</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                @forelse($articles as $article)
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="post format-standard-image">
                            <div class="entry-media">
                                @if($article->featured_image)
                                    <img src="{{ asset($article->featured_image) }}" alt="{{ $article->title }}">
                                @else
                                    <img src="{{ fronttheme_asset('images/blog/img-' . (($loop->index % 3) + 1) . '.jpg') }}" alt="{{ $article->title }}">
                                @endif
                            </div>
                            <div class="entry-meta">
                                <ul>
                                    <li><i class="fi flaticon-user"></i> {{ __('Par') }} <a href="#">{{ $article->user->name ?? 'Admin' }}</a></li>
                                    <li><i class="fi flaticon-calendar"></i> {{ $article->published_at?->format('d M Y') }}</li>
                                </ul>
                            </div>
                            <div class="entry-details">
                                <h3><a href="{{ route('blog.show', $article->slug) }}">{{ $article->title }}</a></h3>
                                <p>{{ Str::limit($article->excerpt ?? strip_tags($article->content), 120) }}</p>
                                <a href="{{ route('blog.show', $article->slug) }}" class="read-more">{{ __('LIRE LA SUITE...') }}</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info">{{ __('Aucun article pour le moment.') }}</div>
                    </div>
                @endforelse
            </div>
            @if($articles->hasPages())
                <div class="row">
                    <div class="col-12">
                        <div class="pagination-wrapper pagination-wrapper-left">
                            {{ $articles->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
    <!-- end wpo-blog-section -->

@endsection

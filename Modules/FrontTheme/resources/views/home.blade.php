@extends(fronttheme_layout())

@section('title', config('app.name'))

@section('content')
        <!-- start of wpo-blog-hero -->
        <div class="wpo-blog-hero-area">
            <div class="container">
                <div class="sortable-gallery">
                    <div class="gallery-filters"></div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="wpo-blog-grids gallery-container clearfix">
                                @if($articles->count() > 0)
                                @php $hero1 = $articles[0]; @endphp
                                <div class="grid">
                                    <div class="img-holder">
                                        <img src="{{ $hero1->featured_image ? asset($hero1->featured_image) : fronttheme_asset('images/hero/img-1.jpg') }}" alt class="img img-responsive">
                                        <div class="wpo-blog-content">
                                            <div class="thumb">{{ $hero1->blogCategory->name ?? __('Général') }}</div>
                                            <h2><a href="{{ route('blog.show', $hero1->slug) }}">{{ $hero1->title }}</a></h2>
                                            <p>{{ Str::limit($hero1->excerpt ?? strip_tags($hero1->content), 120) }}</p>
                                            <ul>
                                                <li><img src="{{ asset('images/logo.webp') }}" alt="" style="width:30px;height:30px;border-radius:50%;"></li>
                                                <li>{{ __('Par') }} <a href="{{ route('blog.show', $hero1->slug) }}">{{ $hero1->user->name ?? 'Admin' }}</a></li>
                                                <li>{{ $hero1->published_at?->format('d M Y') }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if($articles->count() > 1)
                                @php $hero2 = $articles[1]; @endphp
                                <div class="grid">
                                    <div class="img-holder">
                                        <img src="{{ $hero2->featured_image ? asset($hero2->featured_image) : fronttheme_asset('images/hero/img-2.jpg') }}" alt class="img img-responsive">
                                        <div class="wpo-blog-content">
                                            <div class="thumb">{{ $hero2->blogCategory->name ?? __('Général') }}</div>
                                            <h2><a href="{{ route('blog.show', $hero2->slug) }}">{{ $hero2->title }}</a></h2>
                                            <ul>
                                                <li>{{ __('Par') }} <a href="{{ route('blog.show', $hero2->slug) }}">{{ $hero2->user->name ?? 'Admin' }}</a></li>
                                                <li>{{ $hero2->published_at?->format('d M Y') }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if($articles->count() > 2)
                                <div class="grid s2">
                                    @php $hero3 = $articles[2]; @endphp
                                    <div class="img-holder">
                                        <img src="{{ $hero3->featured_image ? asset($hero3->featured_image) : fronttheme_asset('images/hero/img-3.jpg') }}" alt class="img img-responsive">
                                        <div class="wpo-blog-content">
                                            <div class="thumb">{{ $hero3->blogCategory->name ?? __('Général') }}</div>
                                            <h2><a href="{{ route('blog.show', $hero3->slug) }}">{{ $hero3->title }}</a></h2>
                                            <ul>
                                                <li>{{ __('Par') }} <a href="{{ route('blog.show', $hero3->slug) }}">{{ $hero3->user->name ?? 'Admin' }}</a></li>
                                                <li>{{ $hero3->published_at?->format('d M Y') }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                    @if($articles->count() > 3)
                                    @php $hero4 = $articles[3]; @endphp
                                    <div class="img-holder">
                                        <img src="{{ $hero4->featured_image ? asset($hero4->featured_image) : fronttheme_asset('images/hero/img-4.jpg') }}" alt class="img img-responsive">
                                        <div class="wpo-blog-content">
                                            <div class="thumb">{{ $hero4->blogCategory->name ?? __('Général') }}</div>
                                            <h2><a href="{{ route('blog.show', $hero4->slug) }}">{{ $hero4->title }}</a></h2>
                                            <ul>
                                                <li>{{ __('Par') }} <a href="{{ route('blog.show', $hero4->slug) }}">{{ $hero4->user->name ?? 'Admin' }}</a></li>
                                                <li>{{ $hero4->published_at?->format('d M Y') }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end of wpo-blog-hero -->

        <!-- start of wpo-breacking-news -->
        <div class="wpo-breacking-news section-padding">
            <div class="container">
                <div class="row">
                    <div class="b-title"><span>{{ __('Dernières nouvelles') }}</span></div>
                    <div class="wpo-breacking-wrap owl-carousel">
                        @foreach($articles->take(9) as $breaking)
                        <div class="wpo-breacking-item{{ $loop->first ? ' s1' : '' }}">
                            <div class="wpo-breacking-img">
                                <img src="{{ $breaking->featured_image ? asset($breaking->featured_image) : fronttheme_asset('images/breaking-news/img-' . (($loop->index % 3) + 1) . '.jpg') }}" alt="">
                            </div>
                            <div class="wpo-breacking-text">
                                <span>{{ $breaking->published_at?->format('d M Y') }}</span>
                                <h3><a href="{{ route('blog.show', $breaking->slug) }}">{{ $breaking->title }}</a></h3>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <!-- end of wpo-breacking-news -->

        <!-- start wpo-blog-highlights-section -->
        <section class="wpo-blog-highlights-section">
            <div class="container">
                <div class="wpo-section-title">
                    <h2>{{ __('Articles à la une') }}</h2>
                </div>
                <div class="row">
                    <div class="col col-lg-8 col-12">
                        <!-- start wpo-blog-section -->
                        <div class="wpo-blog-highlights-wrap">
                            <div class="wpo-blog-items">
                                <div class="row">
                                    @foreach($articles->take(6) as $highlight)
                                    <div class="col col-lg-6 col-md-6 col-12">
                                        <div class="wpo-blog-item">
                                            <div class="wpo-blog-img">
                                                <img src="{{ $highlight->featured_image ? asset($highlight->featured_image) : fronttheme_asset('images/blog/img-' . ($loop->iteration) . '.jpg') }}" alt="">
                                                <div class="thumb">{{ $highlight->blogCategory->name ?? __('Général') }}</div>
                                            </div>
                                            <div class="wpo-blog-content">
                                                <h2><a href="{{ route('blog.show', $highlight->slug) }}">{{ $highlight->title }}</a></h2>
                                                <ul>
                                                    <li><img src="{{ asset('images/logo.webp') }}" alt="" style="width:30px;height:30px;border-radius:50%;"></li>
                                                    <li>{{ __('Par') }} <a href="{{ route('blog.show', $highlight->slug) }}">{{ $highlight->user->name ?? 'Admin' }}</a></li>
                                                    <li>{{ $highlight->published_at?->format('d M Y') }}</li>
                                                </ul>
                                                <p>{{ Str::limit($highlight->excerpt ?? strip_tags($highlight->content), 100) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <!-- end wpo-blog-section -->
                    </div>
                    <div class="col col-lg-4 col-12">
                        <div class="blog-sidebar">
                            <div class="widget category-widget">
                                <h3>{{ __('Catégories') }}</h3>
                                <ul>
                                    @foreach($categories as $cat)
                                    <li><a href="{{ route('blog.category', $cat->slug) }}">{{ $cat->name }}<span>({{ str_pad((string)($cat->articles_count ?? 0), 2, '0', STR_PAD_LEFT) }})</span></a></li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="widget recent-post-widget">
                                <h3>{{ __('Articles populaires') }}</h3>
                                <div class="posts">
                                    @foreach($recentArticles as $recent)
                                    <div class="post">
                                        <div class="img-holder">
                                            @if($recent->featured_image)
                                                <img src="{{ asset($recent->featured_image) }}" alt>
                                            @else
                                                <img src="{{ fronttheme_asset('images/recent-posts/img-' . ($loop->iteration) . '.jpg') }}" alt>
                                            @endif
                                        </div>
                                        <div class="details">
                                            <span class="date">{{ $recent->published_at?->format('d M Y') }}</span>
                                            <h4><a href="{{ route('blog.show', $recent->slug) }}">{{ $recent->title }}</a></h4>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end container -->
        </section>
        <!-- end wpo-blog-highlights-section -->

        <!-- start wpo-blog-sponsored-section -->
        <section class="wpo-blog-sponsored-section section-padding">
            <div class="container">
                <div class="wpo-section-title">
                    <h2>{{ __('Encore plus d\'articles') }}</h2>
                </div>
                <div class="row">
                    <div class="wpo-blog-sponsored-wrap">
                        <div class="wpo-blog-items">
                            <div class="row">
                                @foreach($articles->skip(6)->take(4) as $sponsored)
                                <div class="col col-xl-3 col-lg-6 col-md-6 col-12">
                                    <div class="wpo-blog-item">
                                        <div class="wpo-blog-img">
                                            <img src="{{ $sponsored->featured_image ? asset($sponsored->featured_image) : fronttheme_asset('images/sponsord/img-' . ($loop->iteration) . '.jpg') }}" alt="">
                                            <div class="thumb">{{ $sponsored->blogCategory->name ?? __('Général') }}</div>
                                        </div>
                                        <div class="wpo-blog-content">
                                            <h2><a href="{{ route('blog.show', $sponsored->slug) }}">{{ $sponsored->title }}</a></h2>
                                            <ul>
                                                <li><img src="{{ asset('images/logo.webp') }}" alt="" style="width:30px;height:30px;border-radius:50%;"></li>
                                                <li>{{ __('Par') }} <a href="{{ route('blog.show', $sponsored->slug) }}">{{ $sponsored->user->name ?? 'Admin' }}</a></li>
                                                <li>{{ $sponsored->published_at?->format('d M Y') }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end container -->
        </section>
        <!-- end wpo-blog-sponsored-section -->

        <!-- start wpo-subscribe-section -->
        <section class="wpo-subscribe-section section-padding">
            <div class="container">
                <div class="wpo-subscribe-wrap">
                    <div class="subscribe-text">
                        <h3>{{ __('Ne manquez aucune mise à jour !') }}</h3>
                        <p>{{ __('Recevez les dernières nouvelles et mises à jour directement dans votre boite courriel.') }}</p>
                    </div>
                    <div class="subscribe-form">
                        <form action="{{ Route::has('newsletter.subscribe') ? route('newsletter.subscribe') : '#' }}" method="POST">
                            @csrf
                            <div class="input-field">
                                <input type="email" name="email" placeholder="{{ __('Entrez votre courriel') }}" required>
                                <button type="submit"><i class="fi flaticon-send"></i> {{ __('S\'inscrire') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div> <!-- end container -->
        </section>
        <!-- end subscribe-section -->
@endsection

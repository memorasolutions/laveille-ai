<!-- Start header -->
<header id="header" class="wpo-site-header">
    <div class="topbar">
        <div class="container">
            <div class="row">
                <div class="col col-lg-7 col-md-9 col-sm-12 col-12">
                    <div class="contact-intro">
                        <ul>
                            <li class="update"><span>New Update</span></li>
                            <li>{{ $latestArticle->title ?? __('Bienvenue sur le blog') }}</li>
                        </ul>
                    </div>
                </div>
                <div class="col col-lg-5 col-md-3 col-sm-12 col-12">
                    <div class="contact-info">
                        <ul>
                            <li><a href="https://www.facebook.com/LaVeilleDeStef" target="_blank" rel="noopener"><i class="ti-facebook"></i></a></li>
                            <li><a href="https://m.me/LaVeilleDeStef" target="_blank" rel="noopener"><i class="ti-comment"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- end topbar -->
    <nav class="navigation navbar navbar-expand-lg navbar-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-3 col-md-3 col-3 d-lg-none dl-block">
                    <div class="mobail-menu">
                        <button type="button" class="navbar-toggler open-btn">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar first-angle"></span>
                            <span class="icon-bar middle-angle"></span>
                            <span class="icon-bar last-angle"></span>
                        </button>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 col-6">
                    <div class="navbar-header">
                        <a class="navbar-brand" href="{{ route('home') }}"><img src="{{ asset('images/logo.webp') }}" alt="{{ config('app.name') }}" style="max-height: 50px; width: auto;"></a>
                    </div>
                </div>
                <div class="col-lg-8 col-md-1 col-1">
                    <div id="navbar" class="collapse navbar-collapse navigation-holder">
                        <button class="menu-close"><i class="ti-close"></i></button>
                        <ul class="nav navbar-nav mb-2 mb-lg-0">
                            <li><a href="{{ route('home') }}">{{ __('Accueil') }}</a></li>
                            <li><a href="{{ route('blog.index') }}">{{ __('Blog') }}</a></li>
                            <li class="menu-item-has-children">
                                <a href="#">{{ __('Pages') }}</a>
                                <ul class="sub-menu">
                                    <li><a href="{{ route('blog.index') }}">{{ __('Archives') }}</a></li>
                                    <li><a href="{{ route('contact') }}">{{ __('Contact') }}</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div><!-- end of nav-collapse -->
                </div>
                <div class="col-lg-2 col-md-2 col-2">
                    <div class="header-right">
                        <div class="header-search-form-wrapper">
                            <div class="cart-search-contact">
                                <button class="search-toggle-btn"><i
                                        class="fi flaticon-magnifiying-glass"></i></button>
                                <div class="header-search-form">
                                    <form action="{{ route('blog.index') }}" method="GET">
                                        <div>
                                            <input type="text" name="search" class="form-control"
                                                placeholder="{{ __('Rechercher...') }}">
                                            <button type="submit"><i
                                                    class="fi flaticon-magnifiying-glass"></i></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="header-right-menu-wrapper">
                            <div class="header-right-menu">
                                <div class="right-menu-toggle-btn">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                                <div class="header-right-menu-wrap">
                                    <button class="right-menu-close"><i class="ti-close"></i></button>
                                    <div class="logo"><img src="{{ asset('images/logo.webp') }}" alt=""></div>
                                    <div class="header-right-sec">
                                        <div class="project-widget widget">
                                            <h3>{{ __('Derniers articles') }}</h3>
                                            <div class="posts">
                                                @isset($latestArticles)
                                                    @forelse($latestArticles->take(3) as $article)
                                                        <div class="post">
                                                            <div class="img-holder">
                                                                @if($article->featured_image)
                                                                    <img src="{{ $article->featured_image }}" alt="{{ $article->title }}">
                                                                @else
                                                                    <img src="{{ fronttheme_asset('images/recent-posts/img-' . ($loop->iteration) . '.jpg') }}" alt="">
                                                                @endif
                                                            </div>
                                                            <div class="details">
                                                                <span class="date">{{ $article->published_at?->format('d M Y') }}</span>
                                                                <h4><a href="{{ route('blog.show', $article->slug) }}">{{ $article->title }}</a></h4>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <p>{{ __('Aucun article pour le moment.') }}</p>
                                                    @endforelse
                                                @endisset
                                            </div>
                                        </div>
                                        <div class="widget wpo-contact-widget">
                                            <div class="widget-title">
                                                <h3>{{ __('Contact') }}</h3>
                                            </div>
                                            <div class="contact-ft">
                                                <ul>
                                                    <li><i class="fi flaticon-email"></i>{{ config('mail.from.address') }}</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- end of container -->
    </nav>
</header>
<!-- end of header -->

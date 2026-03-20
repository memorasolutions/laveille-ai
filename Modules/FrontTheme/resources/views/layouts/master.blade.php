<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" type="image/png" href="{{ fronttheme_asset('images/favicon.png') }}">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', 'Votre source d\'information sur l\'intelligence artificielle et les technologies au Québec.')">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('meta_description', 'Votre source d\'information sur l\'intelligence artificielle et les technologies au Québec.')">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="{{ url()->current() }}">
    <link href="{{ fronttheme_asset('css/themify-icons.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/flaticon.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/animate.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/owl.carousel.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/owl.theme.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/slick.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/slick-theme.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/swiper.min.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/owl.transitions.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/jquery.fancybox.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/odometer-theme-default.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/component.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('sass/style.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/responsive.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link href="{{ asset('css/charte.css') }}?v={{ filemtime(public_path('css/charte.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/components.css') }}?v={{ filemtime(public_path('css/components.css')) }}" rel="stylesheet">
    <style>
        /* Tailles spécifiques au layout */
        .navbar-brand img { max-height: 50px; width: auto; }
        .wpo-site-footer .about-widget .logo img { max-width: 120px; height: auto; }
        .header-right-menu-wrap .logo img { max-width: 150px; height: auto; }
        .blog-sidebar .about-widget .img-holder img { max-width: 150px; height: auto; border-radius: 50%; }
        /* SVG sans dimension */
        svg:not([width]):not([class*="cc-"]) { max-width: 24px; max-height: 24px; }
        .entry-details svg, .wpo-blog-content svg { max-width: 24px; max-height: 24px; }
        /* Hero grid proportions */
        .wpo-blog-hero-area .wpo-blog-grids .grid:first-child .img-holder { height: 530px; overflow: hidden; }
        .wpo-blog-hero-area .wpo-blog-grids .grid:first-child .img-holder img,
        .wpo-blog-hero-area .wpo-blog-grids .grid:nth-child(2) .img-holder img,
        .wpo-blog-hero-area .wpo-blog-grids .grid.s2 .img-holder img { width: 100%; height: 100%; object-fit: cover; }
        .wpo-blog-hero-area .wpo-blog-grids .grid:nth-child(2) .img-holder,
        .wpo-blog-hero-area .wpo-blog-grids .grid.s2 .img-holder { height: 250px; overflow: hidden; }
        .wpo-blog-hero-area .wpo-blog-content h2 { overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; }
        .wpo-blog-hero-area .grid.s2 .wpo-blog-content h2 { -webkit-line-clamp: 2; font-size: 16px; }
        .wpo-blog-hero-area .wpo-blog-content p { overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        @media (max-width: 1200px) {
            .wpo-blog-hero-area .wpo-blog-grids .grid:first-child .img-holder,
            .wpo-blog-hero-area .wpo-blog-grids .grid:nth-child(2) .img-holder,
            .wpo-blog-hero-area .wpo-blog-grids .grid.s2 .img-holder { height: auto; }
        }
        /* Images articles uniformes */
        .wpo-blog-img img { width: 100%; height: 220px; object-fit: cover; }
        .wpo-breacking-img img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; }
        .wpo-blog-sponsored-section .wpo-blog-img img { height: 180px; }
    </style>
    @stack('styles')
</head>

<body>

    <!-- start page-wrapper -->
    <div class="page-wrapper">
        <!-- start preloader -->
        <div class="preloader">
            <div class="angular-shape">
                <div></div>
                <div></div>
                <div></div>
            </div>
            <div class="spinner">
                <div class="double-bounce1"></div>
                <div class="double-bounce2"></div>
            </div>
        </div>
        <!-- end preloader -->

        @include('fronttheme::partials.header')

        @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
            @php $adLeaderboard = app(\Modules\Ads\Services\AdsRenderer::class)->render('header-leaderboard'); @endphp
            @if($adLeaderboard)
                <div class="container" style="padding:1rem 0;">{!! $adLeaderboard !!}</div>
            @endif
        @endif

        @yield('breadcrumb')

        @yield('content')

        @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
            @php $adFooter = app(\Modules\Ads\Services\AdsRenderer::class)->render('footer-banner'); @endphp
            @if($adFooter)
                <div class="container" style="padding:1rem 0;">{!! $adFooter !!}</div>
            @endif
        @endif

        @include('fronttheme::partials.footer')
    </div>
    <!-- end of page-wrapper -->

    @if(class_exists(\Nwidart\Modules\Facades\Module::class) && \Nwidart\Modules\Facades\Module::find('Privacy')?->isEnabled())
        @include('privacy::partials.cookie-consent')
    @endif

    <!-- All JavaScript files -->
    <script src="{{ fronttheme_asset('js/jquery.min.js') }}"></script>
    <script src="{{ fronttheme_asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ fronttheme_asset('js/modernizr.custom.js') }}"></script>
    <script src="{{ fronttheme_asset('js/jquery.dlmenu.js') }}"></script>
    <script src="{{ fronttheme_asset('js/jquery-plugin-collection.js') }}"></script>
    <script src="{{ fronttheme_asset('js/script.js') }}"></script>
    <script>document.querySelectorAll('img:not([loading])').forEach(function(img,i){if(i>0)img.loading='lazy'});</script>
    @stack('scripts')
</body>

</html>

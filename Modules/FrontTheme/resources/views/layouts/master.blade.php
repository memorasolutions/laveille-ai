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
    <style>
        /* Logo navbar */
        .navbar-brand img { max-height: 50px; width: auto; }
        /* Breadcrumb dégradé */
        .wpo-breadcumb-area { background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%) !important; }
        /* SVG et images sans dimension - empêcher le gigantisme */
        svg:not([width]):not([class*="cc-"]) { max-width: 24px; max-height: 24px; }
        img:not([width]):not([class*="img"]):not(.img-responsive) { max-width: 100%; height: auto; }
        .entry-details svg, .wpo-blog-content svg { max-width: 24px; max-height: 24px; }
        /* Hero grid : titres ne débordent pas */
        .wpo-blog-hero-area .wpo-blog-content h2 { overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; }
        .wpo-blog-hero-area .grid.s2 .wpo-blog-content h2 { -webkit-line-clamp: 2; font-size: 16px; }
        .wpo-blog-hero-area .wpo-blog-content p { overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        /* Footer + panneau latéral logo */
        .wpo-site-footer .about-widget .logo img { max-width: 120px; height: auto; }
        .header-right-menu-wrap .logo img { max-width: 150px; height: auto; }
        /* Sidebar about-widget logo */
        .blog-sidebar .about-widget .img-holder img { max-width: 150px; height: auto; border-radius: 50%; }
        /* Images articles : object-fit cover pour taille uniforme */
        .wpo-blog-img img { width: 100%; height: 220px; object-fit: cover; }
        .wpo-breacking-img img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; }
        .wpo-blog-pg-section .entry-media img { width: 100%; height: auto; object-fit: cover; }
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

        @yield('breadcrumb')

        @yield('content')

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
    @stack('scripts')
</body>

</html>

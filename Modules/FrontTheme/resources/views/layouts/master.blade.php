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
        .wpo-breadcumb-area { background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%) !important; }
        .navbar-brand img { max-height: 50px; width: auto; }
        /* Fix liens bleu Bootstrap - appliquer les couleurs du thème Bloggar */
        .wpo-blog-section .post h3 a,
        .wpo-blog-section .entry-details h3 a { color: #232F5F; text-decoration: none; }
        .wpo-blog-section .post h3 a:hover,
        .wpo-blog-section .entry-details h3 a:hover { color: #3756f7; }
        .wpo-blog-section .entry-meta ul li,
        .wpo-blog-section .entry-meta ul li a { color: #636893; text-decoration: none; }
        .wpo-blog-section .entry-details .read-more { color: #3756f7; text-decoration: none; text-transform: uppercase; font-weight: 600; font-size: 14px; }
        .wpo-blog-section .entry-details .read-more:hover { text-decoration: underline; }
        .wpo-blog-section .entry-details p { color: #444; }
        .wpo-blog-section .post { margin-bottom: 30px; }
        .wpo-blog-section .entry-media img { width: 100%; height: auto; }
        .wpo-blog-section .entry-meta { margin-bottom: 10px; }
        .wpo-blog-section .entry-meta ul { list-style: disc; padding-left: 20px; }
        .wpo-blog-section .entry-meta ul li { font-size: 14px; }
        /* Fix liens blog listing + catégorie (même problème Bootstrap) */
        .wpo-blog-pg-section .entry-details h3 a { color: #232F5F; text-decoration: none; }
        .wpo-blog-pg-section .entry-details h3 a:hover { color: #3756f7; }
        .wpo-blog-pg-section .entry-meta ul li a { color: #636893; text-decoration: none; }
        .wpo-blog-pg-section .entry-details .read-more { color: #3756f7; text-decoration: none; text-transform: uppercase; font-weight: 600; font-size: 14px; }
        .wpo-blog-pg-section .entry-details .read-more:hover { text-decoration: underline; }
        /* Fix liens blog single */
        .wpo-blog-single-section .entry-meta ul li a { color: #636893; text-decoration: none; }
        /* Fix liens globaux dans le contenu des articles */
        .wpo-blog-content a { color: #3756f7; }
        .wpo-blog-content a:hover { color: #232F5F; }
        /* Fix sidebar liens */
        .blog-sidebar .category-widget ul li a { color: #444; text-decoration: none; }
        .blog-sidebar .category-widget ul li a:hover { color: #3756f7; }
        .blog-sidebar .recent-post-widget .post h4 a { color: #232F5F; text-decoration: none; }
        .blog-sidebar .recent-post-widget .post h4 a:hover { color: #3756f7; }
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

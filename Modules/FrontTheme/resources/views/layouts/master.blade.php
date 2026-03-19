<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" type="image/png" href="{{ fronttheme_asset('images/favicon.png') }}">
    <title>@yield('title', config('app.name'))</title>
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

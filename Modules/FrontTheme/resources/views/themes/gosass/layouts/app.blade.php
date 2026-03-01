<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', config('app.name').' - Solution SaaS Laravel complète')">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('description', config('app.name').' - Solution SaaS Laravel complète')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary">
    @yield('meta')
    {!! \Modules\SEO\Services\JsonLdService::render(
        \Modules\SEO\Services\JsonLdService::organization(),
        \Modules\SEO\Services\JsonLdService::website()
    ) !!}
    <link href="{{ asset('themes/gosass/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('themes/gosass/css/fontawesome.min.css') }}" rel="stylesheet">
    <link href="{{ asset('themes/gosass/css/animate.css') }}" rel="stylesheet">
    <link href="{{ asset('themes/gosass/css/odometer.css') }}" rel="stylesheet">
    <link href="{{ asset('themes/gosass/css/slick.min.css') }}" rel="stylesheet">
    <link href="{{ asset('themes/gosass/css/style.css') }}" rel="stylesheet">
    @livewireStyles
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
</head>
<body>
    {{-- Preloader --}}
    <div class="cs_preloader cs_white_bg" aria-hidden="true">
        <div class="cs_preloader_in position-relative">
            <span></span><span></span>
        </div>
    </div>

    @include('fronttheme::themes.gosass.partials.header')

    <main id="main-content">
        @yield('content')
    </main>

    @include('fronttheme::themes.gosass.partials.footer')

    {{-- Scroll up button --}}
    <button type="button" aria-label="Scroll to top button" class="cs_scrollup cs_purple_bg cs_white_color cs_radius_100">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 10L1.7625 11.7625L8.75 4.7875V20H11.25V4.7875L18.225 11.775L20 10L10 0L0 10Z" fill="currentColor"/>
        </svg>
    </button>

    <script src="{{ asset('themes/gosass/js/jquery.min.js') }}"></script>
    <script src="{{ asset('build/nobleui/plugins/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('themes/gosass/js/wow.min.js') }}"></script>
    <script src="{{ asset('themes/gosass/js/jquery.slick.min.js') }}"></script>
    <script src="{{ asset('themes/gosass/js/odometer.js') }}"></script>
    <script src="{{ asset('themes/gosass/js/main.js') }}"></script>
    <script>if(typeof WOW!=='undefined'){new WOW({offset:0}).init();}</script>
    @include('partials.cookie-consent')
    @livewire('ai-chatbot')
    @livewireScripts
    @stack('scripts')
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js');
    }
    </script>
</body>
</html>

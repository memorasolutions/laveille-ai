<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/sass/nobleui/app.scss', 'resources/css/nobleui-custom.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        [x-cloak] { display: none !important; }
        .auth-hero {
            background: linear-gradient(135deg, #0c4a6e 0%, #0284c7 50%, #38bdf8 100%);
            min-height: 100vh;
        }
        .auth-hero-overlay {
            background: linear-gradient(to top, rgba(12, 74, 110, 0.8) 0%, transparent 100%);
        }
        .auth-check-icon {
            color: #38bdf8;
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <main>
        <div class="container-fluid g-0">
            <div class="row g-0">
                <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center bg-white p-4 p-sm-5" style="min-height: 100vh;">
                    <div class="w-100" style="max-width: 420px;">
                        <a href="{{ url('/') }}" class="d-block mb-4">
                            <img src="{{ asset('themes/gosass/img/logo.svg') }}" alt="{{ config('app.name') }}" style="height:40px;">
                        </a>
                        {{ $slot ?? '' }}
                        @yield('content')
                    </div>
                </div>
                <div class="col-12 col-lg-6 position-relative d-none d-lg-flex align-items-end auth-hero p-4 p-lg-5 pb-lg-5">
                    <div class="position-absolute top-0 start-0 w-100 h-100 auth-hero-overlay"></div>
                    <div class="position-relative w-100" style="max-width: 540px;">
                        <h3 class="display-5 fw-bold text-white mb-4">{{ config('app.name') }}</h3>
                        <ul class="list-unstyled row g-3">
                            <li class="col-12 col-sm-6 d-flex align-items-center gap-2">
                                <i data-lucide="check-circle" class="auth-check-icon" aria-hidden="true"></i>
                                <span class="fs-5 fw-medium text-white">{{ __('Authentification sécurisée') }}</span>
                            </li>
                            <li class="col-12 col-sm-6 d-flex align-items-center gap-2">
                                <i data-lucide="check-circle" class="auth-check-icon" aria-hidden="true"></i>
                                <span class="fs-5 fw-medium text-white">{{ __('Gestion simplifiée') }}</span>
                            </li>
                            <li class="col-12 col-sm-6 d-flex align-items-center gap-2">
                                <i data-lucide="check-circle" class="auth-check-icon" aria-hidden="true"></i>
                                <span class="fs-5 fw-medium text-white">{{ __('Mises à jour en temps réel') }}</span>
                            </li>
                            <li class="col-12 col-sm-6 d-flex align-items-center gap-2">
                                <i data-lucide="check-circle" class="auth-check-icon" aria-hidden="true"></i>
                                <span class="fs-5 fw-medium text-white">{{ __('Support 24/7') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="{{ asset('build/nobleui/plugins/lucide/lucide.min.js') }}"></script>
    @livewireScripts
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') { lucide.createIcons(); }
        });
    </script>
</body>
</html>

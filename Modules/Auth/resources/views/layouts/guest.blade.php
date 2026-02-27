<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    {{-- NobleUI Bootstrap 5.3.8 CSS --}}
    <link href="{{ asset('build/nobleui/assets/app-B-efjZPS.css') }}" rel="stylesheet">
    <link href="{{ asset('build/nobleui/assets/custom-tn0RQdqM.css') }}" rel="stylesheet">

    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f8f9fa; }
        .auth-wrapper { min-height: 100vh; }
        .auth-left {
            background: linear-gradient(135deg, rgba(101, 113, 255, 0.9), rgba(101, 113, 255, 0.7)),
                        url('{{ asset('assets/auth/login-bg.jpg') }}') center/cover no-repeat;
        }
        .auth-form-card {
            max-width: 440px;
            width: 100%;
        }
        [x-cloak] { display: none !important; }
    </style>

    @livewireStyles
    @vite(['resources/js/app.js'])
</head>

<body>

    <main class="auth-wrapper d-flex flex-wrap">
        {{-- Left panel - decorative (hidden on mobile) --}}
        <div class="auth-left d-none d-lg-flex col-lg-6 align-items-center justify-content-center text-white p-5">
            <div class="text-center">
                <h1 class="display-5 fw-bold mb-3">{{ config('app.name') }}</h1>
                <p class="lead opacity-75">{{ __('Votre espace sécurisé') }}</p>
            </div>
        </div>

        {{-- Right panel - form --}}
        <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5" style="min-height:100vh;">
            <div class="auth-form-card">
                <div class="mb-4">
                    <a href="{{ url('/') }}" class="d-inline-block mb-3">
                        <img src="{{ asset('themes/gosass/img/logo.svg') }}" alt="{{ config('app.name') }}" style="height:40px;">
                    </a>
                </div>

                {{ $slot ?? '' }}
                @yield('content')

            </div>
        </div>
    </main>

    {{-- Bootstrap JS --}}
    <script src="{{ asset('build/nobleui/plugins/bootstrap/bootstrap.bundle.min.js') }}"></script>
    @livewireScripts
    @stack('scripts')
</body>

</html>

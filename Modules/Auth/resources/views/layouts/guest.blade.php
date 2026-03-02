<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    @vite('resources/css/auth-guest.css')
    @livewireStyles
</head>
<body>
    <main class="auth-container">
        <div class="auth-form-col">
            <div class="auth-form-wrapper">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('themes/gosass/img/logo.svg') }}" alt="{{ config('app.name') }}" class="auth-logo">
                </a>
                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </div>

        <div class="auth-hero-col" style="background-image: url('{{ asset('images/auth-hero-bg.png') }}')">
            <div class="auth-hero-content">
                <h2 class="auth-hero-heading">{{ __('Bienvenue sur') }} {{ config('app.name') }}</h2>
                <ul class="auth-features">
                    <li>
                        <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>{{ __('Authentification sécurisée') }}</span>
                    </li>
                    <li>
                        <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>{{ __('Gestion simplifiée') }}</span>
                    </li>
                    <li>
                        <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>{{ __('Mises à jour en temps réel') }}</span>
                    </li>
                    <li>
                        <svg class="fi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>{{ __('Support 24/7') }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </main>
    @livewireScripts
    @stack('scripts')
</body>
</html>

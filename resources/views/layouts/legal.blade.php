{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="privacy-jurisdiction" content="{{ session('privacy_jurisdiction', 'pipeda') }}">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('auth/css/tailwind.css') }}">
</head>
<body class="bg-gray-50 text-gray-800 antialiased">
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-xl font-bold text-gray-900">
                <span class="first-letter:text-sky-500">{{ config('app.name') }}</span>
            </a>
            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ url('/privacy-policy') }}" class="text-gray-600 hover:text-gray-900">{{ __('Confidentialité') }}</a>
                <a href="{{ url('/terms-of-use') }}" class="text-gray-600 hover:text-gray-900">{{ __('Conditions') }}</a>
                <a href="{{ route('login') }}" class="text-sky-600 hover:text-sky-800 font-medium">{{ __('Connexion') }}</a>
            </nav>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8 sm:py-12">
        @yield('content')
    </main>

    <footer class="border-t border-gray-200 mt-12">
        <div class="max-w-4xl mx-auto px-4 py-6 text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} {{ config('privacy.company.name', config('app.name')) }}.
            {{ __('Tous droits réservés.') }}
        </div>
    </footer>

    @include('partials.cookie-consent')
</body>
</html>

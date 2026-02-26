<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('Administration')) - {{ $branding['site_name'] ?? config('app.name') }}</title>

    {{-- Dark mode: must run synchronously before render to avoid flash --}}
    <script src="{{ asset('build/nobleui/assets/color-modes-CkunOepb.js') }}"></script>
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">

    {{-- Favicon --}}
    @php
        $faviconUrl = !empty($branding['favicon'] ?? '') ? asset('storage/' . $branding['favicon']) : asset('favicon.ico');
    @endphp
    <link rel="shortcut icon" href="{{ $faviconUrl }}">

    {{-- Splash screen --}}
    <link href="{{ asset('build/nobleui/splash-screen.css') }}" rel="stylesheet">

    {{-- Plugin CSS --}}
    <link href="{{ asset('build/nobleui/plugins/perfect-scrollbar/perfect-scrollbar.css') }}" rel="stylesheet">
    @stack('plugin-styles')

    {{-- Main CSS (NobleUI Bootstrap 5.3.8 + theme) --}}
    <link href="{{ asset('build/nobleui/assets/app-B-efjZPS.css') }}" rel="stylesheet">
    <link href="{{ asset('build/nobleui/assets/custom-tn0RQdqM.css') }}" rel="stylesheet">

    {{-- Dynamic branding --}}
    @php $primaryColor = $branding['primary_color'] ?? '#6571ff'; @endphp
    <style>
        :root { --bs-primary: {{ $primaryColor }}; --bs-primary-rgb: {{ implode(',', sscanf($primaryColor, '#%02x%02x%02x')) }}; }
        /* Alpine.js x-cloak - hide until Alpine initializes */
        [x-cloak] { display: none !important; }
        /* Lucide icon sizing defaults - prevents giant SVGs */
        [data-lucide] { width: 18px; height: 18px; }
        .icon-sm [data-lucide], [data-lucide].icon-sm { width: 14px; height: 14px; }
        .icon-md [data-lucide], [data-lucide].icon-md { width: 20px; height: 20px; }
        .icon-lg [data-lucide], [data-lucide].icon-lg { width: 24px; height: 24px; }
        .icon-xl [data-lucide], [data-lucide].icon-xl { width: 32px; height: 32px; }
        .icon-xxl [data-lucide], [data-lucide].icon-xxl { width: 48px; height: 48px; }
    </style>

    @livewireStyles
    @stack('style')
    @stack('styles')

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="{{ $primaryColor }}">
</head>
<body class="sidebar-dark" data-base-url="{{ url('/') }}">

    {{-- Splash screen (premier chargement seulement) --}}
    <script>
        if (!sessionStorage.getItem('admin_loaded')) {
            var splash = document.createElement("div");
            splash.innerHTML = '<div class="splash-screen"><div class="logo"></div><div class="spinner"></div></div>';
            document.body.insertBefore(splash, document.body.firstChild);
            document.addEventListener("DOMContentLoaded", function() {
                document.body.classList.add("loaded");
                sessionStorage.setItem('admin_loaded', '1');
            });
        } else {
            document.body.classList.add("loaded");
        }
    </script>

    <div class="main-wrapper" id="app">
        @include('backoffice::themes.backend.partials.sidebar')
        <div class="page-wrapper">
            @include('backoffice::themes.backend.partials.header')
            <div class="page-content container-xxl">
                @include('backoffice::themes.backend.partials.toast')

                @yield('content')
            </div>
            @include('backoffice::themes.backend.partials.footer')
        </div>
    </div>

    {{-- Base JS --}}
    <script src="{{ asset('build/nobleui/assets/app-CAiCLEjY.js') }}"></script>
    <script src="{{ asset('build/nobleui/plugins/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('build/nobleui/plugins/lucide/lucide.min.js') }}"></script>
    <script src="{{ asset('build/nobleui/plugins/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>

    {{-- Plugin JS --}}
    @stack('plugin-scripts')

    {{-- Template JS (sidebar toggle, navbar, etc.) --}}
    <script src="{{ asset('build/nobleui/assets/template-B7IAR9tB.js') }}"></script>

    {{-- Vite app bundle (TipTap editor, axios, Echo) - must load BEFORE Livewire/Alpine --}}
    @vite('resources/js/app.js')

    @livewireScripts
    <script>
        document.addEventListener('livewire:init', () => {
            // Re-render Lucide icons after Livewire DOM morph
            Livewire.hook('morph.updated', ({el}) => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });

            // Global wire:loading - disable buttons & show spinner during Livewire requests
            Livewire.hook('commit', ({component, commit, respond, succeed, fail}) => {
                const el = component.el;
                const btns = el.querySelectorAll('button[wire\\:click], button[type="submit"]');
                btns.forEach(btn => {
                    if (btn.closest('[wire\\:ignore]')) return;
                    btn.disabled = true;
                    btn.dataset.originalHtml = btn.innerHTML;
                    const spinner = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>';
                    btn.innerHTML = spinner + (btn.textContent.trim() || '...');
                });
                const restore = () => {
                    btns.forEach(btn => {
                        btn.disabled = false;
                        if (btn.dataset.originalHtml) {
                            btn.innerHTML = btn.dataset.originalHtml;
                            delete btn.dataset.originalHtml;
                        }
                    });
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                };
                succeed(restore);
                fail(restore);
            });
        });
    </script>
    @stack('custom-scripts')
    @stack('scripts')
</body>
</html>

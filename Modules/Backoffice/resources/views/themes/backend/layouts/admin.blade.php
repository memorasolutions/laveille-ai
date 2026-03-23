<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('Administration')) - {{ $branding['site_name'] ?? config('app.name') }}</title>

    {{-- Dark mode: must run synchronously before render to avoid flash --}}
    @vite('resources/js/nobleui/color-modes.js')

    {{-- Fonts: Roboto self-hosted via @fontsource in app.scss (RGPD compliant) --}}

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

    {{-- Main CSS: NobleUI SCSS compiled via Vite (Bootstrap 5.3.8 + theme + components + plugin overrides) --}}
    @vite(['resources/sass/nobleui/app.scss', 'resources/css/nobleui-custom.css'])

    {{-- Sidebar collapse sub-menu: remove duplicate bullets --}}
    <style>.sidebar-body .collapse .sub-menu .nav-link::before { display: none !important; } .sidebar-body .collapse .sub-menu .nav-link { padding-left: 25px !important; }</style>

    {{-- Dynamic branding --}}
    @php
        $cssColors = [
            'primary'   => $branding['primary_color'] ?? '#6571ff',
            'secondary' => $branding['secondary_color'] ?? '#7987a1',
            'success'   => $branding['success_color'] ?? '#05a34a',
            'warning'   => $branding['warning_color'] ?? '#fbbc06',
            'danger'    => $branding['danger_color'] ?? '#ff3366',
            'info'      => $branding['info_color'] ?? '#66d1d1',
        ];
        $primaryColor = $cssColors['primary'];
    @endphp
    <style>
        :root {
            @foreach($cssColors as $name => $hex)
                --bs-{{ $name }}: {{ $hex }};
                --bs-{{ $name }}-rgb: {{ implode(',', sscanf($hex, '#%02x%02x%02x')) }};
            @endforeach
            --sidebar-bg: {{ $branding['sidebar_bg'] ?? '#0c1427' }};
            --header-bg: {{ $branding['header_bg'] ?? '#ffffff' }};
            --bs-body-bg: {{ $branding['body_bg'] ?? '#ffffff' }};
            --bs-app-bg: {{ $branding['body_bg'] ?? '#ffffff' }};
            --topbar-font-family: {{ $branding['topbar_font_family'] ?? 'Roboto' }}, sans-serif;
            --topbar-font-size: {{ $branding['topbar_font_size'] ?? '1.25rem' }};
            --topbar-font-weight: {{ $branding['topbar_font_weight'] ?? '700' }};
            --topbar-letter-spacing: {{ $branding['topbar_letter_spacing'] ?? '0px' }};
            --topbar-word-spacing: {{ $branding['topbar_word_spacing'] ?? '0px' }};
            --topbar-text-transform: {{ $branding['topbar_text_transform'] ?? 'none' }};
        }
    </style>

    {{-- WCAG 2.5.8 : cibles tactiles min 48px (WCAG 2.2) --}}
    <style>
        .sidebar .nav-link { min-height: 48px; padding-top: 10px; padding-bottom: 10px; display: flex; align-items: center; }
        .sidebar .sub-menu .nav-link { min-height: 44px; padding-top: 8px; padding-bottom: 8px; }
    </style>

    @livewireStyles
    @stack('style')
    @stack('styles')

    {{-- PWA --}}
    @if(config('pwa.enabled'))
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="{{ $primaryColor }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('pwa.short_name') }}">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon-180x180.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="{{ config('pwa.theme_color') }}">
    @endif
</head>
<body class="sidebar-dark" data-base-url="{{ url('/') }}">

    {{-- WCAG 2.4.1 - Skip to content --}}
    <a href="#main-content" class="visually-hidden-focusable position-fixed top-0 start-0 bg-primary text-white px-3 py-2 z-3 rounded-end-bottom fw-semibold" style="z-index:10001">
        {{ __('Aller au contenu') }}
    </a>

    {{-- Splash screen --}}
    <script>
        (function() {
            function markLoaded() {
                document.body.classList.add("loaded");
                sessionStorage.setItem('admin_loaded', '1');
            }
            if (sessionStorage.getItem('admin_loaded')) {
                markLoaded();
                return;
            }
            var splash = document.createElement("div");
            splash.innerHTML = '<div class="splash-screen"><div class="logo"></div><div class="spinner"></div></div>';
            document.body.insertBefore(splash, document.body.firstChild);
            if (document.readyState === 'loading') {
                document.addEventListener("DOMContentLoaded", markLoaded);
            } else {
                markLoaded();
            }
        })();
    </script>

    <div class="main-wrapper" id="app">
        @include('backoffice::themes.backend.partials.sidebar')
        <div class="page-wrapper">
            <header>
                @include('backoffice::themes.backend.partials.header')
            </header>
            <main id="main-content" class="page-content container-xxl">
                @include('backoffice::themes.backend.partials.toast')
                @yield('breadcrumbs')

                @yield('content')
            </main>
            @include('backoffice::themes.backend.partials.footer')
        </div>
    </div>
    @include('backoffice::themes.backend.partials.bottom-bar')

    {{-- Base JS --}}
    <script src="{{ asset('build/nobleui/plugins/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('build/nobleui/plugins/lucide/lucide.min.js') }}"></script>
    <script src="{{ asset('build/nobleui/plugins/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>

    {{-- Plugin JS --}}
    @stack('plugin-scripts')

    {{-- NobleUI template JS (sidebar toggle, tooltips, scrollbar, clipboard, lucide) --}}
    @vite('resources/js/nobleui/template.js')

    {{-- Vite app bundle (TipTap editor, Alpine, axios, Echo) --}}
    @vite('resources/js/app.js')

    @livewireScripts
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', ({el}) => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });

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
    {{-- WCAG 3.3.1 - Auto-link form errors with aria-describedby + aria-invalid --}}
    <script>
        document.querySelectorAll('.is-invalid').forEach(function(input) {
            var feedback = input.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                var errorId = 'error-' + (input.name || input.id || 'field').replace(/[\[\]\s]+/g, '-');
                feedback.id = errorId;
                feedback.setAttribute('role', 'alert');
                input.setAttribute('aria-describedby', errorId);
                input.setAttribute('aria-invalid', 'true');
            }
        });
    </script>
    @stack('custom-scripts')
    @stack('scripts')

    {{-- AI Chatbot (activable via Settings > IA > ai.chatbot_enabled) --}}
    @if(class_exists(\Nwidart\Modules\Facades\Module::class) && \Nwidart\Modules\Facades\Module::has('AI') && \Nwidart\Modules\Facades\Module::isEnabled('AI') && class_exists(\Modules\AI\Livewire\ChatBot::class))
        @livewire('ai-chatbot')
    @endif

    {{-- PWA prompts (production only - hidden in dev to avoid confusion) --}}
    @if(config('pwa.enabled') && app()->isProduction())
        <x-pwa-install-prompt />
        <x-pwa-update-prompt />
    @endif
</body>
</html>

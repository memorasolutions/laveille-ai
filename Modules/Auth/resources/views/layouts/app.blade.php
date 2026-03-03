<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@php
    $unreadCount = $unreadCount ?? (auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('Mon espace')) - {{ $branding['site_name'] ?? config('app.name') }}</title>

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

    {{-- Dynamic branding --}}
    @php $primaryColor = $branding['primary_color'] ?? '#6571ff'; @endphp
    <style>
        :root { --bs-primary: {{ $primaryColor }}; --bs-primary-rgb: {{ implode(',', sscanf($primaryColor, '#%02x%02x%02x')) }}; }
    </style>

    @livewireStyles
    @stack('style')
    @stack('styles')

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="{{ $primaryColor }}">
</head>
<body class="sidebar-dark" data-base-url="{{ url('/') }}">

    {{-- Splash screen --}}
    <script>
        if (!sessionStorage.getItem('user_loaded')) {
            var splash = document.createElement("div");
            splash.innerHTML = '<div class="splash-screen"><div class="logo"></div><div class="spinner"></div></div>';
            document.body.insertBefore(splash, document.body.firstChild);
            document.addEventListener("DOMContentLoaded", function() {
                document.body.classList.add("loaded");
                sessionStorage.setItem('user_loaded', '1');
            });
        } else {
            document.body.classList.add("loaded");
        }
    </script>

    <div class="main-wrapper" id="app">

        {{-- Sidebar --}}
        <nav class="sidebar" aria-label="Menu utilisateur">
            <div class="sidebar-header">
                <a href="{{ route('user.dashboard') }}" class="sidebar-brand">
                    {{ $branding['site_name'] ?? config('app.name') }}
                </a>
                <button type="button" class="sidebar-toggler not-active"
                        aria-label="Basculer le menu" aria-expanded="true" aria-controls="sidebarNav">
                    <span></span><span></span><span></span>
                </button>
            </div>

            {{-- Mobile close button --}}
            <button type="button" class="btn-close d-lg-none position-absolute top-0 end-0 m-3 sidebar-close"
                    aria-label="Fermer le menu"></button>

            <div class="sidebar-body">
                <ul class="nav" id="sidebarNav">

                    {{-- Dashboard --}}
                    <li class="nav-item nav-category">{{ __('Principal') }}</li>
                    <li class="nav-item {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('user.dashboard') }}" class="nav-link">
                            <i class="link-icon" data-lucide="home"></i>
                            <span class="link-title">{{ __('Tableau de bord') }}</span>
                        </a>
                    </li>

                    {{-- Contenu --}}
                    <li class="nav-item nav-category">{{ __('Contenu') }}</li>
                    <li class="nav-item {{ request()->routeIs('user.articles.*') ? 'active' : '' }}">
                        <a class="nav-link" data-bs-toggle="collapse" href="#articlesMenu" role="button"
                           aria-expanded="{{ request()->routeIs('user.articles.*') ? 'true' : 'false' }}">
                            <i class="link-icon" data-lucide="file-text"></i>
                            <span class="link-title">{{ __('Mes articles') }}</span>
                            <i class="link-arrow" data-lucide="chevron-down"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('user.articles.*') ? 'show' : '' }}"
                             id="articlesMenu" data-bs-parent="#sidebarNav">
                            <ul class="nav sub-menu">
                                <li class="nav-item">
                                    <a href="{{ route('user.articles.index') }}"
                                       class="nav-link {{ request()->routeIs('user.articles.index') ? 'active' : '' }}">
                                        {{ __('Liste') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('user.articles.create') }}"
                                       class="nav-link {{ request()->routeIs('user.articles.create') ? 'active' : '' }}">
                                        {{ __('Nouvel article') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    {{-- Compte --}}
                    <li class="nav-item nav-category">{{ __('Compte') }}</li>

                    <li class="nav-item {{ request()->routeIs('user.notifications') ? 'active' : '' }}">
                        <a href="{{ route('user.notifications') }}" class="nav-link">
                            <i class="link-icon" data-lucide="bell"></i>
                            <span class="link-title">{{ __('Notifications') }}</span>
                            @if(($unreadCount ?? 0) > 0)
                                <span class="badge bg-danger rounded-pill ms-auto">{{ $unreadCount }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="nav-item {{ request()->routeIs('user.profile', 'user.sessions', 'user.activity') ? 'active' : '' }}">
                        <a class="nav-link" data-bs-toggle="collapse" href="#profileMenu" role="button"
                           aria-expanded="{{ request()->routeIs('user.profile', 'user.sessions', 'user.activity') ? 'true' : 'false' }}">
                            <i class="link-icon" data-lucide="user"></i>
                            <span class="link-title">{{ __('Mon profil') }}</span>
                            <i class="link-arrow" data-lucide="chevron-down"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('user.profile', 'user.sessions', 'user.activity') ? 'show' : '' }}"
                             id="profileMenu" data-bs-parent="#sidebarNav">
                            <ul class="nav sub-menu">
                                <li class="nav-item">
                                    <a href="{{ route('user.profile') }}"
                                       class="nav-link {{ request()->routeIs('user.profile') ? 'active' : '' }}">
                                        {{ __('Informations') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('user.sessions') }}"
                                       class="nav-link {{ request()->routeIs('user.sessions') ? 'active' : '' }}">
                                        {{ __('Sessions') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('user.activity') }}"
                                       class="nav-link {{ request()->routeIs('user.activity') ? 'active' : '' }}">
                                        {{ __('Journal d\'activité') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item {{ request()->routeIs('user.subscription', 'user.invoices') ? 'active' : '' }}">
                        <a href="{{ route('user.subscription') }}" class="nav-link">
                            <i class="link-icon" data-lucide="credit-card"></i>
                            <span class="link-title">{{ __('Abonnement') }}</span>
                        </a>
                    </li>

                    <li class="nav-item {{ request()->routeIs('user.api-tokens') ? 'active' : '' }}">
                        <a href="{{ route('user.api-tokens') }}" class="nav-link">
                            <i class="link-icon" data-lucide="key"></i>
                            <span class="link-title">{{ __('Tokens API') }}</span>
                        </a>
                    </li>

                    {{-- Administration (admin/super_admin only) --}}
                    @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                        <li class="nav-item nav-category">{{ __('Administration') }}</li>
                        <li class="nav-item">
                            <a href="{{ url('/admin') }}" class="nav-link">
                                <i class="link-icon" data-lucide="shield"></i>
                                <span class="link-title">{{ __('Administration') }}</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </nav>

        {{-- Page wrapper --}}
        <div class="page-wrapper">

            {{-- Header / Navbar --}}
            <nav class="navbar">
                <div class="navbar-content">

                    {{-- Logo mini (mobile) --}}
                    <div class="d-flex d-lg-none align-items-center me-3">
                        <span class="fw-bold text-white rounded-circle d-flex align-items-center justify-content-center"
                              style="width:30px;height:30px;background:{{ $primaryColor }};font-size:14px;">
                            {{ strtoupper(substr($branding['site_name'] ?? config('app.name'), 0, 1)) }}
                        </span>
                    </div>

                    {{-- Search spacer --}}
                    <div class="d-none d-lg-block flex-grow-1"></div>

                    <ul class="navbar-nav">
                        {{-- Theme switcher --}}
                        <li class="nav-item">
                            <div class="theme-switcher-wrapper d-flex align-items-center px-2">
                                <input type="checkbox" class="checkbox d-none" id="theme-switcher">
                                <label for="theme-switcher" class="label d-flex align-items-center gap-1 cursor-pointer mb-0" style="cursor:pointer;">
                                    <i data-lucide="sun" style="width:16px;height:16px;"></i>
                                    <span class="ball"></span>
                                    <i data-lucide="moon" style="width:16px;height:16px;"></i>
                                </label>
                            </div>
                        </li>

                        {{-- Language switcher --}}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
                               data-bs-toggle="dropdown" aria-expanded="false">
                                @php $currentLocale = app()->getLocale(); @endphp
                                <img src="{{ asset('build/nobleui/plugins/flag-icons/flags/4x3/' . ($currentLocale === 'fr' ? 'fr' : 'us') . '.svg') }}"
                                     class="w-20px" alt="">
                                <span class="d-none d-md-inline-block ms-1">{{ strtoupper($currentLocale) }}</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('locale.switch', 'fr') }}">
                                    <img src="{{ asset('build/nobleui/plugins/flag-icons/flags/4x3/fr.svg') }}" class="w-20px" alt="">
                                    Français
                                </a>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('locale.switch', 'en') }}">
                                    <img src="{{ asset('build/nobleui/plugins/flag-icons/flags/4x3/us.svg') }}" class="w-20px" alt="">
                                    English
                                </a>
                            </div>
                        </li>

                        {{-- Notifications --}}
                        <li class="nav-item">
                            <a href="{{ route('user.notifications') }}" class="nav-link position-relative">
                                <i data-lucide="bell"></i>
                                @if(($unreadCount ?? 0) > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                          style="font-size:10px;">
                                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                    </span>
                                @endif
                            </a>
                        </li>

                        {{-- Profile dropdown --}}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#"
                               data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true">
                                @if(auth()->user()->avatar)
                                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}"
                                         class="rounded-circle" width="30" height="30" alt="{{ auth()->user()->name }}">
                                @else
                                    <span class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                          style="width:30px;height:30px;background:{{ $primaryColor }};font-size:12px;">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                    </span>
                                @endif
                                <span class="d-none d-md-inline-block">{{ auth()->user()->name }}</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end p-0" style="min-width:220px;">
                                <div class="d-flex align-items-center gap-3 p-3 border-bottom">
                                    @if(auth()->user()->avatar)
                                        <img src="{{ asset('storage/' . auth()->user()->avatar) }}"
                                             class="rounded-circle" width="50" height="50" alt="">
                                    @else
                                        <span class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                              style="width:50px;height:50px;background:{{ $primaryColor }};font-size:16px;">
                                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                        </span>
                                    @endif
                                    <div>
                                        <p class="mb-0 fw-semibold">{{ auth()->user()->name }}</p>
                                        <small class="text-muted">{{ auth()->user()->email }}</small>
                                    </div>
                                </div>
                                <div class="py-2">
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('user.profile') }}">
                                        <i data-lucide="user" style="width:16px;height:16px;"></i>
                                        {{ __('Profil') }}
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('user.notifications') }}">
                                        <i data-lucide="bell" style="width:16px;height:16px;"></i>
                                        {{ __('Notifications') }}
                                        @if(($unreadCount ?? 0) > 0)
                                            <span class="badge bg-danger rounded-pill ms-auto">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                                        @endif
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('user.subscription') }}">
                                        <i data-lucide="credit-card" style="width:16px;height:16px;"></i>
                                        {{ __('Abonnement') }}
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('user.sessions') }}">
                                        <i data-lucide="monitor" style="width:16px;height:16px;"></i>
                                        {{ __('Sessions') }}
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('user.activity') }}">
                                        <i data-lucide="activity" style="width:16px;height:16px;"></i>
                                        {{ __('Activité') }}
                                    </a>
                                    @if(auth()->user()->hasRole(['admin', 'super_admin']))
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.dashboard') }}">
                                            <i data-lucide="shield" style="width:16px;height:16px;"></i>
                                            {{ __('Administration') }}
                                        </a>
                                    @endif
                                    <div class="dropdown-divider"></div>
                                    <form method="POST" action="{{ route('logout') }}" id="user-logout-form">
                                        @csrf
                                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger"
                                                onclick="document.getElementById('user-logout-form').submit();">
                                            <i data-lucide="log-out" style="width:16px;height:16px;"></i>
                                            {{ __('Déconnexion') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    </ul>

                    {{-- Mobile sidebar toggler --}}
                    <button type="button" class="sidebar-toggler d-lg-none ms-2"
                            aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="sidebarNav">
                        <i data-lucide="menu"></i>
                    </button>
                </div>
            </nav>

            {{-- Page content --}}
            <div class="page-content container-xxl">

                {{-- Onboarding wizard --}}
                @if(auth()->user() && !auth()->user()->onboarding_completed_at)
                    @livewire(\Modules\Auth\Livewire\OnboardingWizard::class)
                @endif

                {{-- Flash messages --}}
                @if(session('success'))
                    <div class="alert alert-success d-flex align-items-center gap-2 alert-dismissible fade show mb-3" role="alert"
                         x-data="{ show: true }" x-show="show" x-transition>
                        <i data-lucide="check-circle" class="icon-sm"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" @click="show = false" aria-label="Fermer"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger d-flex align-items-center gap-2 alert-dismissible fade show mb-3" role="alert"
                         x-data="{ show: true }" x-show="show" x-transition>
                        <i data-lucide="alert-circle" class="icon-sm"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" @click="show = false" aria-label="Fermer"></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning d-flex align-items-center gap-2 alert-dismissible fade show mb-3" role="alert"
                         x-data="{ show: true }" x-show="show" x-transition>
                        <i data-lucide="alert-triangle" class="icon-sm"></i>
                        {{ session('warning') }}
                        <button type="button" class="btn-close" @click="show = false" aria-label="Fermer"></button>
                    </div>
                @endif

                @yield('content')
            </div>

            {{-- Footer --}}
            <footer class="footer d-flex align-items-center">
                <div class="container-xxl d-flex align-items-center justify-content-between flex-wrap py-3">
                    <p class="text-muted mb-0">&copy; {{ date('Y') }} {{ $branding['site_name'] ?? config('app.name') }}. {{ __('Tous droits réservés.') }}</p>
                </div>
            </footer>
        </div>
    </div>

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
    @stack('custom-scripts')
    @stack('scripts')

    {{-- AI Chatbot --}}
    @livewire('ai-chatbot')
</body>
</html>

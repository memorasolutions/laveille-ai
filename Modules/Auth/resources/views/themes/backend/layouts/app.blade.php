<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Mon espace')) - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6610f2">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <!-- Backend (Jobick) CSS -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/backend/icons/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/backend/vendor/bootstrap/scss/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/backend/css/style.css') }}">
    <style>
        [data-lucide] { width: 18px; height: 18px; }
        .icon-sm [data-lucide], [data-lucide].icon-sm { width: 14px; height: 14px; }
        .icon-md [data-lucide], [data-lucide].icon-md { width: 20px; height: 20px; }
        .icon-lg [data-lucide], [data-lucide].icon-lg { width: 24px; height: 24px; }
    </style>
    @vite(['resources/js/app.js'])
    @livewireStyles
    @stack('css')
</head>
<body>

@php
    $unreadCount  = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    $recentNotifs = auth()->check() ? auth()->user()->notifications()->latest()->limit(5)->get() : collect();
    $siteName = config('app.name');
    $initial  = strtoupper(substr($siteName, 0, 1));
    $color    = '#6610f2';
    $svgBase  = '<circle cx="18" cy="18" r="15" fill="' . $color . '"/><text x="18" y="23" text-anchor="middle" font-family="Inter,sans-serif" font-size="14" font-weight="700" fill="white">' . htmlspecialchars($initial) . '</text>';
    $svgLight = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="36">' . $svgBase . '<text x="42" y="23" font-family="Inter,sans-serif" font-size="14" font-weight="600" fill="#1f2937">' . htmlspecialchars($siteName) . '</text></svg>');
    $svgDark  = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="36">' . $svgBase . '<text x="42" y="23" font-family="Inter,sans-serif" font-size="14" font-weight="600" fill="#f1f5f9">' . htmlspecialchars($siteName) . '</text></svg>');
    $svgIcon  = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"><circle cx="16" cy="16" r="14" fill="' . $color . '"/><text x="16" y="21" text-anchor="middle" font-family="Inter,sans-serif" font-size="14" font-weight="700" fill="white">' . htmlspecialchars($initial) . '</text></svg>');
@endphp

<!-- Preloader -->
<div id="preloader" class="bg-white h-100 fixed-top w-100 d-flex align-items-center justify-content-center" style="z-index:99999;">
    <div class="lds-ripple position-relative d-inline-block" style="width:5rem;height:5rem;">
        <div class="position-absolute border border-4 border-primary rounded-circle"></div>
        <div class="position-absolute border border-4 border-primary rounded-circle"></div>
    </div>
</div>

<!-- =================== MAIN WRAPPER =================== -->
<div id="main-wrapper" class="position-relative">

    <!-- Nav header (logo + hamburger) -->
    <div class="nav-header">
        <a href="{{ route('user.dashboard') }}" class="brand-logo">
            <img src="{{ $svgLight }}" alt="{{ $siteName }}" class="brand-title light-logo" style="height:2rem;">
            <img src="{{ $svgDark }}" alt="{{ $siteName }}" class="brand-title dark-logo" style="height:2rem;display:none;">
        </a>
        <div class="nav-control">
            <div class="hamburger" aria-label="{{ __('Basculer le menu') }}" role="button" tabindex="0">
                <span class="line"></span>
                <span class="line"></span>
                <span class="line"></span>
            </div>
        </div>
    </div>

    <!-- =================== TOPBAR =================== -->
    <div class="header">
        <div class="header-content">
            <nav class="navbar navbar-expand" aria-label="{{ __('Barre de navigation') }}">
                <div class="navbar-collapse justify-content-between">

                    <!-- Left: page title -->
                    <div class="header-left">
                        <div class="dashboard_bar">@yield('title', __('Tableau de bord'))</div>
                    </div>

                    <!-- Right: actions -->
                    <ul class="navbar-nav header-right align-items-center gap-2">

                        <!-- Dark mode toggle -->
                        <li class="notification_dropdown">
                            <button type="button"
                                    id="backend-dark-mode-toggle"
                                    class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                    style="width:2.8rem;height:2.8rem;border:none;"
                                    aria-label="{{ __('Basculer le mode sombre') }}"
                                    data-theme-toggle>
                                <i class="fa fa-sun text-primary" id="backend-sun-icon"></i>
                                <i class="fa fa-moon text-primary d-none" id="backend-moon-icon"></i>
                            </button>
                        </li>

                        <!-- Langue dropdown (Alpine.js) -->
                        <li class="notification_dropdown d-none d-sm-flex" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                    style="width:2.8rem;height:2.8rem;border:none;"
                                    aria-label="{{ __('Choisir la langue') }}"
                                    :aria-expanded="open">
                                <i class="fa fa-globe text-primary"></i>
                            </button>
                            <div x-show="open"
                                 @click.away="open = false"
                                 x-transition
                                 class="position-absolute end-0 mt-2 bg-white rounded-3 shadow py-3"
                                 style="z-index:50;width:13rem;display:none;">
                                <div class="px-4 pb-2 border-bottom mb-2">
                                    <span class="small fw-semibold text-muted text-uppercase">
                                        {{ __('Choisir la langue') }}
                                    </span>
                                </div>
                                <div class="px-3 d-flex flex-column gap-2">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <span class="d-flex align-items-center gap-2">
                                            <img src="{{ asset('assets/backoffice/wowdash/images/flags/flag3.png') }}"
                                                 alt="Français" class="rounded-circle object-fit-cover" style="width:1.5rem;height:1.5rem;">
                                            <span class="small">Français</span>
                                        </span>
                                        <form action="{{ route('locale.switch', 'fr') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="radio" class="form-check-input"
                                                   {{ app()->getLocale() === 'fr' ? 'checked' : '' }}
                                                   onclick="this.closest('form').submit()"
                                                   aria-label="Français">
                                        </form>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <span class="d-flex align-items-center gap-2">
                                            <img src="{{ asset('assets/backoffice/wowdash/images/flags/flag1.png') }}"
                                                 alt="English" class="rounded-circle object-fit-cover" style="width:1.5rem;height:1.5rem;">
                                            <span class="small">English</span>
                                        </span>
                                        <form action="{{ route('locale.switch', 'en') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="radio" class="form-check-input"
                                                   {{ app()->getLocale() === 'en' ? 'checked' : '' }}
                                                   onclick="this.closest('form').submit()"
                                                   aria-label="English">
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <!-- Notifications dropdown (Alpine.js) -->
                        <li class="notification_dropdown" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="position-relative rounded-circle bg-light d-flex align-items-center justify-content-center"
                                    style="width:2.8rem;height:2.8rem;border:none;"
                                    aria-label="{{ __('Notifications') }}"
                                    :aria-expanded="open">
                                <i class="fa fa-bell text-primary"></i>
                                @if($unreadCount > 0)
                                    <span class="position-absolute top-0 end-0 bg-danger text-white rounded-circle d-flex align-items-center justify-content-center"
                                          style="width:18px;height:18px;font-size:0.6rem;font-weight:700;">
                                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                    </span>
                                @endif
                            </button>
                            <div x-show="open"
                                 @click.away="open = false"
                                 x-transition
                                 class="position-absolute end-0 mt-2 bg-white rounded-3 shadow"
                                 style="min-width:340px;z-index:50;display:none;">
                                <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
                                    <span class="fw-semibold small">{{ __('Notifications') }}</span>
                                    @if($unreadCount > 0)
                                        <span class="badge bg-primary rounded-pill">
                                            {{ $unreadCount }}
                                        </span>
                                    @endif
                                </div>
                                <div style="max-height:320px;overflow-y:auto;">
                                    @forelse($recentNotifs as $notif)
                                        @php
                                            $nMsg    = $notif->data['message'] ?? $notif->data['title'] ?? __('Notification');
                                            $nUnread = is_null($notif->read_at);
                                            $nType   = class_basename($notif->type ?? '');
                                            $nIcon   = match($nType) {
                                                'PasswordChangedNotification' => 'fa fa-lock',
                                                'SystemAlertNotification'     => 'fa fa-exclamation-triangle',
                                                default                       => 'fa fa-bell',
                                            };
                                            $nColor  = match($nType) {
                                                'PasswordChangedNotification' => 'text-warning',
                                                'SystemAlertNotification'     => 'text-danger',
                                                default                       => 'text-primary',
                                            };
                                        @endphp
                                        <a href="{{ route('user.notifications') }}"
                                           class="d-flex align-items-start gap-3 px-4 py-3 border-bottom {{ $nUnread ? '' : 'bg-light' }}"
                                           style="text-decoration:none;color:inherit;">
                                            <span class="rounded-circle bg-light d-flex align-items-center justify-content-center {{ $nColor }}"
                                                  style="width:36px;height:36px;flex-shrink:0;">
                                                <i class="{{ $nIcon }}"></i>
                                            </span>
                                            <div class="flex-grow-1" style="min-width:0;">
                                                <div class="small fw-medium text-truncate">{{ Str::limit($nMsg, 50) }}</div>
                                                <div class="small text-muted mt-1">{{ $notif->created_at->diffForHumans() }}</div>
                                            </div>
                                            @if($nUnread)
                                                <span class="rounded-circle bg-primary flex-shrink-0 mt-2"
                                                      style="width:8px;height:8px;display:inline-block;"></span>
                                            @endif
                                        </a>
                                    @empty
                                        <div class="text-center py-4 text-muted">
                                            <i class="fa fa-bell-slash mb-2 d-block text-muted" style="font-size:1.5rem;opacity:0.4;"></i>
                                            <span class="small">{{ __('Aucune notification') }}</span>
                                        </div>
                                    @endforelse
                                </div>
                                <div class="d-flex align-items-center justify-content-between px-4 py-3">
                                    <a href="{{ route('user.notifications') }}"
                                       class="text-primary small fw-semibold">
                                        {{ __('Voir toutes') }}
                                    </a>
                                    @if($unreadCount > 0)
                                        <form action="{{ route('user.notifications.markAllRead') }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-primary">
                                                {{ __('Tout marquer lu') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </li>

                        <!-- Profil dropdown (Alpine.js) -->
                        <li class="header-profile" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="nav-link d-flex align-items-center gap-2"
                                    style="cursor:pointer;background:none;border:none;"
                                    aria-label="{{ __('Mon profil') }}"
                                    :aria-expanded="open">
                                <span class="rounded-circle text-white d-flex align-items-center justify-content-center fw-semibold small"
                                      style="width:2.5rem;height:2.5rem;background-color:{{ $color }};">
                                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                                </span>
                                <span class="small fw-medium d-none d-md-inline">{{ auth()->user()->name ?? '' }}</span>
                                <i class="fas fa-chevron-down text-muted d-none d-md-inline" style="font-size:.7rem;"></i>
                            </button>
                            <div x-show="open"
                                 @click.away="open = false"
                                 x-transition
                                 class="position-absolute end-0 mt-2 bg-white rounded-3 shadow py-2"
                                 style="width:14rem;z-index:50;display:none;">
                                <div class="px-4 py-3 border-bottom">
                                    <div class="fw-semibold small">{{ auth()->user()->name ?? '' }}</div>
                                    <div class="small text-muted text-truncate">{{ auth()->user()->email ?? '' }}</div>
                                    <div class="small text-primary mt-1">{{ auth()->user()->roles->first()?->name ?? __('Membre') }}</div>
                                </div>
                                <a href="{{ route('user.profile') }}"
                                   class="d-flex align-items-center gap-2 px-4 py-2 small text-decoration-none text-body">
                                    <i class="fa fa-user text-primary"></i>{{ __('Mon profil') }}
                                </a>
                                <a href="{{ route('user.subscription') }}"
                                   class="d-flex align-items-center gap-2 px-4 py-2 small text-decoration-none text-body">
                                    <i class="fa fa-credit-card text-primary"></i>{{ __('Abonnement') }}
                                </a>
                                <a href="{{ route('user.api-tokens') }}"
                                   class="d-flex align-items-center gap-2 px-4 py-2 small text-decoration-none text-body">
                                    <i class="fa fa-key text-primary"></i>{{ __('Tokens API') }}
                                </a>
                                <div class="border-top mt-1 pt-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                                class="d-flex align-items-center gap-2 px-4 py-2 small text-danger w-100 text-start border-0 bg-transparent">
                                            <i class="fa fa-power-off"></i>{{ __('Déconnexion') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </li>

                    </ul>
                </div>
            </nav>
        </div>
    </div>

    <!-- =================== SIDEBAR (dlabnav) =================== -->
    <div class="dlabnav">
        <div class="dlabnav-scroll">

            <!-- User mini-profile in sidebar -->
            <div class="dropdown header-profile2 mb-4 px-3" x-data="{ open: false }">
                <button @click="open = !open"
                        class="d-flex align-items-center gap-3 w-100 py-2 border-0 bg-transparent"
                        style="cursor:pointer;"
                        aria-haspopup="true"
                        :aria-expanded="open">
                    <span class="rounded-3 text-white d-flex align-items-center justify-content-center fw-semibold small"
                          style="width:2.8rem;height:2.8rem;background-color:{{ $color }};">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                    </span>
                    <div class="header-info2 text-start overflow-hidden">
                        <h6 class="small fw-medium text-truncate mb-0">{{ auth()->user()->name ?? '' }}</h6>
                        <p class="small text-muted text-truncate mb-0">{{ auth()->user()->roles->first()?->name ?? __('Membre') }}</p>
                    </div>
                </button>
                <div x-show="open"
                     @click.outside="open = false"
                     x-transition
                     class="dropdown-menu dropdown-menu-end"
                     style="display:none;">
                    <a href="{{ route('user.profile') }}" class="dropdown-item ai-icon">
                        <i class="fa fa-user-circle text-primary me-2"></i>
                        <span>{{ __('Mon profil') }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item ai-icon">
                            <i class="fa fa-sign-out-alt text-danger me-2"></i>
                            <span>{{ __('Déconnexion') }}</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Navigation menu -->
            <nav aria-label="{{ __('Menu utilisateur') }}">
            <ul class="metismenu" id="user-menu">

                {{-- ===== TABLEAU DE BORD ===== --}}
                <li class="{{ request()->routeIs('user.dashboard') ? 'mm-active' : '' }}">
                    <a href="{{ route('user.dashboard') }}" aria-expanded="false">
                        <i class="fa fa-home"></i>
                        <span class="nav-text">{{ __('Tableau de bord') }}</span>
                    </a>
                </li>

                {{-- ===== CONTENU ===== --}}
                <li class="nav-label">{{ __('Contenu') }}</li>

                <li class="{{ request()->routeIs('user.articles.*') ? 'mm-active' : '' }}">
                    <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">
                        <i class="fa fa-file-alt"></i>
                        <span class="nav-text">{{ __('Mes articles') }}</span>
                    </a>
                    <ul aria-expanded="false">
                        <li class="{{ request()->routeIs('user.articles.index') ? 'mm-active' : '' }}">
                            <a href="{{ route('user.articles.index') }}">{{ __('Liste') }}</a>
                        </li>
                        <li class="{{ request()->routeIs('user.articles.create') ? 'mm-active' : '' }}">
                            <a href="{{ route('user.articles.create') }}">{{ __('Nouvel article') }}</a>
                        </li>
                    </ul>
                </li>

                {{-- ===== COMPTE ===== --}}
                <li class="nav-label">{{ __('Compte') }}</li>

                {{-- Notifications --}}
                <li class="{{ request()->routeIs('user.notifications') ? 'mm-active' : '' }}">
                    <a href="{{ route('user.notifications') }}" aria-expanded="false">
                        <i class="fa fa-bell"></i>
                        <span class="nav-text">
                            {{ __('Notifications') }}
                            @if($unreadCount > 0)
                                <span class="badge bg-danger ms-2 rounded-pill"
                                      style="font-size:0.65rem;padding:0.2em 0.5em;">
                                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                </span>
                            @endif
                        </span>
                    </a>
                </li>

                {{-- Mon profil (accordéon) --}}
                <li class="{{ request()->routeIs('user.profile', 'user.sessions', 'user.activity') ? 'mm-active' : '' }}">
                    <a class="has-arrow" href="javascript:void(0);" aria-expanded="false">
                        <i class="fa fa-user-circle"></i>
                        <span class="nav-text">{{ __('Mon profil') }}</span>
                    </a>
                    <ul aria-expanded="false">
                        <li class="{{ request()->routeIs('user.profile') ? 'mm-active' : '' }}">
                            <a href="{{ route('user.profile') }}">{{ __('Informations') }}</a>
                        </li>
                        <li class="{{ request()->routeIs('user.sessions') ? 'mm-active' : '' }}">
                            <a href="{{ route('user.sessions') }}">{{ __('Sessions') }}</a>
                        </li>
                        <li class="{{ request()->routeIs('user.activity') ? 'mm-active' : '' }}">
                            <a href="{{ route('user.activity') }}">{{ __("Journal d'activité") }}</a>
                        </li>
                    </ul>
                </li>

                {{-- Abonnement --}}
                <li class="{{ request()->routeIs('user.subscription') ? 'mm-active' : '' }}">
                    <a href="{{ route('user.subscription') }}" aria-expanded="false">
                        <i class="fa fa-credit-card"></i>
                        <span class="nav-text">{{ __('Abonnement') }}</span>
                    </a>
                </li>

                {{-- Tokens API --}}
                <li class="{{ request()->routeIs('user.api-tokens') ? 'mm-active' : '' }}">
                    <a href="{{ route('user.api-tokens') }}" aria-expanded="false">
                        <i class="fa fa-key"></i>
                        <span class="nav-text">{{ __('Tokens API') }}</span>
                    </a>
                </li>

                {{-- Administration (admin uniquement) --}}
                @if(auth()->check() && auth()->user()->hasAnyRole(['admin', 'super_admin']))
                    <li class="nav-label">{{ __('Administration') }}</li>
                    <li>
                        <a href="{{ route('admin.dashboard') }}" aria-expanded="false">
                            <i class="fa fa-shield-alt"></i>
                            <span class="nav-text">{{ __('Administration') }}</span>
                        </a>
                    </li>
                @endif

            </ul>
            </nav>

        </div>
    </div>
    <!-- fin dlabnav -->

    <!-- =================== CONTENT BODY =================== -->
    <main class="content-body default-height" id="main-content">
        <div class="container-fluid">

            @auth
                @if(auth()->user()->needsOnboarding())
                    @livewire(\Modules\Auth\Livewire\OnboardingWizard::class)
                @endif
            @endauth

            @if(session('success'))
                <div class="card mb-4 border-start border-4 border-success" x-data="{ show: true }" x-show="show" x-transition>
                    <div class="p-4 d-flex align-items-center justify-content-between gap-3">
                        <span class="d-flex align-items-center gap-2 text-success small">
                            <i class="fa fa-check-circle"></i>
                            {{ session('success') }}
                        </span>
                        <button @click="show = false"
                                class="text-muted border-0 bg-transparent flex-shrink-0"
                                aria-label="{{ __('Fermer') }}">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="card mb-4 border-start border-4 border-danger" x-data="{ show: true }" x-show="show" x-transition>
                    <div class="p-4 d-flex align-items-center justify-content-between gap-3">
                        <span class="d-flex align-items-center gap-2 text-danger small">
                            <i class="fa fa-times-circle"></i>
                            {{ session('error') }}
                        </span>
                        <button @click="show = false"
                                class="text-muted border-0 bg-transparent flex-shrink-0"
                                aria-label="{{ __('Fermer') }}">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            @if(session('warning'))
                <div class="card mb-4 border-start border-4 border-warning" x-data="{ show: true }" x-show="show" x-transition>
                    <div class="p-4 d-flex align-items-center justify-content-between gap-3">
                        <span class="d-flex align-items-center gap-2 text-warning small">
                            <i class="fa fa-exclamation-triangle"></i>
                            {{ session('warning') }}
                        </span>
                        <button @click="show = false"
                                class="text-muted border-0 bg-transparent flex-shrink-0"
                                aria-label="{{ __('Fermer') }}">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            @yield('content')

        </div>
    </main>

    <!-- =================== FOOTER =================== -->
    <footer class="footer">
        <div class="copyright">
            <p class="mb-0">
                &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('Tous droits réservés.') }}
            </p>
        </div>
    </footer>

</div>
<!-- fin main-wrapper -->

<!-- =================== JS =================== -->
<script src="{{ asset('build/nobleui/plugins/lucide/lucide.min.js') }}"></script>
<script src="{{ asset('assets/backoffice/backend/vendor/global/global.min.js') }}"></script>
<script src="{{ asset('assets/backoffice/backend/vendor/flatpickr-master/js/flatpickr.js') }}"></script>
<script src="{{ asset('assets/backoffice/backend/js/dlabnav-init.js') }}"></script>
<script src="{{ asset('assets/backoffice/backend/js/custom.js') }}"></script>

<script>
// Fallback preloader : masque le spinner même si Jobick échoue
// Utilise classList.add('d-none') pour surpasser Bootstrap d-flex !important
(function () {
    function hidePreloader() {
        var p = document.getElementById('preloader');
        var w = document.getElementById('main-wrapper');
        if (p) {
            p.classList.remove('d-flex');
            p.classList.add('d-none');
        }
        if (w) { w.classList.add('show'); }
    }
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        hidePreloader();
    } else {
        document.addEventListener('DOMContentLoaded', hidePreloader);
    }
    setTimeout(hidePreloader, 500);
})();
if (window.lucide) lucide.createIcons();
</script>

<script>
// Dark mode toggle (Backend/Jobick theme)
document.addEventListener('DOMContentLoaded', function () {
    var toggle    = document.getElementById('backend-dark-mode-toggle');
    var sunIcon   = document.getElementById('backend-sun-icon');
    var moonIcon  = document.getElementById('backend-moon-icon');
    var wrapper   = document.getElementById('main-wrapper');

    var saved = localStorage.getItem('backendUserTheme');
    if (saved === 'dark') {
        if (wrapper)  wrapper.classList.add('dark');
        if (sunIcon)  sunIcon.classList.add('d-none');
        if (moonIcon) moonIcon.classList.remove('d-none');
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            var isDark = wrapper && wrapper.classList.contains('dark');
            if (wrapper) wrapper.classList.toggle('dark');
            localStorage.setItem('backendUserTheme', isDark ? 'light' : 'dark');
            if (sunIcon)  sunIcon.classList.toggle('d-none');
            if (moonIcon) moonIcon.classList.toggle('d-none');
        });
    }
});
</script>

@livewire('ai-chatbot')
@livewireScripts
@stack('js')

<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js');
}
</script>

@if(\Modules\Settings\Facades\Settings::get('push.web_push_enabled', false) && \Modules\Settings\Facades\Settings::get('push.vapid_public_key'))
<script>
(function() {
    var vapidKey = '{{ \Modules\Settings\Facades\Settings::get("push.vapid_public_key") }}';
    if (!('PushManager' in window) || !('serviceWorker' in navigator)) return;
    if (Notification.permission === 'denied') return;

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var raw = atob(base64);
        var arr = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; ++i) arr[i] = raw.charCodeAt(i);
        return arr;
    }

    async function subscribePush() {
        try {
            var reg = await navigator.serviceWorker.ready;
            var sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey)
            });
            var key  = sub.getKey('p256dh');
            var auth = sub.getKey('auth');
            await fetch('/api/v1/push-subscriptions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    endpoint: sub.endpoint,
                    keys: {
                        p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(key))),
                        auth:   btoa(String.fromCharCode.apply(null, new Uint8Array(auth)))
                    }
                })
            });
        } catch (e) { /* Push registration failed silently */ }
    }

    if (Notification.permission === 'granted') {
        subscribePush();
    } else if (Notification.permission === 'default') {
        Notification.requestPermission().then(function(p) { if (p === 'granted') subscribePush(); });
    }
})();
</script>
@endif

</body>
</html>

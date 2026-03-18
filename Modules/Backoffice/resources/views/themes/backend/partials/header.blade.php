<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<nav class="navbar">
    <div class="navbar-content">

        {{-- Logo mini (mobile) --}}
        <div class="logo-mini-wrapper">
            @php
                $siteName = $branding['site_name'] ?? config('app.name');
                $initial = mb_strtoupper(mb_substr($siteName, 0, 1));
                $color = $branding['primary_color'] ?? '#6571ff';
            @endphp
            <span class="fw-bold fs-5" style="color: {{ $color }}">{{ $initial }}</span>
        </div>

        {{-- Search --}}
        @if(class_exists(\Livewire\Livewire::class))
            <div class="search-form">
                @livewire('backoffice-global-search')
            </div>
        @else
            <form class="search-form">
                <div class="input-group">
                    <div class="input-group-text"><i data-lucide="search"></i></div>
                    <input type="text" class="form-control" placeholder="{{ __('Rechercher...') }}">
                </div>
            </form>
        @endif

        <ul class="navbar-nav">

            {{-- Dark/Light mode toggle --}}
            <li class="theme-switcher-wrapper nav-item">
                <input type="checkbox" value="" id="theme-switcher">
                <label for="theme-switcher" aria-label="{{ __('Basculer mode clair/sombre') }}">
                    <div class="box">
                        <div class="ball"></div>
                        <div class="icons">
                            <i class="link-icon" data-lucide="sun"></i>
                            <i class="link-icon" data-lucide="moon"></i>
                        </div>
                    </div>
                </label>
            </li>

            {{-- Language switcher --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    @if(app()->getLocale() === 'fr')
                        <img src="{{ asset('build/nobleui/plugins/flag-icons/flags/4x3/fr.svg') }}" class="w-20px" alt="{{ __('Drapeau français') }}">
                        <span class="ms-2 d-none d-md-inline-block">{{ __('Français') }}</span>
                    @else
                        <img src="{{ asset('build/nobleui/plugins/flag-icons/flags/4x3/us.svg') }}" class="w-20px" alt="American flag">
                        <span class="ms-2 d-none d-md-inline-block">English</span>
                    @endif
                </a>
                <div class="dropdown-menu" aria-labelledby="languageDropdown">
                    <a href="{{ route('locale.switch', 'fr') }}" class="dropdown-item py-2 d-flex">
                        <img src="{{ asset('build/nobleui/plugins/flag-icons/flags/4x3/fr.svg') }}" class="w-20px" alt="{{ __('Drapeau français') }}">
                        <span class="ms-2">{{ __('Français') }}</span>
                    </a>
                    <a href="{{ route('locale.switch', 'en') }}" class="dropdown-item py-2 d-flex">
                        <img src="{{ asset('build/nobleui/plugins/flag-icons/flags/4x3/us.svg') }}" class="w-20px" alt="American flag">
                        <span class="ms-2">English</span>
                    </a>
                </div>
            </li>

            {{-- Guided tour restart --}}
            <li class="nav-item">
                <button type="button" id="restartTour" class="nav-link btn btn-link border-0" title="{{ __('Visite guidee') }}" aria-label="{{ __('Relancer la visite guidee') }}">
                    <i data-lucide="compass" class="link-icon"></i>
                </button>
            </li>

            {{-- Notifications --}}
            <li class="nav-item dropdown">
                @livewire('backoffice-notification-bell')
            </li>

            {{-- Profile dropdown --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    @if(auth()->user()->avatar)
                        <img class="w-30px h-30px ms-1 rounded-circle" src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="avatar">
                    @else
                        <span class="w-30px h-30px ms-1 rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="font-size: 12px; font-weight: 600;">
                            {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
                        </span>
                    @endif
                </a>
                <div class="dropdown-menu p-0" aria-labelledby="profileDropdown">
                    <div class="d-flex flex-column align-items-center border-bottom px-5 py-3">
                        <div class="mb-3">
                            @if(auth()->user()->avatar)
                                <img class="w-80px h-80px rounded-circle" src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="{{ __('Avatar de') }} {{ auth()->user()->name }}">
                            @else
                                <span class="w-80px h-80px rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="font-size: 24px; font-weight: 700;">
                                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}
                                </span>
                            @endif
                        </div>
                        <div class="text-center">
                            <p class="fs-16px fw-bolder">{{ auth()->user()->name }}</p>
                            <p class="fs-12px text-secondary">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <ul class="list-unstyled p-1">
                        <li>
                            <a href="{{ route('admin.profile') }}" class="dropdown-item py-2 text-body ms-0">
                                <i class="me-2 icon-md" data-lucide="user"></i>
                                <span>{{ __('Profil') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('user.dashboard') }}" class="dropdown-item py-2 text-body ms-0">
                                <i class="me-2 icon-md" data-lucide="layout-dashboard"></i>
                                <span>{{ __('Mon espace') }}</span>
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                @csrf
                                <button type="button" class="dropdown-item py-2 text-body ms-0" onclick="document.getElementById('logout-form').submit()">
                                    <i class="me-2 icon-md" data-lucide="log-out"></i>
                                    <span>{{ __('Déconnexion') }}</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>

        {{-- Mobile sidebar toggler --}}
        <button type="button" class="sidebar-toggler" aria-label="{{ __('Ouvrir le menu') }}" aria-expanded="false" aria-controls="sidebarNav">
            <i data-lucide="menu" aria-hidden="true"></i>
        </button>

    </div>
</nav>

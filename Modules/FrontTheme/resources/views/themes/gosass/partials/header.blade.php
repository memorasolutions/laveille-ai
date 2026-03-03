<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<header class="cs_site_header cs_style_1 cs_sticky_header">
    <div class="cs_main_header cs_fs_18 cs_heading_color">
        <div class="container">
            <div class="cs_main_header_in">
                <div class="cs_main_header_left">
                    <a class="cs_site_branding fw-bold fs-4 text-decoration-none cs_heading_color" href="{{ url('/') }}" aria-label="Accueil">
                        {{ config('app.name') }}
                    </a>
                </div>
                <div class="cs_main_header_center">
                    <nav class="cs_nav" aria-label="{{ __('Navigation principale') }}">
                        <ul class="cs_nav_list">
                            <li><a href="{{ url('/') }}" aria-label="Accueil">Accueil</a></li>
                            <li class="menu-item-has-children">
                                <a href="#" aria-label="Ressources">Ressources</a>
                                <ul>
                                    <li><a href="{{ route('blog.index') }}" aria-label="Blog">Blog</a></li>
                                    <li><a href="{{ route('faq.show') }}" aria-label="FAQ">FAQ</a></li>
                                </ul>
                            </li>
                            <li><a href="{{ route('contact.show') }}" aria-label="Contact">Contact</a></li>
                            {{-- Language switcher mobile (caché sur desktop, visible dans le menu burger) --}}
                            <li class="d-md-none">
                                <div class="d-flex gap-1 align-items-center py-1">
                                    <form action="{{ route('locale.switch', 'fr') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm px-2 py-1 {{ app()->getLocale() === 'fr' ? 'btn-dark' : 'btn-outline-secondary' }}" style="font-size:12px;font-weight:600;">FR</button>
                                    </form>
                                    <form action="{{ route('locale.switch', 'en') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm px-2 py-1 {{ app()->getLocale() === 'en' ? 'btn-dark' : 'btn-outline-secondary' }}" style="font-size:12px;font-weight:600;">EN</button>
                                    </form>
                                </div>
                            </li>
                        </ul>
                    </nav>
                </div>
                <div class="cs_main_header_right">
                    {{-- Search --}}
                    <a href="{{ route('search.front') }}" class="me-2 cs_heading_color" aria-label="{{ __('Rechercher') }}" style="font-size:20px;">
                        <i class="fas fa-search"></i>
                    </a>
                    {{-- Language switcher --}}
                    <div class="d-flex align-items-center me-3 gap-1">
                        <form action="{{ route('locale.switch', 'fr') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm px-2 py-1 {{ app()->getLocale() === 'fr' ? 'btn-dark' : 'btn-outline-secondary' }}" style="font-size:12px;font-weight:600;">FR</button>
                        </form>
                        <form action="{{ route('locale.switch', 'en') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm px-2 py-1 {{ app()->getLocale() === 'en' ? 'btn-dark' : 'btn-outline-secondary' }}" style="font-size:12px;font-weight:600;">EN</button>
                        </form>
                    </div>
                    @auth
                        <div class="dropdown">
                            <button class="cs_btn cs_style_1 cs_accent_bg cs_white_color cs_fs_16 cs_semibold cs_radius_30 dropdown-toggle"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="d-flex align-items-center">
                                    @if(auth()->user()->avatar)
                                        <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}"
                                             class="rounded-circle me-2" width="24" height="24">
                                    @else
                                        <span class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center me-2"
                                              style="width:24px;height:24px;font-size:10px;font-weight:700;">
                                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                        </span>
                                    @endif
                                    <span>{{ auth()->user()->name }}</span>
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:260px;">
                                <li>
                                    <div class="px-4 py-3">
                                        <h6 class="mb-0">{{ auth()->user()->name }}</h6>
                                        <p class="text-muted mb-0 small">{{ auth()->user()->email }}</p>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('user.dashboard') }}"><i class="fas fa-tachometer-alt me-2"></i>{{ __('Tableau de bord') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('user.profile') }}"><i class="fas fa-user me-2"></i>{{ __('Profil') }}</a></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('user.notifications') }}">
                                        <i class="fas fa-bell me-2"></i>{{ __('Notifications') }}
                                        @if(($unreadCount ?? 0) > 0)
                                            <span class="badge bg-danger rounded-pill float-end">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                                        @endif
                                    </a>
                                </li>
                                <li><a class="dropdown-item" href="{{ route('user.subscription') }}"><i class="fas fa-credit-card me-2"></i>{{ __('Abonnement') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('user.sessions') }}"><i class="fas fa-desktop me-2"></i>{{ __('Sessions') }}</a></li>
                                @if(auth()->user()->hasRole(['admin', 'super_admin']))
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-warning" href="{{ route('admin.dashboard') }}"><i class="fas fa-shield-alt me-2"></i>{{ __('Administration') }}</a></li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt me-2"></i>{{ __('Déconnexion') }}</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="cs_btn cs_style_1 cs_heading_bg cs_white_color cs_fs_16 cs_semibold cs_radius_30 me-2">
                            <span>{{ __('Connexion') }}</span>
                        </a>
                        <a href="{{ route('register') }}" class="cs_btn cs_style_1 cs_accent_bg cs_purple_hover cs_white_color cs_fs_16 cs_semibold cs_radius_30">
                            <span>{{ __('Inscription') }}</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</header>

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
                        <a href="{{ route('user.dashboard') }}" class="cs_btn cs_style_1 cs_accent_bg cs_white_color cs_fs_16 cs_semibold cs_radius_30">
                            <span>Mon espace</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="cs_btn cs_style_1 cs_heading_bg cs_white_color cs_fs_16 cs_semibold cs_radius_30 me-2">
                            <span>Connexion</span>
                        </a>
                        <a href="{{ route('register') }}" class="cs_btn cs_style_1 cs_accent_bg cs_purple_hover cs_white_color cs_fs_16 cs_semibold cs_radius_30">
                            <span>Inscription</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</header>

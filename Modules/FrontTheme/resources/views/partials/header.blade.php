<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Skip-link WCAG 2.4.1 géré par layout master.blade.php (DRY — session 21 dedup) --}}
{{-- Barre admin retirée — remplacée par le dropdown avatar dans le header (session 2026-03-28) --}}
<!-- Start header -->
<header id="header" class="wpo-site-header">
    <div class="topbar">
        <div class="container">
            <div class="row">
                <div class="col col-lg-7 col-md-9 col-sm-12 col-12">
                    <div class="contact-intro">
                        <ul>
                            <li class="update"><a href="{{ route('news.index') }}" style="color:inherit;text-decoration:none;"><span>{{ __('Actualités') }}</span></a></li>
                            <li>@if(isset($latestNewsArticle) && $latestNewsArticle)<a href="{{ route('news.show', $latestNewsArticle) }}" style="color:inherit;text-decoration:none;">{{ $latestNewsArticle->seo_title ?? $latestNewsArticle->title }}</a>@elseif(isset($latestArticle) && $latestArticle)<a href="{{ $latestArticle->getPublicUrl() }}" style="color:inherit;text-decoration:none;">{{ $latestArticle->title }}</a>@else{{ __('Veille IA et technologie') }}@endif</li>
                        </ul>
                    </div>
                </div>
                <div class="col col-lg-5 col-md-3 col-sm-12 col-12">
                    <div class="contact-info">
                        <ul>
                            <li><a href="{{ lv_social('facebook') }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="ti-facebook"></i></a></li>
                            <li><a href="{{ lv_social('messenger') }}" target="_blank" rel="noopener" aria-label="Messenger"><i class="ti-comment"></i></a></li>
                            <li><a href="{{ lv_social('linkedin') }}" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="ti-linkedin"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- end topbar -->
    <nav class="navigation navbar navbar-expand-lg navbar-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-3 col-md-3 col-3 d-lg-none dl-block">
                    <div class="mobail-menu">
                        <button type="button" class="navbar-toggler open-btn" aria-label="{{ __('Ouvrir le menu') }}" aria-expanded="false" aria-controls="navbar">
                            <span class="sr-only" aria-hidden="true">Toggle navigation</span>
                            <span class="icon-bar first-angle"></span>
                            <span class="icon-bar middle-angle"></span>
                            <span class="icon-bar last-angle"></span>
                        </button>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 col-6">
                    <div class="navbar-header">
                        <a class="navbar-brand" href="{{ route('home') }}"><img src="{{ asset('images/logo-horizontal.svg') }}?v=8" alt="{{ config('app.name') }}" style="max-height: 56px; width: auto; max-width: 200px;"></a>
                    </div>
                </div>
                <div class="col-lg-8 col-md-1 col-1">
                    <div id="navbar" class="collapse navbar-collapse navigation-holder">
                        <button class="menu-close"><i class="ti-close"></i></button>
                        @php
                            // Source unique de la navigation (#3013) : même arbre pour l'entête et pour GET /api/header-nav.
                            $headerNav = app(\Modules\FrontTheme\Services\HeaderNavService::class)->tree();
                        @endphp
                        <ul class="nav navbar-nav mb-2 mb-lg-0">
                            @include('fronttheme::partials.nav.items', ['nav' => $headerNav])
                        </ul>
                    </div><!-- end of nav-collapse -->
                </div>
                <div class="col-lg-2 col-md-2 col-2">
                    <div class="header-right">
                        <div class="header-search-form-wrapper">
                            <div class="cart-search-contact">
                                <button
                                    type="button"
                                    class="search-toggle-btn"
                                    aria-label="{{ __('Ouvrir la recherche (Ctrl+K)') }}"
                                    title="{{ __('Rechercher (Ctrl+K)') }}"
                                    onclick="window.dispatchEvent(new CustomEvent('open-search-palette'))"
                                ><i class="fi flaticon-magnifiying-glass"></i></button>
                            </div>
                        </div>
                        {{-- Mini-cart (conditionnel — module Shop activé ET boutique pas en maintenance) --}}
                        @unless(config('shop.maintenance', false))
                            @includeIf('shop::partials.mini-cart')
                        @endunless
                        {{-- Menu utilisateur connecté --}}
                        @auth
                        <div x-data="{ open: false }" style="display:inline-block;position:relative;margin-right:8px;vertical-align:middle;">
                            @php $unread = auth()->user()->unreadNotifications->count(); @endphp
                            <button @click="open = !open" @click.outside="open = false" style="background:none!important;border:none!important;cursor:pointer;padding:0;display:flex!important;align-items:center!important;gap:4px;outline:none!important;box-shadow:none!important;">
                                @if(auth()->user()->avatar)
                                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" loading="lazy">
                                @else
                                    <div style="width:32px;height:32px;border-radius:50%;background:var(--c-primary);color:#fff;display:flex!important;align-items:center!important;justify-content:center!important;font-weight:700;font-size:13px;">{{ substr(auth()->user()->name, 0, 1) }}</div>
                                @endif
                                @include('fronttheme::partials.badge-count', ['count' => $unread, 'color' => '#ef4444'])
                            </button>
                            <div x-show="open" x-cloak x-transition style="position:absolute;right:0;top:40px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.12);width:220px;z-index:9999;padding:8px 0;">
                                <div style="padding:12px 16px;border-bottom:1px solid #f3f4f6;">
                                    <div style="font-weight:700;color:var(--c-dark);font-size:14px;">{{ auth()->user()->name }}</div>
                                    <div style="font-size:11px;color:#374151;">{{ auth()->user()->email }}</div>
                                </div>
                                @include('auth::components.user-menu-links', ['variant' => 'dropdown'])
                                @can('view_admin_panel')
                                <div style="border-top:1px solid #f3f4f6;margin-top:4px;padding-top:4px;">
                                    <a href="{{ url('/admin') }}" target="_blank" style="display:block;padding:10px 16px;color:var(--c-dark);text-decoration:none!important;font-size:13px;font-weight:500;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">{{ __('Administration') }}</a>
                                    @if(Route::has('admin.directory.moderation'))<a href="{{ route('admin.directory.moderation') }}" target="_blank" style="display:block;padding:10px 16px;color:var(--c-dark);text-decoration:none!important;font-size:13px;font-weight:500;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">📋 {{ __('Modération') }}</a>@endif
                                </div>
                                @endcan
                                <div style="border-top:1px solid #f3f4f6;margin-top:4px;padding-top:4px;">
                                    <form method="POST" action="{{ route('logout') }}">@csrf
                                        <button type="submit" style="display:block;width:100%;text-align:left;padding:10px 16px;background:none!important;border:none!important;color:#ef4444;font-size:13px;font-weight:500;cursor:pointer;outline:none!important;box-shadow:none!important;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">🚪 {{ __('Se déconnecter') }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endauth
                        @guest
                        <div x-data style="display:inline-block;margin-right:8px;vertical-align:middle;">
                            <button @click="$dispatch('open-auth-modal', { message: '' })" aria-label="{{ __('Se connecter') }}" style="background:none!important;border:none!important;cursor:pointer;padding:0;display:flex!important;align-items:center!important;gap:6px;outline:none!important;box-shadow:none!important;color:var(--c-dark);font-size:13px;font-weight:600;">
                                <div style="width:32px;height:32px;border-radius:50%;background:#E5E7EB;display:flex!important;align-items:center!important;justify-content:center!important;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </div>
                            </button>
                        </div>
                        @endguest

                        <div class="header-right-menu-wrapper">
                            <div class="header-right-menu">
                                <div class="right-menu-toggle-btn">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                                <div class="header-right-menu-wrap">
                                    <button class="right-menu-close"><i class="ti-close"></i></button>
                                    <div class="logo"><img src="{{ asset('images/logo-horizontal.svg') }}?v=8" alt="{{ config('app.name') }}" style="max-height:40px;"></div>
                                    <div class="header-right-sec">
                                        {{-- Barre latérale mobile : même source que le menu desktop (HeaderNavService, #3013) --}}
                                        @include('fronttheme::partials.nav.sidebar', ['nav' => $headerNav])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- end of container -->
    </nav>
</header>
<!-- end of header -->

@include('fronttheme::partials.search-palette')

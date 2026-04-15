<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(config('app.noindex', false))
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    @endif
    <style>[x-cloak] { display: none !important; }</style>
    @if(env('ADSENSE_CLIENT_ID'))
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ env('ADSENSE_CLIENT_ID') }}" crossorigin="anonymous"></script>
    @endif
    @if(env('GA_MEASUREMENT_ID') && env('PRIVACY_GA_ENABLED', false))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GA_MEASUREMENT_ID') }}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('consent', 'default', {
        'ad_storage': 'denied',
        'analytics_storage': 'denied',
        'ad_user_data': 'denied',
        'ad_personalization': 'denied'
      });
      gtag('js', new Date());
      gtag('config', '{{ env('GA_MEASUREMENT_ID') }}', {
        'anonymize_ip': true,
        'send_page_view': true
      });
      function updateGtagConsent(granted) {
        var status = granted ? 'granted' : 'denied';
        gtag('consent', 'update', {
          'ad_storage': status,
          'analytics_storage': status,
          'ad_user_data': status,
          'ad_personalization': status
        });
      }
    </script>
    @endif
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0B7285">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon-180x180.png') }}">
    <meta name="msapplication-config" content="{{ asset('browserconfig.xml') }}">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', \Modules\Settings\Facades\Settings::get('seo.meta_description', 'Votre source d\'information sur l\'intelligence artificielle et les technologies au Québec.'))">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('meta_description', \Modules\Settings\Facades\Settings::get('seo.meta_description', 'Votre source d\'information sur l\'intelligence artificielle et les technologies au Québec.'))">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:image" content="@yield('og_image')">
    @else
        <meta property="og:image" content="{{ asset('images/og-image.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endif
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="fr_CA">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', config('app.name'))">
    <meta name="twitter:description" content="@yield('meta_description', \Modules\Settings\Facades\Settings::get('seo.meta_description', 'Votre source d\'information sur l\'intelligence artificielle et les technologies au Quebec.'))">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="fr-CA" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">
    {{-- RSS désactivé (décision utilisateur 2026-04-04) --}}
    @stack('head')
    @include('fronttheme::partials.critical-css')
    <link href="{{ fronttheme_asset('css/themify-icons.css') }}" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="{{ fronttheme_asset('css/flaticon.css') }}" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="{{ fronttheme_asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/animate.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/owl.carousel.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/owl.theme.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/slick.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/slick-theme.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/swiper.min.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/owl.transitions.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/jquery.fancybox.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/odometer-theme-default.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/component.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('sass/style.css') }}" rel="stylesheet">
    <link href="{{ fronttheme_asset('css/responsive.css') }}" rel="stylesheet">
    <link rel="preload" href="/fonts/dm-sans/dm-sans-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/plus-jakarta-sans/plus-jakarta-sans-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('css/charte.css') }}?v={{ filemtime(public_path('css/charte.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/components.css') }}?v={{ filemtime(public_path('css/components.css')) }}" rel="stylesheet">
    <style>
        /* Tailles spécifiques au layout */
        .navbar-brand img { max-height: 50px; width: auto; }
        .wpo-site-footer .about-widget .logo img { max-width: 120px; height: auto; }
        .header-right-menu-wrap .logo img { max-width: 150px; height: auto; }
        /* Drawer overlay fix — fond sombre quand le panneau lateral est ouvert */
        .header-right-menu-wrap.right-menu-active { z-index: 9999; }
        .header-right-menu-wrapper::after {
            content: "";
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            pointer-events: none;
        }
        .header-right-menu-wrapper:has(.right-menu-active)::after {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        .blog-sidebar .about-widget .img-holder img { max-width: 150px; height: auto; border-radius: 50%; }
        /* SVG sans dimension */
        svg:not([width]):not([class*="cc-"]) { max-width: 24px; max-height: 24px; }
        .entry-details svg, .wpo-blog-content svg { max-width: 24px; max-height: 24px; }
        /* Hero grid proportions */
        .wpo-blog-hero-area .wpo-blog-grids .grid:first-child .img-holder { height: 530px; overflow: hidden; }
        .wpo-blog-hero-area .wpo-blog-grids .grid:first-child .img-holder img,
        .wpo-blog-hero-area .wpo-blog-grids .grid:nth-child(2) .img-holder img,
        .wpo-blog-hero-area .wpo-blog-grids .grid.s2 .img-holder img { width: 100%; height: 100%; object-fit: cover; }
        .wpo-blog-hero-area .wpo-blog-grids .grid:nth-child(2) .img-holder,
        .wpo-blog-hero-area .wpo-blog-grids .grid.s2 .img-holder { height: 250px; overflow: hidden; }
        .wpo-blog-hero-area .wpo-blog-content h2 { overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; }
        .wpo-blog-hero-area .grid.s2 .wpo-blog-content h2 { -webkit-line-clamp: 2; font-size: 16px; }
        .wpo-blog-hero-area .wpo-blog-content p { overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        @media (max-width: 1200px) {
            .wpo-blog-hero-area .wpo-blog-grids .grid:first-child .img-holder,
            .wpo-blog-hero-area .wpo-blog-grids .grid:nth-child(2) .img-holder,
            .wpo-blog-hero-area .wpo-blog-grids .grid.s2 .img-holder { height: auto; }
        }
        /* Images articles uniformes */
        .wpo-blog-img img { width: 100%; height: 220px; object-fit: cover; }
        .wpo-breacking-img img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; }
        .wpo-blog-sponsored-section .wpo-blog-img img { height: 180px; }
    </style>
    @stack('styles')
</head>

<body>
    <a href="#main-content" class="skip-link" style="position:absolute;top:-100px;left:0;background:var(--c-primary,#0B7285);color:#fff;padding:8px 16px;z-index:100000;font-weight:700;transition:top .2s;" onfocus="this.style.top='0'" onblur="this.style.top='-100px'">{{ __('Aller au contenu principal') }}</a>

    <!-- start page-wrapper -->
    <div class="page-wrapper">
        <!-- preloader : overlay flou + logo animé -->
        <div id="site-preloader" style="position:fixed;inset:0;z-index:99999;background:rgba(255,255,255,0.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);display:flex;justify-content:center;align-items:center;flex-direction:column;transition:opacity 0.4s ease-out;">
            <style>
                @keyframes preloaderFadeIn{from{opacity:0;transform:scale(0.8)}to{opacity:1;transform:scale(1)}}
                @keyframes preloaderPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.03)}}
                @keyframes preloaderBar{from{width:0}to{width:90%}}
                #site-preloader img{opacity:0;animation:preloaderFadeIn 0.6s ease-out 0.1s forwards}
                #site-preloader .pl-pulse{animation:preloaderPulse 1.5s infinite ease-in-out}
                #site-preloader .pl-bar{height:100%;background:var(--c-primary,#0B7285);border-radius:99px;animation:preloaderBar 2s ease-out forwards}
            </style>
            <img src="{{ asset('images/logo-horizontal.svg') }}" alt="{{ config('app.name') }}" style="height:50px;display:block;">
            <div style="width:180px;height:3px;background:#E5E7EB;border-radius:99px;overflow:hidden;margin-top:16px;">
                <div class="pl-bar"></div>
            </div>
            <script>
                (function(){
                    var p=document.getElementById('site-preloader');
                    if(sessionStorage.getItem('sl')){p.style.display='none';return}
                    var img=p.querySelector('img');
                    setTimeout(function(){img.classList.add('pl-pulse')},700);
                    function hide(){p.style.opacity='0';setTimeout(function(){p.style.display='none';if(typeof wow!=='undefined')wow.init()},400);sessionStorage.setItem('sl','1')}
                    window.addEventListener('load',hide);
                    setTimeout(function(){if(p.style.display!=='none')hide()},3000);
                })();
            </script>
        </div>

        @include('fronttheme::partials.header')

        @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
            @php $adLeaderboard = app(\Modules\Ads\Services\AdsRenderer::class)->render('header-leaderboard'); @endphp
            @if($adLeaderboard)
                <div class="container" style="padding:1rem 0;">{!! $adLeaderboard !!}</div>
            @endif
        @endif

        @yield('breadcrumb')

        {{-- Toast enrichi ajout panier --}}
        @if(session('cart_added'))
        @php $cartAdded = session('cart_added'); @endphp
        <div x-data="{ show: true }" x-show="show" x-init="$nextTick(() => setTimeout(() => show = false, 6000))"
             style="position: fixed; top: 20px; right: 20px; z-index: 1050; max-width: 360px; background: #fff; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.15); overflow: hidden;">
            <div style="background: #f0fdfa; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center;"><i class="ti-check" style="color: #0CA678; margin-right: 8px;"></i><strong style="font-size: 14px;">{{ __('Produit ajouté au panier') }}</strong></div>
                <button @click="show = false" style="background: none; border: none; cursor: pointer; font-size: 18px; color: #94a3b8;">&times;</button>
            </div>
            <div style="padding: 12px 16px; display: flex; align-items: center;">
                @if(is_array($cartAdded) && !empty($cartAdded['image']))<img src="{{ asset($cartAdded['image']) }}" alt="" style="width: 50px; height: 50px; border-radius: 6px; margin-right: 12px; object-fit: cover;">@endif
                <div>
                    <div style="font-size: 14px; font-weight: 700;">{{ is_array($cartAdded) ? ($cartAdded['name'] ?? '') : __('Produit') }}</div>
                    @if(is_array($cartAdded) && !empty($cartAdded['variant']))<div style="font-size: 12px; color: #6b7280;">{{ $cartAdded['variant'] }}</div>@endif
                    @if(is_array($cartAdded) && !empty($cartAdded['price']))<div style="font-size: 15px; font-weight: 700; color: #0B7285;">{{ number_format($cartAdded['price'], 2, ',', ' ') }} $</div>@endif
                </div>
            </div>
            <div style="padding: 8px 16px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px;">
                <a href="{{ Route::has('shop.cart') ? route('shop.cart') : '#' }}" style="flex: 1; text-align: center; padding: 8px; background: #0B7285; color: #fff; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">{{ __('Voir le panier') }}</a>
                <button @click="show = false" style="flex: 1; padding: 8px; background: #f1f5f9; color: #374151; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">{{ __('Continuer') }}</button>
            </div>
        </div>
        @endif

        <main id="main-content">
        @yield('content')
        </main>

        @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
            @php $adFooter = app(\Modules\Ads\Services\AdsRenderer::class)->render('footer-banner'); @endphp
            @if($adFooter)
                <div class="container" style="padding:1rem 0;">{!! $adFooter !!}</div>
            @endif
        @endif

        @include('fronttheme::partials.footer')

        @include('fronttheme::partials.newsletter-modal')
        @include('fronttheme::partials.auth-modal')
        @auth
            @if(class_exists(\Nwidart\Modules\Facades\Module::class) && \Nwidart\Modules\Facades\Module::find('Core')?->isEnabled())
                @include('core::components.moderation-history-modal')
            @endif
        @endauth
    </div>
    <!-- end of page-wrapper -->

    @if(class_exists(\Nwidart\Modules\Facades\Module::class) && \Nwidart\Modules\Facades\Module::find('Privacy')?->isEnabled())
        @include('privacy::partials.cookie-consent')
    @endif

    <!-- All JavaScript files -->
    <script src="{{ fronttheme_asset('js/jquery.min.js') }}"></script>
    <script src="{{ fronttheme_asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ fronttheme_asset('js/modernizr.custom.js') }}"></script>
    <script src="{{ fronttheme_asset('js/jquery.dlmenu.js') }}"></script>
    <script src="{{ fronttheme_asset('js/jquery-plugin-collection.js') }}"></script>
    <script src="{{ fronttheme_asset('js/script.js') }}?v={{ filemtime(public_path('themes/' . config('app.frontend_theme', 'bloggar') . '/js/script.js')) }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>document.querySelectorAll('img:not([loading])').forEach(function(img,i){if(i>0)img.loading='lazy'});</script>
    @stack('scripts')
    @include('fronttheme::partials.toast')
    <script src="/js/infinite-scroll.js?v={{ filemtime(public_path('js/infinite-scroll.js')) }}" defer></script>
    <script src="/js/sw-register.js" defer></script>
    <script src="/js/ga4-events.js" defer></script>

    {{-- Floating share bar (sidebar desktop + bottom bar mobile) — masqué sur pages protégées --}}
    @if(!request()->is('user/*', 'dashboard*', 'login*', 'register*', 'magic-link*', 'admin*', 'privacy-policy*', 'terms-of-use*', 'cookie-policy*', 'rights-request*', 'boutique/panier*', 'boutique/paiement*', 'boutique/commander*', 'boutique/confirmation*', 'boutique/suivi*', 'boutique/mes-commandes*'))
    @php
        $shareUrl = urlencode(request()->url());
        $shareTitle = urlencode(config('app.name') . ' - ' . ($__env->yieldContent('title') ?: ''));
        $shareDescRaw = html_entity_decode(trim(strip_tags($__env->yieldContent('meta_description') ?: '')), ENT_QUOTES, 'UTF-8');
        $shareDescRaw = mb_substr($shareDescRaw, 0, 200);
        $shareDesc = urlencode($shareDescRaw);
        $shareTextRaw = trim($__env->yieldContent('share_text') ?: '');
        $shareClipboard = $shareTextRaw ? html_entity_decode($shareTextRaw, ENT_QUOTES, 'UTF-8') : ($shareDescRaw . "\n\nPourquoi c'est important? Qu'en penses-tu?\n\n📰 " . request()->url() . "\n🔄 Actualités mises à jour en continu sur laveille.ai");
        $shareTagline = urlencode('Actualités mises à jour en continu sur laveille.ai');
        $xText = urlencode($shareDescRaw ? $shareDescRaw . ' — Actualités en continu sur laveille.ai' : config('app.name'));
    @endphp
    <div x-data="{ copied: false, linkedinCopied: false, showLiModal: false, liUrl: '', liText: {{ \Illuminate\Support\Js::from($shareClipboard) }}, openLi() { window.open(this.liUrl, '_blank'); this.showLiModal = false; } }" class="share-float">
        {{-- Desktop sidebar --}}
        <div class="share-sidebar">
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}&quote={{ $shareDesc }}" target="_blank" rel="noopener" aria-label="{{ __('Partager sur Facebook') }}" class="share-btn share-fb">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            </a>
            <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $xText }}" target="_blank" rel="noopener" aria-label="{{ __('Partager sur X') }}" class="share-btn share-x">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ $shareUrl }}&summary={{ $shareDesc }}" target="_blank" rel="noopener" aria-label="{{ __('Partager sur LinkedIn') }}" class="share-btn share-li" @click.prevent="window.__openLinkedIn($el.href,liText)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            </a>
            <a href="{{ \Modules\Settings\Facades\Settings::get('social.messenger_url', 'https://m.me/LaVeilleDeStef') }}" target="_blank" rel="noopener" aria-label="{{ __('Envoyer via Messenger') }}" class="share-btn share-msg">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.654V24l4.088-2.242c1.092.301 2.246.464 3.443.464 6.627 0 12-4.975 12-11.111S18.627 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8.2l3.131 3.26 5.886-3.26-6.558 6.763z"/></svg>
            </a>
            <button @click="navigator.clipboard.writeText(window.location.href);copied=true;setTimeout(()=>copied=false,2000)" :aria-label="copied ? '{{ __('Lien copié') }}' : '{{ __('Copier le lien') }}'" class="share-btn share-copy">
                <svg x-show="!copied" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                <svg x-show="copied" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
        </div>

        {{-- Mobile bottom bar --}}
        <div class="share-bottom">
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}&quote={{ $shareDesc }}" target="_blank" rel="noopener" aria-label="{{ __('Partager sur Facebook') }}" class="share-btn share-fb"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" role="img" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
            <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $xText }}" target="_blank" rel="noopener" aria-label="{{ __('Partager sur X') }}" class="share-btn share-x"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" role="img" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ $shareUrl }}&summary={{ $shareDesc }}" target="_blank" rel="noopener" aria-label="{{ __('Partager sur LinkedIn') }}" class="share-btn share-li" @click.prevent="window.__openLinkedIn($el.href,liText)"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" role="img" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
            <button @click="navigator.clipboard.writeText(window.location.href);copied=true;setTimeout(()=>copied=false,2000)" aria-label="{{ __('Copier le lien') }}" class="share-btn share-copy">
                <svg x-show="!copied" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                <svg x-show="copied" x-cloak width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
        </div>

    </div>

    <style>
    .share-sidebar{position:fixed!important;left:12px;top:50%;transform:translateY(-50%);z-index:999;display:flex!important;flex-direction:column!important;gap:8px;}
    .share-bottom{display:none!important;}
    .share-btn{width:40px;height:40px;border-radius:50%;display:flex!important;align-items:center!important;justify-content:center!important;border:none!important;outline:none!important;box-shadow:0 2px 8px rgba(0,0,0,0.12)!important;cursor:pointer;transition:transform .2s,box-shadow .2s;text-decoration:none!important;}
    .share-btn:hover{transform:scale(1.1);box-shadow:0 4px 16px rgba(0,0,0,0.2)!important;}
    .share-fb{background:#1877F2!important;color:#fff!important;}
    .share-x{background:#000!important;color:#fff!important;}
    .share-li{background:#0A66C2!important;color:#fff!important;}
    .share-msg{background:#0099FF!important;color:#fff!important;}
    .share-copy{background:#fff!important;color:#6b7280!important;}
    @media(max-width:991px){
        .share-sidebar{display:none!important;}
        .share-bottom{display:flex!important;position:fixed!important;bottom:0;left:0;right:0;z-index:999;background:#fff!important;border-top:1px solid #e5e7eb!important;padding:8px 0!important;justify-content:center!important;gap:16px;box-shadow:0 -2px 8px rgba(0,0,0,0.08)!important;}
        .share-bottom .share-btn{width:44px;height:44px;}
    }
    </style>
    @endif

    {{-- Lightbox image réutilisable --}}
    <div x-data="{ open: false, src: '', alt: '' }"
         @lightbox.window="open = true; src = $event.detail.src; alt = $event.detail.alt || ''"
         x-show="open" x-cloak
         @click="open = false"
         @keydown.escape.window="open && (open = false)"
         style="position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:99998;align-items:center;justify-content:center;padding:20px;cursor:zoom-out;"
         :style="open ? 'display:flex' : ''">
        <button @click="open = false" style="position:absolute;top:16px;right:20px;background:none;border:none;color:#fff;font-size:32px;cursor:pointer;z-index:1;line-height:1;">&times;</button>
        <img :src="src" :alt="alt" @click.stop style="max-width:95%;max-height:90vh;object-fit:contain;border-radius:8px;box-shadow:0 0 40px rgba(0,0,0,0.5);cursor:default;">
    </div>

    {{-- Modale confirmation réutilisable (remplace confirm() natif) --}}
    <div x-data="{ open: false, title: '', message: '' }"
         @confirm-action.window="open = true; title = $event.detail.title || 'Confirmer'; message = $event.detail.message || ''; if ($event.detail.action) window.__confirmAction = $event.detail.action"
         @click.self="open = false"
         :style="open ? 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;' : 'display:none'">
        <div @click.stop style="background:#fff;border-radius:16px;padding:28px;max-width:400px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.2);text-align:center;">
            <div style="font-size:32px;margin-bottom:12px;">⚠️</div>
            <h4 style="font-weight:700;color:var(--c-dark,#1a1a2e);margin:0 0 8px;font-size:17px;" x-text="title"></h4>
            <p style="color:#6b7280;font-size:14px;margin:0 0 20px;line-height:1.5;" x-text="message"></p>
            <div style="display:flex!important;gap:10px;justify-content:center!important;">
                <button @click="open = false" style="padding:10px 24px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-weight:600;color:#6b7280;font-size:14px;">{{ __('Annuler') }}</button>
                <button @click="if(window.__confirmAction){window.__confirmAction()};open = false" style="padding:10px 24px;border:none;border-radius:8px;background:#ef4444;color:#fff;cursor:pointer;font-weight:600;font-size:14px;">{{ __('Confirmer') }}</button>
            </div>
        </div>
    </div>
    {{-- Speculation Rules API — prefetch/prerender navigation instantanee --}}
    <script type="speculationrules">
    {
      "prerender": [{"source": "document", "where": {"href_matches": "/*"}, "eagerness": "moderate"}],
      "prefetch": [{"source": "document", "where": {"href_matches": "/*"}, "eagerness": "conservative"}]
    }
    </script>
    <script>
    window.__openLinkedIn=function(url,text){
        navigator.clipboard.writeText(text).catch(function(){});
        var o=document.createElement('div');
        o.id='li-modal-overlay';
        o.style.cssText='position:fixed;top:0;left:0;width:100%;height:100%;z-index:99999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.45)';
        o.innerHTML='<div style="background:#fff;max-width:380px;width:90%;border-radius:12px;padding:24px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.18)">'
            +'<div style="font-size:2.5rem;margin-bottom:12px">📋</div>'
            +'<p style="margin:0 0 18px;font-size:15px;line-height:1.5;color:#333">{{ __("Texte copié! Collez-le dans votre publication LinkedIn.") }}</p>'
            +'<button onclick="window.open(this.dataset.url,\'_blank\');document.getElementById(\'li-modal-overlay\').remove()" data-url="'+url+'" style="background:#0A66C2;color:#fff;border:none;padding:10px 22px;border-radius:8px;font-size:15px;cursor:pointer;font-weight:600">{{ __("Ouvrir LinkedIn") }} →</button>'
            +'<div style="margin-top:12px;font-size:12px;color:#9ca3af">{{ __("Ouverture automatique dans 2 secondes...") }}</div>'
            +'</div>';
        o.addEventListener('click',function(e){if(e.target===o)o.remove()});
        document.body.appendChild(o);
        setTimeout(function(){if(document.getElementById('li-modal-overlay')){window.open(url,'_blank');o.remove()}},2500);
    };
    </script>
</body>

</html>

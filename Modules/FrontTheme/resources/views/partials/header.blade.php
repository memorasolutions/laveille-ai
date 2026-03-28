<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!-- Skip navigation (WCAG 2.4.1) -->
<a href="#main-content" class="sr-only sr-only-focusable" style="position: absolute; top: -40px; left: 0; background: var(--c-primary); color: #fff; padding: 8px 16px; z-index: 10000; transition: top 0.2s;" onfocus="this.style.top='0'" onblur="this.style.top='-40px'">{{ __('Aller au contenu principal') }}</a>
<!-- Admin bar (visible admins seulement) -->
{{-- Barre admin retirée — remplacée par le dropdown avatar dans le header (session 2026-03-28) --}}
<!-- Start header -->
<header id="header" class="wpo-site-header">
    <div class="topbar">
        <div class="container">
            <div class="row">
                <div class="col col-lg-7 col-md-9 col-sm-12 col-12">
                    <div class="contact-intro">
                        <ul>
                            <li class="update"><span>{{ __('Nouveau') }}</span></li>
                            <li>{{ $latestArticle->title ?? __('Bienvenue sur le blog') }}</li>
                        </ul>
                    </div>
                </div>
                <div class="col col-lg-5 col-md-3 col-sm-12 col-12">
                    <div class="contact-info">
                        <ul>
                            <li><a href="https://www.facebook.com/LaVeilleDeStef" target="_blank" rel="noopener" aria-label="Facebook"><i class="ti-facebook"></i></a></li>
                            <li><a href="https://m.me/LaVeilleDeStef" target="_blank" rel="noopener" aria-label="Messenger"><i class="ti-comment"></i></a></li>
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
                        <button type="button" class="navbar-toggler open-btn">
                            <span class="sr-only">Toggle navigation</span>
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
                        <ul class="nav navbar-nav mb-2 mb-lg-0">
                            <li><a href="{{ route('home') }}">{{ __('Accueil') }}</a></li>
                            <li><a href="{{ route('blog.index') }}">{{ __('Blog') }}</a></li>
                            @isset($categories)
                            <li class="menu-item-has-children">
                                <a href="#">{{ __('Catégories') }}</a>
                                <ul class="sub-menu">
                                    @foreach($categories as $cat)
                                        <li><a href="{{ route('blog.category', $cat->slug) }}">{{ $cat->name }}</a></li>
                                    @endforeach
                                </ul>
                            </li>
                            @endisset
                            <li class="menu-item-has-children">
                                <a href="#">{{ __('Ressources') }}</a>
                                <ul class="sub-menu">
                                    @if(Route::has('tools.index'))
                                        <li><a href="{{ route('tools.index') }}">{{ __('Outils gratuits') }}</a></li>
                                    @endif
                                    @if(Route::has('dictionary.index'))
                                        <li><a href="{{ route('dictionary.index') }}">{{ __('Glossaire IA') }}</a></li>
                                    @endif
                                    @if(Route::has('directory.index'))
                                        <li><a href="{{ route('directory.index') }}">{{ __('Répertoire techno') }}</a></li>
                                    @endif
                                    @if(Route::has('acronyms.index'))
                                        <li><a href="{{ route('acronyms.index') }}">{{ __('Acronymes éducation') }}</a></li>
                                    @endif
                                    @if(Route::has('roadmap.boards.index'))
                                        <li><a href="{{ route('roadmap.boards.index') }}">{{ __('Propositions') }}</a></li>
                                    @endif
                                </ul>
                            </li>
                            <li class="menu-item-has-children">
                                <a href="#">{{ __('Pages') }}</a>
                                <ul class="sub-menu">
                                    @if(Route::has('page.show'))
                                        <li><a href="{{ route('page.show', 'a-propos') }}">{{ __('À propos') }}</a></li>
                                    @endif
                                    @if(Route::has('faq.index'))
                                        <li><a href="{{ route('faq.index') }}">{{ __('FAQ') }}</a></li>
                                    @endif
                                    <li><a href="{{ route('contact') }}">{{ __('Contact') }}</a></li>
                                    @if(Route::has('blog.submissions.create'))
                                        <li><a href="{{ route('blog.submissions.create') }}">✍️ {{ __('Proposer un article') }}</a></li>
                                    @endif
                                </ul>
                            </li>
                        </ul>
                    </div><!-- end of nav-collapse -->
                </div>
                <div class="col-lg-2 col-md-2 col-2">
                    <div class="header-right">
                        <div class="header-search-form-wrapper">
                            <div class="cart-search-contact">
                                <button class="search-toggle-btn" aria-label="{{ __('Ouvrir la recherche') }}"><i
                                        class="fi flaticon-magnifiying-glass"></i></button>
                                <div class="header-search-form">
                                    <form action="{{ route('blog.index') }}" method="GET">
                                        <div>
                                            <input type="text" name="search" class="form-control"
                                                placeholder="{{ __('Rechercher...') }}" aria-label="{{ __('Rechercher sur le site') }}">
                                            <button type="submit" aria-label="{{ __('Lancer la recherche') }}"><i
                                                    class="fi flaticon-magnifiying-glass"></i></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        {{-- Menu utilisateur connecté --}}
                        @auth
                        <div x-data="{ open: false }" style="display:inline-block;position:relative;margin-right:8px;vertical-align:middle;">
                            @php $unread = auth()->user()->unreadNotifications->count(); @endphp
                            <button @click="open = !open" @click.outside="open = false" style="background:none!important;border:none!important;cursor:pointer;padding:0;display:flex!important;align-items:center!important;gap:4px;outline:none!important;box-shadow:none!important;">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--c-primary);color:#fff;display:flex!important;align-items:center!important;justify-content:center!important;font-weight:700;font-size:13px;">{{ substr(auth()->user()->name, 0, 1) }}</div>
                                @if($unread > 0)<span style="position:absolute;top:-2px;right:-4px;background:#ef4444;color:#fff;font-size:9px;font-weight:700;width:16px;height:16px;border-radius:50%;display:flex!important;align-items:center!important;justify-content:center!important;">{{ min($unread, 9) }}</span>@endif
                            </button>
                            <div x-show="open" x-cloak x-transition style="position:absolute;right:0;top:40px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.12);width:220px;z-index:9999;padding:8px 0;">
                                <div style="padding:12px 16px;border-bottom:1px solid #f3f4f6;">
                                    <div style="font-weight:700;color:var(--c-dark);font-size:14px;">{{ auth()->user()->name }}</div>
                                    <div style="font-size:11px;color:#9ca3af;">{{ auth()->user()->email }}</div>
                                </div>
                                <a href="{{ route('user.profile') }}" style="display:block;padding:10px 16px;color:var(--c-dark);text-decoration:none!important;font-size:13px;font-weight:500;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">👤 {{ __('Mon profil') }}</a>
                                @if(Route::has('user.contributions'))<a href="{{ route('user.contributions') }}" style="display:block;padding:10px 16px;color:var(--c-dark);text-decoration:none!important;font-size:13px;font-weight:500;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">📝 {{ __('Mes contributions') }}</a>@endif
                                @if(Route::has('bookmarks.index'))<a href="{{ route('bookmarks.index') }}" style="display:block;padding:10px 16px;color:var(--c-dark);text-decoration:none!important;font-size:13px;font-weight:500;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">❤️ {{ __('Mes favoris') }}</a>@endif
                                @if(Route::has('user.notifications'))<a href="{{ route('user.notifications') }}" style="display:block;padding:10px 16px;color:var(--c-dark);text-decoration:none!important;font-size:13px;font-weight:500;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">🔔 {{ __('Notifications') }} @if($unread > 0)<span style="background:#ef4444;color:#fff;padding:1px 6px;border-radius:10px;font-size:10px;font-weight:700;margin-left:4px;">{{ $unread }}</span>@endif</a>@endif
                                @can('view_admin_panel')
                                <div style="border-top:1px solid #f3f4f6;margin-top:4px;padding-top:4px;">
                                    <a href="{{ url('/admin') }}" target="_blank" style="display:block;padding:10px 16px;color:var(--c-dark);text-decoration:none!important;font-size:13px;font-weight:500;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">⚙️ {{ __('Administration') }}</a>
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

                        <div class="header-right-menu-wrapper">
                            <div class="header-right-menu">
                                <div class="right-menu-toggle-btn">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                                <div class="header-right-menu-wrap">
                                    <button class="right-menu-close"><i class="ti-close"></i></button>
                                    <div class="logo"><img src="{{ asset('images/logo.webp') }}" alt="{{ config('app.name') }}"></div>
                                    <div class="header-right-sec">
                                        <div class="project-widget widget">
                                            <h3>{{ __('Derniers articles') }}</h3>
                                            <div class="posts">
                                                @isset($latestArticles)
                                                    @forelse($latestArticles->take(3) as $article)
                                                        <div class="post">
                                                            <div class="img-holder">
                                                                @if($article->featured_image)
                                                                    <img src="{{ asset($article->featured_image) }}" alt="{{ $article->title }}">
                                                                @else
                                                                    <img src="{{ fronttheme_asset('images/recent-posts/img-' . ($loop->iteration) . '.jpg') }}" alt="">
                                                                @endif
                                                            </div>
                                                            <div class="details">
                                                                <span class="date">{{ $article->published_at?->format('d M Y') }}</span>
                                                                <h4><a href="{{ route('blog.show', $article->slug) }}">{{ $article->title }}</a></h4>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <p>{{ __('Aucun article pour le moment.') }}</p>
                                                    @endforelse
                                                @endisset
                                            </div>
                                        </div>
                                        <div class="widget link-widget">
                                            <div class="widget-title">
                                                <h3>{{ __('Ressources') }}</h3>
                                            </div>
                                            <ul>
                                                @if(Route::has('tools.index'))
                                                    <li><a href="{{ route('tools.index') }}">{{ __('Outils gratuits') }}</a></li>
                                                @endif
                                                @if(Route::has('dictionary.index'))
                                                    <li><a href="{{ route('dictionary.index') }}">{{ __('Glossaire IA') }}</a></li>
                                                @endif
                                                @if(Route::has('directory.index'))
                                                    <li><a href="{{ route('directory.index') }}">{{ __('Répertoire techno') }}</a></li>
                                                @endif
                                                @if(Route::has('acronyms.index'))
                                                    <li><a href="{{ route('acronyms.index') }}">{{ __('Acronymes éducation') }}</a></li>
                                                @endif
                                            </ul>
                                        </div>
                                        <div class="widget wpo-contact-widget">
                                            <div class="widget-title">
                                                <h3>{{ __('Contact') }}</h3>
                                            </div>
                                            <div class="contact-ft">
                                                <ul>
                                                    <li><i class="fi flaticon-email"></i>{{ config('mail.from.address') }}</li>
                                                </ul>
                                            </div>
                                        </div>
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

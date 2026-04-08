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
                            <li class="update"><a href="{{ route('news.index') }}" style="color:inherit;text-decoration:none;"><span>{{ __('Actualités') }}</span></a></li>
                            <li>@if(isset($latestNewsArticle) && $latestNewsArticle)<a href="{{ route('news.show', $latestNewsArticle) }}" style="color:inherit;text-decoration:none;">{{ $latestNewsArticle->seo_title ?? $latestNewsArticle->title }}</a>@elseif(isset($latestArticle))<a href="{{ route('blog.show', $latestArticle->slug) }}" style="color:inherit;text-decoration:none;">{{ $latestArticle->title }}</a>@else{{ __('Veille IA et technologie') }}@endif</li>
                        </ul>
                    </div>
                </div>
                <div class="col col-lg-5 col-md-3 col-sm-12 col-12">
                    <div class="contact-info">
                        <ul>
                            <li><a href="{{ \Modules\Settings\Facades\Settings::get('social.facebook_page_url', 'https://www.facebook.com/LaVeilleDeStef') }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="ti-facebook"></i></a></li>
                            <li><a href="{{ \Modules\Settings\Facades\Settings::get('social.messenger_url', 'https://m.me/LaVeilleDeStef') }}" target="_blank" rel="noopener" aria-label="Messenger"><i class="ti-comment"></i></a></li>
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
                            <li class="menu-item-has-children has-mega-menu" x-data="{ megaOpen: false }" @mouseenter="megaOpen = true" @mouseleave="megaOpen = false" style="position:relative;">
                                <a href="#" @click.prevent="megaOpen = !megaOpen">{{ __('Catégories') }}</a>
                                {{-- Mega menu catégories --}}
                                <div x-show="megaOpen" x-cloak x-transition.opacity.duration.100ms
                                    style="position:absolute;left:0;top:100%;width:520px;background:#fff;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,0.12);padding:24px;z-index:9999;border:1px solid #E5E7EB;"
                                    @click.outside="megaOpen = false">
                                    <div style="font-family:var(--f-heading, 'Plus Jakarta Sans', sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--c-text-muted, #6E7687);margin-bottom:12px;">{{ __('Explorer par thème') }}</div>
                                    <div style="display:flex!important;flex-wrap:wrap!important;gap:4px;">
                                        @php
                                            $catEmojis = [
                                                'intelligence-artificielle' => '🤖',
                                                'actualites-techno' => '📰',
                                                'guides-tutoriels' => '📖',
                                                'outils-ressources' => '🛠️',
                                                'pedagogie-numerique' => '🎓',
                                                'frequence-numerique' => '🎙️',
                                                'le-concentre' => '☕',
                                                'divers' => '📌',
                                            ];
                                        @endphp
                                        @foreach($categories as $cat)
                                        <a href="{{ route('blog.category', $cat->slug) }}" style="display:flex!important;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;width:calc(50% - 2px);" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                            <span style="font-size:18px;line-height:1;">{{ $catEmojis[$cat->slug] ?? '📂' }}</span>
                                            <div style="font-weight:700;font-size:13px;color:var(--c-dark, #1A1D23);text-transform:none;">{{ ucfirst(mb_strtolower($cat->name)) }}</div>
                                        </a>
                                        @endforeach
                                    </div>
                                    <div style="border-top:1px solid #E5E7EB;margin-top:12px;padding-top:12px;text-align:center;">
                                        <a href="{{ route('blog.index') }}" style="font-size:13px;font-weight:700;color:var(--c-primary, #0B7285);text-decoration:none!important;">{{ __('Voir tous les articles') }} →</a>
                                    </div>
                                </div>
                                {{-- Fallback sub-menu mobile --}}
                                <ul class="sub-menu">
                                    @foreach($categories as $cat)
                                        <li><a href="{{ route('blog.category', $cat->slug) }}">{{ $cat->name }}</a></li>
                                    @endforeach
                                </ul>
                            </li>
                            @endisset
                            <li class="menu-item-has-children has-mega-menu" x-data="{ megaOpen: false }" @mouseenter="megaOpen = true" @mouseleave="megaOpen = false" style="position:relative;">
                                <a href="#" @click.prevent="megaOpen = !megaOpen">{{ __('Ressources') }}</a>
                                {{-- Mega menu --}}
                                <div x-show="megaOpen" x-cloak x-transition.opacity.duration.100ms
                                    style="position:absolute;left:-100px;top:100%;width:560px;background:#fff;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,0.12);padding:24px;z-index:9999;border:1px solid #E5E7EB;"
                                    @click.outside="megaOpen = false">
                                    <div style="display:flex!important;gap:24px;">
                                        {{-- Colonne gauche : outils et référence --}}
                                        <div style="flex:1!important;">
                                            <div style="font-family:var(--f-heading, 'Plus Jakarta Sans', sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--c-text-muted, #6E7687);margin-bottom:12px;">{{ __('Outils et référence') }}</div>
                                            @if(Route::has('news.index'))
                                            <a href="{{ route('news.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">📰</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Actualités') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Veille IA et technologie') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('directory.index'))
                                            <a href="{{ route('directory.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">🔍</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Répertoire techno') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ cache()->remember('directory_tools_count', 3600, fn () => class_exists(\Modules\Directory\Models\Tool::class) ? \Modules\Directory\Models\Tool::where('status', 'published')->count() : 0) }} {{ __('outils IA avec avis, tutoriels et discussions') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('tools.index'))
                                            <a href="{{ route('tools.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">🛠️</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Outils gratuits') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Calculatrices, générateurs et plus') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('dictionary.index'))
                                            <a href="{{ route('dictionary.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">📚</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Glossaire IA') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Termes et définitions de l\'IA') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('acronyms.index'))
                                            <a href="{{ route('acronyms.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">🎓</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Acronymes éducation') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ cache()->remember('acronyms_count', 3600, fn () => class_exists(\Modules\Acronyms\Models\Acronym::class) ? \Modules\Acronyms\Models\Acronym::count() : 0) }} {{ __('acronymes du Québec') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('shop.index'))
                                            <a href="{{ route('shop.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">🛍️</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Boutique') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Merch IA et technologie') }}</div></div>
                                            </a>
                                            @endif
                                        </div>
                                        {{-- Colonne droite : communauté + CTA --}}
                                        <div style="flex:1!important;">
                                            <div style="font-family:var(--f-heading, 'Plus Jakarta Sans', sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--c-text-muted, #6E7687);margin-bottom:12px;">{{ __('Communauté') }}</div>
                                            @if(Route::has('roadmap.boards.index'))
                                            <a href="{{ route('roadmap.boards.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">💡</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Propositions') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Suggérez vos idées') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('directory.leaderboard'))
                                            <a href="{{ route('directory.leaderboard') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:12px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">🏆</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Classement') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Top contributeurs') }}</div></div>
                                            </a>
                                            @endif
                                            {{-- CTA Raccourcisseur --}}
                                            @if(Route::has('shorturl.create'))
                                            <a href="{{ route('shorturl.create') }}" style="display:block;background:var(--c-primary-light, #F0FAFB);border:1px solid var(--c-primary, #0B7285);border-radius:10px;padding:14px;text-decoration:none!important;color:inherit;transition:background .15s;margin-top:8px;" onmouseover="this.style.background='#E0F2F4'" onmouseout="this.style.background='var(--c-primary-light, #F0FAFB)'">
                                                <div style="display:flex!important;align-items:center!important;gap:8px;margin-bottom:6px;">
                                                    <span style="font-size:20px;">🔗</span>
                                                    <div style="font-weight:700;font-size:14px;color:var(--c-primary, #0B7285);">{{ __('Raccourcir un lien') }}</div>
                                                </div>
                                                <div style="font-size:12px;color:var(--c-text-muted, #6E7687);margin-bottom:8px;">veille.la — {{ __('gratuit, QR code, statistiques') }}</div>
                                                <span style="display:inline-block;background:var(--c-primary, #0B7285);color:#fff;padding:5px 14px;border-radius:6px;font-weight:700;font-size:12px;">{{ __('Essayer') }} →</span>
                                            </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                {{-- Fallback sub-menu pour mobile (le mega menu est masqué en mobile) --}}
                                <ul class="sub-menu">
                                    @if(Route::has('news.index'))<li><a href="{{ route('news.index') }}">{{ __('Actualités') }}</a></li>@endif
                                    @if(Route::has('directory.index'))<li><a href="{{ route('directory.index') }}">{{ __('Répertoire techno') }}</a></li>@endif
                                    @if(Route::has('tools.index'))<li><a href="{{ route('tools.index') }}">{{ __('Outils gratuits') }}</a></li>@endif
                                    @if(Route::has('dictionary.index'))<li><a href="{{ route('dictionary.index') }}">{{ __('Glossaire IA') }}</a></li>@endif
                                    @if(Route::has('acronyms.index'))<li><a href="{{ route('acronyms.index') }}">{{ __('Acronymes éducation') }}</a></li>@endif
                                    @if(Route::has('roadmap.boards.index'))<li><a href="{{ route('roadmap.boards.index') }}">{{ __('Propositions') }}</a></li>@endif
                                    @if(Route::has('shorturl.create'))<li><a href="{{ route('shorturl.create') }}">{{ __('Raccourcir un lien') }}</a></li>@endif
                                </ul>
                            </li>
                            {{-- Lien raccourcir retiré — déjà dans le mega menu Ressources --}}
                            <li class="menu-item-has-children has-mega-menu" x-data="{ megaOpen: false }" @mouseenter="megaOpen = true" @mouseleave="megaOpen = false" style="position:relative;">
                                <a href="#" @click.prevent="megaOpen = !megaOpen">{{ __('Pages') }}</a>
                                {{-- Mega menu pages --}}
                                <div x-show="megaOpen" x-cloak x-transition.opacity.duration.100ms
                                    style="position:absolute;right:0;top:100%;width:440px;background:#fff;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,0.12);padding:24px;z-index:9999;border:1px solid #E5E7EB;"
                                    @click.outside="megaOpen = false">
                                    <div style="display:flex!important;gap:24px;">
                                        <div style="flex:1!important;">
                                            <div style="font-family:var(--f-heading, 'Plus Jakarta Sans', sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--c-text-muted, #6E7687);margin-bottom:12px;">{{ __('Informations') }}</div>
                                            @if(Route::has('page.show'))
                                            <a href="{{ route('page.show', 'a-propos') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">👋</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('À propos') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Notre mission et notre equipe') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('faq.index'))
                                            <a href="{{ route('faq.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">❓</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('FAQ') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Questions frequentes') }}</div></div>
                                            </a>
                                            @endif
                                            <a href="{{ route('contact') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">✉️</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Contact') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Nous ecrire') }}</div></div>
                                            </a>
                                        </div>
                                        <div style="flex:1!important;">
                                            <div style="font-family:var(--f-heading, 'Plus Jakarta Sans', sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--c-text-muted, #6E7687);margin-bottom:12px;">{{ __('Participer') }}</div>
                                            @if(Route::has('blog.submissions.create'))
                                            <a href="{{ route('blog.submissions.create') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">✍️</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Proposer un article') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Partagez votre expertise') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('directory.index'))
                                            <a href="{{ route('directory.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">🔧</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Proposer un outil') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Enrichir le repertoire') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('legal.privacy'))
                                            <a href="{{ route('legal.privacy') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">🔒</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Confidentialite') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Politique de vie privee') }}</div></div>
                                            </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                {{-- Fallback sub-menu mobile --}}
                                <ul class="sub-menu">
                                    @if(Route::has('page.show'))<li><a href="{{ route('page.show', 'a-propos') }}">{{ __('À propos') }}</a></li>@endif
                                    @if(Route::has('faq.index'))<li><a href="{{ route('faq.index') }}">{{ __('FAQ') }}</a></li>@endif
                                    <li><a href="{{ route('contact') }}">{{ __('Contact') }}</a></li>
                                    @if(Route::has('blog.submissions.create'))<li><a href="{{ route('blog.submissions.create') }}">{{ __('Proposer un article') }}</a></li>@endif
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
                        {{-- Mini-cart (conditionnel — module Shop activé) --}}
                        @includeIf('shop::partials.mini-cart')
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
                                    <div style="font-size:11px;color:#9ca3af;">{{ auth()->user()->email }}</div>
                                </div>
                                @include('auth::components.user-menu-links', ['variant' => 'dropdown'])
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
                        @guest
                        <div x-data style="display:inline-block;margin-right:8px;vertical-align:middle;">
                            <button @click="$dispatch('open-auth-modal', { message: '' })" aria-label="{{ __('Se connecter') }}" style="background:none!important;border:none!important;cursor:pointer;padding:0;display:flex!important;align-items:center!important;gap:6px;outline:none!important;box-shadow:none!important;color:var(--c-dark);font-size:13px;font-weight:600;">
                                <div style="width:32px;height:32px;border-radius:50%;background:#E5E7EB;display:flex!important;align-items:center!important;justify-content:center!important;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
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
                                        {{-- Navigation principale --}}
                                        <div class="widget link-widget">
                                            <div class="widget-title"><h3>{{ __('Navigation') }}</h3></div>
                                            <ul>
                                                <li><a href="{{ route('home') }}">{{ __('Accueil') }}</a></li>
                                                <li><a href="{{ route('blog.index') }}">{{ __('Blog') }}</a></li>
                                                @if(Route::has('shorturl.create'))<li><a href="{{ route('shorturl.create') }}">🔗 {{ __('Raccourcir un lien') }}</a></li>@endif
                                            </ul>
                                        </div>
                                        {{-- Ressources --}}
                                        <div class="widget link-widget">
                                            <div class="widget-title"><h3>{{ __('Ressources') }}</h3></div>
                                            <ul>
                                                @if(Route::has('news.index'))<li><a href="{{ route('news.index') }}">📰 {{ __('Actualités') }}</a></li>@endif
                                                @if(Route::has('directory.index'))<li><a href="{{ route('directory.index') }}">🔍 {{ __('Répertoire techno') }}</a></li>@endif
                                                @if(Route::has('tools.index'))<li><a href="{{ route('tools.index') }}">🛠️ {{ __('Outils gratuits') }}</a></li>@endif
                                                @if(Route::has('dictionary.index'))<li><a href="{{ route('dictionary.index') }}">📚 {{ __('Glossaire IA') }}</a></li>@endif
                                                @if(Route::has('acronyms.index'))<li><a href="{{ route('acronyms.index') }}">🎓 {{ __('Acronymes éducation') }}</a></li>@endif
                                            </ul>
                                        </div>
                                        {{-- Pages --}}
                                        <div class="widget link-widget">
                                            <div class="widget-title"><h3>{{ __('Pages') }}</h3></div>
                                            <ul>
                                                @if(Route::has('page.show'))<li><a href="{{ route('page.show', 'a-propos') }}">{{ __('À propos') }}</a></li>@endif
                                                @if(Route::has('faq.index'))<li><a href="{{ route('faq.index') }}">{{ __('FAQ') }}</a></li>@endif
                                                <li><a href="{{ route('contact') }}">{{ __('Contact') }}</a></li>
                                                @if(Route::has('blog.submissions.create'))<li><a href="{{ route('blog.submissions.create') }}">✍️ {{ __('Proposer un article') }}</a></li>@endif
                                            </ul>
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

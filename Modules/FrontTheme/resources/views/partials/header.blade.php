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
                            <li>@if(isset($latestNewsArticle) && $latestNewsArticle)<a href="{{ route('news.show', $latestNewsArticle) }}" style="color:inherit;text-decoration:none;">{{ $latestNewsArticle->seo_title ?? $latestNewsArticle->title }}</a>@elseif(isset($latestArticle))<a href="{{ route('blog.show', $latestArticle->slug) }}" style="color:inherit;text-decoration:none;">{{ $latestArticle->title }}</a>@else{{ __('Veille IA et technologie') }}@endif</li>
                        </ul>
                    </div>
                </div>
                <div class="col col-lg-5 col-md-3 col-sm-12 col-12">
                    <div class="contact-info">
                        <ul>
                            <li><a href="{{ lv_social('facebook') }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="ti-facebook"></i></a></li>
                            <li><a href="{{ lv_social('messenger') }}" target="_blank" rel="noopener" aria-label="Messenger"><i class="ti-comment"></i></a></li>
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
                        @php
                            // #200 Refonte nav E hybride mai 2026 — data-driven GA4 top pages
                            $directoryCount = cache()->remember('directory_tools_count', 3600, fn () => class_exists(\Modules\Directory\Models\Tool::class) ? \Modules\Directory\Models\Tool::where('status', 'published')->count() : 0);
                            $dictionaryCount = cache()->remember('dictionary_terms_count', 3600, fn () => class_exists(\Modules\Dictionary\Models\Term::class) ? \Modules\Dictionary\Models\Term::where('is_published', 1)->count() : 0);
                            // Fiches stars annuaire selon GA4 30j (Poe 75v 29:33 · ChatGPT 25v · Zuflow 51:07 · Canva AI · Wooclap · Claude Design)
                            $directoryStars = [
                                ['slug' => 'poe', 'name' => 'Poe', 'desc' => 'Multi-IA en un'],
                                ['slug' => 'chatgpt', 'name' => 'ChatGPT', 'desc' => "L'IA générale OpenAI"],
                                ['slug' => 'canva-ai', 'name' => 'Canva AI', 'desc' => 'Création visuelle'],
                                ['slug' => 'wooclap', 'name' => 'Wooclap', 'desc' => 'Quiz interactifs'],
                                ['slug' => 'claude-design', 'name' => 'Claude Design', 'desc' => 'Anthropic premium'],
                            ];
                        @endphp
                        <ul class="nav navbar-nav mb-2 mb-lg-0">
                            <li><a href="{{ route('home') }}">{{ __('Accueil') }}</a></li>

                            {{-- 1. OUTILS — mega 4 groupes (Productivité / Création / Détente / Pratique) — #200 fusion ancien Jouer + outils gratuits --}}
                            <li class="menu-item-has-children has-mega-menu" x-data="{ megaOpen: false }" @mouseenter="megaOpen = true" @mouseleave="megaOpen = false" style="position:relative;">
                                <a href="{{ Route::has('tools.index') ? route('tools.index') : url('/outils') }}" @click.prevent="megaOpen = !megaOpen" aria-haspopup="true" :aria-expanded="megaOpen">{{ __('Outils') }}</a>
                                <div x-show="megaOpen" x-cloak x-transition.opacity.duration.100ms
                                    style="position:absolute;left:0;top:100%;width:780px;background:#fff;border-radius:16px;box-shadow:0 12px 36px rgba(0,0,0,0.14);padding:28px;z-index:9999;border:1px solid #E5E7EB;"
                                    @click.outside="megaOpen = false"
                                    role="menu" aria-label="{{ __('Menu Outils') }}">
                                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:24px;">
                                        {{-- Productivité --}}
                                        <div>
                                            <div style="font-family:var(--f-heading,'Plus Jakarta Sans',sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--c-text-muted,#6E7687);margin-bottom:10px;">💡 {{ __('Productivité') }}</div>
                                            <a href="{{ url('/outils/brain-dump') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🧠</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Brain Dump 2026') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('10 min papier + IA = clarté') }}</div></div>
                                            </a>
                                            <a href="{{ url('/outils/constructeur-prompts') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">✏️</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Constructeur de prompts') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __("Maîtrisez l'art du prompt IA") }}</div></div>
                                            </a>
                                            @if(Route::has('directory.compare-by-ids'))
                                            <a href="{{ route('directory.compare-by-ids') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🆚</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __("Comparateur d'outils IA") }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('Jusqu\'à 6 outils côte à côte') }}</div></div>
                                            </a>
                                            @endif
                                        </div>
                                        {{-- Création --}}
                                        <div>
                                            <div style="font-family:var(--f-heading,'Plus Jakarta Sans',sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--c-text-muted,#6E7687);margin-bottom:10px;">🎨 {{ __('Création') }}</div>
                                            <a href="{{ url('/outils/mots-croises') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🔤</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Générateur de mots croisés') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('Grilles personnalisées + PDF') }}</div></div>
                                            </a>
                                            <a href="{{ url('/outils/code-qr') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">📱</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Générateur de code QR') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('QR personnalisable PNG/SVG') }}</div></div>
                                            </a>
                                            @if(Route::has('shorturl.create'))
                                            <a href="{{ route('shorturl.create') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🔗</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Raccourcir un lien') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('veille.la gratuit + QR') }}</div></div>
                                            </a>
                                            @endif
                                        </div>
                                        {{-- Détente --}}
                                        <div>
                                            <div style="font-family:var(--f-heading,'Plus Jakarta Sans',sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--c-text-muted,#6E7687);margin-bottom:10px;">🎲 {{ __('Détente') }}</div>
                                            <a href="{{ url('/outils/sudoku') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🧩</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Sudoku quotidien') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('Nouvelle grille chaque jour') }}</div></div>
                                            </a>
                                            <a href="{{ url('/jeumc') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🎯</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Grilles partagées') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('Mots croisés à jouer en ligne') }}</div></div>
                                            </a>
                                        </div>
                                        {{-- Pratique --}}
                                        <div>
                                            <div style="font-family:var(--f-heading,'Plus Jakarta Sans',sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--c-text-muted,#6E7687);margin-bottom:10px;">⚙️ {{ __('Pratique') }}</div>
                                            <a href="{{ url('/outils/simulateur-fiscal') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">💰</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Calculatrice taxes QC') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('Simulateur fiscal Québec') }}</div></div>
                                            </a>
                                            @if(Route::has('tools.quest.index') && config('tools.quest.enabled', false))
                                            <a href="{{ route('tools.quest.index') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🎮</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __("Quête narrative IA") }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __("Les Sentiers de l'IA") }}</div></div>
                                            </a>
                                            @endif
                                        </div>
                                    </div>
                                    <div style="border-top:1px solid #E5E7EB;margin-top:18px;padding-top:14px;text-align:center;">
                                        @if(Route::has('tools.index'))
                                        <a href="{{ route('tools.index') }}" style="font-size:13px;font-weight:700;color:var(--c-primary,#064E5A);text-decoration:none!important;">{{ __('Voir tous les outils gratuits') }} →</a>
                                        @endif
                                    </div>
                                </div>
                                {{-- Fallback sub-menu mobile --}}
                                <ul class="sub-menu">
                                    <li><a href="{{ url('/outils/brain-dump') }}">🧠 {{ __('Brain Dump 2026') }}</a></li>
                                    <li><a href="{{ url('/outils/constructeur-prompts') }}">✏️ {{ __('Constructeur de prompts') }}</a></li>
                                    @if(Route::has('directory.compare-by-ids'))<li><a href="{{ route('directory.compare-by-ids') }}">🆚 {{ __("Comparateur d'outils IA") }}</a></li>@endif
                                    <li><a href="{{ url('/outils/mots-croises') }}">🔤 {{ __('Mots croisés') }}</a></li>
                                    <li><a href="{{ url('/outils/sudoku') }}">🧩 {{ __('Sudoku') }}</a></li>
                                    <li><a href="{{ url('/outils/simulateur-fiscal') }}">💰 {{ __('Calculatrice taxes QC') }}</a></li>
                                    @if(Route::has('tools.quest.index') && config('tools.quest.enabled', false))<li><a href="{{ route('tools.quest.index') }}">🎮 {{ __('Quête narrative') }}</a></li>@endif
                                    @if(Route::has('tools.index'))<li><a href="{{ route('tools.index') }}">→ {{ __('Tous les outils') }}</a></li>@endif
                                </ul>
                            </li>

                            {{-- 2. ANNUAIRE — mega option b (fiches stars data-driven GA4) — #200 NEW top-level --}}
                            @if(Route::has('directory.index'))
                            <li class="menu-item-has-children has-mega-menu" x-data="{ megaOpen: false }" @mouseenter="megaOpen = true" @mouseleave="megaOpen = false" style="position:relative;">
                                <a href="{{ route('directory.index') }}" @click.prevent="megaOpen = !megaOpen" aria-haspopup="true" :aria-expanded="megaOpen">{{ __('Annuaire') }}</a>
                                <div x-show="megaOpen" x-cloak x-transition.opacity.duration.100ms
                                    style="position:absolute;left:-150px;top:100%;width:560px;background:#fff;border-radius:16px;box-shadow:0 12px 36px rgba(0,0,0,0.14);padding:24px;z-index:9999;border:1px solid #E5E7EB;"
                                    @click.outside="megaOpen = false"
                                    role="menu" aria-label="{{ __('Menu Annuaire') }}">
                                    <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:20px;">
                                        <div>
                                            <div style="font-family:var(--f-heading,'Plus Jakarta Sans',sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--c-text-muted,#6E7687);margin-bottom:10px;">⭐ {{ __('Top consultés') }}</div>
                                            @foreach($directoryStars as $star)
                                            <a href="{{ url('/annuaire/'.$star['slug']) }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🔧</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ $star['name'] }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ $star['desc'] }}</div></div>
                                            </a>
                                            @endforeach
                                        </div>
                                        <div>
                                            <div style="font-family:var(--f-heading,'Plus Jakarta Sans',sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--c-text-muted,#6E7687);margin-bottom:10px;">🧭 {{ __('Navigation') }}</div>
                                            <a href="{{ route('directory.index') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🔍</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Tous les outils') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ $directoryCount }} {{ __('avec avis + tutos') }}</div></div>
                                            </a>
                                            @if(Route::has('directory.leaderboard') && config('directory.leaderboard.enabled', false))
                                            <a href="{{ route('directory.leaderboard') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🏆</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Classement') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('Top contributeurs') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('collections.index'))
                                            <a href="{{ route('collections.index') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">📁</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Collections') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('Listes communauté') }}</div></div>
                                            </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                {{-- Fallback sub-menu mobile --}}
                                <ul class="sub-menu">
                                    <li><a href="{{ route('directory.index') }}">🔍 {{ __('Tous les outils ('.$directoryCount.')') }}</a></li>
                                    @foreach($directoryStars as $star)<li><a href="{{ url('/annuaire/'.$star['slug']) }}">{{ $star['name'] }}</a></li>@endforeach
                                    @if(Route::has('directory.leaderboard') && config('directory.leaderboard.enabled', false))<li><a href="{{ route('directory.leaderboard') }}">🏆 {{ __('Classement') }}</a></li>@endif
                                    @if(Route::has('collections.index'))<li><a href="{{ route('collections.index') }}">📁 {{ __('Collections') }}</a></li>@endif
                                </ul>
                            </li>
                            @endif

                            {{-- 3. APPRENDRE — mega Blog + Glossaire + Actualités + FAQ — #200 fusionne ancien Apprendre+Ressources --}}
                            <li class="menu-item-has-children has-mega-menu" x-data="{ megaOpen: false }" @mouseenter="megaOpen = true" @mouseleave="megaOpen = false" style="position:relative;">
                                <a href="{{ Route::has('blog.index') ? route('blog.index') : url('/blog') }}" @click.prevent="megaOpen = !megaOpen" aria-haspopup="true" :aria-expanded="megaOpen">{{ __('Apprendre') }}</a>
                                <div x-show="megaOpen" x-cloak x-transition.opacity.duration.100ms
                                    style="position:absolute;right:0;top:100%;width:640px;background:#fff;border-radius:16px;box-shadow:0 12px 36px rgba(0,0,0,0.14);padding:24px;z-index:9999;border:1px solid #E5E7EB;"
                                    @click.outside="megaOpen = false"
                                    role="menu" aria-label="{{ __('Menu Apprendre') }}">
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                                        <div>
                                            <div style="font-family:var(--f-heading,'Plus Jakarta Sans',sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--c-text-muted,#6E7687);margin-bottom:10px;">📚 {{ __('Contenu éditorial') }}</div>
                                            @if(Route::has('news.index'))
                                            <a href="{{ route('news.index') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">📰</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Actualités') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('Veille IA et technologie') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('blog.index'))
                                            <a href="{{ route('blog.index') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">✍️</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Blog') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('Articles longs et guides') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('faq.index'))
                                            <a href="{{ route('faq.index') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">❓</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('FAQ') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('Questions fréquentes') }}</div></div>
                                            </a>
                                            @endif
                                        </div>
                                        <div>
                                            <div style="font-family:var(--f-heading,'Plus Jakarta Sans',sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--c-text-muted,#6E7687);margin-bottom:10px;">📖 {{ __('Référence') }}</div>
                                            @if(Route::has('dictionary.index'))
                                            <a href="{{ route('dictionary.index') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">📚</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Glossaire IA') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ $dictionaryCount }} {{ __('termes et définitions') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('acronyms.index'))
                                            <a href="{{ route('acronyms.index') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🔤</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Acronymes éducation') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('Sigles du Québec') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('shop.index') && (! config('shop.maintenance', false) || (auth()->check() && auth()->user()->isSuperAdmin())))
                                            <a href="{{ route('shop.index') }}" style="display:flex;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🛍️</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark,#1A1D23);">{{ __('Boutique') }}</div><div style="font-size:12px;color:var(--c-text-muted,#6E7687);">{{ __('Merch IA et technologie') }}</div></div>
                                            </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                {{-- Fallback sub-menu mobile --}}
                                <ul class="sub-menu">
                                    @if(Route::has('news.index'))<li><a href="{{ route('news.index') }}">📰 {{ __('Actualités') }}</a></li>@endif
                                    @if(Route::has('blog.index'))<li><a href="{{ route('blog.index') }}">✍️ {{ __('Blog') }}</a></li>@endif
                                    @if(Route::has('dictionary.index'))<li><a href="{{ route('dictionary.index') }}">📚 {{ __('Glossaire IA') }}</a></li>@endif
                                    @if(Route::has('acronyms.index'))<li><a href="{{ route('acronyms.index') }}">🔤 {{ __('Acronymes') }}</a></li>@endif
                                    @if(Route::has('faq.index'))<li><a href="{{ route('faq.index') }}">❓ {{ __('FAQ') }}</a></li>@endif
                                    @if(Route::has('shop.index') && (! config('shop.maintenance', false) || (auth()->check() && auth()->user()->isSuperAdmin())))<li><a href="{{ route('shop.index') }}">🛍️ {{ __('Boutique') }}</a></li>@endif
                                </ul>
                            </li>
                            {{-- #200 Anciens blocs Ressources + Jouer retirés — remplacés par les 3 nouveaux mega Outils/Annuaire/Apprendre ci-dessus. Old code ci-dessous gardé en @if(false) zone --}}
                            @if(false)
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
                                            {{-- #181 "Outils gratuits" déplacé vers mega menu "Jouer" --}}
                                            @if(Route::has('dictionary.index'))
                                            <a href="{{ route('dictionary.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">📚</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Glossaire IA') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ cache()->remember('dictionary_terms_count', 3600, fn () => class_exists(\Modules\Dictionary\Models\Term::class) ? \Modules\Dictionary\Models\Term::where('is_published', 1)->count() : 0) }} {{ __('termes et définitions de l\'IA') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('acronyms.index'))
                                            <a href="{{ route('acronyms.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">🎓</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Acronymes éducation') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ cache()->remember('acronyms_count', 3600, fn () => class_exists(\Modules\Acronyms\Models\Acronym::class) ? \Modules\Acronyms\Models\Acronym::count() : 0) }} {{ __('acronymes du Québec') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('shop.index') && (! config('shop.maintenance', false) || (auth()->check() && auth()->user()->isSuperAdmin())))
                                            <a href="{{ route('shop.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">🛍️</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Boutique') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Merch IA et technologie') }}</div></div>
                                            </a>
                                            @endif
                                            @if(Route::has('collections.index'))
                                            <a href="{{ route('collections.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">📁</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Collections') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Listes d\'outils de la communauté') }}</div></div>
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
                                            @if(Route::has('directory.leaderboard') && config('directory.leaderboard.enabled', false))
                                            <a href="{{ route('directory.leaderboard') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:12px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                                                <span style="font-size:18px;line-height:1;">🏆</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Classement') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Top contributeurs') }}</div></div>
                                            </a>
                                            @endif
                                            {{-- #181 CTA Raccourcisseur déplacé vers mega menu "Jouer" --}}
                                        </div>
                                    </div>
                                </div>
                                {{-- Fallback sub-menu pour mobile (le mega menu est masqué en mobile) --}}
                                <ul class="sub-menu">
                                    @if(Route::has('news.index'))<li><a href="{{ route('news.index') }}">{{ __('Actualités') }}</a></li>@endif
                                    @if(Route::has('directory.index'))<li><a href="{{ route('directory.index') }}">{{ __('Répertoire techno') }}</a></li>@endif
                                    @if(Route::has('dictionary.index'))<li><a href="{{ route('dictionary.index') }}">{{ __('Glossaire IA') }}</a></li>@endif
                                    @if(Route::has('acronyms.index'))<li><a href="{{ route('acronyms.index') }}">{{ __('Acronymes éducation') }}</a></li>@endif
                                    @if(Route::has('collections.index'))<li><a href="{{ route('collections.index') }}">{{ __('Collections') }}</a></li>@endif
                                    @if(Route::has('roadmap.boards.index'))<li><a href="{{ route('roadmap.boards.index') }}">{{ __('Propositions') }}</a></li>@endif
                                </ul>
                            </li>
                            {{-- #181 Mega menu "Jouer" — saga + avatar + outils interactifs + jeux + utilitaires --}}
                            <li class="menu-item-has-children has-mega-menu" x-data="{ megaOpen: false }" @mouseenter="megaOpen = true" @mouseleave="megaOpen = false" style="position:relative;">
                                <a href="#" @click.prevent="megaOpen = !megaOpen" aria-haspopup="true" :aria-expanded="megaOpen">{{ __('Jouer') }}</a>
                                <div x-show="megaOpen" x-cloak x-transition.opacity.duration.100ms
                                    style="position:absolute;left:-200px;top:100%;width:600px;background:#fff;border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,0.12);padding:24px;z-index:9999;border:1px solid #E5E7EB;"
                                    @click.outside="megaOpen = false"
                                    role="menu" aria-label="{{ __('Menu Jouer') }}">
                                    <div style="display:flex!important;gap:24px;">
                                        <div style="flex:1!important;">
                                            <div style="font-family:var(--f-heading, 'Plus Jakarta Sans', sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--c-text-muted, #6E7687);margin-bottom:12px;">{{ __('Aventures interactives') }}</div>
                                            @if(Route::has('tools.quest.index') && config('tools.quest.enabled', false))
                                            <a href="{{ route('tools.quest.index') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🎮</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Les Sentiers de l\'IA') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Quête narrative IA avec Loop') }}</div></div>
                                            </a>
                                            @endif
                                            <div style="font-family:var(--f-heading, 'Plus Jakarta Sans', sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--c-text-muted, #6E7687);margin:16px 0 12px;">{{ __('Exercer son cerveau') }}</div>
                                            @if(Route::has('tools.show'))
                                            <a href="{{ url('/outils/constructeur-prompts') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🧠</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Constructeur de prompts') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Maîtrisez l\'art du prompt IA') }}</div></div>
                                            </a>
                                            @if(Route::has('directory.compare-by-ids'))
                                            <a href="{{ route('directory.compare-by-ids') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🆚</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Comparateur d\'outils IA') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Comparez jusqu\'à 6 outils côte-à-côte') }}</div></div>
                                            </a>
                                            @endif
                                            @endif
                                        </div>
                                        <div style="flex:1!important;">
                                            <div style="font-family:var(--f-heading, 'Plus Jakarta Sans', sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--c-text-muted, #6E7687);margin-bottom:12px;">{{ __('Détente') }}</div>
                                            <a href="{{ url('/outils/sudoku') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🧩</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Sudoku quotidien') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Une nouvelle grille chaque jour') }}</div></div>
                                            </a>
                                            <a href="{{ url('/outils/mots-croises') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🔤</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Mots croisés IA') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Générateur de grilles personnalisé') }}</div></div>
                                            </a>
                                            <div style="font-family:var(--f-heading, 'Plus Jakarta Sans', sans-serif);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--c-text-muted, #6E7687);margin:16px 0 12px;">{{ __('Utilitaires') }}</div>
                                            @if(Route::has('shorturl.create'))
                                            <a href="{{ route('shorturl.create') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;margin-bottom:2px;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">🔗</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Raccourcir un lien') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('veille.la gratuit + QR') }}</div></div>
                                            </a>
                                            @endif
                                            <a href="{{ url('/outils/simulateur-fiscal') }}" style="display:flex!important;gap:10px;padding:8px 10px;border-radius:8px;text-decoration:none!important;color:inherit;transition:background .15s;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'" role="menuitem">
                                                <span style="font-size:18px;line-height:1;">💰</span>
                                                <div><div style="font-weight:700;font-size:14px;color:var(--c-dark, #1A1D23);">{{ __('Calculatrice taxes QC') }}</div><div style="font-size:12px;color:var(--c-text-muted, #6E7687);">{{ __('Simulateur fiscal Québec') }}</div></div>
                                            </a>
                                        </div>
                                    </div>
                                    <div style="border-top:1px solid #E5E7EB;margin-top:16px;padding-top:12px;text-align:center;">
                                        @if(Route::has('tools.index'))
                                        <a href="{{ route('tools.index') }}" style="font-size:13px;font-weight:700;color:var(--c-primary, #064E5A);text-decoration:none!important;">{{ __('Tous les outils') }} →</a>
                                        @endif
                                    </div>
                                </div>
                                {{-- Fallback sub-menu mobile --}}
                                <ul class="sub-menu">
                                    @if(Route::has('tools.quest.index') && config('tools.quest.enabled', false))<li><a href="{{ route('tools.quest.index') }}">{{ __('Les Sentiers de l\'IA') }}</a></li>@endif
                                    <li><a href="{{ url('/outils/constructeur-prompts') }}">{{ __('Constructeur de prompts') }}</a></li>
                                    @if(Route::has('directory.compare-by-ids'))<li><a href="{{ route('directory.compare-by-ids') }}">{{ __('Comparateur d\'outils IA') }}</a></li>@endif
                                    <li><a href="{{ url('/outils/sudoku') }}">{{ __('Sudoku quotidien') }}</a></li>
                                    <li><a href="{{ url('/outils/mots-croises') }}">{{ __('Mots croisés IA') }}</a></li>
                                    @if(Route::has('shorturl.create'))<li><a href="{{ route('shorturl.create') }}">{{ __('Raccourcir un lien') }}</a></li>@endif
                                    <li><a href="{{ url('/outils/simulateur-fiscal') }}">{{ __('Calculatrice taxes') }}</a></li>
                                    @if(Route::has('tools.index'))<li><a href="{{ route('tools.index') }}">{{ __('Tous les outils') }}</a></li>@endif
                                </ul>
                            </li>
                            @endif
                            {{-- #181 "Pages" retiré du menu principal : tous les liens (À propos, FAQ, Contact, Confidentialité) déjà dans le footer --}}
                            @if(false)
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
                            @endif
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
                                    <div style="font-size:11px;color:#374151;">{{ auth()->user()->email }}</div>
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
                                        {{-- #200 Mobile sidebar refondue : Outils · Annuaire · Apprendre (aligné desktop) --}}
                                        <div class="widget link-widget">
                                            <div class="widget-title"><h3>{{ __('Accueil') }}</h3></div>
                                            <ul>
                                                <li><a href="{{ route('home') }}">🏠 {{ __('Page d\'accueil') }}</a></li>
                                            </ul>
                                        </div>
                                        {{-- Outils — groupes 2026 --}}
                                        <div class="widget link-widget">
                                            <div class="widget-title"><h3>{{ __('Outils') }}</h3></div>
                                            <ul>
                                                <li><a href="{{ url('/outils/brain-dump') }}">🧠 {{ __('Brain Dump 2026') }}</a></li>
                                                <li><a href="{{ url('/outils/constructeur-prompts') }}">✏️ {{ __('Constructeur de prompts') }}</a></li>
                                                @if(Route::has('directory.compare-by-ids'))<li><a href="{{ route('directory.compare-by-ids') }}">🆚 {{ __('Comparateur d\'outils IA') }}</a></li>@endif
                                                <li><a href="{{ url('/outils/mots-croises') }}">🔤 {{ __('Générateur mots croisés') }}</a></li>
                                                <li><a href="{{ url('/outils/code-qr') }}">📱 {{ __('Code QR') }}</a></li>
                                                <li><a href="{{ url('/outils/sudoku') }}">🧩 {{ __('Sudoku quotidien') }}</a></li>
                                                <li><a href="{{ url('/jeumc') }}">🎯 {{ __('Grilles partagées') }}</a></li>
                                                @if(Route::has('shorturl.create'))<li><a href="{{ route('shorturl.create') }}">🔗 {{ __('Raccourcir un lien') }}</a></li>@endif
                                                <li><a href="{{ url('/outils/simulateur-fiscal') }}">💰 {{ __('Calculatrice taxes QC') }}</a></li>
                                                @if(Route::has('tools.quest.index') && config('tools.quest.enabled', false))<li><a href="{{ route('tools.quest.index') }}">🎮 {{ __('Quête narrative') }}</a></li>@endif
                                                @if(Route::has('tools.index'))<li><a href="{{ route('tools.index') }}"><strong>→ {{ __('Voir tous les outils') }}</strong></a></li>@endif
                                            </ul>
                                        </div>
                                        {{-- Annuaire — fiches stars data-driven --}}
                                        @if(Route::has('directory.index'))
                                        <div class="widget link-widget">
                                            <div class="widget-title"><h3>{{ __('Annuaire') }}</h3></div>
                                            <ul>
                                                <li><a href="{{ route('directory.index') }}">🔍 <strong>{{ __('Tous les outils ('.$directoryCount.')') }}</strong></a></li>
                                                @foreach($directoryStars as $star)<li><a href="{{ url('/annuaire/'.$star['slug']) }}">{{ $star['name'] }}</a></li>@endforeach
                                                @if(Route::has('directory.leaderboard') && config('directory.leaderboard.enabled', false))<li><a href="{{ route('directory.leaderboard') }}">🏆 {{ __('Classement') }}</a></li>@endif
                                                @if(Route::has('collections.index'))<li><a href="{{ route('collections.index') }}">📁 {{ __('Collections') }}</a></li>@endif
                                            </ul>
                                        </div>
                                        @endif
                                        {{-- Apprendre — éditorial + référence --}}
                                        <div class="widget link-widget">
                                            <div class="widget-title"><h3>{{ __('Apprendre') }}</h3></div>
                                            <ul>
                                                @if(Route::has('news.index'))<li><a href="{{ route('news.index') }}">📰 {{ __('Actualités') }}</a></li>@endif
                                                @if(Route::has('blog.index'))<li><a href="{{ route('blog.index') }}">✍️ {{ __('Blog') }}</a></li>@endif
                                                @if(Route::has('dictionary.index'))<li><a href="{{ route('dictionary.index') }}">📚 {{ __('Glossaire IA ('.$dictionaryCount.')') }}</a></li>@endif
                                                @if(Route::has('acronyms.index'))<li><a href="{{ route('acronyms.index') }}">🔤 {{ __('Acronymes') }}</a></li>@endif
                                                @if(Route::has('faq.index'))<li><a href="{{ route('faq.index') }}">❓ {{ __('FAQ') }}</a></li>@endif
                                                @if(Route::has('shop.index') && (! config('shop.maintenance', false) || (auth()->check() && auth()->user()->isSuperAdmin())))<li><a href="{{ route('shop.index') }}">🛍️ {{ __('Boutique') }}</a></li>@endif
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

@include('fronttheme::partials.search-palette')

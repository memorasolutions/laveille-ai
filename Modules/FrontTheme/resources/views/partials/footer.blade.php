<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!-- start of wpo-site-footer-section -->
<footer class="wpo-site-footer">
    <div class="wpo-upper-footer">
        <div class="container">
            <div class="row">
                <div class="col col-lg-3 col-md-6 col-sm-12 col-12">
                    <div class="widget about-widget">
                        <div class="logo widget-title" style="max-width: none;">
                            <img src="{{ asset('images/logo-horizontal-white.svg') }}?v=10" alt="{{ config('app.name') }}" style="width: 230px !important; max-width: 230px !important; height: auto !important; margin-bottom: 16px;" loading="lazy">
                        </div>
                        <p>{{ __('Votre plateforme d\'information dédiée à l\'intelligence artificielle, aux technologies innovantes et à la transformation numérique. Profitez de nos outils gratuits et recevez des analyses, actualités et ressources exclusives.') }}</p>
                    </div>
                </div>
                <div class="col col-lg-3 col-md-6 col-sm-12 col-12">
                    <div class="widget link-widget">
                        <div class="widget-title"><h3>{{ __('Ressources') }}</h3></div>
                        <ul>
                            @if(Route::has('resources.index'))
                                <li><a href="{{ route('resources.index') }}"><strong>{{ __('Toutes les ressources') }}</strong></a></li>
                            @endif
                            @if(Route::has('blog.index'))
                                <li><a href="{{ route('blog.index') }}">{{ __('Blog') }}</a></li>
                            @endif
                            @if(Route::has('dictionary.index'))
                                <li><a href="{{ route('dictionary.index') }}">{{ __('Glossaire Techno') }}</a></li>
                            @endif
                            @if(Route::has('directory.index'))
                                <li><a href="{{ route('directory.index') }}">{{ __('Répertoire techno') }}</a></li>
                            @endif
                            @if(Route::has('pillar.ia-pme'))
                                <li><a href="{{ route('pillar.ia-pme') }}">{{ __('IA pour les PME québécoises') }}</a></li>
                            @endif
                            @if(Route::has('pillar.ia-education'))
                                <li><a href="{{ route('pillar.ia-education') }}">{{ __('IA en éducation (Québec)') }}</a></li>
                            @endif
                            @if(Route::has('pillar.ia-dev'))
                                <li><a href="{{ route('pillar.ia-dev') }}">{{ __('IA pour développeurs (Québec)') }}</a></li>
                            @endif
                            @if(Route::has('pillar.veille-ia'))
                                <li><a href="{{ route('pillar.veille-ia') }}">{{ __('Faire sa veille IA (Québec)') }}</a></li>
                            @endif
                            @if(Route::has('pillar.ia-generative'))
                                <li><a href="{{ route('pillar.ia-generative') }}">{{ __('IA générative') }}</a></li>
                            @endif
                            @if(Route::has('news.index'))
                                <li><a href="{{ route('news.index') }}">{{ __('Actualités') }}</a></li>
                            @endif
                            @if(Route::has('acronyms.index'))
                                <li><a href="{{ route('acronyms.index') }}">{{ __('Acronymes éducation') }}</a></li>
                            @endif
                            @if(Route::has('tools.index'))
                                <li><a href="{{ route('tools.index') }}">{{ __('Outils gratuits') }}</a></li>
                            @endif
                            @if(Route::has('shop.index') && ! config('shop.maintenance', false))
                                <li><a href="{{ route('shop.index') }}">{{ __('Boutique') }}</a></li>
                            @endif
                            @if(Route::has('shorturl.create'))
                                <li><a href="{{ route('shorturl.create') }}">{{ __('Raccourcir un lien') }}</a></li>
                            @endif
                            @if(Route::has('rss.concentres'))
                                <li><a href="{{ route('rss.concentres') }}" rel="alternate" title="{{ __('Flux RSS Concentré IA hebdo') }}">📡 {{ __('RSS — Concentré IA') }}</a></li>
                            @endif
                            @if(Route::has('rss.annuaire'))
                                <li><a href="{{ route('rss.annuaire') }}" rel="alternate" title="{{ __('Flux RSS nouveaux outils du répertoire') }}">📡 {{ __('RSS — Nouveaux outils') }}</a></li>
                            @endif
                            @if(Route::has('api.docs'))
                                <li><a href="{{ route('api.docs') }}" title="{{ __('API JSON publique') }}">🔌 {{ __('API publique') }}</a></li>
                            @endif
                            @if(Route::has('stats.public'))
                                <li><a href="{{ route('stats.public') }}" title="{{ __('Statistiques publiques temps réel') }}">📊 {{ __('Statistiques') }}</a></li>
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="col col-lg-3 col-md-6 col-sm-12 col-12">
                    <div class="widget link-widget">
                        <div class="widget-title"><h3>{{ __('À propos') }}</h3></div>
                        <ul>
                            @if(Route::has('page.show'))
                                <li><a href="{{ route('page.show', 'a-propos') }}">{{ __('À propos') }}</a></li>
                            @endif
                            @if(Route::has('faq.index'))
                                <li><a href="{{ route('faq.index') }}">{{ __('FAQ') }}</a></li>
                            @endif
                            @if(Route::has('methodologie'))
                                <li><a href="{{ route('methodologie') }}">{{ __('Méthodologie') }}</a></li>
                            @endif
                            <li><a href="{{ route('contact') }}">{{ __('Contact') }}</a></li>
                            @if(Route::has('statut.index'))
                                <li><a href="{{ route('statut.index') }}">{{ __('Statut des services') }}</a></li>
                            @endif
                            @if(Route::has('legal.privacy'))
                                <li><a href="{{ route('legal.privacy') }}">{{ __('Confidentialité') }}</a></li>
                            @endif
                            @if(Route::has('legal.terms'))
                                <li><a href="{{ route('legal.terms') }}">{{ __('Conditions d\'utilisation') }}</a></li>
                            @endif
                            @if(Route::has('legal.sales'))
                                <li><a href="{{ route('legal.sales') }}">{{ __('Conditions de vente') }}</a></li>
                            @endif
                            @if(Route::has('legal.cookies'))
                                <li><a href="{{ route('legal.cookies') }}">{{ __('Cookies') }}</a></li>
                            @endif
                            @if(Route::has('legal.rights'))
                                <li><a href="{{ route('legal.rights') }}">{{ __('Exercer mes droits') }}</a></li>
                            @endif
                            @if(Route::has('directory.takedown.create'))
                                <li><a href="{{ route('directory.takedown.create') }}">{{ __('Demande de retrait') }}</a></li>
                            @endif
                            <li><a href="#" onclick="event.preventDefault(); var fab=document.getElementById('cc-fab'); if(fab) fab.click();" style="cursor: pointer;">{{ __('Gérer les témoins') }}</a></li>
                            @if(Route::has('sitemap.html'))
                                <li><a href="{{ route('sitemap.html') }}">{{ __('Plan du site') }}</a></li>
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="col col-lg-3 col-md-6 col-sm-12 col-12">
                    <div class="widget link-widget">
                        <div class="widget-title"><h3>{{ __('Communauté') }}</h3></div>
                        <ul>
                            @if(Route::has('directory.leaderboard') && config('directory.leaderboard.enabled', false))
                                <li><a href="{{ route('directory.leaderboard') }}">🏆 {{ __('Classement') }}</a></li>
                            @endif
                            {{-- Lien Propositions retiré du footer (décision utilisateur 2026-03-28) --}}
                            <li><a href="{{ lv_social('facebook') }}" target="_blank" rel="noopener"><i><img src="{{ fronttheme_asset('images/ft-icon/1.png') }}" alt="Facebook" loading="lazy"></i> Facebook</a></li>
                            <li><a href="{{ lv_social('messenger') }}" target="_blank" rel="noopener"><i><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" width="20" height="20" aria-hidden="true" focusable="false"><defs><linearGradient id="lvMsgrG" x1="9" y1="32" x2="27" y2="2" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#0099FF"/><stop offset="1" stop-color="#A033FF"/></linearGradient></defs><path fill="url(#lvMsgrG)" d="M18 0C8.06 0 0 7.46 0 16.66c0 5.24 2.61 9.91 6.69 12.96V36l6.13-3.36c1.63.45 3.36.69 5.18.69 9.94 0 18-7.46 18-16.67S27.94 0 18 0z"/><path fill="#fff" d="M19.79 22.43l-4.59-4.91-8.95 4.91L15.06 12.7l4.7 4.91 8.84-4.91-8.81 9.73z"/></svg></i> Messenger</a></li>
                            <li><a href="{{ lv_social('linkedin') }}" target="_blank" rel="noopener"><i><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="#0A66C2" aria-hidden="true" focusable="false"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.34V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.72V1.72C24 .77 23.2 0 22.22 0z"/></svg></i> LinkedIn</a></li>
                            @guest
                                <li><a href="{{ route('login') }}" target="_blank" rel="noopener">🔑 {{ __('Se connecter') }}</a></li>
                            @endguest
                        </ul>
                        @auth
                            @can('view_admin_panel')
                            <li style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.15);">
                                <a href="{{ url('/admin') }}" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:6px;">
                                    <span style="background:#b45309;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:700;">ADMIN</span>
                                    {{ __('Administration') }}
                                </a>
                            </li>
                            <li style="margin-top:6px;"><a href="{{ route('admin.directory.moderation') }}" target="_blank" rel="noopener">📋 {{ __('Modération') }}</a></li>
                            @endcan
                        @endauth
                    </div>
                </div>
            </div>
        </div> <!-- end container -->
    </div>
    <div class="wpo-lower-footer">
        <div class="container">
            <div class="row">
                <div class="col col-xs-12">
                    <p class="copyright"> Copyright &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('Tous droits réservés.') }}
                        <span style="font-size:10px;color:rgba(255,255,255,0.35);margin-left:8px;font-variant-numeric:tabular-nums;" title="{{ __('Version applicative') }}">{{ function_exists('lv_version') ? lv_version(false) : 'v?' }}</span>
                    </p>
                    <p style="font-size:12px;color:rgba(255,255,255,0.55);margin-top:6px;">{{ __('Conçu et hébergé par') }} <a href="https://memora.solutions" target="_blank" rel="nofollow noopener noreferrer" style="color:rgba(255,255,255,0.85);text-decoration:underline;">MEMORA solutions</a> · {{ __('Entreprise canadienne') }} 🍁</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.25); margin-top: 6px;">
                        {{ __('Certains liens sont des liens d\'affiliation. Nous pouvons recevoir une commission sans frais pour vous.') }}
                        @if(Route::has('directory.affiliation.policy'))
                            <a href="{{ route('directory.affiliation.policy') }}" style="color: rgba(255,255,255,0.85); text-decoration: underline;">{{ __('En savoir plus') }}</a>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>
@push('styles')
<style>
.wpo-site-footer .wpo-upper-footer .widget ul li a { color: rgba(255,255,255,0.85) !important; transition: color 0.2s, text-decoration 0.2s; }
.wpo-site-footer .wpo-upper-footer .widget ul li a:hover { color: #fff !important; text-decoration: underline !important; }
.wpo-site-footer .wpo-upper-footer .about-widget p { color: rgba(255,255,255,0.75) !important; }
.wpo-site-footer .wpo-upper-footer .about-widget .logo.widget-title img { width: 240px !important; max-width: 100% !important; height: auto !important; }
</style>
@endpush
<!-- end of wpo-site-footer-section -->

<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!-- start of wpo-site-footer-section -->
<footer class="wpo-site-footer">
    <div class="wpo-upper-footer">
        <div class="container">
            <div class="row">
                <div class="col col-lg-4 col-md-6 col-sm-12 col-12">
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
                            <li><a href="{{ route('blog.index') }}">{{ __('Blog') }}</a></li>
                            @if(Route::has('dictionary.index'))
                                <li><a href="{{ route('dictionary.index') }}">{{ __('Glossaire IA') }}</a></li>
                            @endif
                            @if(Route::has('directory.index'))
                                <li><a href="{{ route('directory.index') }}">{{ __('Répertoire techno') }}</a></li>
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
                            @if(Route::has('shorturl.create'))
                                <li><a href="{{ route('shorturl.create') }}">{{ __('Raccourcir un lien') }}</a></li>
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
                            <li><a href="{{ route('contact') }}">{{ __('Contact') }}</a></li>
                            @if(Route::has('legal.privacy'))
                                <li><a href="{{ route('legal.privacy') }}">{{ __('Confidentialité') }}</a></li>
                            @endif
                            @if(Route::has('legal.terms'))
                                <li><a href="{{ route('legal.terms') }}">{{ __('Conditions d\'utilisation') }}</a></li>
                            @endif
                            @if(Route::has('legal.cookies'))
                                <li><a href="{{ route('legal.cookies') }}">{{ __('Cookies') }}</a></li>
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="col col-lg-2 col-md-6 col-sm-12 col-12">
                    <div class="widget social-widget">
                        <div class="widget-title"><h3>{{ __('Communauté') }}</h3></div>
                        <ul>
                            @if(Route::has('directory.leaderboard'))
                                <li><a href="{{ route('directory.leaderboard') }}">🏆 {{ __('Classement') }}</a></li>
                            @endif
                            {{-- Lien Propositions retiré du footer (décision utilisateur 2026-03-28) --}}
                            <li><a href="https://www.facebook.com/LaVeilleDeStef" target="_blank" rel="noopener"><i><img src="{{ fronttheme_asset('images/ft-icon/1.png') }}" alt="Facebook" loading="lazy"></i> Facebook</a></li>
                            <li><a href="https://m.me/LaVeilleDeStef" target="_blank" rel="noopener"><i><img src="{{ fronttheme_asset('images/ft-icon/2.png') }}" alt="Messenger" loading="lazy"></i> Messenger</a></li>
                            @guest
                                <li><a href="{{ route('login') }}" @click.prevent="$dispatch('open-auth-modal', { message: '' })" style="cursor: pointer;">🔑 {{ __('Se connecter') }}</a></li>
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
                    <p class="copyright"> Copyright &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('Tous droits réservés.') }}</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.25); margin-top: 6px;">{{ __('Certains liens sont des liens d\'affiliation. Nous pouvons recevoir une commission sans frais pour vous.') }}</p>
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

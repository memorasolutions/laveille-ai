<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<!-- start of wpo-site-footer-section -->
<footer class="wpo-site-footer">
    <div class="wpo-upper-footer">
        <div class="container">
            <div class="row">
                <div class="col col-lg-4 col-md-6 col-sm-12 col-12">
                    <div class="widget about-widget">
                        <div class="logo widget-title" style="max-width: none;">
                            <img src="{{ asset('images/logo-horizontal-white.svg') }}?v=10" alt="{{ config('app.name') }}" style="width: 230px !important; max-width: 230px !important; height: auto !important; margin-bottom: 16px;">
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
                            @if(Route::has('acronyms.index'))
                                <li><a href="{{ route('acronyms.index') }}">{{ __('Acronymes éducation') }}</a></li>
                            @endif
                            @if(Route::has('tools.index'))
                                <li><a href="{{ route('tools.index') }}">{{ __('Outils gratuits') }}</a></li>
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
                            @if(Route::has('directory.roadmap'))
                                <li><a href="{{ route('directory.roadmap') }}">💡 {{ __('Idées et votes') }}</a></li>
                            @endif
                            <li><a href="https://www.facebook.com/LaVeilleDeStef" target="_blank" rel="noopener"><i><img src="{{ fronttheme_asset('images/ft-icon/1.png') }}" alt="Facebook"></i> Facebook</a></li>
                            <li><a href="https://m.me/LaVeilleDeStef" target="_blank" rel="noopener"><i><img src="{{ fronttheme_asset('images/ft-icon/2.png') }}" alt="Messenger"></i> Messenger</a></li>
                        </ul>
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
                </div>
            </div>
        </div>
    </div>
</footer>
@push('styles')
<style>
.wpo-site-footer .wpo-upper-footer .widget ul li a { color: rgba(255,255,255,0.85) !important; }
.wpo-site-footer .wpo-upper-footer .widget ul li a:hover { color: #fff !important; }
.wpo-site-footer .wpo-upper-footer .about-widget p { color: rgba(255,255,255,0.75) !important; }
.wpo-site-footer .wpo-upper-footer .about-widget .logo.widget-title img { width: 240px !important; max-width: 100% !important; height: auto !important; }
</style>
@endpush
<!-- end of wpo-site-footer-section -->

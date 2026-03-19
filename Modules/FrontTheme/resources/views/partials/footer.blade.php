<!-- start of wpo-site-footer-section -->
<footer class="wpo-site-footer">
    <div class="wpo-upper-footer">
        <div class="container">
            <div class="row">
                <div class="col col-lg-3 col-md-6 col-sm-12 col-12">
                    <div class="widget about-widget">
                        <div class="logo widget-title">
                            <img src="{{ asset('images/logo.webp') }}" alt="{{ config('app.name') }}">
                        </div>
                        <p>{{ __('Votre plateforme d\'information dédiée à l\'intelligence artificielle, aux technologies innovantes et à la transformation numérique. Profitez de nos outils gratuits et recevez des analyses, actualités et ressources exclusives.') }}</p>
                    </div>
                </div>
                <div class="col col-lg-3 col-md-6 col-sm-12 col-12">
                    <div class="widget link-widget">
                        <div class="widget-title"><h3>{{ __('Liens importants') }}</h3></div>
                        <ul>
                            <li><a href="{{ route('home') }}">{{ __('Accueil') }}</a></li>
                            <li><a href="{{ route('blog.index') }}">{{ __('Blog') }}</a></li>
                            <li><a href="{{ route('contact') }}">{{ __('Contact') }}</a></li>
                            @if(Route::has('legal.privacy'))
                                <li><a href="{{ route('legal.privacy') }}">{{ __('Politique de confidentialité') }}</a></li>
                            @endif
                            @if(Route::has('legal.cookies'))
                                <li><a href="{{ route('legal.cookies') }}">{{ __('Politique de cookies') }}</a></li>
                            @endif
                        </ul>
                    </div>
                </div>
                <div class="col col-lg-4 col-md-6 col-sm-12 col-12">
                    <div class="widget tag-widget">
                        <div class="widget-title"><h3>{{ __('Catégories') }}</h3></div>
                        <ul>
                            @isset($categories)
                                @foreach($categories as $category)
                                    <li><a href="{{ route('blog.category', $category->slug) }}">{{ $category->name }}</a></li>
                                @endforeach
                            @endisset
                        </ul>
                    </div>
                </div>
                <div class="col col-lg-2 col-md-6 col-sm-12 col-12">
                    <div class="widget social-widget">
                        <div class="widget-title"><h3>{{ __('Réseaux sociaux') }}</h3></div>
                        <ul>
                            <li><a href="https://www.facebook.com/LaVeilleDeStef" target="_blank" rel="noopener"><i><img src="{{ fronttheme_asset('images/ft-icon/1.png') }}" alt=""></i> Facebook</a></li>
                            <li><a href="https://m.me/LaVeilleDeStef" target="_blank" rel="noopener"><i><img src="{{ fronttheme_asset('images/ft-icon/2.png') }}" alt=""></i> Messenger</a></li>
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
<!-- end of wpo-site-footer-section -->

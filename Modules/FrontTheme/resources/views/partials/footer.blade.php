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
                        <p>{{ __('Votre source d\'information sur les technologies et l\'intelligence artificielle.') }}</p>
                    </div>
                </div>
                <div class="col col-lg-3 col-md-6 col-sm-12 col-12">
                    <div class="widget link-widget">
                        <div class="widget-title"><h3>{{ __('Liens importants') }}</h3></div>
                        <ul>
                            <li><a href="{{ route('home') }}">{{ __('Accueil') }}</a></li>
                            <li><a href="{{ route('blog.index') }}">{{ __('Blog') }}</a></li>
                            <li><a href="{{ route('contact') }}">{{ __('Contact') }}</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col col-lg-4 col-md-6 col-sm-12 col-12">
                    <div class="widget tag-widget">
                        <div class="widget-title"><h3>{{ __('Tags populaires') }}</h3></div>
                        <ul>
                            @isset($tags)
                                @forelse($tags as $tag)
                                    <li><a href="{{ route('blog.index', ['tag' => $tag->slug ?? $tag]) }}">{{ $tag->name ?? $tag }}</a></li>
                                @empty
                                    <li>{{ __('Aucun tag disponible') }}</li>
                                @endforelse
                            @endisset
                        </ul>
                    </div>
                </div>
                <div class="col col-lg-2 col-md-6 col-sm-12 col-12">
                    <div class="widget social-widget">
                        <div class="widget-title"><h3>{{ __('Réseaux sociaux') }}</h3></div>
                        <ul>
                            <li><a href="#"><i><img src="{{ fronttheme_asset('images/ft-icon/1.png') }}" alt=""></i> Facebook</a></li>
                            <li><a href="#"><i><img src="{{ fronttheme_asset('images/ft-icon/2.png') }}" alt=""></i> Twitter</a></li>
                            <li><a href="#"><i><img src="{{ fronttheme_asset('images/ft-icon/3.png') }}" alt=""></i> Instagram</a></li>
                            <li><a href="#"><i><img src="{{ fronttheme_asset('images/ft-icon/4.png') }}" alt=""></i> Youtube</a></li>
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

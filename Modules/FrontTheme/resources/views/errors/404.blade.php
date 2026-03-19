@extends(fronttheme_layout())

@section('title', __('Page non trouvée'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Erreur 404')])
@endsection

@section('content')
    <!-- start error-404-section -->
    <section class="error-404-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-xs-12">
                    <div class="content clearfix">
                        <div class="error">
                            <h2>404</h2>
                        </div>
                        <div class="error-message">
                            <h3>{{ __('Oups ! Page non trouvée !') }}</h3>
                            <p>{{ __('La page que vous recherchez n\'existe pas ou a été déplacée.') }}</p>
                            <a href="{{ route('home') }}" class="theme-btn">{{ __('Retour à l\'accueil') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- end error-404-section -->
@endsection

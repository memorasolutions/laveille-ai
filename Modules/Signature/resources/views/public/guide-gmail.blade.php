<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Installer sa signature dans Gmail - ' . config('app.name'))
@section('meta_description', "Guide illustré pour installer sa signature HTML dans Gmail (web) en 5 minutes.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Guide Gmail'), 'breadcrumbItems' => [__('Outils'), __('Signature de courriel'), __('Guide Gmail')]])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-12">
                <div class="card shadow-sm p-4 p-md-5" style="border-radius: var(--r-base);">
                    <h1>📧 {{ __('Installer sa signature dans Gmail (web)') }}</h1>
                    <p class="text-muted small">{{ __('Vérifié le') }} 25 {{ __('septembre') }} 2026 ·
                        <a href="https://support.google.com/mail/answer/8395" target="_blank" rel="noopener">{{ __("Aide officielle Gmail") }}</a>
                    </p>

                    <ol class="mt-4">
                        <li class="mb-3">{{ __('Copiez votre signature avec le bouton « Copier » de l\'éditeur.') }}</li>
                        <li class="mb-3">{{ __('Dans Gmail, cliquez sur l\'icône ⚙️ Paramètres, puis « Afficher tous les paramètres ».') }}</li>
                        <li class="mb-3">{{ __('Dans l\'onglet « Général », descendez jusqu\'à « Signature », puis « Créer nouvelle ».') }}</li>
                        <li class="mb-3">{{ __('Cliquez dans la zone de texte de la signature, puis collez (Ctrl+V ou Cmd+V).') }}</li>
                        <li class="mb-3">{{ __('Faites défiler jusqu\'en bas et cliquez sur « Enregistrer les modifications ».') }}</li>
                    </ol>

                    <p>
                        <a href="{{ route('signature.assistant') }}" class="btn btn-primary">{{ __('Retourner à l\'éditeur') }}</a>
                        <a href="{{ route('signature.guide.outlook-web') }}">{{ __('Voir le guide Outlook web') }}</a>
                    </p>

                    <p class="small text-muted mt-4">{{ __('Votre client n\'est pas listé?') }} <a href="mailto:info@memora.ca">{{ __('Dites-nous lequel') }}</a>.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

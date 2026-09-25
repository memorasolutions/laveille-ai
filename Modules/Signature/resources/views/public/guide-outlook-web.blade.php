<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Installer sa signature dans Outlook web - ' . config('app.name'))
@section('meta_description', "Guide illustré pour installer sa signature HTML dans Outlook sur le web en 5 minutes.")

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Guide Outlook web'), 'breadcrumbItems' => [__('Outils'), __('Signature de courriel'), __('Guide Outlook web')]])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-12">
                <div class="card shadow-sm p-4 p-md-5" style="border-radius: var(--r-base);">
                    <h1>📧 {{ __('Installer sa signature dans Outlook web') }}</h1>
                    <p class="text-muted small">{{ __('Vérifié le') }} 25 {{ __('septembre') }} 2026 ·
                        <a href="https://support.microsoft.com/fr-fr/office/cr%C3%A9er-et-ajouter-une-signature-de-messagerie-dans-outlook-sur-le-web-91123694-38a9-4d0e-b7cf-670b3e4c7e34" target="_blank" rel="noopener">{{ __("Aide officielle Outlook") }}</a>
                    </p>

                    <ol class="mt-4">
                        <li class="mb-3">{{ __('Copiez votre signature avec le bouton « Copier » de l\'éditeur.') }}</li>
                        <li class="mb-3">{{ __('Dans Outlook web (outlook.com ou Microsoft 365), cliquez sur ⚙️ Paramètres, puis « Afficher tous les paramètres Outlook ».') }}</li>
                        <li class="mb-3">{{ __('Sélectionnez « Courrier » puis « Rédaction et réponse ».') }}</li>
                        <li class="mb-3">{{ __('Cliquez dans la zone de signature, puis collez (Ctrl+V ou Cmd+V).') }}</li>
                        <li class="mb-3">{{ __('Activez « Ajouter automatiquement ma signature aux nouveaux messages » si désiré, puis cliquez sur « Enregistrer ».') }}</li>
                    </ol>

                    <p>
                        <a href="{{ route('signature.assistant') }}" class="btn btn-primary">{{ __('Retourner à l\'éditeur') }}</a>
                        <a href="{{ route('signature.guide.gmail') }}">{{ __('Voir le guide Gmail') }}</a>
                    </p>

                    <p class="small text-muted mt-4">{{ __('Votre client n\'est pas listé?') }} <a href="mailto:info@memora.ca">{{ __('Dites-nous lequel') }}</a>.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

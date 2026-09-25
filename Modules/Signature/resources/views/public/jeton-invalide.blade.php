<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Lien invalide - ' . config('app.name'))
@section('page_noindex', true)

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-12 text-center">
                <div class="card shadow-sm p-4 p-md-5" style="border-radius: var(--r-base);">
                    <h1>🔒 {{ __('Lien invalide ou expiré') }}</h1>
                    <p class="text-muted">{{ __("Ce lien de gestion de signature n'est pas reconnu. Il a peut-être été régénéré, ou l'adresse a été mal copiée.") }}</p>
                    <a href="{{ route('signature.assistant') }}" class="btn btn-primary">{{ __('Créer une nouvelle signature') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

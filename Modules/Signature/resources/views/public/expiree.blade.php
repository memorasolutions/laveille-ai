<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Signature supprimée - ' . config('app.name'))
@section('page_noindex', true)

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-12 text-center">
                <div class="card shadow-sm p-4 p-md-5" style="border-radius: var(--r-base);">
                    <h1>🗑️ {{ __('Cette signature a été supprimée') }}</h1>
                    <p class="text-muted">{{ __("Conformément à notre politique de conservation, cette signature a été supprimée automatiquement après 6 mois d'inactivité.") }}</p>
                    <a href="{{ route('signature.assistant') }}" class="btn btn-primary">{{ __('Créer une nouvelle signature') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

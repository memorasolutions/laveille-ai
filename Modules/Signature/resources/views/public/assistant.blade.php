<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Générateur de signature de courriel gratuit - ' . config('app.name'))
@section('meta_description', 'Créez une signature de courriel professionnelle gratuite, sans compte obligatoire' . "\u{00A0}" . ': 8 gabarits, liens sociaux, logo, portrait et bannière, copie en un clic.')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Signature de courriel'), 'breadcrumbItems' => [__('Outils'), __('Signature de courriel')]])
@endsection

@push('head')
<link rel="stylesheet" href="{{ asset('assets/tools/signature-courriel/signature.css') }}?v={{ config('version.semver') }}">
<link rel="canonical" href="{{ url('/outils/signature-courriel') }}">
@php
    $sigJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Générateur de signature de courriel',
        'description' => "Outil gratuit qui produit une signature HTML compatible avec les principaux clients courriel, sans compte obligatoire.",
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'url' => url('/outils/signature-courriel'),
        'isAccessibleForFree' => true,
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'CAD'],
    ];
@endphp
<script type="application/ld+json">{!! json_encode(array_filter($sigJsonLd), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-12">
                <div class="card shadow-sm" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5"
                         x-data="signatureAssistant({
                            template: '{{ $initialTemplate }}',
                            initialTemplate: '{{ $initialTemplate }}',
                            templates: @js($templates),
                            fontFamilies: @js($fontFamilies),
                            socialPlatforms: @js($socialPlatforms),
                            portraitShapes: @js($portraitShapes),
                            fontScales: @js($fontScales),
                            draftStoreUrl: '{{ $formAction }}',
                            imagesUploadUrl: '{{ route('signature.images.store') }}'
                         })">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">✍️ {{ __('Générateur de signature de courriel') }}</h1>
                                <p class="text-muted mb-0">{{ __('Gratuit, sans compte obligatoire. Choisissez un gabarit, remplissez vos coordonnées, copiez.') }}</p>
                            </div>
                        </div>

                        @include('signature::public.partials.editor', ['showReminderOptIn' => true])
                    </div>
                </div>

                @auth
                <p class="text-center mt-3">
                    <a href="{{ route('signature.user.index') }}">{{ __('Voir mes signatures enregistrées') }}</a>
                </p>
                @endauth
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('assets/tools/signature-courriel/signature-render.js') }}?v={{ config('version.semver') }}"></script>
<script src="{{ asset('assets/tools/signature-courriel/signature-core.js') }}?v={{ config('version.semver') }}"></script>
@endpush

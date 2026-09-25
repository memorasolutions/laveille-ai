<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', 'Gérer ma signature de courriel - ' . config('app.name'))
@section('page_noindex', true)

@push('head')
<meta name="referrer" content="no-referrer">
<link rel="stylesheet" href="{{ asset('assets/tools/signature-courriel/signature.css') }}?v={{ config('version.semver') }}">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Gérer ma signature'), 'breadcrumbItems' => [__('Outils'), __('Signature de courriel'), __('Gérer ma signature')]])
@endsection

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
                            initialContent: @js($initialContent),
                            initialImages: @js($initialImages ?? []),
                            templates: @js($templates),
                            fontFamilies: @js($fontFamilies),
                            socialPlatforms: @js($socialPlatforms),
                            token: {{ $token ? "'".$token."'" : 'null' }},
                            signatureId: {{ $signatureId ?? 'null' }},
                            updateUrl: '{{ $formAction }}',
                            imagesUploadUrl: '{{ route('signature.images.store') }}',
                            manageUrlBase: '{{ url('/outils/signature-courriel/gerer') }}',
                            @if($token)
                            extendUrl: '{{ route('signature.manage.extend', ['token' => $token]) }}',
                            rotateUrl: '{{ route('signature.manage.rotate', ['token' => $token]) }}',
                            attachUrl: '{{ route('signature.manage.attach', ['token' => $token]) }}',
                            @endif
                         })">
                        <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark);">✍️ {{ __('Gérer ma signature') }}</h1>

                        @include('signature::public.partials.editor', ['showReminderOptIn' => false])

                        <hr class="my-4">

                        @if($token)
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-success btn-sm" @click="extendLink()" :disabled="linkActionBusy">🔄 {{ __('Prolonger (annule l\'avertissement)') }}</button>
                            <button type="button" class="btn btn-outline-warning btn-sm" @click="confirmRotate = true">🔑 {{ __('Régénérer mon lien') }}</button>
                            @auth
                            <button type="button" class="btn btn-outline-primary btn-sm" @click="attachAccount()" :disabled="linkActionBusy">👤 {{ __('Rattacher à mon compte') }}</button>
                            @endauth
                            <a href="{{ route('signature.export.outlook', ['token' => $token]) }}" class="btn btn-outline-secondary btn-sm">⬇️ {{ __('Export Outlook classique (.htm)') }}</a>
                        </div>

                        {{-- M2.2 - modale MAISON de confirmation AVANT rotation (jamais confirm() natif) :
                             la rotation tue l'ancien lien immédiatement, sans confirmation ça surprend. --}}
                        <div x-show="confirmRotate" x-cloak
                             style="position:fixed;inset:0;z-index:1055;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5);"
                             role="dialog" aria-modal="true" aria-labelledby="sigRotateModalTitle"
                             x-init="$watch('confirmRotate', (v) => v && $nextTick(() => focusFirstIn($el)))"
                             @keydown.escape.window="confirmRotate = false"
                             @keydown.tab="trapFocusTab($event)">
                            <div style="background:#fff;border-radius:var(--r-base,0.75rem);max-width:480px;width:92%;padding:1.5rem;box-shadow:0 1rem 3rem rgba(0,0,0,.2);" @click.outside="confirmRotate = false">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 id="sigRotateModalTitle" class="mb-0">{{ __('Régénérer ton lien secret?') }}</h5>
                                    <button type="button" class="btn-close" @click="confirmRotate = false" aria-label="{{ __('Fermer') }}"></button>
                                </div>
                                <p>{{ __('Ton lien actuel cessera de fonctionner IMMÉDIATEMENT. Le nouveau lien te sera montré une seule fois - conserve-le.') }}</p>
                                <div class="text-end mt-3 d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-secondary" @click="confirmRotate = false">{{ __('Annuler') }}</button>
                                    <button type="button" class="btn btn-warning" @click="rotateLink()" :disabled="linkActionBusy">{{ __('Régénérer quand même') }}</button>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('assets/tools/signature-courriel/signature-render.js') }}?v={{ config('version.semver') }}"></script>
<script src="{{ asset('assets/tools/signature-courriel/signature-core.js') }}?v={{ config('version.semver') }}"></script>
@endpush

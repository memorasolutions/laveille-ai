<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Feature Flags', 'subtitle' => 'Gestion'])

@push('css')
<style>
    /* Fix modal Bootstrap pour thème Jobick */
    .modal { display: none; position: fixed; top: 0; left: 0; z-index: 1055; width: 100%; height: 100%; overflow-x: hidden; overflow-y: auto; outline: 0; }
    .modal.show { display: block; }
    .modal-backdrop { position: fixed; top: 0; left: 0; z-index: 1050; width: 100vw; height: 100vh; background-color: #000; }
    .modal-backdrop.show { opacity: 0.5; }
    .modal-dialog { position: relative; width: auto; margin: 1.75rem auto; max-width: 800px; }
    .modal-content { position: relative; display: flex; flex-direction: column; width: 100%; background-color: #fff; border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15); }
    .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid #dee2e6; }
    .modal-title { margin-bottom: 0; font-size: 1.1rem; font-weight: 600; }
    .modal-body { position: relative; flex: 1 1 auto; padding: 1.25rem; }
    .modal-footer { display: flex; align-items: center; justify-content: flex-end; padding: 0.75rem 1.25rem; border-top: 1px solid #dee2e6; gap: 0.5rem; }
    .modal-dialog-scrollable { max-height: calc(100% - 3.5rem); }
    .modal-dialog-scrollable .modal-content { max-height: calc(100vh - 3.5rem); overflow: hidden; }
    .modal-dialog-scrollable .modal-body { overflow-y: auto; }
    .btn-close { box-sizing: content-box; width: 1em; height: 1em; padding: 0.25em; background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat; border: 0; border-radius: 0.375rem; opacity: 0.5; cursor: pointer; }
    .btn-close:hover { opacity: 0.75; }
</style>
@endpush

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Feature Flags') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="flag" class="icon-md text-primary"></i>{{ __('Feature Flags') }}</h4>
    <button type="button"
            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2"
            data-bs-toggle="modal"
            data-bs-target="#helpFeatureFlagsModal">
        <i data-lucide="help-circle"></i>
        {{ __('Aide') }}
    </button>
</div>

<div class="card">
    <div class="card-body p-4">
        @livewire('backoffice-feature-flags-table')
    </div>
</div>

{{-- Help Modal Bootstrap --}}
<div class="modal fade" id="helpFeatureFlagsModal" tabindex="-1" aria-labelledby="helpFeatureFlagsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2" id="helpFeatureFlagsModalLabel">
                    <i data-lucide="flag" class="text-primary"></i>
                    {{ __("Qu'est-ce qu'un Feature Flag ?") }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Fermer') }}"></button>
            </div>
            <div class="modal-body">
                {{-- Section 1 --}}
                <div class="mb-4">
                    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
                        <i data-lucide="lightbulb" class="text-warning" style="width:16px;height:16px;"></i>
                        {{ __('En un mot') }}
                    </h6>
                    <p class="text-muted small">
                        Un <strong>Feature Flag</strong> (ou « drapeau de fonctionnalité ») est un interrupteur ON/OFF
                        qui vous permet d'<strong>activer ou désactiver une fonctionnalité</strong> de votre application
                        sans toucher au code et sans redéployer.
                    </p>
                </div>

                {{-- Section 2 --}}
                <div class="p-3 bg-light rounded mb-4">
                    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
                        <i data-lucide="home" class="text-info" style="width:16px;height:16px;"></i>
                        {{ __('Pensez-y comme un interrupteur de lumière') }}
                    </h6>
                    <p class="text-muted small mb-0">
                        Imaginez votre maison : chaque pièce a un interrupteur. Vous pouvez allumer le salon
                        sans toucher à la cuisine. Les Feature Flags fonctionnent pareil : chaque fonctionnalité
                        a son propre interrupteur.
                    </p>
                </div>

                {{-- Section 3 --}}
                <div class="mb-4">
                    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
                        <i data-lucide="clipboard-check" class="text-success" style="width:16px;height:16px;"></i>
                        {{ __('Exemples concrets') }}
                    </h6>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge bg-success mt-1">{{ __('Activé') }}</span>
                            <div>
                                <strong class="small">{{ __('Mode blog activé') }}</strong>
                                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Les visiteurs voient la section blog sur votre site.') }}</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge bg-danger mt-1">{{ __('Désactivé') }}</span>
                            <div>
                                <strong class="small">{{ __('Mode blog désactivé') }}</strong>
                                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __("La section blog disparaît du site. Le contenu reste en base de données, rien n'est perdu.") }}</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge bg-success mt-1">{{ __('Activé') }}</span>
                            <div>
                                <strong class="small">{{ __('Inscription ouverte') }}</strong>
                                <p class="text-muted" style="font-size:11px;margin-bottom:0;">{{ __('Les nouveaux utilisateurs peuvent créer un compte.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 4 --}}
                <div class="mb-4">
                    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
                        <i data-lucide="mouse-pointer" class="text-primary" style="width:16px;height:16px;"></i>
                        {{ __('Comment ça marche ?') }}
                    </h6>
                    <ol class="text-muted small ps-3 mb-0">
                        <li class="mb-1">{{ __('Trouvez le flag que vous voulez modifier dans la liste ci-dessous.') }}</li>
                        <li class="mb-1">{{ __('Cliquez sur le') }} <strong>{{ __('bouton toggle') }}</strong> {{ __('(interrupteur) pour l\'activer ou le désactiver.') }}</li>
                        <li class="mb-1">{{ __('Le changement prend effet') }} <strong>{{ __('immédiatement') }}</strong>, {{ __('sans besoin de redémarrer quoi que ce soit.') }}</li>
                        <li>{{ __('Pour revenir en arrière, cliquez simplement à nouveau sur le toggle.') }}</li>
                    </ol>
                </div>

                {{-- Section 5 --}}
                <div class="p-3 rounded" style="background-color: rgba(123,44,245,0.05);">
                    <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
                        <i data-lucide="shield" class="text-primary" style="width:16px;height:16px;"></i>
                        {{ __('Pourquoi c\'est utile ?') }}
                    </h6>
                    <ul class="text-muted small ps-3 mb-0">
                        <li class="mb-1"><strong>{{ __('Tester') }}</strong> {{ __('une nouvelle fonctionnalité sans risque.') }}</li>
                        <li class="mb-1"><strong>{{ __('Désactiver') }}</strong> {{ __('rapidement quelque chose qui pose problème.') }}</li>
                        <li class="mb-1"><strong>{{ __('Lancer') }}</strong> {{ __('progressivement une fonctionnalité.') }}</li>
                        <li><strong>{{ __('Personnaliser') }}</strong> {{ __("l'expérience selon vos besoins du moment.") }}</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ __("J'ai compris") }}</button>
            </div>
        </div>
    </div>
</div>

@endsection

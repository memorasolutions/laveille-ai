<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Clés d\'accès (Passkeys)'))

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('user.profile') }}">{{ __('Profil') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Clés d\'accès') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i data-lucide="fingerprint" class="icon-sm"></i>
        <h5 class="mb-0">{{ __('Clés d\'accès (Passkeys)') }}</h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-4">{{ __('Les clés d\'accès permettent une connexion sécurisée sans mot de passe, via empreinte digitale, Face ID ou code PIN.') }}</p>

        <div class="alert alert-info d-flex gap-2 mb-4">
            <i data-lucide="info" class="icon-sm mt-1 flex-shrink-0"></i>
            <div>
                <strong>{{ __('Avantages') }}</strong>
                <ul class="mb-0 mt-1 ps-3">
                    <li>{{ __('Sécurité renforcée - résistant au phishing') }}</li>
                    <li>{{ __('Connexion rapide en un clic') }}</li>
                    <li>{{ __('Synchronisé entre vos appareils (iCloud, Google)') }}</li>
                </ul>
            </div>
        </div>

        <livewire:passkeys />
    </div>
</div>
@endsection

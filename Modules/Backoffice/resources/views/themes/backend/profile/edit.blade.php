@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Mon profil', 'subtitle' => 'Modifier'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Mon profil') }}</li>
    </ol>
</nav>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
        <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
        {{ session('success') }}
    </div>
@endif

<div class="row g-4">

    {{-- Informations du profil --}}
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header border-bottom py-3 px-4 d-flex align-items-center gap-2">
                <i data-lucide="user" class="text-primary icon-md"></i>
                <h4 class="fw-bold mb-0">{{ __('Informations personnelles') }}</h4>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="form-label fw-medium">
                            Nom <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">
                            Courriel <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Mot de passe --}}
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header border-bottom py-3 px-4 d-flex align-items-center gap-2">
                <i data-lucide="lock" class="text-warning icon-md"></i>
                <h4 class="fw-bold mb-0">{{ __('Changer le mot de passe') }}</h4>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="form-label fw-medium">
                            Mot de passe actuel <span class="text-danger">*</span>
                        </label>
                        <input type="password" name="current_password"
                               class="form-control @error('current_password') is-invalid @enderror"
                               required>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">
                            Nouveau mot de passe <span class="text-danger">*</span>
                        </label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">
                            Confirmer le nouveau mot de passe <span class="text-danger">*</span>
                        </label>
                        <input type="password" name="password_confirmation"
                               class="form-control"
                               required>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Double authentification (2FA) --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom py-3 px-4 d-flex align-items-center gap-2">
                <i data-lucide="shield" class="text-success icon-md"></i>
                <h4 class="fw-bold mb-0">{{ __('Double authentification (2FA)') }}</h4>
            </div>
            <div class="card-body p-4">

                @if(session('status') === '2fa-setup' || session()->has('2fa.setup'))
                    {{-- Étape de configuration : QR code + codes de récupération --}}
                    <div class="alert alert-info d-flex align-items-start gap-2 mb-4">
                        <i data-lucide="info" style="width:18px;height:18px;flex-shrink:0;margin-top:2px;"></i>
                        <div>
                            <strong>Configurez votre application d'authentification</strong><br>
                            Scannez ce QR code avec Google Authenticator, Authy ou une application TOTP compatible.
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-auto">
                            <img src="{{ session('2fa.setup')['qr_url'] }}" alt="QR Code 2FA"
                                 class="border rounded p-2" style="width:200px;height:200px;">
                        </div>
                        <div class="col">
                            <div class="alert alert-warning d-flex align-items-start gap-2 mb-4">
                                <i data-lucide="alert-triangle" style="width:18px;height:18px;flex-shrink:0;margin-top:2px;"></i>
                                <div>
                                    <strong>Codes de récupération</strong> - Conservez-les en lieu sûr.<br>
                                    <small>Chaque code ne peut être utilisé qu'une seule fois.</small>
                                </div>
                            </div>
                            <div class="row g-2 mb-4">
                                @foreach(session('2fa.setup')['recovery_codes'] as $code)
                                    <div class="col-6">
                                        <code class="d-block bg-light rounded px-3 py-2 small font-monospace">{{ $code }}</code>
                                    </div>
                                @endforeach
                            </div>

                            <form action="{{ route('admin.profile.2fa.confirm') }}" method="POST">
                                @csrf
                                <div class="mb-4">
                                    <label class="form-label fw-medium">Code OTP de confirmation</label>
                                    <input type="text" name="code" maxlength="6" inputmode="numeric"
                                           placeholder="000000"
                                           class="form-control font-monospace text-center @error('code') is-invalid @enderror"
                                           style="max-width:200px;font-size:1.25rem;letter-spacing:0.3em;" required>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-success d-inline-flex align-items-center gap-2">
                                    <i data-lucide="shield-check" style="width:16px;height:16px;"></i>
                                    Confirmer et activer le 2FA
                                </button>
                            </form>
                        </div>
                    </div>

                @elseif($user->hasEnabledTwoFactor())
                    {{-- 2FA actif --}}
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 d-inline-flex align-items-center gap-1">
                            <i data-lucide="check-circle" style="width:14px;height:14px;"></i>
                            Activé
                        </span>
                        <span class="text-muted small">Votre compte est protégé par la double authentification.</span>
                    </div>
                    <form action="{{ route('admin.profile.2fa.disable') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="mb-4">
                            <label class="form-label fw-medium">Confirmez votre mot de passe pour désactiver</label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   style="max-width:320px;" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-danger d-inline-flex align-items-center gap-2">
                            <i data-lucide="x-circle" style="width:16px;height:16px;"></i>
                            Désactiver le 2FA
                        </button>
                    </form>

                @else
                    {{-- 2FA inactif --}}
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 d-inline-flex align-items-center gap-1">
                            <i data-lucide="x" style="width:14px;height:14px;"></i>
                            Désactivé
                        </span>
                        <span class="text-muted small">Ajoutez une couche de sécurité supplémentaire à votre compte.</span>
                    </div>
                    <form action="{{ route('admin.profile.2fa.enable') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <i data-lucide="key" style="width:16px;height:16px;"></i>
                            Activer la double authentification
                        </button>
                    </form>
                @endif

            </div>
        </div>
    </div>

</div>

@endsection

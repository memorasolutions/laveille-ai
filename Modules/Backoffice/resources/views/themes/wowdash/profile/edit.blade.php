@extends('backoffice::layouts.admin', ['title' => 'Mon profil', 'subtitle' => 'Modifier'])

@section('content')

@if (session('success'))
    <div class="alert alert-success alert-left border-success-main">
        <i class="ri-checkbox-circle-line text-success-main me-2"></i>
        {{ session('success') }}
    </div>
@endif

<div class="row gy-4">

    {{-- Informations du profil --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Informations personnelles</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nom <span class="text-danger-main">*</span></label>
                        <input type="text" name="name" class="form-control radius-8 @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Courriel <span class="text-danger-main">*</span></label>
                        <input type="email" name="email" class="form-control radius-8 @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mt-24">
                        <button type="submit" class="btn btn-primary-600">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Mot de passe --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Changer le mot de passe</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Mot de passe actuel <span class="text-danger-main">*</span></label>
                        <input type="password" name="current_password" class="form-control radius-8 @error('current_password') is-invalid @enderror" required>
                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nouveau mot de passe <span class="text-danger-main">*</span></label>
                        <input type="password" name="password" class="form-control radius-8 @error('password') is-invalid @enderror" required>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-20">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Confirmer le nouveau mot de passe <span class="text-danger-main">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control radius-8" required>
                    </div>

                    <div class="mt-24">
                        <button type="submit" class="btn btn-primary-600">Changer le mot de passe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Double authentification (2FA) --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Double authentification (2FA)</h6>
            </div>
            <div class="card-body">

                @if (session('status') === '2fa-setup' || session()->has('2fa.setup'))
                    {{-- Étape de configuration : QR code + codes de récupération --}}
                    <div class="alert alert-info alert-left border-info-main mb-20">
                        <strong>Configurez votre application d'authentification</strong><br>
                        Scannez ce QR code avec Google Authenticator, Authy ou une application TOTP compatible.
                    </div>

                    <div class="row gy-4">
                        <div class="col-md-4 text-center">
                            <img src="{{ session('2fa.setup')['qr_url'] }}" alt="QR Code 2FA"
                                 class="border rounded p-2" style="width: 200px; height: 200px;">
                        </div>
                        <div class="col-md-8">
                            <div class="alert alert-warning mb-20">
                                <strong>Codes de récupération</strong> - Conservez-les en lieu sûr.<br>
                                <small class="text-secondary-light">Chaque code ne peut être utilisé qu'une seule fois.</small>
                            </div>
                            <div class="row g-2 mb-20">
                                @foreach(session('2fa.setup')['recovery_codes'] as $code)
                                    <div class="col-6">
                                        <code class="d-block bg-neutral-100 rounded px-3 py-2 text-sm font-monospace">{{ $code }}</code>
                                    </div>
                                @endforeach
                            </div>

                            <form action="{{ route('admin.profile.2fa.confirm') }}" method="POST">
                                @csrf
                                <div class="mb-20">
                                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Code OTP de confirmation</label>
                                    <input type="text" name="code" maxlength="6" inputmode="numeric"
                                           placeholder="000000"
                                           class="form-control radius-8 @error('code') is-invalid @enderror text-center font-monospace"
                                           style="letter-spacing: 0.5em; font-size: 1.25rem;" required>
                                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <button type="submit" class="btn btn-success-600">
                                    <i class="ri-shield-check-line me-1"></i>Confirmer et activer le 2FA
                                </button>
                            </form>
                        </div>
                    </div>

                @elseif($user->hasEnabledTwoFactor())
                    {{-- 2FA actif --}}
                    <div class="d-flex align-items-center gap-3 mb-20">
                        <span class="badge text-success-600 bg-success-100">
                            <i class="ri-checkbox-circle-fill me-1"></i>Activé
                        </span>
                        <span class="text-secondary-light">Votre compte est protégé par la double authentification.</span>
                    </div>
                    <form action="{{ route('admin.profile.2fa.disable') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Confirmez votre mot de passe pour désactiver</label>
                            <input type="password" name="password"
                                   class="form-control radius-8 @error('password') is-invalid @enderror" required
                                   style="max-width: 320px;">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-danger-600">
                            <i class="ri-shield-cross-line me-1"></i>Désactiver le 2FA
                        </button>
                    </form>

                @else
                    {{-- 2FA inactif --}}
                    <div class="d-flex align-items-center gap-3 mb-20">
                        <span class="badge text-neutral-600 bg-neutral-100">
                            <i class="ri-close-circle-line me-1"></i>Désactivé
                        </span>
                        <span class="text-secondary-light">Ajoutez une couche de sécurité supplémentaire à votre compte.</span>
                    </div>
                    <form action="{{ route('admin.profile.2fa.enable') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary-600">
                            <i class="ri-shield-keyhole-line me-1"></i>Activer la double authentification
                        </button>
                    </form>
                @endif

            </div>
        </div>
    </div>

</div>

@endsection

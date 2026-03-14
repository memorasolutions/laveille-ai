<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Mon profil'), 'subtitle' => __('Modifier')])

@section('breadcrumbs')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Mon profil') }}</li>
    </ol>
</nav>
@endsection

@section('content')

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="user" class="icon-md text-primary"></i>{{ __('Mon profil') }}</h4>
    <x-backoffice::help-modal id="helpProfileModal" :title="__('Mon profil')" icon="user" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.profile._help')
    </x-backoffice::help-modal>
</div>

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
                            {{ __('Nom') }} <span class="text-danger">*</span>
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
                            {{ __('Courriel') }} <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">{{ __('Mettre à jour') }}</button>
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
                            {{ __('Mot de passe actuel') }} <span class="text-danger">*</span>
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
                            {{ __('Nouveau mot de passe') }} <span class="text-danger">*</span>
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
                            {{ __('Confirmer le nouveau mot de passe') }} <span class="text-danger">*</span>
                        </label>
                        <input type="password" name="password_confirmation"
                               class="form-control"
                               required>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">{{ __('Changer le mot de passe') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Sessions actives --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom py-3 px-4 d-flex align-items-center gap-2">
                <i data-lucide="monitor-smartphone" class="text-info icon-md"></i>
                <h4 class="fw-bold mb-0">{{ __('Sessions actives') }}</h4>
            </div>
            <div class="card-body p-4">
                @if($sessions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Appareil') }}</th>
                                    <th>{{ __('Adresse IP') }}</th>
                                    <th>{{ __('Dernière activité') }}</th>
                                    <th class="text-end">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sessions as $session)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i data-lucide="monitor" style="width:16px;height:16px;" class="text-muted"></i>
                                                {{ $session->browser }} {{ __('sur') }} {{ $session->os }}
                                            </div>
                                        </td>
                                        <td><code class="small">{{ $session->ip_address }}</code></td>
                                        <td class="text-muted small">{{ $session->last_activity }}</td>
                                        <td class="text-end">
                                            @if($session->is_current)
                                                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 d-inline-flex align-items-center gap-1">
                                                    <i data-lucide="check-circle" style="width:14px;height:14px;"></i>
                                                    {{ __('Session actuelle') }}
                                                </span>
                                            @else
                                                <form method="POST" action="{{ route('admin.profile.sessions.revoke', $session->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
                                                        <i data-lucide="x-circle" style="width:14px;height:14px;"></i>
                                                        {{ __('Révoquer') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($sessions->where('is_current', false)->count() > 0)
                        <div class="card border border-danger border-opacity-25 mt-4 mb-0">
                            <div class="card-body p-3">
                                <h6 class="fw-semibold text-danger d-flex align-items-center gap-2 mb-2">
                                    <i data-lucide="log-out" style="width:16px;height:16px;"></i>
                                    {{ __('Révoquer toutes les autres sessions') }}
                                </h6>
                                <p class="text-muted small mb-3">{{ __('Déconnectez tous les autres appareils. Confirmez avec votre mot de passe.') }}</p>
                                <form method="POST" action="{{ route('admin.profile.sessions.revoke-others') }}">
                                    @csrf
                                    <div class="d-flex align-items-start gap-2 flex-wrap">
                                        <div class="flex-grow-1" style="max-width:320px;">
                                            <input type="password" name="current_password" required
                                                   placeholder="{{ __('Votre mot de passe actuel') }}"
                                                   class="form-control @error('current_password') is-invalid @enderror">
                                            @error('current_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <button type="submit" class="btn btn-danger d-inline-flex align-items-center gap-1">
                                            <i data-lucide="log-out" style="width:16px;height:16px;"></i>
                                            {{ __('Révoquer tout') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <i data-lucide="monitor-smartphone" class="d-block mx-auto mb-2 text-muted" style="width:48px;height:48px;"></i>
                        <p class="text-muted mb-0">{{ __('Aucune session active.') }}</p>
                    </div>
                @endif
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
                            <strong>{{ __('Configurez votre application d\'authentification') }}</strong><br>
                            {{ __('Scannez ce QR code avec Google Authenticator, Authy ou une application TOTP compatible.') }}
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
                                    <strong>{{ __('Codes de récupération') }}</strong> - {{ __('Conservez-les en lieu sûr.') }}<br>
                                    <small>{{ __('Chaque code ne peut être utilisé qu\'une seule fois.') }}</small>
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
                                    <label class="form-label fw-medium">{{ __('Code OTP de confirmation') }}</label>
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
                                    {{ __('Confirmer et activer le 2FA') }}
                                </button>
                            </form>
                        </div>
                    </div>

                @elseif($user->hasEnabledTwoFactor())
                    {{-- 2FA actif --}}
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 d-inline-flex align-items-center gap-1">
                            <i data-lucide="check-circle" style="width:14px;height:14px;"></i>
                            {{ __('Activé') }}
                        </span>
                        <span class="text-muted small">{{ __('Votre compte est protégé par la double authentification.') }}</span>
                    </div>
                    <form action="{{ route('admin.profile.2fa.disable') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="mb-4">
                            <label class="form-label fw-medium">{{ __('Confirmez votre mot de passe pour désactiver') }}</label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   style="max-width:320px;" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-danger d-inline-flex align-items-center gap-2">
                            <i data-lucide="x-circle" style="width:16px;height:16px;"></i>
                            {{ __('Désactiver le 2FA') }}
                        </button>
                    </form>

                @else
                    {{-- 2FA inactif --}}
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 d-inline-flex align-items-center gap-1">
                            <i data-lucide="x" style="width:14px;height:14px;"></i>
                            {{ __('Désactivé') }}
                        </span>
                        <span class="text-muted small">{{ __('Ajoutez une couche de sécurité supplémentaire à votre compte.') }}</span>
                    </div>
                    <form action="{{ route('admin.profile.2fa.enable') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <i data-lucide="key" style="width:16px;height:16px;"></i>
                            {{ __('Activer la double authentification') }}
                        </button>
                    </form>
                @endif

            </div>
        </div>
    </div>

</div>

@endsection

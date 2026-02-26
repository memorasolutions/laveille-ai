@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Utilisateurs', 'subtitle' => 'Ajouter'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">{{ __('Utilisateurs') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Ajouter') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="user-plus" class="icon-md text-primary"></i>{{ __('Ajouter un utilisateur') }}</h4>
    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
        <i data-lucide="arrow-left"></i> {{ __('Retour') }}
    </a>
</div>

<form action="{{ route('admin.users.store') }}" method="POST">
    @csrf

    <div class="row g-3">
        {{-- Colonne principale --}}
        <div class="col-xl-8">
            {{-- Informations personnelles --}}
            <div class="card mb-3">
                <div class="card-header border-bottom py-3 px-4 d-flex align-items-center gap-2">
                    <i data-lucide="user" class="icon-md text-primary"></i>
                    <h5 class="fw-semibold mb-0">{{ __('Informations personnelles') }}</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="name">
                                {{ __('Nom') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="{{ __('Nom complet') }}">
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="email">
                                {{ __('Courriel') }} <span class="text-danger">*</span>
                            </label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="{{ __('adresse@exemple.com') }}">
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="phone">
                                {{ __('Téléphone') }}
                            </label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   placeholder="{{ __('+1 514 000-0000') }}" maxlength="20">
                            @error('phone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sécurité --}}
            <div class="card mb-3">
                <div class="card-header border-bottom py-3 px-4 d-flex align-items-center gap-2">
                    <i data-lucide="lock" class="icon-md text-warning"></i>
                    <h5 class="fw-semibold mb-0">{{ __('Sécurité') }}</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="password">
                                {{ __('Mot de passe') }} <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="password" name="password" required
                                   class="form-control @error('password') is-invalid @enderror">
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="password_confirmation">
                                {{ __('Confirmer le mot de passe') }} <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required
                                   class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-3 d-flex align-items-center justify-content-between gap-3">
                                <div>
                                    <span class="fw-medium small">{{ __('Forcer le changement de mot de passe') }}</span>
                                    <p class="text-muted small mb-0">{{ __("L'utilisateur devra changer son mot de passe à la prochaine connexion") }}</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="must_change_password" id="must_change_password" value="1"
                                           class="form-check-input"
                                           {{ old('must_change_password') ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne latérale --}}
        <div class="col-xl-4">
            {{-- Rôles --}}
            <div class="card mb-3">
                <div class="card-header border-bottom py-3 px-4 d-flex align-items-center gap-2">
                    <i data-lucide="shield" class="icon-md text-success"></i>
                    <h5 class="fw-semibold mb-0">{{ __('Rôles') }}</h5>
                </div>
                <div class="card-body p-4">
                    @php
                        $roleLabels = [
                            'super_admin' => ['label' => 'Super administrateur', 'desc' => 'Accès total, toutes permissions.', 'color' => 'danger'],
                            'admin' => ['label' => 'Administrateur', 'desc' => 'Gestion complète du backoffice.', 'color' => 'primary'],
                            'editor' => ['label' => 'Éditeur', 'desc' => 'Gestion du contenu et du blog.', 'color' => 'info'],
                            'user' => ['label' => 'Utilisateur', 'desc' => 'Accès basique au compte.', 'color' => 'secondary'],
                        ];
                    @endphp
                    <div class="d-flex flex-column gap-2">
                        @foreach($roles as $id => $name)
                            @php $meta = $roleLabels[$name] ?? ['label' => ucfirst(str_replace('_', ' ', $name)), 'desc' => '', 'color' => 'secondary']; @endphp
                            <label class="d-block p-3 rounded border {{ in_array($id, old('roles', [])) ? 'border-'.$meta['color'].' bg-'.$meta['color'].' bg-opacity-5' : 'border-light-subtle' }}"
                                   for="role_{{ $id }}" style="cursor:pointer;transition:all .15s ease;">
                                <div class="d-flex align-items-start gap-3">
                                    <input class="form-check-input mt-1 flex-shrink-0" type="checkbox"
                                           name="roles[]" value="{{ $id }}" id="role_{{ $id }}"
                                           @checked(in_array($id, old('roles', [])))>
                                    <div>
                                        <div class="fw-semibold text-body">{{ $meta['label'] }}</div>
                                        @if($meta['desc'])
                                            <div class="text-muted small mt-1">{{ $meta['desc'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('roles')
                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div class="card-body p-4">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2">
                            <i data-lucide="save" class="icon-sm"></i> {{ __('Enregistrer') }}
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light d-inline-flex align-items-center justify-content-center gap-2">
                            <i data-lucide="x" class="icon-sm"></i> {{ __('Annuler') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

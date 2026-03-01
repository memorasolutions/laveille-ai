<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Utilisateurs', 'subtitle' => 'Modifier'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">{{ __('Utilisateurs') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Modifier') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="user-pen" class="icon-md text-primary"></i>{{ __("Modifier l'utilisateur :") }} {{ $user->name }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-2">
            <i data-lucide="eye"></i> {{ __('Voir') }}
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
            <i data-lucide="arrow-left"></i> {{ __('Retour') }}
        </a>
    </div>
</div>

<form action="{{ route('admin.users.update', $user) }}" method="POST">
    @csrf
    @method('PUT')

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
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="email">
                                {{ __('Courriel') }} <span class="text-danger">*</span>
                            </label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="phone">
                                {{ __('Téléphone') }}
                            </label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
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
                                {{ __('Nouveau mot de passe') }}
                                <small class="text-muted fw-normal">({{ __('laisser vide pour ne pas changer') }})</small>
                            </label>
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror">
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" for="password_confirmation">
                                {{ __('Confirmer le mot de passe') }}
                            </label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
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
                                           {{ old('must_change_password', $user->must_change_password) ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne latérale --}}
        <div class="col-xl-4">
            {{-- Statut du compte --}}
            <div class="card mb-3">
                <div class="card-header border-bottom py-3 px-4 d-flex align-items-center gap-2">
                    <i data-lucide="activity" class="icon-md text-info"></i>
                    <h5 class="fw-semibold mb-0">{{ __('Statut du compte') }}</h5>
                </div>
                <div class="card-body p-4">
                    <div class="border rounded p-3 d-flex align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <span class="fw-medium small">{{ __('Compte actif') }}</span>
                            <p class="text-muted small mb-0">{{ __("Désactiver bloque l'accès sans supprimer le compte") }}</p>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_active" id="is_active" value="1"
                                   class="form-check-input"
                                   {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-2 small text-muted">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="calendar" class="icon-sm"></i>
                            <span>{{ __('Inscrit le') }} {{ $user->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @if($user->email_verified_at)
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="mail-check" class="icon-sm text-success"></i>
                            <span>{{ __('Courriel vérifié le') }} {{ $user->email_verified_at->format('d/m/Y') }}</span>
                        </div>
                        @else
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="mail-x" class="icon-sm text-warning"></i>
                            <span>{{ __('Courriel non vérifié') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

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
                        $userRoleIds = $user->roles->pluck('id')->toArray();
                    @endphp
                    <div class="d-flex flex-column gap-2">
                        @foreach($roles as $id => $name)
                            @php $meta = $roleLabels[$name] ?? ['label' => ucfirst(str_replace('_', ' ', $name)), 'desc' => '', 'color' => 'secondary']; @endphp
                            <label class="d-block p-3 rounded border {{ in_array($id, old('roles', $userRoleIds)) ? 'border-'.$meta['color'].' bg-'.$meta['color'].' bg-opacity-5' : 'border-light-subtle' }}"
                                   for="role_{{ $id }}" style="cursor:pointer;transition:all .15s ease;">
                                <div class="d-flex align-items-start gap-3">
                                    <input class="form-check-input mt-1 flex-shrink-0" type="checkbox"
                                           name="roles[]" value="{{ $id }}" id="role_{{ $id }}"
                                           @checked(in_array($id, old('roles', $userRoleIds)))>
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

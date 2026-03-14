<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Étapes onboarding'), 'subtitle' => __('Modifier')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.onboarding-steps.index') }}">{{ __('Étapes onboarding') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Modifier') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="compass" class="icon-md text-primary"></i>{{ __('Modifier') }} : {{ $step->title }}</h4>
            <a href="{{ route('admin.onboarding-steps.index') }}" class="btn btn-light btn-sm d-inline-flex align-items-center gap-2">
                <i data-lucide="arrow-left" class="icon-sm"></i>
                {{ __('Retour') }}
            </a>
        </div>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.onboarding-steps.update', $step) }}">
            @csrf
            @method('PUT')

            <div class="row g-4">

                <div class="col-12">
                    <label for="title" class="form-label fw-medium">
                        {{ __('Titre') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="title" id="title"
                        class="form-control @error('title') is-invalid @enderror"
                        value="{{ old('title', $step->title) }}" required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="description" class="form-label fw-medium">{{ __('Description') }}</label>
                    <textarea name="description" id="description"
                        class="form-control"
                        rows="3">{{ old('description', $step->description) }}</textarea>
                </div>

                <div class="col-12 col-md-6">
                    <label for="icon" class="form-label fw-medium">{{ __('Icône') }}</label>
                    <select name="icon" id="icon" class="form-select">
                        <option value="solar:user-check-outline" @selected(old('icon', $step->icon) === 'solar:user-check-outline')>{{ __('Profil utilisateur') }}</option>
                        <option value="solar:shield-check-outline" @selected(old('icon', $step->icon) === 'solar:shield-check-outline')>{{ __('Sécurité') }}</option>
                        <option value="solar:bell-outline" @selected(old('icon', $step->icon) === 'solar:bell-outline')>{{ __('Notifications') }}</option>
                        <option value="solar:card-outline" @selected(old('icon', $step->icon) === 'solar:card-outline')>{{ __('Facturation') }}</option>
                        <option value="solar:settings-outline" @selected(old('icon', $step->icon) === 'solar:settings-outline')>{{ __('Paramètres') }}</option>
                        <option value="solar:check-circle-outline" @selected(old('icon', $step->icon) === 'solar:check-circle-outline')>{{ __('Complété') }}</option>
                        <option value="solar:hand-shake-outline" @selected(old('icon', $step->icon) === 'solar:hand-shake-outline')>{{ __('Bienvenue') }}</option>
                        <option value="solar:palette-outline" @selected(old('icon', $step->icon) === 'solar:palette-outline')>{{ __('Personnalisation') }}</option>
                        <option value="solar:star-outline" @selected(old('icon', $step->icon) === 'solar:star-outline')>{{ __('Favoris') }}</option>
                        <option value="solar:book-outline" @selected(old('icon', $step->icon) === 'solar:book-outline')>{{ __('Guide') }}</option>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label for="order" class="form-label fw-medium">{{ __("Ordre d'affichage") }}</label>
                    <input type="number" name="order" id="order"
                        class="form-control"
                        style="max-width: 120px;"
                        value="{{ old('order', $step->order) }}" min="0">
                </div>

                <div class="col-12">
                    <div class="border rounded p-3 d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <span class="fw-medium small">{{ __('Active') }}</span>
                            <p class="text-muted small mb-0">{{ __("L'étape sera affichée dans le flux d'onboarding") }}</p>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_active" id="is_active" value="1"
                                class="form-check-input"
                                {{ old('is_active', $step->is_active) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

            </div>

            <div class="d-flex align-items-center gap-3 mt-4">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="check" class="icon-sm"></i>
                    {{ __('Enregistrer') }}
                </button>
                <a href="{{ route('admin.onboarding-steps.index') }}" class="btn btn-light">{{ __('Annuler') }}</a>
            </div>
        </form>
    </div>
</div>

@endsection

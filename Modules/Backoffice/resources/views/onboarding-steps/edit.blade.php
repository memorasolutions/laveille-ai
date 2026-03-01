<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin')
@section('title', 'Modifier étape onboarding')
@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0 fw-semibold">Modifier : {{ $step->title }}</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.onboarding-steps.update', $step) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="title" class="form-label">Titre</label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $step->title) }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $step->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label for="icon" class="form-label">Icône</label>
                <select name="icon" id="icon" class="form-select">
                    <option value="solar:user-check-outline" @selected(old('icon', $step->icon) === 'solar:user-check-outline')>Profil utilisateur</option>
                    <option value="solar:shield-check-outline" @selected(old('icon', $step->icon) === 'solar:shield-check-outline')>Sécurité</option>
                    <option value="solar:bell-outline" @selected(old('icon', $step->icon) === 'solar:bell-outline')>Notifications</option>
                    <option value="solar:card-outline" @selected(old('icon', $step->icon) === 'solar:card-outline')>Facturation</option>
                    <option value="solar:settings-outline" @selected(old('icon', $step->icon) === 'solar:settings-outline')>Paramètres</option>
                    <option value="solar:check-circle-outline" @selected(old('icon', $step->icon) === 'solar:check-circle-outline')>Complété</option>
                    <option value="solar:hand-shake-outline" @selected(old('icon', $step->icon) === 'solar:hand-shake-outline')>Bienvenue</option>
                    <option value="solar:palette-outline" @selected(old('icon', $step->icon) === 'solar:palette-outline')>Personnalisation</option>
                    <option value="solar:star-outline" @selected(old('icon', $step->icon) === 'solar:star-outline')>Favoris</option>
                    <option value="solar:book-outline" @selected(old('icon', $step->icon) === 'solar:book-outline')>Guide</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="order" class="form-label">Ordre d'affichage</label>
                <input type="number" name="order" id="order" class="form-control" value="{{ old('order', $step->order) }}" min="0">
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $step->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.onboarding-steps.index') }}" class="btn btn-secondary">Retour</a>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
@endsection

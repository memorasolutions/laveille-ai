@extends('backoffice::layouts.admin', ['title' => 'Catégories cookies', 'subtitle' => 'Créer'])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Créer une catégorie cookie</h3>
        <a href="{{ route('admin.cookie-categories.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i> Retour
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.cookie-categories.store') }}">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label required">Nom (identifiant)</label>
                <input type="text" name="name" id="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}" required placeholder="ex: preferences">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Identifiant technique unique, lettres minuscules et tirets uniquement.</small>
            </div>

            <div class="mb-3">
                <label for="label" class="form-label required">Label affiché</label>
                <input type="text" name="label" id="label"
                    class="form-control @error('label') is-invalid @enderror"
                    value="{{ old('label') }}" required placeholder="ex: Cookies de préférences">
                @error('label')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="mb-3">
                <label for="order" class="form-label">Ordre d'affichage</label>
                <input type="number" name="order" id="order" class="form-control" value="{{ old('order', 0) }}" min="0" style="max-width: 120px;">
            </div>

            <div class="mb-3">
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="required" id="required" value="1" {{ old('required') ? 'checked' : '' }}>
                    <span class="form-check-label">Obligatoire (ne peut être refusé)</span>
                </label>
            </div>

            <div class="mb-4">
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <span class="form-check-label">Active</span>
                </label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-check me-1"></i> Enregistrer
                </button>
                <a href="{{ route('admin.cookie-categories.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

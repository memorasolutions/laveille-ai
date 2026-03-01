<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin')
@section('title', 'Modifier catégorie cookie')
@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0 fw-semibold">Modifier : {{ $category->label }}</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.cookie-categories.update', $category) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="name" class="form-label">Nom (identifiant)</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="label" class="form-label">Label affiché</label>
                <input type="text" name="label" id="label" class="form-control @error('label') is-invalid @enderror" value="{{ old('label', $category->label) }}" required>
                @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $category->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label for="order" class="form-label">Ordre d'affichage</label>
                <input type="number" name="order" id="order" class="form-control" value="{{ old('order', $category->order) }}" min="0">
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="required" id="required" value="1" {{ old('required', $category->required) ? 'checked' : '' }}>
                    <label class="form-check-label" for="required">Obligatoire (ne peut être refusé)</label>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.cookie-categories.index') }}" class="btn btn-secondary">Retour</a>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
@endsection

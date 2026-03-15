<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Nouvelle catégorie'), 'subtitle' => __('Boutique')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.dashboard') }}">{{ __('Boutique') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.categories.index') }}">{{ __('Catégories') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Créer') }}</li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.ecommerce.categories.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nom') }}</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Slug') }}</label>
                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}">
                        <small class="text-muted">{{ __('Laissez vide pour générer automatiquement.') }}</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Catégorie parent') }}</label>
                            <select name="parent_id" class="form-select">
                                <option value="">{{ __('Aucun (racine)') }}</option>
                                @foreach($parents as $parent)
                                    <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Position') }}</label>
                            <input type="number" name="position" class="form-control" value="{{ old('position', 0) }}">
                        </div>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">{{ __('Activer la catégorie') }}</label>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
                    <a href="{{ route('admin.ecommerce.categories.index') }}" class="btn btn-secondary">{{ __('Annuler') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

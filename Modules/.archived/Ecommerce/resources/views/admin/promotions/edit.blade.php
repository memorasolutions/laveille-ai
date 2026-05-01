<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Modifier la promotion'), 'subtitle' => __('Boutique')])

@section('content')
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
            <li class="breadcrumb-item">{{ __('Boutique') }}</li>
            <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.promotions.index') }}">{{ __('Promotions') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ __('Modifier') }}</li>
        </ol>
    </nav>

    <form action="{{ route('admin.ecommerce.promotions.update', $promotion) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-header">
                <h5 class="fw-bold mb-0">{{ __('Détails de la promotion') }}</h5>
            </div>
            <div class="card-body">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">{{ __('Nom') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $promotion->name) }}" required>
                        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="type" class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="percentage_off" {{ old('type', $promotion->type) == 'percentage_off' ? 'selected' : '' }}>{{ __('Remise en pourcentage') }}</option>
                            <option value="fixed_off" {{ old('type', $promotion->type) == 'fixed_off' ? 'selected' : '' }}>{{ __('Remise fixe') }}</option>
                            <option value="bogo" {{ old('type', $promotion->type) == 'bogo' ? 'selected' : '' }}>{{ __('1 acheté = 1 offert') }}</option>
                            <option value="free_shipping" {{ old('type', $promotion->type) == 'free_shipping' ? 'selected' : '' }}>{{ __('Livraison gratuite') }}</option>
                            <option value="tiered_pricing" {{ old('type', $promotion->type) == 'tiered_pricing' ? 'selected' : '' }}>{{ __('Prix par palier') }}</option>
                        </select>
                        @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="value" class="form-label">{{ __('Valeur') }}</label>
                        <input type="number" step="0.01" class="form-control" id="value" name="value" value="{{ old('value', $promotion->value) }}">
                        @error('value')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="applies_to" class="form-label">{{ __('S\'applique à') }}</label>
                        <select class="form-select" id="applies_to" name="applies_to">
                            <option value="all" {{ old('applies_to', $promotion->applies_to) == 'all' ? 'selected' : '' }}>{{ __('Tout') }}</option>
                            <option value="specific_products" {{ old('applies_to', $promotion->applies_to) == 'specific_products' ? 'selected' : '' }}>{{ __('Produits spécifiques') }}</option>
                            <option value="specific_categories" {{ old('applies_to', $promotion->applies_to) == 'specific_categories' ? 'selected' : '' }}>{{ __('Catégories spécifiques') }}</option>
                        </select>
                        @error('applies_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="priority" class="form-label">{{ __('Priorité') }}</label>
                        <input type="number" class="form-control" id="priority" name="priority" value="{{ old('priority', $promotion->priority) }}">
                        @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="max_uses" class="form-label">{{ __('Utilisations max') }}</label>
                        <input type="number" class="form-control" id="max_uses" name="max_uses" value="{{ old('max_uses', $promotion->max_uses) }}" placeholder="{{ __('Illimité') }}">
                        @error('max_uses')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="starts_at" class="form-label">{{ __('Date de début') }}</label>
                        <input type="datetime-local" class="form-control" id="starts_at" name="starts_at" value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d\TH:i')) }}">
                        @error('starts_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="expires_at" class="form-label">{{ __('Date de fin') }}</label>
                        <input type="datetime-local" class="form-control" id="expires_at" name="expires_at" value="{{ old('expires_at', $promotion->expires_at?->format('Y-m-d\TH:i')) }}">
                        @error('expires_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6 d-flex align-items-center gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $promotion->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">{{ __('Actif') }}</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_stackable" name="is_stackable" value="1" {{ old('is_stackable', $promotion->is_stackable) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_stackable">{{ __('Cumulable') }}</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_automatic" name="is_automatic" value="1" {{ old('is_automatic', $promotion->is_automatic) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_automatic">{{ __('Automatique') }}</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" class="me-1"></i> {{ __('Mettre à jour') }}
                    </button>
                    <a href="{{ route('admin.ecommerce.promotions.index') }}" class="btn btn-secondary ms-2">
                        {{ __('Retour') }}
                    </a>
                </div>

            </div>
        </div>
    </form>
@endsection

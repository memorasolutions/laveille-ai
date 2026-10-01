<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.ads.index') }}">{{ __('Publicités') }}</a></li>
        <li class="breadcrumb-item active">{{ $ad->name }}</li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="card-title m-0">{{ __('Modifier la publicité') }}</h6>
                    <form action="{{ route('admin.ads.destroy', $ad) }}" method="POST" data-confirm="Supprimer cette publicité ?">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm">{{ __('Supprimer') }}</button>
                    </form>
                </div>
                <form action="{{ route('admin.ads.update', $ad) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nom') }} *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $ad->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Clé de position') }} *</label>
                        <input type="text" name="key" class="form-control @error('key') is-invalid @enderror" value="{{ old('key', $ad->key) }}" required>
                        @error('key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $ad->description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Code publicitaire direct (HTML/JS)') }}</label>
                        <textarea name="ad_code" class="form-control @error('ad_code') is-invalid @enderror" rows="10" style="font-family:monospace;font-size:0.85rem;">{{ old('ad_code', $ad->ad_code) }}</textarea>
                        @error('ad_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Identifiant d\'emplacement AdSense (data-ad-slot)') }}</label>
                        <input type="text" name="ad_slot" maxlength="32" class="form-control @error('ad_slot') is-invalid @enderror" value="{{ old('ad_slot', $ad->ad_slot) }}">
                        <small class="text-muted">{{ __('Laisser vide pour une pub directe maison.') }} {{ __('Remplir À LA FOIS le code de pub directe ET l\'identifiant AdSense fait alterner les deux chaque jour.') }}</small>
                        @error('ad_slot') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Ordre') }}</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $ad->sort_order) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Format AdSense') }}</label>
                            <select name="ad_format" class="form-select @error('ad_format') is-invalid @enderror">
                                @foreach (['auto', 'horizontal', 'rectangle', 'vertical', 'fluid'] as $fmt)
                                    <option value="{{ $fmt }}" {{ old('ad_format', $ad->ad_format ?? 'auto') === $fmt ? 'selected' : '' }}>{{ $fmt }}</option>
                                @endforeach
                            </select>
                            @error('ad_format') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Hauteur minimale (px)') }}</label>
                            <input type="number" name="min_height" min="0" max="2000" class="form-control @error('min_height') is-invalid @enderror" value="{{ old('min_height', $ad->min_height) }}" placeholder="280">
                            <small class="text-muted">{{ __('Hauteur réservée, anti-saut de page.') }}</small>
                            @error('min_height') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch mb-2">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ old('is_active', $ad->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label">{{ __('Activer') }}</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input type="checkbox" class="form-check-input" name="lazy" value="1" {{ old('lazy', $ad->lazy) ? 'checked' : '' }}>
                            <label class="form-check-label">{{ __('Chargement différé (à l\'approche de l\'écran)') }}</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_external" value="1" {{ old('is_external', $ad->is_external) ? 'checked' : '' }}>
                            <label class="form-check-label">{{ __('Source externe (AdSense, etc.) – pas de label "Publicité"') }}</label>
                        </div>
                    </div>
                    <div class="mb-3 p-3 bg-light rounded">
                        <small class="text-muted">{{ __('Pour insérer cette pub dans un article via l\'éditeur :') }}</small>
                        <code class="d-block mt-1">[ad key="{{ $ad->key }}"]</code>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Mettre à jour') }}</button>
                    <a href="{{ route('admin.ads.index') }}" class="btn btn-secondary">{{ __('Annuler') }}</a>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4 grid-margin">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3">{{ __('Position actuelle') }}</h6>
                <p class="text-muted small">{{ __('La zone') }} <strong>{{ $ad->key }}</strong> {{ __('est mise en évidence.') }}</p>
                @include('ads::admin._position-preview', ['activeKey' => $ad->key])
            </div>
        </div>
    </div>
</div>
@endsection

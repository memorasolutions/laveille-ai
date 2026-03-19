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
                    <form action="{{ route('admin.ads.destroy', $ad) }}" method="POST" onsubmit="return confirm('Supprimer cette publicité ?')">
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
                        <label class="form-label">{{ __('Code publicitaire (HTML/JS)') }} *</label>
                        <textarea name="ad_code" class="form-control @error('ad_code') is-invalid @enderror" rows="10" style="font-family:monospace;font-size:0.85rem;">{{ old('ad_code', $ad->ad_code) }}</textarea>
                        @error('ad_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Ordre') }}</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $ad->sort_order) }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch mb-2">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ old('is_active', $ad->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label">{{ __('Activer') }}</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_external" value="1" {{ old('is_external', $ad->is_external) ? 'checked' : '' }}>
                            <label class="form-check-label">{{ __('Source externe (AdSense, etc.) — pas de label "Publicité"') }}</label>
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

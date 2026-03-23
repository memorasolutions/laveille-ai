<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.ads.index') }}">{{ __('Publicités') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Ajouter') }}</li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">{{ __('Nouvelle publicité') }}</h6>
                <form action="{{ route('admin.ads.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('Nom') }} *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Clé de position') }} *</label>
                        <input type="text" name="key" class="form-control @error('key') is-invalid @enderror" value="{{ old('key') }}" placeholder="header-leaderboard" required>
                        <small class="text-muted">{{ __('Voir le schéma à droite pour les positions disponibles.') }}</small>
                        @error('key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Code publicitaire (HTML/JS)') }} *</label>
                        <textarea name="ad_code" class="form-control @error('ad_code') is-invalid @enderror" rows="8" style="font-family:monospace;font-size:0.85rem;">{{ old('ad_code') }}</textarea>
                        @error('ad_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Ordre') }}</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch mb-2">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}>
                            <label class="form-check-label">{{ __('Activer') }}</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_external" value="1" {{ old('is_external') ? 'checked' : '' }}>
                            <label class="form-check-label">{{ __('Source externe (AdSense, etc.) — pas de label "Publicité"') }}</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
                    <a href="{{ route('admin.ads.index') }}" class="btn btn-secondary">{{ __('Annuler') }}</a>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4 grid-margin">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3">{{ __('Positions disponibles') }}</h6>
                <p class="text-muted small">{{ __('Utilisez une des clés affichées dans ce schéma.') }}</p>
                @include('ads::admin._position-preview', ['activeKey' => ''])
            </div>
        </div>
    </div>
</div>
@endsection

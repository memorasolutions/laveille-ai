@extends('backoffice::layouts.admin', ['title' => 'SEO', 'subtitle' => 'Modifier'])

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Modifier le Meta Tag : {{ $metaTag->route_name }}</h3></div>
    <div class="card-body">
        <form action="{{ route('admin.seo.update', $metaTag) }}" method="POST">
            @csrf @method('PUT')
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label required">Nom de la route</label>
                    <input type="text" name="route_name" class="form-control @error('route_name') is-invalid @enderror" value="{{ old('route_name', $metaTag->route_name) }}" required>
                    @error('route_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">URL canonique</label>
                    <input type="url" name="canonical_url" class="form-control @error('canonical_url') is-invalid @enderror" value="{{ old('canonical_url', $metaTag->canonical_url) }}">
                    @error('canonical_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label required">Titre</label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $metaTag->title) }}" required>
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $metaTag->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Mots-clés</label>
                    <input type="text" name="keywords" class="form-control @error('keywords') is-invalid @enderror" value="{{ old('keywords', $metaTag->keywords) }}">
                    @error('keywords') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Image OG</label>
                    <input type="text" name="og_image" class="form-control @error('og_image') is-invalid @enderror" value="{{ old('og_image', $metaTag->og_image) }}">
                    @error('og_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Enregistrer</button>
                <a href="{{ route('admin.seo.index') }}" class="btn btn-outline-danger">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

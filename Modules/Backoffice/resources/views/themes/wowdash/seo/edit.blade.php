@extends('backoffice::layouts.admin', ['title' => 'SEO', 'subtitle' => 'Modifier le tag'])

@section('content')
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Modifier : <code>{{ $metaTag->url_pattern }}</code></h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.seo.update', $metaTag) }}">
            @csrf
            @method('PUT')

            <div class="row gy-3">
                <div class="col-md-6">
                    <label for="url_pattern" class="form-label fw-semibold text-primary-light text-sm mb-8">Modèle d'URL <span class="text-danger-main">*</span></label>
                    <input type="text" class="form-control radius-8 @error('url_pattern') is-invalid @enderror"
                           id="url_pattern" name="url_pattern"
                           value="{{ old('url_pattern', $metaTag->url_pattern) }}"
                           placeholder="/blog ou /blog/*" required>
                    @error('url_pattern')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="title" class="form-label fw-semibold text-primary-light text-sm mb-8">Titre (meta title)</label>
                    <input type="text" class="form-control radius-8 @error('title') is-invalid @enderror"
                           id="title" name="title"
                           value="{{ old('title', $metaTag->title) }}"
                           maxlength="255">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-20">
                <label for="description" class="form-label fw-semibold text-primary-light text-sm mb-8">Description (meta description)</label>
                <textarea class="form-control radius-8 @error('description') is-invalid @enderror"
                          id="description" name="description" rows="3">{{ old('description', $metaTag->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-20">
                <label for="keywords" class="form-label fw-semibold text-primary-light text-sm mb-8">Mots-clés</label>
                <input type="text" class="form-control radius-8 @error('keywords') is-invalid @enderror"
                       id="keywords" name="keywords"
                       value="{{ old('keywords', $metaTag->keywords) }}"
                       maxlength="500">
                @error('keywords')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <hr class="my-3">
            <h6 class="text-secondary-light mb-3">Open Graph / Réseaux sociaux</h6>

            <div class="row gy-3">
                <div class="col-md-6">
                    <label for="og_title" class="form-label fw-semibold text-primary-light text-sm mb-8">Titre Open Graph</label>
                    <input type="text" class="form-control radius-8 @error('og_title') is-invalid @enderror"
                           id="og_title" name="og_title"
                           value="{{ old('og_title', $metaTag->og_title) }}"
                           maxlength="255">
                    @error('og_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="og_image" class="form-label fw-semibold text-primary-light text-sm mb-8">Image OG (URL)</label>
                    <input type="url" class="form-control radius-8 @error('og_image') is-invalid @enderror"
                           id="og_image" name="og_image"
                           value="{{ old('og_image', $metaTag->og_image) }}"
                           maxlength="500">
                    @error('og_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-20">
                <label for="og_description" class="form-label fw-semibold text-primary-light text-sm mb-8">Description OG</label>
                <textarea class="form-control radius-8 @error('og_description') is-invalid @enderror"
                          id="og_description" name="og_description" rows="2">{{ old('og_description', $metaTag->og_description) }}</textarea>
                @error('og_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <hr class="my-3">
            <h6 class="text-secondary-light mb-3">Paramètres techniques</h6>

            <div class="row gy-3">
                <div class="col-md-4">
                    <label for="twitter_card" class="form-label fw-semibold text-primary-light text-sm mb-8">Twitter Card</label>
                    <select class="form-select radius-8 @error('twitter_card') is-invalid @enderror"
                            id="twitter_card" name="twitter_card">
                        <option value="">Sélectionner...</option>
                        <option value="summary" @selected(old('twitter_card', $metaTag->twitter_card) === 'summary')>summary</option>
                        <option value="summary_large_image" @selected(old('twitter_card', $metaTag->twitter_card) === 'summary_large_image')>summary_large_image</option>
                        <option value="app" @selected(old('twitter_card', $metaTag->twitter_card) === 'app')>app</option>
                        <option value="player" @selected(old('twitter_card', $metaTag->twitter_card) === 'player')>player</option>
                    </select>
                    @error('twitter_card')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="robots" class="form-label fw-semibold text-primary-light text-sm mb-8">Robots</label>
                    <select class="form-select radius-8 @error('robots') is-invalid @enderror"
                            id="robots" name="robots">
                        <option value="index, follow" @selected(old('robots', $metaTag->robots) === 'index, follow')>index, follow (défaut)</option>
                        <option value="noindex, follow" @selected(old('robots', $metaTag->robots) === 'noindex, follow')>noindex, follow</option>
                        <option value="index, nofollow" @selected(old('robots', $metaTag->robots) === 'index, nofollow')>index, nofollow</option>
                        <option value="noindex, nofollow" @selected(old('robots', $metaTag->robots) === 'noindex, nofollow')>noindex, nofollow</option>
                        <option value="noarchive" @selected(old('robots', $metaTag->robots) === 'noarchive')>noarchive</option>
                        <option value="nosnippet" @selected(old('robots', $metaTag->robots) === 'nosnippet')>nosnippet</option>
                    </select>
                    @error('robots')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="canonical_url" class="form-label fw-semibold text-primary-light text-sm mb-8">URL canonique</label>
                    <input type="url" class="form-control radius-8 @error('canonical_url') is-invalid @enderror"
                           id="canonical_url" name="canonical_url"
                           value="{{ old('canonical_url', $metaTag->canonical_url) }}"
                           maxlength="500">
                    @error('canonical_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-20">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox"
                           id="is_active" name="is_active" value="1"
                           @checked(old('is_active', $metaTag->is_active))>
                    <label class="form-check-label" for="is_active">Tag actif</label>
                </div>
            </div>

            <div class="d-flex gap-3 mt-24">
                <button type="submit" class="btn btn-primary-600">Mettre à jour</button>
                <a href="{{ route('admin.seo.index') }}" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">Retour</a>
            </div>
        </form>

        <hr class="my-4">

        <form method="POST" action="{{ route('admin.seo.destroy', $metaTag) }}"
              onsubmit="return confirm('Supprimer ce tag SEO ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger-600 radius-4">Supprimer ce tag</button>
        </form>
    </div>
</div>
@endsection

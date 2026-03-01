<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'SEO', 'subtitle' => 'Modifier le tag'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.seo.index') }}">{{ __('SEO') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Modifier') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i data-lucide="search" class="icon-md text-primary"></i>Modifier : <code class="text-primary">{{ $metaTag->url_pattern }}</code>
        </h4>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.seo.update', $metaTag) }}">
            @csrf
            @method('PUT')

            {{-- URL et Titre --}}
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6">
                    <label for="url_pattern" class="form-label fw-medium">
                        Modèle d'URL <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('url_pattern') is-invalid @enderror"
                           id="url_pattern" name="url_pattern"
                           value="{{ old('url_pattern', $metaTag->url_pattern) }}"
                           placeholder="/blog ou /blog/*" required>
                    @error('url_pattern') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="title" class="form-label fw-medium">Titre (meta title)</label>
                    <input type="text"
                           class="form-control @error('title') is-invalid @enderror"
                           id="title" name="title"
                           value="{{ old('title', $metaTag->title) }}"
                           maxlength="255">
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Description --}}
            <div class="mb-4">
                <label for="description" class="form-label fw-medium">Description (meta description)</label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description" rows="3">{{ old('description', $metaTag->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Mots-clés --}}
            <div class="mb-4">
                <label for="keywords" class="form-label fw-medium">Mots-clés</label>
                <input type="text"
                       class="form-control @error('keywords') is-invalid @enderror"
                       id="keywords" name="keywords"
                       value="{{ old('keywords', $metaTag->keywords) }}"
                       maxlength="500">
                @error('keywords') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Séparateur Open Graph --}}
            <hr class="my-4">
            <h6 class="fw-semibold mb-3">Open Graph / Réseaux sociaux</h6>

            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6">
                    <label for="og_title" class="form-label fw-medium">Titre Open Graph</label>
                    <input type="text"
                           class="form-control @error('og_title') is-invalid @enderror"
                           id="og_title" name="og_title"
                           value="{{ old('og_title', $metaTag->og_title) }}"
                           maxlength="255">
                    @error('og_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="og_image" class="form-label fw-medium">Image OG (URL)</label>
                    <input type="url"
                           class="form-control @error('og_image') is-invalid @enderror"
                           id="og_image" name="og_image"
                           value="{{ old('og_image', $metaTag->og_image) }}"
                           maxlength="500">
                    @error('og_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Description OG --}}
            <div class="mb-4">
                <label for="og_description" class="form-label fw-medium">Description OG</label>
                <textarea class="form-control @error('og_description') is-invalid @enderror"
                          id="og_description" name="og_description" rows="2">{{ old('og_description', $metaTag->og_description) }}</textarea>
                @error('og_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Séparateur paramètres techniques --}}
            <hr class="my-4">
            <h6 class="fw-semibold mb-3">Paramètres techniques</h6>

            <div class="row g-4 mb-4">
                {{-- Twitter Card --}}
                <div class="col-12 col-md-4">
                    <label for="twitter_card" class="form-label fw-medium">Twitter Card</label>
                    <select class="form-select @error('twitter_card') is-invalid @enderror"
                            id="twitter_card" name="twitter_card">
                        <option value="">Sélectionner...</option>
                        <option value="summary" @selected(old('twitter_card', $metaTag->twitter_card) === 'summary')>summary</option>
                        <option value="summary_large_image" @selected(old('twitter_card', $metaTag->twitter_card) === 'summary_large_image')>summary_large_image</option>
                        <option value="app" @selected(old('twitter_card', $metaTag->twitter_card) === 'app')>app</option>
                        <option value="player" @selected(old('twitter_card', $metaTag->twitter_card) === 'player')>player</option>
                    </select>
                    @error('twitter_card') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Robots --}}
                <div class="col-12 col-md-4">
                    <label for="robots" class="form-label fw-medium">Robots</label>
                    <select class="form-select @error('robots') is-invalid @enderror"
                            id="robots" name="robots">
                        <option value="index, follow" @selected(old('robots', $metaTag->robots) === 'index, follow')>index, follow (défaut)</option>
                        <option value="noindex, follow" @selected(old('robots', $metaTag->robots) === 'noindex, follow')>noindex, follow</option>
                        <option value="index, nofollow" @selected(old('robots', $metaTag->robots) === 'index, nofollow')>index, nofollow</option>
                        <option value="noindex, nofollow" @selected(old('robots', $metaTag->robots) === 'noindex, nofollow')>noindex, nofollow</option>
                        <option value="noarchive" @selected(old('robots', $metaTag->robots) === 'noarchive')>noarchive</option>
                        <option value="nosnippet" @selected(old('robots', $metaTag->robots) === 'nosnippet')>nosnippet</option>
                    </select>
                    @error('robots') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- URL canonique --}}
                <div class="col-12 col-md-4">
                    <label for="canonical_url" class="form-label fw-medium">URL canonique</label>
                    <input type="url"
                           class="form-control @error('canonical_url') is-invalid @enderror"
                           id="canonical_url" name="canonical_url"
                           value="{{ old('canonical_url', $metaTag->canonical_url) }}"
                           maxlength="500">
                    @error('canonical_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Tag actif --}}
            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox"
                           id="is_active" name="is_active" value="1"
                           @checked(old('is_active', $metaTag->is_active))>
                    <label class="form-check-label" for="is_active">Tag actif</label>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3 mt-4">
                <button type="submit" class="btn btn-primary">Mettre à jour</button>
                <a href="{{ route('admin.seo.index') }}" class="btn btn-light">Retour</a>
            </div>
        </form>

        <hr class="my-4">

        <form method="POST" action="{{ route('admin.seo.destroy', $metaTag) }}"
              onsubmit="return confirm('Supprimer ce tag SEO ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-2">
                <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                Supprimer ce tag
            </button>
        </form>
    </div>
</div>

@endsection

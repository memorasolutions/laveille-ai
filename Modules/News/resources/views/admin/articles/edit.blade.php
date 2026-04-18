@extends('backoffice::layouts.admin')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-4">{{ __('Modifier l\'article') }}</h4>

        <div class="mb-3 p-3 bg-light rounded">
            <strong>{{ $article->title }}</strong><br>
            <small class="text-muted">{{ $article->source->name ?? '' }} &middot; {{ $article->pub_date?->format('d/m/Y H:i') }} &middot; Score : {{ $article->relevance_score ?? '-' }}/10</small>
        </div>

        <form action="{{ route('admin.news.articles.update', $article) }}" method="POST">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label" for="slug">{{ __('Slug (URL)') }}</label>
                <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror"
                    value="{{ old('slug', $article->slug) }}" maxlength="255">
                <small class="text-muted">{{ __('URL actuelle') }} : /actualites/{{ $article->slug }}</small>
                @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="seo_title">{{ __('Titre SEO') }}</label>
                <input type="text" name="seo_title" id="seo_title" class="form-control @error('seo_title') is-invalid @enderror"
                    value="{{ old('seo_title', $article->seo_title) }}" maxlength="255">
                <small class="text-muted">{{ __('Max 60 caracteres recommandes') }}</small>
                @error('seo_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="meta_description">{{ __('Meta description') }}</label>
                <textarea name="meta_description" id="meta_description" class="form-control @error('meta_description') is-invalid @enderror"
                    rows="2" maxlength="255">{{ old('meta_description', $article->meta_description) }}</textarea>
                <small class="text-muted">{{ __('Max 155 caracteres recommandes') }}</small>
                @error('meta_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="category_tag">{{ __('Categorie') }}</label>
                <input type="text" name="category_tag" id="category_tag" class="form-control @error('category_tag') is-invalid @enderror"
                    value="{{ old('category_tag', $article->category_tag) }}" maxlength="50">
                @error('category_tag') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="summary">{{ __('Resume') }}</label>
                <textarea name="summary" id="summary" class="form-control @error('summary') is-invalid @enderror"
                    rows="4" maxlength="2000">{{ old('summary', $article->summary) }}</textarea>
                @error('summary') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
                <a href="{{ route('admin.news.articles.index') }}" class="btn btn-secondary">{{ __('Annuler') }}</a>
            </div>
        </form>

        <x-core::screenshot-capture
            :uploadUrl="route('admin.news.articles.upload-image', $article)"
            :enabled="\Modules\Settings\Facades\Settings::get('news.assisted_screenshot_enabled', true)"
        />
    </div>
</div>
@endsection

@extends('auth::layouts.app')

@section('title', __('Modifier l\'article'))

@section('content')

<div class="mb-20">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-8">
            <li class="breadcrumb-item">
                <a href="{{ route('user.articles.index') }}" class="text-primary-600">{{ __('Mes articles') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('Modifier') }}</li>
        </ol>
    </nav>
    <h1 class="fw-semibold mb-0">{{ __('Modifier l\'article') }}</h1>
</div>

<form method="POST" action="{{ route('user.articles.update', $article) }}" class="row gy-20">
    @csrf @method('PUT')

    {{-- Colonne principale --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="mb-16">
                    <label class="form-label fw-medium text-secondary-light">
                        {{ __('Titre') }} <span class="text-danger-main">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title', $article->title) }}" required
                           class="form-control radius-8 @error('title') is-invalid @enderror">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-16">
                    <label class="form-label fw-medium text-secondary-light">{{ __('Extrait') }}</label>
                    <textarea name="excerpt" rows="3"
                              class="form-control radius-8 @error('excerpt') is-invalid @enderror">{{ old('excerpt', $article->excerpt) }}</textarea>
                    @error('excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="form-label fw-medium text-secondary-light">
                        {{ __('Contenu') }} <span class="text-danger-main">*</span>
                    </label>
                    <textarea name="content" rows="18" required
                              class="form-control radius-8 font-monospace @error('content') is-invalid @enderror">{{ old('content', $article->content) }}</textarea>
                    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Colonne latérale --}}
    <div class="col-lg-4">
        <div class="card mb-16">
            <div class="card-header">
                <h5 class="card-title fw-semibold text-base mb-0">{{ __('Publication') }}</h5>
            </div>
            <div class="card-body">
                <div class="mb-16">
                    <label class="form-label fw-medium text-secondary-light">{{ __('Statut') }} <span class="text-danger-main">*</span></label>
                    <select name="status" class="form-select radius-8">
                        <option value="draft" @selected(old('status', $article->status) === 'draft')>{{ __('Brouillon') }}</option>
                        <option value="published" @selected(old('status', $article->status) === 'published')>{{ __('Publié') }}</option>
                    </select>
                </div>

                <div class="mb-16">
                    <label class="form-label fw-medium text-secondary-light">{{ __('Catégorie') }}</label>
                    <select name="category_id" class="form-select radius-8">
                        <option value="">-- {{ __('Aucune') }} --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $article->category_id) == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label fw-medium text-secondary-light">{{ __('Tags') }}</label>
                    @php
                        $existingTags = old('tags_input', is_array($article->tags) ? implode(', ', $article->tags) : '');
                    @endphp
                    <input type="text" name="tags_input" value="{{ $existingTags }}"
                           placeholder="tag1, tag2, tag3"
                           class="form-control radius-8">
                    <div class="form-text">{{ __('Séparés par des virgules') }}</div>
                </div>
            </div>
        </div>

        @if($article->status === 'published')
        <a href="{{ route('blog.show', $article->slug) }}" target="_blank"
           class="btn btn-outline-secondary radius-8 w-100 mb-16 d-flex align-items-center justify-content-center gap-8">
            <iconify-icon icon="solar:eye-outline"></iconify-icon>
            {{ __('Voir l\'article publié') }}
        </a>
        @endif

        <div class="d-flex flex-column gap-8">
            <button type="submit" class="btn btn-primary-600 radius-8 w-100">
                <iconify-icon icon="solar:floppy-disk-outline"></iconify-icon>
                {{ __('Mettre à jour') }}
            </button>
            <a href="{{ route('user.articles.index') }}" class="btn btn-outline-secondary radius-8 w-100">
                {{ __('Annuler') }}
            </a>
        </div>
    </div>

</form>

@endsection

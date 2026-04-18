@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Modifier le terme')])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.dictionary.index') }}">Glossaire</a></li>
            <li class="breadcrumb-item active">Modifier</li>
        </ol>
    </nav>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Modifier le terme</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.dictionary.update', $term) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="d-flex justify-content-end mb-2">
                    <x-core::autosave-indicator :save-url="route('admin.dictionary.autosave', $term)" />
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $term->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="definition" class="form-label">Définition <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('definition') is-invalid @enderror" id="definition" name="definition" rows="5" required>{{ old('definition', $term->definition) }}</textarea>
                    @error('definition')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="acronym" @selected(old('type', $term->type) == 'acronym')>Acronyme</option>
                        <option value="ai_term" @selected(old('type', $term->type) == 'ai_term')>Terme IA</option>
                        <option value="explainer" @selected(old('type', $term->type) == 'explainer')>Explication</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="dictionary_category_id" class="form-label">Catégorie</label>
                    <select class="form-select @error('dictionary_category_id') is-invalid @enderror" id="dictionary_category_id" name="dictionary_category_id">
                        <option value="">-- Aucune catégorie --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('dictionary_category_id', $term->dictionary_category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('dictionary_category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_published" name="is_published" value="1" @checked(old('is_published', $term->is_published))>
                    <label class="form-check-label" for="is_published">Publié</label>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label">Ordre d'affichage</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $term->sort_order) }}">
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i> Mettre à jour
                    </button>
                    <a href="{{ route('admin.dictionary.index') }}" class="btn btn-outline-secondary">
                        <i data-lucide="arrow-left"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

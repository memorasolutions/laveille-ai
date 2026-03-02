<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::layouts.admin', ['title' => 'Modifier la page', 'subtitle' => 'Pages statiques'])

@section('content')

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.pages.preview', $page) }}" target="_blank"
       class="btn btn-sm btn-outline-info rounded-2 d-flex align-items-center gap-2">
        <i data-lucide="eye"></i>
        {{ __('Apercu') }}
    </a>
</div>

<form action="{{ route('admin.pages.update', $page->slug) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row gy-3">
        {{-- Colonne principale --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-semibold mb-0">Contenu</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">
                            Titre <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $page->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <x-editor::tiptap name="content" :value="old('content', $page->content ?? '')" label="Contenu" />
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-medium">Extrait</label>
                        <textarea name="excerpt" rows="3" maxlength="500"
                                  class="form-control">{{ old('excerpt', $page->excerpt) }}</textarea>
                        <div class="form-text">Résumé court affiché dans les listes (max 500 caractères)</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne latérale --}}
        <div class="col-lg-4">
            {{-- Publication --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="fw-semibold mb-0">Publication</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Statut</label>
                        <select name="status" class="form-select">
                            <option value="draft" {{ old('status', $page->status) === 'draft' ? 'selected' : '' }}>Brouillon</option>
                            <option value="published" {{ old('status', $page->status) === 'published' ? 'selected' : '' }}>Publié</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Template</label>
                        <select name="template" class="form-select">
                            @foreach(\Modules\Pages\Models\StaticPage::TEMPLATES as $key => $label)
                                <option value="{{ $key }}" {{ old('template', $page->template) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Mise en page utilisée pour l'affichage public</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Slug</label>
                        <input type="text" class="form-control bg-light text-muted"
                               value="{{ $page->slug }}" readonly>
                        <div class="form-text">Non modifiable après création</div>
                    </div>
                    <div class="d-flex gap-3 pt-2">
                        <button type="submit" class="btn btn-primary flex-fill">Enregistrer</button>
                        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary flex-fill text-center">Annuler</a>
                    </div>
                </div>
            </div>

            {{-- SEO --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-semibold mb-0">SEO</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Meta titre</label>
                        <input type="text" name="meta_title"
                               class="form-control"
                               value="{{ old('meta_title', $page->meta_title) }}" maxlength="255">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-medium">Meta description</label>
                        <textarea name="meta_description" rows="3" maxlength="500"
                                  class="form-control">{{ old('meta_description', $page->meta_description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

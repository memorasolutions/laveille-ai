@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Modifier la page', 'subtitle' => 'Pages statiques'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.pages.index') }}">{{ __('Pages') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Modifier') }}</li>
    </ol>
</nav>

@if($errors->any())
    <div class="alert alert-danger mb-4">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.pages.update', $page->slug) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row g-3">
        {{-- Colonne principale --}}
        <div class="col-xl-8">
            <div class="card mb-3">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="fw-bold mb-0">Contenu</h5>
                </div>
                <div class="p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Titre <span class="text-danger ms-1">*</span>
                        </label>
                        <input type="text" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $page->title) }}" required>
                        @error('title')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <x-editor::tiptap name="content" :value="old('content', $page->content ?? '')" label="Contenu" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Extrait</label>
                        <textarea name="excerpt" rows="3" maxlength="500"
                                  class="form-control"
                                  style="resize:none;">{{ old('excerpt', $page->excerpt) }}</textarea>
                        <div class="form-text text-muted">Résumé court affiché dans les listes (max 500 caractères)</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne latérale --}}
        <div class="col-xl-4">
            {{-- Publication --}}
            <div class="card mb-3">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="fw-semibold mb-0">Publication</h5>
                </div>
                <div class="p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Statut</label>
                        <select name="status" class="form-control">
                            <option value="draft" {{ old('status', $page->status) === 'draft' ? 'selected' : '' }}>Brouillon</option>
                            <option value="published" {{ old('status', $page->status) === 'published' ? 'selected' : '' }}>Publié</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Template</label>
                        <select name="template" class="form-control">
                            @foreach(\Modules\Pages\Models\StaticPage::TEMPLATES as $key => $label)
                                <option value="{{ $key }}" {{ old('template', $page->template) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted">Mise en page utilisée pour l'affichage public</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Slug</label>
                        <input type="text" class="form-control bg-light text-muted"
                               value="{{ $page->slug }}" readonly>
                        <div class="form-text text-muted">Non modifiable après création</div>
                    </div>
                    <div class="d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary text-center">Annuler</a>
                    </div>
                </div>
            </div>

            {{-- SEO --}}
            <div class="card mb-3">
                <div class="card-header py-3 px-4 border-bottom">
                    <h5 class="fw-semibold mb-0">SEO</h5>
                </div>
                <div class="p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Meta titre</label>
                        <input type="text" name="meta_title"
                               class="form-control"
                               value="{{ old('meta_title', $page->meta_title) }}" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Meta description</label>
                        <textarea name="meta_description" rows="3" maxlength="500"
                                  class="form-control"
                                  style="resize:none;">{{ old('meta_description', $page->meta_description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

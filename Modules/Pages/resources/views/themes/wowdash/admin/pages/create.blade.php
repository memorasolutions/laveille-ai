@extends('backoffice::layouts.admin', ['title' => 'Nouvelle page', 'subtitle' => 'Pages statiques'])

@section('content')

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-24" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('admin.pages.store') }}" method="POST">
    @csrf
    <div class="row gy-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Contenu</h6>
                </div>
                <div class="card-body">
                    <div class="mb-20">
                        <label class="form-label">Titre <span class="text-danger-main">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-20">
                        <x-editor::tiptap name="content" :value="old('content', '')" label="Contenu" />
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Extrait</label>
                        <textarea name="excerpt" class="form-control" rows="3" maxlength="500">{{ old('excerpt') }}</textarea>
                        <div class="form-text">Résumé court (max 500 caractères)</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-20">
                <div class="card-header">
                    <h6 class="mb-0">Publication</h6>
                </div>
                <div class="card-body">
                    <div class="mb-20">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                            <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Publié</option>
                        </select>
                    </div>
                    <div class="d-flex gap-3 mt-24">
                        <button type="submit" class="btn btn-primary-600">Enregistrer</button>
                        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">SEO</h6>
                </div>
                <div class="card-body">
                    <div class="mb-20">
                        <label class="form-label">Meta titre</label>
                        <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title') }}" maxlength="255">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Meta description</label>
                        <textarea name="meta_description" class="form-control" rows="3" maxlength="500">{{ old('meta_description') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

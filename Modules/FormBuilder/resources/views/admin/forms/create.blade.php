<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')
@section('title', 'Nouveau formulaire')

@section('content')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.formbuilder.forms.index') }}">Formulaires</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nouveau formulaire</li>
    </ol>
</nav>
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
    <h4 class="mb-3 mb-md-0">Nouveau formulaire</h4>
</div>

<form action="{{ route('admin.formbuilder.forms.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-lg-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" id="is_published" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_published">Publier immédiatement</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Créer</button>
                        <a href="{{ route('admin.formbuilder.forms.index') }}" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

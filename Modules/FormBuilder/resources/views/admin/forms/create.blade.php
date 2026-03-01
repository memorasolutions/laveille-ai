@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Nouveau formulaire'])

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
    <h4 class="mb-3 mb-md-0">Nouveau formulaire</h4>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.formbuilder.forms.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="is_published" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_published">Publier immédiatement</label>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary me-2">Créer</button>
                <a href="{{ route('admin.formbuilder.forms.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

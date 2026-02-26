@extends('backoffice::layouts.admin', ['title' => 'Modifier catégorie', 'subtitle' => 'Blog'])

@section('content')
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Modifier la catégorie</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.blog.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-20">
                <label for="name" class="form-label">Nom <span class="text-danger-main">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name', $category->name) }}" required maxlength="100">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-20">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-20">
                <label for="color" class="form-label">Couleur <span class="text-danger-main">*</span></label>
                <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror"
                       id="color" name="color" value="{{ old('color', $category->color) }}" required>
                @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-20">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active"
                           name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Catégorie active</label>
                </div>
            </div>

            <div class="d-flex gap-3 mt-24">
                <button type="submit" class="btn btn-primary-600">Mettre à jour</button>
                <a href="{{ route('admin.blog.categories.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

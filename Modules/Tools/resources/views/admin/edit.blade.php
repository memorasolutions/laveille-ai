<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Modifier l\'outil')])

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <h4 class="fw-bold mb-4">{{ __('Modifier') }} : {{ $tool->name }}</h4>
                <form action="{{ route('admin.tools.update', $tool) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Nom') }}</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $tool->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Description') }}</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $tool->description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">{{ __('Icône (emoji)') }}</label>
                        <input type="text" name="icon" class="form-control" value="{{ old('icon', $tool->icon) }}" maxlength="10">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">{{ __('Ordre d\'affichage') }}</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $tool->sort_order) }}">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <label class="d-flex align-items-center gap-2">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" {{ $tool->is_active ? 'checked' : '' }}>
                                {{ __('Actif') }}
                            </label>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
                        <a href="{{ route('admin.tools.index') }}" class="btn btn-outline-secondary">{{ __('Annuler') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

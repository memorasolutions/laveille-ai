<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Nouveau tableau'))

@section('content')
    @include('backoffice::themes.backend.components.breadcrumb', [
        'title' => __('Nouveau tableau'),
        'items' => [
            ['label' => 'Idées et votes'],
            ['label' => __('Tableaux'), 'url' => route('admin.roadmap.boards.index')],
            ['label' => __('Nouveau')],
        ],
    ])

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('Nouveau tableau') }}</h5>
                </div>
                <form method="POST" action="{{ route('admin.roadmap.boards.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('Nom') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">{{ __('Description') }}</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="color" class="form-label">{{ __('Couleur') }}</label>
                            <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" id="color" name="color" value="{{ old('color', '#3b82f6') }}">
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" {{ old('is_public') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_public">{{ __('Public') }}</label>
                            </div>
                            <small class="text-muted">{{ __('Si coché, ce tableau sera visible par tous les utilisateurs.') }}</small>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="plus" class="me-1"></i> {{ __('Créer') }}
                        </button>
                        <a href="{{ route('admin.roadmap.boards.index') }}" class="btn btn-secondary">{{ __('Annuler') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

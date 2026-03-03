<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Catégories cookies', 'subtitle' => 'Modifier'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.cookie-categories.index') }}">{{ __('Cookies GDPR') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Modifier') }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header border-bottom py-3 px-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h5 class="mb-0 fw-semibold">Modifier : {{ $category->label }}</h5>
            <a href="{{ route('admin.cookie-categories.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
                <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                Retour
            </a>
        </div>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.cookie-categories.update', $category) }}">
            @csrf
            @method('PUT')

            <div class="row g-4">

                <div class="col-12 col-md-6">
                    <label for="name" class="form-label fw-medium">
                        Nom (identifiant) <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" id="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $category->name) }}" required aria-required="true" autocomplete="off">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="label" class="form-label fw-medium">
                        Label affiché <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="label" id="label"
                        class="form-control @error('label') is-invalid @enderror"
                        value="{{ old('label', $category->label) }}" required aria-required="true" autocomplete="off">
                    @error('label')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="description" class="form-label fw-medium">Description</label>
                    <textarea name="description" id="description"
                        class="form-control"
                        rows="3">{{ old('description', $category->description) }}</textarea>
                </div>

                <div class="col-12 col-md-6">
                    <label for="order" class="form-label fw-medium">Ordre d'affichage</label>
                    <input type="number" name="order" id="order"
                        class="form-control"
                        style="max-width:120px;"
                        value="{{ old('order', $category->order) }}" min="0">
                </div>

                <div class="col-12">
                    <div class="d-flex flex-column gap-3">
                        <div class="border rounded p-3 d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <div class="fw-medium small">Obligatoire (ne peut être refusé)</div>
                                <div class="text-muted" style="font-size:0.8rem;">L'utilisateur ne pourra pas désactiver cette catégorie</div>
                            </div>
                            <input type="checkbox" name="required" id="required" value="1"
                                class="form-check-input"
                                {{ old('required', $category->required) ? 'checked' : '' }}>
                        </div>

                        <div class="border rounded p-3 d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <div class="fw-medium small">{{ __('Actif') }}</div>
                                <div class="text-muted" style="font-size:0.8rem;">La catégorie sera affichée dans le bandeau de consentement</div>
                            </div>
                            <input type="checkbox" name="is_active" id="is_active" value="1"
                                class="form-check-input"
                                {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

            </div>

            <div class="d-flex align-items-center gap-3 mt-4">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="save" style="width:16px;height:16px;"></i>
                    Enregistrer
                </button>
                <a href="{{ route('admin.cookie-categories.index') }}" class="btn btn-light">Annuler</a>
            </div>
        </form>
    </div>
</div>

@endsection

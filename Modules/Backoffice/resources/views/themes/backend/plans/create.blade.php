<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Créer un plan', 'subtitle' => 'SaaS'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.plans.index') }}">{{ __('Plans') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Créer') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="plus-circle" class="icon-md text-primary"></i>{{ __('Nouveau plan') }}</h4>
    <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
        <i data-lucide="arrow-left"></i> {{ __('Retour') }}
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.plans.store') }}" method="POST">
            @csrf

            <div class="row g-4">

                {{-- Nom --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">
                        Nom <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Slug --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">
                        Slug <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('slug') is-invalid @enderror"
                           id="slug" name="slug" value="{{ old('slug') }}" placeholder="ex: pro-monthly">
                    @error('slug')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <label class="form-label fw-medium">Description</label>
                    <textarea class="form-control"
                              name="description" rows="3">{{ old('description') }}</textarea>
                </div>

                {{-- Prix --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">
                        Prix <span class="text-danger">*</span>
                    </label>
                    <input type="number" step="0.01" min="0"
                           class="form-control @error('price') is-invalid @enderror"
                           name="price" value="{{ old('price', '0.00') }}" required>
                    @error('price')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Devise --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">Devise</label>
                    <select class="form-select" name="currency">
                        <option value="CAD" @selected(old('currency', 'CAD') === 'CAD')>CAD - Dollar canadien</option>
                        <option value="USD" @selected(old('currency', 'CAD') === 'USD')>USD - Dollar américain</option>
                        <option value="EUR" @selected(old('currency', 'CAD') === 'EUR')>EUR - Euro</option>
                        <option value="GBP" @selected(old('currency', 'CAD') === 'GBP')>GBP - Livre sterling</option>
                        <option value="CHF" @selected(old('currency', 'CAD') === 'CHF')>CHF - Franc suisse</option>
                    </select>
                </div>

                {{-- Intervalle --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">
                        Intervalle <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('interval') is-invalid @enderror"
                            name="interval" required>
                        <option value="monthly" {{ old('interval') === 'monthly' ? 'selected' : '' }}>Mensuel</option>
                        <option value="yearly"  {{ old('interval') === 'yearly'  ? 'selected' : '' }}>Annuel</option>
                        <option value="one_time" {{ old('interval') === 'one_time' ? 'selected' : '' }}>Paiement unique</option>
                    </select>
                    @error('interval')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Jours d'essai --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">Jours d'essai</label>
                    <input type="number" min="0"
                           class="form-control"
                           name="trial_days" value="{{ old('trial_days', 0) }}">
                </div>

                {{-- Ordre --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">Ordre</label>
                    <input type="number"
                           class="form-control"
                           name="sort_order" value="{{ old('sort_order', 0) }}">
                </div>

                {{-- Plan actif --}}
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check form-switch d-flex align-items-center gap-3">
                        <input class="form-check-input" type="checkbox" role="switch"
                               name="is_active" value="1" id="is_active" checked>
                        <label class="form-check-label" for="is_active">Plan actif</label>
                    </div>
                </div>

            </div>

            <div class="d-flex align-items-center gap-3 mt-4">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="plus"></i> Créer le plan
                </button>
                <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
                    <i data-lucide="arrow-left"></i> Retour
                </a>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    let manualSlug = false;

    if (!nameInput || !slugInput) return;

    function slugify(text) {
        return text
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    nameInput.addEventListener('input', function() {
        if (!manualSlug) {
            slugInput.value = slugify(nameInput.value);
        }
    });

    slugInput.addEventListener('input', function() {
        if (slugInput.value.trim() === '') {
            manualSlug = false;
        } else {
            manualSlug = true;
        }
    });

    slugInput.addEventListener('blur', function() {
        if (slugInput.value.trim() !== '') {
            slugInput.value = slugify(slugInput.value);
        }
    });
});
</script>
@endpush

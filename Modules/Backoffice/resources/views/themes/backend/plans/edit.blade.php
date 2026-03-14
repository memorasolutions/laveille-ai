<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Modifier le plan'), 'subtitle' => $plan->name])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.plans.index') }}">{{ __('Plans') }}</a></li>
        <li class="breadcrumb-item active">{{ $plan->name }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="credit-card" class="icon-md text-primary"></i>{{ $plan->name }}</h4>
    <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
        <i data-lucide="arrow-left"></i> {{ __('Retour') }}
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.plans.update', $plan) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-4">

                {{-- Nom --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">
                        {{ __('Nom') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           name="name" value="{{ old('name', $plan->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Slug --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">
                        {{ __('Slug') }} <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('slug') is-invalid @enderror"
                           name="slug" value="{{ old('slug', $plan->slug) }}">
                    @error('slug')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <label class="form-label fw-medium">{{ __('Description') }}</label>
                    <textarea class="form-control"
                              name="description" rows="3">{{ old('description', $plan->description) }}</textarea>
                </div>

                {{-- Prix --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">
                        {{ __('Prix') }} <span class="text-danger">*</span>
                    </label>
                    <input type="number" step="0.01" min="0"
                           class="form-control @error('price') is-invalid @enderror"
                           name="price" value="{{ old('price', $plan->price) }}" required>
                    @error('price')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Devise --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">{{ __('Devise') }}</label>
                    <select class="form-select" name="currency">
                        <option value="CAD" @selected(old('currency', $plan->currency) === 'CAD')>{{ __('CAD - Dollar canadien') }}</option>
                        <option value="USD" @selected(old('currency', $plan->currency) === 'USD')>{{ __('USD - Dollar américain') }}</option>
                        <option value="EUR" @selected(old('currency', $plan->currency) === 'EUR')>{{ __('EUR - Euro') }}</option>
                        <option value="GBP" @selected(old('currency', $plan->currency) === 'GBP')>{{ __('GBP - Livre sterling') }}</option>
                        <option value="CHF" @selected(old('currency', $plan->currency) === 'CHF')>{{ __('CHF - Franc suisse') }}</option>
                    </select>
                </div>

                {{-- Intervalle --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">
                        {{ __('Intervalle') }} <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('interval') is-invalid @enderror"
                            name="interval" required>
                        <option value="monthly"  {{ old('interval', $plan->interval) === 'monthly'  ? 'selected' : '' }}>{{ __('Mensuel') }}</option>
                        <option value="yearly"   {{ old('interval', $plan->interval) === 'yearly'   ? 'selected' : '' }}>{{ __('Annuel') }}</option>
                        <option value="one_time" {{ old('interval', $plan->interval) === 'one_time' ? 'selected' : '' }}>{{ __('Paiement unique') }}</option>
                    </select>
                    @error('interval')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Jours d'essai --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">{{ __('Jours d\'essai') }}</label>
                    <input type="number" min="0"
                           class="form-control"
                           name="trial_days" value="{{ old('trial_days', $plan->trial_days) }}">
                </div>

                {{-- Ordre --}}
                <div class="col-md-6">
                    <label class="form-label fw-medium">{{ __('Ordre') }}</label>
                    <input type="number"
                           class="form-control"
                           name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}">
                </div>

                {{-- Plan actif --}}
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check form-switch d-flex align-items-center gap-3">
                        <input class="form-check-input" type="checkbox" role="switch"
                               name="is_active" value="1" id="is_active"
                               {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">{{ __('Plan actif') }}</label>
                    </div>
                </div>

            </div>

            <div class="d-flex align-items-center gap-3 mt-4">
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="save"></i> {{ __('Mettre à jour') }}
                </button>
                <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-light d-inline-flex align-items-center gap-2">
                    <i data-lucide="arrow-left"></i> {{ __('Retour') }}
                </a>
            </div>
        </form>

        <hr class="my-4">

        <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST"
              onsubmit="return confirm('{{ __('Supprimer ce plan ? Cette action est irréversible.') }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-2">
                <i data-lucide="trash-2"></i> {{ __('Supprimer ce plan') }}
            </button>
        </form>
    </div>
</div>

@endsection

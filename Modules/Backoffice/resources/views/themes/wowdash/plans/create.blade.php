@extends('backoffice::layouts.admin', ['title' => 'Créer un plan', 'subtitle' => 'SaaS'])

@section('content')
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Nouveau plan</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.plans.store') }}" method="POST">
                @csrf
                <div class="row gy-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nom *</label>
                        <input type="text" class="form-control radius-8 @error('name') is-invalid @enderror"
                               name="name" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Slug *</label>
                        <input type="text" class="form-control radius-8 @error('slug') is-invalid @enderror"
                               name="slug" value="{{ old('slug') }}" placeholder="ex: pro-monthly">
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Description</label>
                        <textarea class="form-control radius-8" name="description" rows="3">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Prix *</label>
                        <input type="number" step="0.01" min="0" class="form-control radius-8 @error('price') is-invalid @enderror"
                               name="price" value="{{ old('price', '0.00') }}" required>
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Devise</label>
                        <select class="form-select radius-8" name="currency">
                            <option value="CAD" @selected(old('currency', 'CAD') === 'CAD')>CAD - Dollar canadien</option>
                            <option value="USD" @selected(old('currency', 'CAD') === 'USD')>USD - Dollar américain</option>
                            <option value="EUR" @selected(old('currency', 'CAD') === 'EUR')>EUR - Euro</option>
                            <option value="GBP" @selected(old('currency', 'CAD') === 'GBP')>GBP - Livre sterling</option>
                            <option value="CHF" @selected(old('currency', 'CAD') === 'CHF')>CHF - Franc suisse</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Intervalle *</label>
                        <select class="form-select radius-8 @error('interval') is-invalid @enderror" name="interval" required>
                            <option value="monthly" {{ old('interval') === 'monthly' ? 'selected' : '' }}>Mensuel</option>
                            <option value="yearly"  {{ old('interval') === 'yearly'  ? 'selected' : '' }}>Annuel</option>
                            <option value="one_time" {{ old('interval') === 'one_time' ? 'selected' : '' }}>Paiement unique</option>
                        </select>
                        @error('interval') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Jours d'essai</label>
                        <input type="number" min="0" class="form-control radius-8" name="trial_days" value="{{ old('trial_days', 0) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Ordre</label>
                        <input type="number" class="form-control radius-8" name="sort_order" value="{{ old('sort_order', 0) }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   name="is_active" value="1" id="is_active" checked>
                            <label class="form-check-label" for="is_active">Plan actif</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-3 mt-24">
                    <button type="submit" class="btn btn-primary-600">Créer le plan</button>
                    <a href="{{ route('admin.plans.index') }}" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">Retour</a>
                </div>
            </form>
        </div>
    </div>
@endsection

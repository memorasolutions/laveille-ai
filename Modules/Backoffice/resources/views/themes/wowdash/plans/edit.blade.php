@extends('backoffice::layouts.admin', ['title' => 'Modifier le plan', 'subtitle' => $plan->name])

@section('content')
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">{{ $plan->name }}</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.plans.update', $plan) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row gy-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nom *</label>
                        <input type="text" class="form-control radius-8 @error('name') is-invalid @enderror"
                               name="name" value="{{ old('name', $plan->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Slug *</label>
                        <input type="text" class="form-control radius-8 @error('slug') is-invalid @enderror"
                               name="slug" value="{{ old('slug', $plan->slug) }}">
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Description</label>
                        <textarea class="form-control radius-8" name="description" rows="3">{{ old('description', $plan->description) }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Prix *</label>
                        <input type="number" step="0.01" min="0" class="form-control radius-8 @error('price') is-invalid @enderror"
                               name="price" value="{{ old('price', $plan->price) }}" required>
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Devise</label>
                        <select class="form-select radius-8" name="currency">
                            <option value="CAD" @selected(old('currency', $plan->currency) === 'CAD')>CAD - Dollar canadien</option>
                            <option value="USD" @selected(old('currency', $plan->currency) === 'USD')>USD - Dollar américain</option>
                            <option value="EUR" @selected(old('currency', $plan->currency) === 'EUR')>EUR - Euro</option>
                            <option value="GBP" @selected(old('currency', $plan->currency) === 'GBP')>GBP - Livre sterling</option>
                            <option value="CHF" @selected(old('currency', $plan->currency) === 'CHF')>CHF - Franc suisse</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Intervalle *</label>
                        <select class="form-select radius-8 @error('interval') is-invalid @enderror" name="interval" required>
                            <option value="monthly"  {{ old('interval', $plan->interval) === 'monthly'  ? 'selected' : '' }}>Mensuel</option>
                            <option value="yearly"   {{ old('interval', $plan->interval) === 'yearly'   ? 'selected' : '' }}>Annuel</option>
                            <option value="one_time" {{ old('interval', $plan->interval) === 'one_time' ? 'selected' : '' }}>Paiement unique</option>
                        </select>
                        @error('interval') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Jours d'essai</label>
                        <input type="number" min="0" class="form-control radius-8" name="trial_days"
                               value="{{ old('trial_days', $plan->trial_days) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Ordre</label>
                        <input type="number" class="form-control radius-8" name="sort_order"
                               value="{{ old('sort_order', $plan->sort_order) }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   name="is_active" value="1" id="is_active"
                                   {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Plan actif</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-3 mt-24">
                    <button type="submit" class="btn btn-primary-600">Mettre à jour</button>
                    <a href="{{ route('admin.plans.index') }}" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">Retour</a>
                </div>
            </form>

            <hr class="my-4">

            <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST"
                  onsubmit="return confirm('Supprimer ce plan ? Cette action est irréversible.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger-600 radius-4 d-inline-flex align-items-center gap-1">
                    <iconify-icon icon="solar:trash-bin-minimalistic-outline"></iconify-icon> Supprimer ce plan
                </button>
            </form>
        </div>
    </div>
@endsection

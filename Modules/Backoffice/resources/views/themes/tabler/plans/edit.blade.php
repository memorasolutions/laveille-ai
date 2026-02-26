@extends('backoffice::layouts.admin', ['title' => 'Plans', 'subtitle' => 'Modifier'])

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Modifier le plan : {{ $plan->name }}</h3></div>
    <div class="card-body">
        <form action="{{ route('admin.plans.update', $plan) }}" method="POST">
            @csrf @method('PUT')
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label required">Nom</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $plan->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Slug</label>
                    <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $plan->slug) }}" required>
                    @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $plan->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label required">Prix</label>
                    <div class="input-group">
                        <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $plan->price) }}" step="0.01" min="0" required>
                        <span class="input-group-text">$</span>
                    </div>
                    @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label required">Intervalle</label>
                    <select name="interval" class="form-select @error('interval') is-invalid @enderror">
                        <option value="monthly" {{ old('interval', $plan->interval) == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                        <option value="yearly" {{ old('interval', $plan->interval) == 'yearly' ? 'selected' : '' }}>Annuel</option>
                    </select>
                    @error('interval') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Stripe Price ID</label>
                    <input type="text" name="stripe_price_id" class="form-control @error('stripe_price_id') is-invalid @enderror" value="{{ old('stripe_price_id', $plan->stripe_price_id) }}">
                    @error('stripe_price_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Ordre de tri</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $plan->sort_order) }}" min="0">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                        <span class="form-check-label">Actif</span>
                    </label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Fonctionnalités (JSON)</label>
                <textarea name="features" class="form-control font-monospace @error('features') is-invalid @enderror" rows="4">{{ old('features', is_array($plan->features) ? json_encode($plan->features, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $plan->features) }}</textarea>
                @error('features') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Enregistrer</button>
                <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-danger">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

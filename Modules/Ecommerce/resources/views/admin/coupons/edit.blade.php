@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Modifier le coupon', 'subtitle' => 'Boutique'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.dashboard') }}">Boutique</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.coupons.index') }}">Coupons</a></li>
        <li class="breadcrumb-item active" aria-current="page">Modifier</li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.ecommerce.coupons.update', $coupon) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code promo</label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $coupon->code) }}" required>
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" id="couponType" class="form-select">
                                <option value="fixed" {{ old('type', $coupon->type) === 'fixed' ? 'selected' : '' }}>Montant fixe</option>
                                <option value="percent" {{ old('type', $coupon->type) === 'percent' ? 'selected' : '' }}>Pourcentage (%)</option>
                                <option value="free_shipping" {{ old('type', $coupon->type) === 'free_shipping' ? 'selected' : '' }}>Livraison gratuite</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3" id="valueGroup">
                        <label class="form-label">Valeur</label>
                        <input type="number" step="0.01" name="value" class="form-control" value="{{ old('value', $coupon->value) }}">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Montant minimum de commande</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ config('modules.ecommerce.currency_symbol') }}</span>
                                <input type="number" step="0.01" name="min_order_amount" class="form-control" value="{{ old('min_order_amount', $coupon->min_order_amount) }}">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre max d'utilisations</label>
                            <input type="number" name="max_uses" class="form-control" value="{{ old('max_uses', $coupon->max_uses) }}" placeholder="Illimité">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date de début</label>
                            <input type="date" name="starts_at" class="form-control" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date d'expiration</label>
                            <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d')) }}">
                        </div>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Actif</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    <a href="{{ route('admin.ecommerce.coupons.index') }}" class="btn btn-secondary">Annuler</a>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('couponType').addEventListener('change', function() {
        document.getElementById('valueGroup').style.display = this.value === 'free_shipping' ? 'none' : 'block';
    });
    document.getElementById('couponType').dispatchEvent(new Event('change'));
</script>
@endpush

@endsection

<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Modifier la zone de livraison')

@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.shipping-zones.index') }}">Zones de livraison</a></li>
        <li class="breadcrumb-item active" aria-current="page">Modifier</li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Modifier : {{ $zone->name }}</h6>

                <form action="{{ route('admin.ecommerce.shipping-zones.update', $zone) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Nom de la zone</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $zone->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $zone->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Zone active</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Régions (provinces)</label>
                        <div class="row g-3">
                            @php
                                $provinces = [
                                    'AB' => 'Alberta', 'BC' => 'Colombie-Britannique', 'MB' => 'Manitoba',
                                    'NB' => 'Nouveau-Brunswick', 'NL' => 'Terre-Neuve-et-Labrador', 'NS' => 'Nouvelle-Écosse',
                                    'NT' => 'Territoires du Nord-Ouest', 'NU' => 'Nunavut', 'ON' => 'Ontario',
                                    'PE' => 'Île-du-Prince-Édouard', 'QC' => 'Québec', 'SK' => 'Saskatchewan', 'YT' => 'Yukon',
                                ];
                                $selectedRegions = old('regions', $zone->regions ?? []);
                            @endphp
                            @foreach($provinces as $code => $name)
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="region_{{ $code }}" name="regions[]" value="{{ $code }}" {{ in_array($code, $selectedRegions) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="region_{{ $code }}">{{ $name }} ({{ $code }})</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('regions')<div class="text-danger mt-1 small">{{ $message }}</div>@enderror
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title mb-0">Méthodes de livraison</h6>
                        <button type="button" class="btn btn-sm btn-secondary" id="add-method-btn">
                            <i data-lucide="plus" class="icon-sm me-1"></i> Ajouter une méthode
                        </button>
                    </div>

                    <div id="methods-container">
                        @foreach($zone->methods as $i => $method)
                        <div class="card mb-3 border method-row" id="method-row-existing-{{ $i }}">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <h6 class="fw-bold text-muted small">{{ $method->name }}</h6>
                                    <button type="button" class="btn btn-xs btn-danger btn-icon remove-method" data-target="method-row-existing-{{ $i }}">
                                        <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                                    </button>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small">Nom</label>
                                        <input type="text" name="methods[{{ $i }}][name]" class="form-control form-control-sm" value="{{ $method->name }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Type</label>
                                        <select name="methods[{{ $i }}][type]" class="form-select form-select-sm">
                                            <option value="flat_rate" {{ $method->type === 'flat_rate' ? 'selected' : '' }}>Forfaitaire</option>
                                            <option value="free" {{ $method->type === 'free' ? 'selected' : '' }}>Gratuit</option>
                                            <option value="per_weight" {{ $method->type === 'per_weight' ? 'selected' : '' }}>Par poids</option>
                                            <option value="percentage" {{ $method->type === 'percentage' ? 'selected' : '' }}>Pourcentage</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Coût</label>
                                        <input type="number" step="0.01" name="methods[{{ $i }}][cost]" class="form-control form-control-sm" value="{{ $method->cost }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Min.</label>
                                        <input type="number" step="0.01" name="methods[{{ $i }}][min_order]" class="form-control form-control-sm" value="{{ $method->min_order }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Max.</label>
                                        <input type="number" step="0.01" name="methods[{{ $i }}][max_order]" class="form-control form-control-sm" value="{{ $method->max_order }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">Mettre à jour</button>
                        <a href="{{ route('admin.ecommerce.shipping-zones.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('custom-scripts')
@include('ecommerce::admin.shipping-zones._methods-js')
@endpush
@endsection

<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Mes adresses'), 'subtitle' => __('Boutique')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">{{ __('Mon compte') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Mes adresses') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="map-pin" class="icon-md text-primary"></i> {{ __('Mes adresses') }}
    </h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
        <i data-lucide="plus" class="icon-sm me-1"></i> {{ __('Ajouter') }}
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
    </div>
@endif

<div class="row">
    @forelse($addresses as $address)
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header py-2 border-bottom d-flex justify-content-between align-items-center">
                <span class="badge bg-{{ $address->type === 'shipping' ? 'info' : 'primary' }}">{{ __($address->type === 'shipping' ? 'Livraison' : 'Facturation') }}</span>
                @if($address->is_default)<span class="badge bg-success">{{ __('Par défaut') }}</span>@endif
            </div>
            <div class="card-body">
                <p class="mb-1 fw-semibold">{{ $address->full_name }}</p>
                @if($address->company)<p class="mb-1 text-muted">{{ $address->company }}</p>@endif
                <p class="mb-1">{{ $address->address_line_1 }}</p>
                @if($address->address_line_2)<p class="mb-1">{{ $address->address_line_2 }}</p>@endif
                <p class="mb-1">{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</p>
                <p class="mb-0">{{ $address->country }}</p>
                @if($address->phone)<p class="mb-0 mt-1 text-muted"><i data-lucide="phone" class="icon-sm me-1"></i>{{ $address->phone }}</p>@endif
            </div>
            <div class="card-footer d-flex gap-2">
                <form action="{{ route('customer.addresses.destroy', $address) }}" method="POST" data-confirm="{{ __('Supprimer cette adresse ?') }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i data-lucide="trash-2" class="icon-sm"></i></button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-info">{{ __('Aucune adresse enregistrée.') }}</div>
    </div>
    @endforelse
</div>

{{-- Add address modal --}}
<div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('customer.addresses.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAddressLabel">{{ __('Nouvelle adresse') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Fermer') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Type') }} *</label>
                            <select name="type" class="form-select" required>
                                <option value="shipping">{{ __('Livraison') }}</option>
                                <option value="billing">{{ __('Facturation') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_default" value="1" class="form-check-input" id="isDefault">
                                <label class="form-check-label" for="isDefault">{{ __('Par défaut') }}</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Prénom') }} *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Nom') }} *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Entreprise') }}</label>
                            <input type="text" name="company" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Adresse') }} *</label>
                            <input type="text" name="address_line_1" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Adresse (suite)') }}</label>
                            <input type="text" name="address_line_2" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Ville') }} *</label>
                            <input type="text" name="city" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Province') }} *</label>
                            <input type="text" name="state" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Code postal') }} *</label>
                            <input type="text" name="postal_code" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Pays') }} *</label>
                            <input type="text" name="country" class="form-control" value="CA" required maxlength="2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Téléphone') }}</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

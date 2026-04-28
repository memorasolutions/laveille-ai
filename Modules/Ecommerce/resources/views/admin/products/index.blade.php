<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Produits'), 'subtitle' => __('Boutique')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.dashboard') }}">{{ __('Boutique') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Produits') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="package" class="icon-md text-primary"></i> {{ __('Produits') }}
    </h4>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <form action="{{ route('admin.ecommerce.products.index') }}" method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('Rechercher...') }}" value="{{ request('search') }}" style="width: 200px;">
            <button type="submit" class="btn btn-sm btn-outline-primary"><i data-lucide="search" class="icon-sm"></i></button>
        </form>
        <a href="{{ route('admin.ecommerce.products.create') }}" class="btn btn-primary btn-sm">
            <i data-lucide="plus" class="me-1"></i> {{ __('Nouveau produit') }}
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 50px;"></th>
                        <th>{{ __('Nom') }}</th>
                        <th>{{ __('SKU') }}</th>
                        <th>{{ __('Prix') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th class="text-end pe-4">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td class="ps-4">
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                <i data-lucide="image" class="text-muted icon-sm"></i>
                            </div>
                        </td>
                        <td class="fw-semibold">{{ $product->name }}</td>
                        <td><code>{{ $product->sku ?? '-' }}</code></td>
                        <td>{{ config('modules.ecommerce.currency_symbol') }}{{ number_format($product->price, 2) }}</td>
                        <td>
                            @if($product->is_active)
                                <span class="badge bg-success">{{ __('Actif') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('Inactif') }}</span>
                            @endif
                            @if($product->is_featured)
                                <span class="badge bg-warning text-dark ms-1"><i data-lucide="star" class="icon-xs"></i></span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.ecommerce.products.edit', $product) }}" class="btn btn-sm btn-light"><i data-lucide="edit-2" class="icon-sm"></i></a>
                            <form action="{{ route('admin.ecommerce.products.destroy', $product) }}" method="POST" class="d-inline" data-confirm="{{ __('Supprimer ce produit ?') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light text-danger"><i data-lucide="trash-2" class="icon-sm"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Aucun produit trouvé.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="px-4 py-3">{{ $products->links() }}</div>
        @endif
    </div>
</div>

@endsection

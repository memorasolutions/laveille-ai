@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Tableau de bord', 'subtitle' => 'Boutique'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Boutique</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="store" class="icon-md text-primary"></i> Tableau de bord e-commerce
    </h4>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

<div class="row mb-4">
    <div class="col-12 col-md-6 col-xl-3 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Chiffre d'affaires</p>
                        <h3 class="mb-0">{{ config('modules.ecommerce.currency_symbol') }}{{ number_format($totalRevenue, 2) }}</h3>
                    </div>
                    <div class="icon-lg bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center">
                        <i data-lucide="dollar-sign" class="icon-md"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Commandes</p>
                        <h3 class="mb-0">{{ $totalOrders }}</h3>
                    </div>
                    <div class="icon-lg bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center">
                        <i data-lucide="shopping-cart" class="icon-md"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Produits</p>
                        <h3 class="mb-0">{{ $totalProducts }}</h3>
                    </div>
                    <div class="icon-lg bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center">
                        <i data-lucide="package" class="icon-md"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Stock faible</p>
                        <h3 class="mb-0">{{ $lowStockVariants->count() }}</h3>
                    </div>
                    <div class="icon-lg bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center">
                        <i data-lucide="alert-triangle" class="icon-md"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Commandes récentes</h5>
                <a href="{{ route('admin.ecommerce.orders.index') }}" class="btn btn-primary btn-sm">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4"># Commande</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                            <tr>
                                <td class="ps-4">{{ $order->order_number }}</td>
                                <td>{{ $order->user->name ?? 'Invité' }}</td>
                                <td>{{ $order->created_at->format('d M Y') }}</td>
                                <td>
                                    @php
                                        $badges = [
                                            'pending' => 'bg-warning text-dark',
                                            'processing' => 'bg-info',
                                            'shipped' => 'bg-primary',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            'refunded' => 'bg-secondary',
                                        ];
                                    @endphp
                                    <span class="badge {{ $badges[$order->status] ?? 'bg-secondary' }}">{{ ucfirst($order->status) }}</span>
                                </td>
                                <td>{{ config('modules.ecommerce.currency_symbol') }}{{ number_format($order->total, 2) }}</td>
                                <td>
                                    <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="btn btn-icon btn-sm btn-light">
                                        <i data-lucide="eye" class="icon-sm"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Aucune commande.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Alertes stock</h5>
                <a href="{{ route('admin.ecommerce.products.index') }}" class="btn btn-outline-primary btn-sm">Gérer</a>
            </div>
            <div class="card-body">
                @if($lowStockVariants->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($lowStockVariants->take(5) as $variant)
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <h6 class="mb-0 text-truncate" style="max-width: 180px;">{{ $variant->product->name ?? '-' }}</h6>
                                <small class="text-muted">SKU : {{ $variant->sku }}</small>
                            </div>
                            <span class="badge bg-danger rounded-pill">{{ $variant->stock }}</span>
                        </div>
                        @endforeach
                    </div>
                    @if($lowStockVariants->count() > 5)
                        <p class="text-center text-muted mt-3 mb-0">+ {{ $lowStockVariants->count() - 5 }} autres</p>
                    @endif
                @else
                    <div class="text-center py-4">
                        <i data-lucide="check-circle" class="icon-lg text-success mb-2"></i>
                        <p class="text-muted mb-0">Stocks en ordre.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

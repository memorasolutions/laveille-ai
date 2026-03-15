<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Commandes', 'subtitle' => 'Boutique'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.dashboard') }}">Boutique</a></li>
        <li class="breadcrumb-item active" aria-current="page">Commandes</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="shopping-cart" class="icon-md text-primary"></i> Commandes
    </h4>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="row align-items-center">
            <div class="col-md-4">
                <form action="{{ route('admin.ecommerce.orders.index') }}" method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Rechercher par n° commande..." value="{{ request('search') }}" style="width: 200px;">
                    <button type="submit" class="btn btn-sm btn-outline-primary"><i data-lucide="search" class="icon-sm"></i></button>
                </form>
            </div>
            <div class="col-md-3">
                <form action="{{ route('admin.ecommerce.orders.index') }}" method="GET">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tous les statuts</option>
                        @foreach(['pending' => 'En attente', 'processing' => 'En traitement', 'shipped' => 'Expédié', 'completed' => 'Complété', 'cancelled' => 'Annulé', 'refunded' => 'Remboursé'] as $val => $label)
                            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4"># Commande</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th class="text-end">Total</th>
                        <th class="text-end pe-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="ps-4">
                            <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="fw-semibold">#{{ $order->order_number }}</a>
                        </td>
                        <td>{{ $order->user?->name ?? 'Invité' }}</td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
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
                        <td class="text-end">{{ config('modules.ecommerce.currency_symbol') }}{{ number_format($order->total, 2) }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="btn btn-sm btn-light"><i data-lucide="eye" class="icon-sm"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucune commande.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="px-4 py-3">{{ $orders->links() }}</div>
        @endif
    </div>
</div>

@endsection

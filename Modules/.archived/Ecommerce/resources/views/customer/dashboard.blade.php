<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Mon compte'), 'subtitle' => __('Boutique')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">{{ __('Mon compte') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Tableau de bord') }}</li>
    </ol>
</nav>

<h4 class="fw-bold mb-4 d-flex align-items-center gap-2">
    <i data-lucide="user" class="icon-md text-primary"></i> {{ __('Mon compte') }}
</h4>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i data-lucide="shopping-bag" class="icon-lg text-primary mb-2"></i>
                <h3 class="fw-bold">{{ $totalOrders }}</h3>
                <p class="text-muted mb-0">{{ __('Commandes totales') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i data-lucide="clock" class="icon-lg text-warning mb-2"></i>
                <h3 class="fw-bold">{{ $pendingCount }}</h3>
                <p class="text-muted mb-0">{{ __('En attente') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i data-lucide="map-pin" class="icon-lg text-info mb-2"></i>
                <a href="{{ route('customer.addresses') }}" class="btn btn-outline-primary btn-sm mt-2">{{ __('Mes adresses') }}</a>
            </div>
        </div>
    </div>
</div>

@if($recentOrders->isNotEmpty())
<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('Commandes récentes') }}</h5>
            <a href="{{ route('customer.orders') }}" class="btn btn-sm btn-outline-primary">{{ __('Voir tout') }}</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Numéro') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                    <tr>
                        <td class="fw-semibold">{{ $order->order_number }}</td>
                        <td>{{ $order->created_at->format('d/m/Y') }}</td>
                        <td>
                            @php
                                $badges = ['pending' => 'warning', 'paid' => 'success', 'shipped' => 'info', 'delivered' => 'primary', 'cancelled' => 'danger', 'refunded' => 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $badges[$order->status] ?? 'secondary' }}">{{ __('ecommerce.status.' . $order->status) }}</span>
                        </td>
                        <td class="text-end">{{ number_format((float) $order->total, 2) }} $</td>
                        <td><a href="{{ route('customer.orders.show', $order) }}" class="btn btn-sm btn-outline-primary"><i data-lucide="eye" class="icon-sm"></i></a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="alert alert-info">{{ __('Aucune commande pour le moment.') }}</div>
@endif

@endsection

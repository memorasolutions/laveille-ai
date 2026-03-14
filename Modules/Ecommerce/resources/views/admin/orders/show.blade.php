@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Commande #' . $order->order_number, 'subtitle' => 'Boutique'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.dashboard') }}">Boutique</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.orders.index') }}">Commandes</a></li>
        <li class="breadcrumb-item active" aria-current="page">#{{ $order->order_number }}</li>
    </ol>
</nav>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Articles commandés</h5>
                @php
                    $badges = [
                        'pending' => 'bg-warning text-dark', 'processing' => 'bg-info',
                        'shipped' => 'bg-primary', 'completed' => 'bg-success',
                        'cancelled' => 'bg-danger', 'refunded' => 'bg-secondary',
                    ];
                @endphp
                <span class="badge {{ $badges[$order->status] ?? 'bg-secondary' }}">{{ ucfirst($order->status) }}</span>
            </div>
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Produit</th>
                            <th class="text-center">Qté</th>
                            <th class="text-end">Prix unitaire</th>
                            <th class="text-end pe-4">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td class="ps-4">{{ $item->product_name }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ config('modules.ecommerce.currency_symbol') }}{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end pe-4">{{ config('modules.ecommerce.currency_symbol') }}{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @if($order->subtotal)
                        <tr><td colspan="3" class="text-end">Sous-total</td><td class="text-end pe-4">{{ config('modules.ecommerce.currency_symbol') }}{{ number_format($order->subtotal, 2) }}</td></tr>
                        @endif
                        @if($order->tax_amount)
                        <tr><td colspan="3" class="text-end">Taxes</td><td class="text-end pe-4">{{ config('modules.ecommerce.currency_symbol') }}{{ number_format($order->tax_amount, 2) }}</td></tr>
                        @endif
                        @if($order->shipping_amount)
                        <tr><td colspan="3" class="text-end">Livraison</td><td class="text-end pe-4">{{ config('modules.ecommerce.currency_symbol') }}{{ number_format($order->shipping_amount, 2) }}</td></tr>
                        @endif
                        @if($order->discount_amount)
                        <tr><td colspan="3" class="text-end text-success">Rabais</td><td class="text-end pe-4 text-success">-{{ config('modules.ecommerce.currency_symbol') }}{{ number_format($order->discount_amount, 2) }}</td></tr>
                        @endif
                        <tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end pe-4">{{ config('modules.ecommerce.currency_symbol') }}{{ number_format($order->total, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title">Informations</h6>
                <p class="mb-1"><strong>Commande :</strong> #{{ $order->order_number }}</p>
                <p class="mb-1"><strong>Date :</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                <p class="mb-1"><strong>Client :</strong> {{ $order->user?->name ?? 'Invité' }}</p>
                @if($order->user)
                    <p class="mb-0"><small class="text-muted">{{ $order->user->email }}</small></p>
                @endif
            </div>
        </div>

        @if($order->shippingAddress)
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title">Adresse de livraison</h6>
                <address class="mb-0">
                    {{ $order->shippingAddress->first_name }} {{ $order->shippingAddress->last_name }}<br>
                    {{ $order->shippingAddress->address_line_1 }}<br>
                    @if($order->shippingAddress->address_line_2){{ $order->shippingAddress->address_line_2 }}<br>@endif
                    {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->postal_code }}<br>
                    {{ $order->shippingAddress->country }}
                </address>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Changer le statut</h6>
                <form action="{{ route('admin.ecommerce.orders.update-status', $order) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <select name="status" class="form-select">
                            @foreach(['pending' => 'En attente', 'processing' => 'En traitement', 'shipped' => 'Expédié', 'completed' => 'Complété', 'cancelled' => 'Annulé', 'refunded' => 'Remboursé'] as $val => $label)
                                <option value="{{ $val }}" {{ $order->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Mettre à jour</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

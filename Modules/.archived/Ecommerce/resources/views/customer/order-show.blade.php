<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Commande') . ' ' . $order->order_number, 'subtitle' => __('Boutique')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">{{ __('Mon compte') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customer.orders') }}">{{ __('Mes commandes') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $order->order_number }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="file-text" class="icon-md text-primary"></i> {{ __('Commande') }} {{ $order->order_number }}
    </h4>
    @php
        $badges = ['pending' => 'warning', 'paid' => 'success', 'shipped' => 'info', 'delivered' => 'primary', 'cancelled' => 'danger', 'refunded' => 'secondary'];
    @endphp
    <span class="badge bg-{{ $badges[$order->status] ?? 'secondary' }} fs-6">{{ __('ecommerce.status.' . $order->status) }}</span>
</div>

<div class="row">
    {{-- Order items --}}
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header py-3 border-bottom"><h5 class="mb-0">{{ __('Articles') }}</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Produit') }}</th>
                                <th>{{ __('Variante') }}</th>
                                <th class="text-center">{{ __('Qté') }}</th>
                                <th class="text-end">{{ __('Prix') }}</th>
                                <th class="text-end">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->variant_label }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">{{ number_format((float) $item->price, 2) }} $</td>
                                <td class="text-end">{{ number_format((float) $item->total, 2) }} $</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-semibold">{{ __('Sous-total') }}</td>
                                <td class="text-end">{{ number_format((float) $order->subtotal, 2) }} $</td>
                            </tr>
                            @if((float) $order->shipping_cost > 0)
                            <tr>
                                <td colspan="4" class="text-end">{{ __('Livraison') }}</td>
                                <td class="text-end">{{ number_format((float) $order->shipping_cost, 2) }} $</td>
                            </tr>
                            @endif
                            @if((float) $order->tax_amount > 0)
                            <tr>
                                <td colspan="4" class="text-end">{{ __('Taxes') }}</td>
                                <td class="text-end">{{ number_format((float) $order->tax_amount, 2) }} $</td>
                            </tr>
                            @endif
                            @if((float) $order->discount_amount > 0)
                            <tr>
                                <td colspan="4" class="text-end text-success">{{ __('Rabais') }}</td>
                                <td class="text-end text-success">-{{ number_format((float) $order->discount_amount, 2) }} $</td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="4" class="text-end fw-bold fs-5">{{ __('Total') }}</td>
                                <td class="text-end fw-bold fs-5">{{ number_format((float) $order->total, 2) }} $</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tracking --}}
        @if($order->tracking_number)
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="fw-bold"><i data-lucide="truck" class="icon-sm me-1"></i> {{ __('Suivi de livraison') }}</h6>
                <p class="mb-0">{{ __('Numéro de suivi') }} : <strong>{{ $order->tracking_number }}</strong></p>
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar info --}}
    <div class="col-lg-4">
        {{-- Dates --}}
        <div class="card mb-3">
            <div class="card-header py-3 border-bottom"><h6 class="mb-0">{{ __('Dates') }}</h6></div>
            <div class="card-body">
                <p class="mb-1"><strong>{{ __('Commandé le') }} :</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                @if($order->paid_at)
                    <p class="mb-1"><strong>{{ __('Payé le') }} :</strong> {{ $order->paid_at->format('d/m/Y H:i') }}</p>
                @endif
                @if($order->shipped_at)
                    <p class="mb-1"><strong>{{ __('Expédié le') }} :</strong> {{ $order->shipped_at->format('d/m/Y H:i') }}</p>
                @endif
                @if($order->delivered_at)
                    <p class="mb-1"><strong>{{ __('Livré le') }} :</strong> {{ $order->delivered_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        </div>

        {{-- Shipping address --}}
        @if($order->shippingAddress)
        <div class="card mb-3">
            <div class="card-header py-3 border-bottom"><h6 class="mb-0">{{ __('Adresse de livraison') }}</h6></div>
            <div class="card-body">
                <p class="mb-0">{{ $order->shippingAddress->full_name }}<br>
                {{ $order->shippingAddress->address_line_1 }}<br>
                @if($order->shippingAddress->address_line_2){{ $order->shippingAddress->address_line_2 }}<br>@endif
                {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->postal_code }}<br>
                {{ $order->shippingAddress->country }}</p>
            </div>
        </div>
        @endif

        {{-- Billing address --}}
        @if($order->billingAddress)
        <div class="card mb-3">
            <div class="card-header py-3 border-bottom"><h6 class="mb-0">{{ __('Adresse de facturation') }}</h6></div>
            <div class="card-body">
                <p class="mb-0">{{ $order->billingAddress->full_name }}<br>
                {{ $order->billingAddress->address_line_1 }}<br>
                @if($order->billingAddress->address_line_2){{ $order->billingAddress->address_line_2 }}<br>@endif
                {{ $order->billingAddress->city }}, {{ $order->billingAddress->state }} {{ $order->billingAddress->postal_code }}<br>
                {{ $order->billingAddress->country }}</p>
            </div>
        </div>
        @endif

        {{-- Refunds --}}
        @if($order->refunds->isNotEmpty())
        <div class="card">
            <div class="card-header py-3 border-bottom"><h6 class="mb-0">{{ __('Remboursements') }}</h6></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($order->refunds as $refund)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ number_format((float) $refund->amount, 2) }} $</span>
                        @php
                            $refBadges = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                        @endphp
                        <span class="badge bg-{{ $refBadges[$refund->status] ?? 'secondary' }}">{{ __($refund->status) }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Mes commandes'), 'subtitle' => __('Boutique')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">{{ __('Mon compte') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Mes commandes') }}</li>
    </ol>
</nav>

<h4 class="fw-bold mb-4 d-flex align-items-center gap-2">
    <i data-lucide="shopping-bag" class="icon-md text-primary"></i> {{ __('Mes commandes') }}
</h4>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Numéro') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Articles') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="fw-semibold">{{ $order->order_number }}</td>
                        <td>{{ $order->created_at->format('d/m/Y') }}</td>
                        <td>{{ $order->items_count ?? $order->items()->count() }}</td>
                        <td>
                            @php
                                $badges = ['pending' => 'warning', 'paid' => 'success', 'shipped' => 'info', 'delivered' => 'primary', 'cancelled' => 'danger', 'refunded' => 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $badges[$order->status] ?? 'secondary' }}">{{ __('ecommerce.status.' . $order->status) }}</span>
                        </td>
                        <td class="text-end">{{ number_format((float) $order->total, 2) }} $</td>
                        <td>
                            <a href="{{ route('customer.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                <i data-lucide="eye" class="icon-sm"></i> {{ __('Détails') }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">{{ __('Aucune commande pour le moment.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($orders->hasPages())
<div class="d-flex justify-content-center mt-3">
    {{ $orders->links() }}
</div>
@endif

@endsection

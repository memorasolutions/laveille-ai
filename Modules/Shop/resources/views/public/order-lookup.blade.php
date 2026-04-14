@extends(fronttheme_layout())

@section('title', __('Suivi de commande'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
<link rel="stylesheet" href="/css/shop.css">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Suivi de commande'), 'breadcrumbItems' => [__('Boutique'), __('Suivi de commande')]])
@endsection

@section('content')
<div class="container sp-container">
    @if(!isset($order))
        {{-- Formulaire de recherche --}}
        <div class="sp-lookup-box">
            <div class="sp-summary-box" style="position: static;">
                <h2 class="sp-order-title" style="text-align: center; margin-bottom: 16px;">{{ __('Suivre ma commande') }}</h2>
                <p style="color: var(--c-text-muted); font-size: 14px; text-align: center; margin-bottom: 20px;">{{ __('Entrez votre numéro de commande et votre courriel pour consulter le statut.') }}</p>

                @if(session('error'))
                    <div class="sp-error-inline">{{ session('error') }}</div>
                @endif

                <form action="{{ route('shop.order-lookup.search') }}" method="POST">
                    @csrf
                    <div class="sp-form-group">
                        <label class="sp-form-label">{{ __('Numéro de commande') }}</label>
                        <input type="number" name="order_id" required autocomplete="off" class="sp-form-input">
                    </div>
                    <div class="sp-form-group" style="margin-bottom: 16px;">
                        <label class="sp-form-label">{{ __('Courriel') }}</label>
                        <input type="email" name="email" required autocomplete="email" class="sp-form-input">
                    </div>
                    <button type="submit" class="sp-btn-primary sp-btn-full">{{ __('Rechercher ma commande') }}</button>
                </form>
            </div>
        </div>
    @else
        {{-- Affichage commande --}}
        <div class="sp-order-box">
            <div class="sp-summary-box" style="position: static;">
                <div class="sp-order-header">
                    <h2 class="sp-order-title">{{ __('Commande') }} #{{ $order->order_number ?? $order->id }}</h2>
                    @php
                        $statusColors = ['paid' => '#0CA678', 'processing' => '#0B7285', 'shipped' => '#3b82f6', 'fulfilled' => '#0CA678', 'pending' => '#f59e0b', 'cancelled' => '#ef4444', 'gelato_failed' => '#ef4444'];
                        $bg = $statusColors[$order->status] ?? '#94a3b8';
                    @endphp
                    <span class="sp-status-badge" style="background: {{ $bg }};">{{ ['pending' => 'En attente', 'paid' => 'Payé', 'shipped' => 'Expédié', 'fulfilled' => 'Complété', 'cancelled' => 'Annulé', 'refunded' => 'Remboursé'][$order->status] ?? ucfirst($order->status) }}</span>
                </div>

                <div class="sp-order-date">
                    {{ __('Passée le') }} {{ $order->created_at->format('j F Y à H:i') }}
                </div>

                {{-- Items --}}
                @foreach($order->items as $item)
                    <div class="sp-order-item">
                        @if($item->product && !empty($item->product->images[0]))
                            <img src="{{ asset($item->product->images[0]) }}" alt="" class="sp-order-item-img">
                        @endif
                        <div style="flex: 1;">
                            <div class="sp-order-item-name">{{ $item->product?->name ?? __('Produit') }}</div>
                            @if($item->variant_label)<div class="sp-order-item-variant">{{ $item->variant_label }}</div>@endif
                        </div>
                        <div class="sp-order-item-qty">x{{ $item->quantity }}</div>
                        <div class="sp-order-item-price">{{ number_format($item->unit_price * $item->quantity, 2, ',', ' ') }} $</div>
                    </div>
                @endforeach

                {{-- Totaux --}}
                <div class="sp-totals-block">
                    <div class="sp-total-row">
                        <span>{{ __('Sous-total') }}</span>
                        <span>{{ number_format($order->subtotal, 2, ',', ' ') }} $</span>
                    </div>
                    <div class="sp-total-row sp-total-row-muted">
                        <span>{{ __('Taxes') }}</span>
                        <span>{{ number_format($order->tax_amount, 2, ',', ' ') }} $</span>
                    </div>
                    @if($order->shipping_cost > 0)
                    <div class="sp-total-row sp-total-row-muted">
                        <span>{{ __('Livraison') }}</span>
                        <span>{{ number_format($order->shipping_cost, 2, ',', ' ') }} $</span>
                    </div>
                    @endif
                    <div class="sp-total-row sp-total-row-grand">
                        <span>{{ __('Total') }}</span>
                        <span class="sp-total-value-primary">{{ number_format($order->total, 2, ',', ' ') }} $</span>
                    </div>
                </div>

                {{-- Tracking --}}
                @if($order->tracking_url)
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener" class="sp-btn-primary">
                            <i class="fa fa-truck"></i> {{ __('Suivre mon colis') }}
                        </a>
                    </div>
                @endif
            </div>

            <div style="text-align: center; margin-top: 16px;">
                <a href="{{ route('shop.order-lookup') }}" style="color: var(--c-primary); font-weight: 600; text-decoration: none;">{{ __('Nouvelle recherche') }}</a>
            </div>
        </div>
    @endif
</div>
@endsection

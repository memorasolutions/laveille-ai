@extends(fronttheme_layout())

@section('title', __('Suivi de commande'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Suivi de commande'), 'breadcrumbItems' => [__('Boutique'), __('Suivi de commande')]])
@endsection

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 40px;">
    @if(!isset($order))
        {{-- Formulaire de recherche --}}
        <div style="max-width: 480px; margin: 0 auto;">
            <div style="background: #fff; border-radius: 8px; padding: 24px; border: 1px solid #e2e8f0;">
                <h2 style="font-size: 22px; font-weight: 700; margin: 0 0 16px; text-align: center;">{{ __('Suivre ma commande') }}</h2>
                <p style="color: #64748b; font-size: 14px; text-align: center; margin-bottom: 20px;">{{ __('Entrez votre numéro de commande et votre courriel pour consulter le statut.') }}</p>

                @if(session('error'))
                    <div style="background: #fef2f2; color: #dc2626; padding: 10px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 14px;">{{ session('error') }}</div>
                @endif

                <form action="{{ route('shop.order-lookup.search') }}" method="POST">
                    @csrf
                    <div style="margin-bottom: 12px;">
                        <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 4px;">{{ __('Numéro de commande') }}</label>
                        <input type="number" name="order_id" required autocomplete="off" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 4px;">{{ __('Courriel') }}</label>
                        <input type="email" name="email" required autocomplete="email" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                    </div>
                    <button type="submit" style="background: #0B7285; color: #fff; width: 100%; padding: 12px; border-radius: 6px; font-weight: 700; font-size: 15px; border: none; cursor: pointer;">{{ __('Rechercher ma commande') }}</button>
                </form>
            </div>
        </div>
    @else
        {{-- Affichage commande --}}
        <div style="max-width: 640px; margin: 0 auto;">
            <div style="background: #fff; border-radius: 8px; padding: 24px; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h2 style="font-size: 22px; font-weight: 700; margin: 0;">{{ __('Commande') }} #{{ $order->id }}</h2>
                    @php
                        $statusColors = ['paid' => '#0CA678', 'processing' => '#0B7285', 'shipped' => '#3b82f6', 'fulfilled' => '#0CA678', 'pending' => '#f59e0b', 'cancelled' => '#ef4444', 'gelato_failed' => '#ef4444'];
                        $bg = $statusColors[$order->status] ?? '#94a3b8';
                    @endphp
                    <span style="padding: 4px 12px; border-radius: 20px; background: {{ $bg }}; color: #fff; font-size: 13px; font-weight: 600;">{{ ['pending' => 'En attente', 'paid' => 'Payé', 'shipped' => 'Expédié', 'fulfilled' => 'Complété', 'cancelled' => 'Annulé', 'refunded' => 'Remboursé'][$order->status] ?? ucfirst($order->status) }}</span>
                </div>

                <div style="color: #64748b; font-size: 13px; margin-bottom: 16px;">
                    {{ __('Passée le') }} {{ $order->created_at->format('j F Y à H:i') }}
                </div>

                {{-- Items --}}
                @foreach($order->items as $item)
                    <div style="display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9;">
                        @if($item->product && !empty($item->product->images[0]))
                            <img src="{{ asset($item->product->images[0]) }}" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 12px;">
                        @endif
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 14px;">{{ $item->product?->name ?? __('Produit') }}</div>
                            @if($item->variant_label)<div style="font-size: 12px; color: #94a3b8;">{{ $item->variant_label }}</div>@endif
                        </div>
                        <div style="font-size: 14px; color: #475569;">x{{ $item->quantity }}</div>
                        <div style="font-weight: 600; font-size: 14px; margin-left: 16px;">{{ number_format($item->unit_price * $item->quantity, 2, ',', ' ') }} $</div>
                    </div>
                @endforeach

                {{-- Totaux --}}
                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 14px;">
                        <span>{{ __('Sous-total') }}</span>
                        <span>{{ number_format($order->subtotal, 2, ',', ' ') }} $</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 14px; color: #64748b;">
                        <span>{{ __('Taxes') }}</span>
                        <span>{{ number_format($order->tax_amount, 2, ',', ' ') }} $</span>
                    </div>
                    @if($order->shipping_cost > 0)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 14px; color: #64748b;">
                        <span>{{ __('Livraison') }}</span>
                        <span>{{ number_format($order->shipping_cost, 2, ',', ' ') }} $</span>
                    </div>
                    @endif
                    <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: 700; margin-top: 8px;">
                        <span>{{ __('Total') }}</span>
                        <span style="color: #0B7285;">{{ number_format($order->total, 2, ',', ' ') }} $</span>
                    </div>
                </div>

                {{-- Tracking --}}
                @if($order->tracking_url)
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener" style="display: inline-block; background: #0B7285; color: #fff; padding: 10px 24px; border-radius: 6px; font-weight: 600; text-decoration: none;">
                            <i class="fa fa-truck"></i> {{ __('Suivre mon colis') }}
                        </a>
                    </div>
                @endif
            </div>

            <div style="text-align: center; margin-top: 16px;">
                <a href="{{ route('shop.order-lookup') }}" style="color: #0B7285; font-weight: 600; text-decoration: none;">{{ __('Nouvelle recherche') }}</a>
            </div>
        </div>
    @endif
</div>
@endsection

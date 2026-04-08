@extends(fronttheme_layout())

@section('title', __('Paiement'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Paiement'), 'breadcrumbItems' => [__('Boutique'), __('Paiement')]])
@endsection

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 40px;">
    <div class="row">
        {{-- Résumé commande --}}
        <div class="col-md-5" style="margin-bottom: 24px;">
            <div style="background: #fff; border-radius: 8px; padding: 20px; border: 1px solid #e2e8f0;">
                <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 16px;">{{ __('Résumé de commande') }}</h2>
                @foreach($order->items as $item)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
                        <span>{{ $item->product?->name ?? __('Produit') }}@if($item->variant_label) <span style="color: #94a3b8;">({{ $item->variant_label }})</span>@endif <span style="color: #64748b;">x{{ $item->quantity }}</span></span>
                        <span style="font-weight: 600;">{{ number_format($item->unit_price * $item->quantity, 2, ',', ' ') }} $</span>
                    </div>
                @endforeach
                <hr style="margin: 12px 0; border-color: #e2e8f0;">
                <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 4px;">
                    <span>{{ __('Sous-total') }}</span>
                    <span>{{ number_format($order->subtotal, 2, ',', ' ') }} $</span>
                </div>
                @if($order->tax_amount > 0)
                @php $tpsAmt = round($order->subtotal * config('shop.tax.tps', 5) / 100, 2); $tvqAmt = round($order->subtotal * config('shop.tax.tvq', 9.975) / 100, 2); @endphp
                <div style="display: flex; justify-content: space-between; font-size: 13px; color: #64748b; margin-bottom: 3px;">
                    <span>{{ __('TPS') }} (5%) <span style="color: #94a3b8; font-size: 11px;">839145984</span></span>
                    <span>{{ number_format($tpsAmt, 2, ',', ' ') }} $</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px; color: #64748b; margin-bottom: 4px;">
                    <span>{{ __('TVQ') }} (9,975%) <span style="color: #94a3b8; font-size: 11px;">1221788059</span></span>
                    <span>{{ number_format($tvqAmt, 2, ',', ' ') }} $</span>
                </div>
                @endif
                @if($order->shipping_cost > 0)
                <div style="display: flex; justify-content: space-between; font-size: 14px; color: #64748b; margin-bottom: 4px;">
                    <span>{{ __('Livraison') }}</span>
                    <span>{{ number_format($order->shipping_cost, 2, ',', ' ') }} $</span>
                </div>
                @endif
                <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: 700; margin-top: 12px; padding-top: 12px; border-top: 2px solid #e2e8f0;">
                    <span>{{ __('Total') }}</span>
                    <span style="color: #0B7285;">{{ number_format($order->total, 2, ',', ' ') }} $</span>
                </div>
            </div>
        </div>

        {{-- Formulaire Stripe embedded --}}
        <div class="col-md-7">
            <div style="background: #fff; border-radius: 8px; padding: 20px; border: 1px solid #e2e8f0;">
                <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 16px;">
                    <i class="ti-lock" style="margin-right: 8px; color: #0CA678;"></i>{{ __('Paiement sécurisé') }}
                </h2>
                <div id="checkout"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
(async function() {
    const stripe = Stripe('{{ $stripeKey }}');
    const checkout = await stripe.initEmbeddedCheckout({clientSecret: '{{ $clientSecret }}'});
    checkout.mount('#checkout');
})();
</script>
@endpush

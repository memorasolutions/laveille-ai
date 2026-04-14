@extends(fronttheme_layout())

@section('title', __('Paiement'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
<link rel="stylesheet" href="/css/shop.css">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Paiement'), 'breadcrumbItems' => [__('Boutique'), __('Paiement')]])
@endsection

@section('content')
<div class="container sp-container">
    <div class="row">
        {{-- Résumé commande --}}
        <div class="col-md-5" style="margin-bottom: 24px;">
            <div class="sp-summary-box" style="position: static;">
                <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 16px; font-family: var(--f-heading); color: var(--c-dark);">{{ __('Résumé de commande') }}</h2>
                @foreach($order->items as $item)
                    <div class="sp-summary-row">
                        <span>{{ $item->product?->name ?? __('Produit') }}@if($item->variant_label) <span style="color: var(--c-text-muted);">({{ $item->variant_label }})</span>@endif <span style="color: var(--c-text-muted);">x{{ $item->quantity }}</span></span>
                        <span style="font-weight: 600;">{{ number_format($item->unit_price * $item->quantity, 2, ',', ' ') }} $</span>
                    </div>
                @endforeach
                <hr style="margin: 12px 0; border-color: #E5E7EB;">
                <div class="sp-summary-row">
                    <span>{{ __('Sous-total') }}</span>
                    <span>{{ number_format($order->subtotal, 2, ',', ' ') }} $</span>
                </div>
                @if($order->tax_amount > 0)
                @php $tpsAmt = round($order->subtotal * config('shop.tax.tps', 5) / 100, 2); $tvqAmt = round($order->subtotal * config('shop.tax.tvq', 9.975) / 100, 2); @endphp
                <div class="sp-tax-row">
                    <span>{{ __('TPS') }} (5%) <span class="sp-tax-number">839145984</span></span>
                    <span>{{ number_format($tpsAmt, 2, ',', ' ') }} $</span>
                </div>
                <div class="sp-tax-row">
                    <span>{{ __('TVQ') }} (9,975%) <span class="sp-tax-number">1221788059</span></span>
                    <span>{{ number_format($tvqAmt, 2, ',', ' ') }} $</span>
                </div>
                @endif
                @if($order->shipping_cost > 0)
                <div class="sp-summary-row" style="color: var(--c-text-muted);">
                    <span>{{ __('Livraison') }}</span>
                    <span>{{ number_format($order->shipping_cost, 2, ',', ' ') }} $</span>
                </div>
                @endif
                <div class="sp-summary-total" style="margin-top: 12px; padding-top: 12px; border-top: 2px solid #E5E7EB;">
                    <span>{{ __('Total') }}</span>
                    <span class="sp-summary-total-value">{{ number_format($order->total, 2, ',', ' ') }} $</span>
                </div>
            </div>
        </div>

        {{-- Formulaire Stripe embedded --}}
        <div class="col-md-7">
            <div class="sp-summary-box" style="position: static;">
                <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 16px; font-family: var(--f-heading); color: var(--c-dark);">
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

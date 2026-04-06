@extends(fronttheme_layout())

@section('title', __('Panier'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 40px;">
    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 24px;">{{ __('Panier') }} ({{ $itemCount ?? 0 }})</h1>

    @if(empty($content))
        <div style="text-align: center; padding: 60px 0;">
            <p style="font-size: 18px; color: #64748b; margin-bottom: 20px;">{{ __('Votre panier est vide.') }}</p>
            <a href="{{ route('shop.index') }}" class="btn" style="background: #0B7285; color: #fff; padding: 10px 24px; border-radius: 6px;">{{ __('Parcourir la boutique') }}</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table" style="background: #fff; border-radius: 8px;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0;">
                        <th>{{ __('Produit') }}</th>
                        <th>{{ __('Variante') }}</th>
                        <th style="width: 120px;">{{ __('Quantité') }}</th>
                        <th>{{ __('Prix') }}</th>
                        <th>{{ __('Sous-total') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($content as $item)
                        <tr>
                            <td>
                                <a href="{{ $item['product_slug'] ? route('shop.show', $item['product_slug']) : '#' }}" style="color: #1e293b; font-weight: 600;">
                                    {{ $item['product_name'] }}
                                </a>
                            </td>
                            <td>{{ $item['variant_label'] ?? '-' }}</td>
                            <td>
                                <form action="{{ route('shop.cart.quantity') }}" method="POST" style="display: flex; gap: 4px;">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                    <input type="hidden" name="variant_label" value="{{ $item['variant_label'] ?? '' }}">
                                    <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="0" max="99" style="width: 60px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; text-align: center;">
                                    <button type="submit" class="btn btn-sm" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 8px;">OK</button>
                                </form>
                            </td>
                            <td>{{ number_format($item['unit_price'], 2, ',', ' ') }} $</td>
                            <td style="font-weight: 600;">{{ number_format($item['unit_price'] * $item['quantity'], 2, ',', ' ') }} $</td>
                            <td>
                                <form action="{{ route('shop.cart.remove') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                    <input type="hidden" name="variant_label" value="{{ $item['variant_label'] ?? '' }}">
                                    <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 18px;" title="{{ __('Retirer') }}">&times;</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Totaux --}}
        <div class="row" style="margin-top: 24px;">
            <div class="col-md-6 col-md-offset-6">
                <div style="background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>{{ __('Sous-total') }}</span>
                        <span>{{ number_format($subtotal, 2, ',', ' ') }} $</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: #64748b; font-size: 14px;">
                        <span>{{ __('TPS') }} ({{ config('shop.tax.tps', 5) }}%) + {{ __('TVQ') }} ({{ config('shop.tax.tvq', 9.975) }}%)</span>
                        <span>{{ number_format($tax, 2, ',', ' ') }} $</span>
                    </div>
                    <hr style="margin: 12px 0;">
                    <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: 700;">
                        <span>{{ __('Total') }}</span>
                        <span style="color: #0B7285;">{{ number_format($total, 2, ',', ' ') }} $</span>
                    </div>

                    {{-- Formulaire checkout --}}
                    <form action="{{ route('shop.checkout') }}" method="POST" style="margin-top: 20px;">
                        @csrf
                        <div style="margin-bottom: 12px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 4px;">{{ __('Courriel') }}</label>
                            <input type="email" name="email" value="{{ auth()->user()?->email ?? old('email') }}" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                        <div style="margin-bottom: 8px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 4px;">{{ __('Prénom') }}</label>
                            <input type="text" name="shipping_address[first_name]" value="{{ old('shipping_address.first_name') }}" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                        <div style="margin-bottom: 8px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 4px;">{{ __('Nom') }}</label>
                            <input type="text" name="shipping_address[last_name]" value="{{ old('shipping_address.last_name') }}" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                        <div style="margin-bottom: 8px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 4px;">{{ __('Adresse') }}</label>
                            <input type="text" name="shipping_address[address_line1]" value="{{ old('shipping_address.address_line1') }}" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                        <div class="row">
                            <div class="col-sm-6" style="margin-bottom: 8px;">
                                <label style="font-weight: 600; display: block; margin-bottom: 4px;">{{ __('Ville') }}</label>
                                <input type="text" name="shipping_address[city]" value="{{ old('shipping_address.city') }}" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                            <div class="col-sm-6" style="margin-bottom: 8px;">
                                <label style="font-weight: 600; display: block; margin-bottom: 4px;">{{ __('Code postal') }}</label>
                                <input type="text" name="shipping_address[postal_code]" value="{{ old('shipping_address.postal_code') }}" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                        </div>
                        <input type="hidden" name="shipping_address[country]" value="CA">

                        {{-- Estimation livraison dynamique --}}
                        <div x-data="{
                            loading: false,
                            methods: [],
                            selectedUid: null,
                            selectedCost: 0,
                            async fetchQuote() {
                                const pc = document.querySelector('[name=\'shipping_address[postal_code]\']').value.replace(/\s/g, '');
                                if (pc.length < 6) { this.methods = []; return; }
                                this.loading = true;
                                try {
                                    const res = await fetch('{{ route('shop.shipping-quote') }}', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                        body: JSON.stringify({ postal_code: pc, country: 'CA' })
                                    });
                                    const data = await res.json();
                                    this.methods = data.methods || [];
                                    if (this.methods.length) {
                                        this.selectedUid = this.methods[0].uid;
                                        this.selectedCost = this.methods[0].price;
                                    }
                                } catch (e) { this.methods = []; }
                                this.loading = false;
                            },
                            select(m) { this.selectedUid = m.uid; this.selectedCost = m.price; }
                        }" style="margin-top: 12px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 8px;">{{ __('Livraison') }}</label>
                            <button type="button" @click="fetchQuote()" class="btn btn-sm" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; margin-bottom: 8px;">
                                <i class="fa fa-truck"></i> {{ __('Calculer les frais') }}
                            </button>
                            <div x-show="loading" style="color: #64748b;"><i class="fa fa-spinner fa-spin"></i> {{ __('Calcul en cours...') }}</div>
                            <div x-show="!loading && methods.length === 0" style="font-style: italic; color: #64748b; font-size: 13px;">
                                {{ __('Entrez votre code postal pour estimer les frais de livraison.') }}
                            </div>
                            <template x-for="m in methods" :key="m.uid">
                                <label style="display: block; padding: 6px 0; cursor: pointer;">
                                    <input type="radio" name="shipping_method" :value="m.uid" x-model="selectedUid" @change="select(m)">
                                    <span x-text="m.name"></span> –
                                    <strong style="color: #0B7285;" x-text="parseFloat(m.price).toFixed(2) + ' $'"></strong>
                                    <span style="color: #64748b; font-size: 12px;" x-text="'(' + m.min_days + '-' + m.max_days + ' jours)'"></span>
                                </label>
                            </template>
                            <input type="hidden" name="shipping_method_uid" x-model="selectedUid">
                            <input type="hidden" name="shipping_cost" x-model="selectedCost">
                        </div>

                        <button type="submit" class="btn" style="width: 100%; background: #0B7285; color: #fff; padding: 12px; border-radius: 6px; font-weight: 700; font-size: 16px; margin-top: 12px;">{{ __('Passer la commande') }}</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

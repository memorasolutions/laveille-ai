@extends(fronttheme_layout())

@section('title', __('Panier'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Panier'), 'breadcrumbItems' => [__('Boutique'), __('Panier')]])
@endsection

@section('content')
{{-- Toast notification ajout panier --}}
@if(session('cart_added'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
     x-transition:enter="transition ease-out duration-300" x-transition:leave="transition ease-in duration-200"
     style="position: fixed; top: 20px; right: 20px; z-index: 1050; max-width: 320px; background: #fff; border-left: 4px solid #0CA678; box-shadow: 0 4px 20px rgba(0,0,0,0.15); border-radius: 8px; padding: 12px 16px; display: flex; align-items: center; gap: 12px;">
    <i class="fa fa-check-circle" style="color: #0CA678; font-size: 20px;"></i>
    <div>
        <strong style="font-size: 14px;">{{ __('Produit ajouté au panier') }}</strong><br>
        <a href="{{ route('shop.cart') }}" style="font-size: 13px; color: #0B7285;">{{ __('Voir le panier') }}</a>
    </div>
    <button @click="show = false" style="background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 18px; margin-left: auto;">&times;</button>
</div>
@endif

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
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    @if(!empty($item['product_images'][0]))
                                        <img src="{{ asset($item['product_images'][0]) }}" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0;">
                                    @endif
                                    <a href="{{ $item['product_slug'] ? route('shop.show', $item['product_slug']) : '#' }}" style="color: #1e293b; font-weight: 600;">
                                        {{ $item['product_name'] }}
                                    </a>
                                </div>
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

                    {{-- Formulaire checkout (layout multi-colonnes compact) --}}
                    <form action="{{ route('shop.checkout') }}" method="POST" style="margin-top: 16px;">
                        @csrf
                        <div style="margin-bottom: 10px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">{{ __('Courriel') }}</label>
                            <input type="email" name="email" value="{{ auth()->user()?->email ?? old('email') }}" required autocomplete="email" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                        </div>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 10px;">
                            <div style="flex: 1; min-width: 120px;">
                                <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">{{ __('Prénom') }}</label>
                                <input type="text" name="shipping_address[first_name]" value="{{ old('shipping_address.first_name') }}" required autocomplete="given-name" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                            </div>
                            <div style="flex: 1; min-width: 120px;">
                                <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">{{ __('Nom') }}</label>
                                <input type="text" name="shipping_address[last_name]" value="{{ old('shipping_address.last_name') }}" required autocomplete="family-name" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                            </div>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">{{ __('Adresse') }}</label>
                            <input type="text" name="shipping_address[address_line1]" value="{{ old('shipping_address.address_line1') }}" required autocomplete="street-address" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                        </div>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 10px;">
                            <div style="flex: 2; min-width: 100px;">
                                <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">{{ __('Ville') }}</label>
                                <input type="text" name="shipping_address[city]" value="{{ old('shipping_address.city') }}" required autocomplete="address-level2" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                            </div>
                            <div style="flex: 1.5; min-width: 100px;">
                                <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">{{ __('Province') }}</label>
                                <select name="shipping_address[state]" required autocomplete="address-level1" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                                    <option value="QC" selected>Québec</option>
                                    <option value="ON">Ontario</option>
                                    <option value="BC">Colombie-Britannique</option>
                                    <option value="AB">Alberta</option>
                                    <option value="MB">Manitoba</option>
                                    <option value="SK">Saskatchewan</option>
                                    <option value="NS">Nouvelle-Écosse</option>
                                    <option value="NB">Nouveau-Brunswick</option>
                                    <option value="NL">Terre-Neuve-et-Labrador</option>
                                    <option value="PE">Île-du-Prince-Édouard</option>
                                    <option value="NT">Territoires du Nord-Ouest</option>
                                    <option value="YT">Yukon</option>
                                    <option value="NU">Nunavut</option>
                                </select>
                            </div>
                            <div style="flex: 1; min-width: 90px;">
                                <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">{{ __('Code postal') }}</label>
                                <input type="text" name="shipping_address[postal_code]" value="{{ old('shipping_address.postal_code') }}" required autocomplete="postal-code" pattern="[A-Za-z]\d[A-Za-z]\s?\d[A-Za-z]\d" title="Format : A1A 1A1" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                            </div>
                        </div>
                        <input type="hidden" name="shipping_address[country]" value="CA">

                        {{-- Estimation livraison dynamique (auto-calcul) --}}
                        <div x-data="{
                            loading: false,
                            methods: [],
                            selectedUid: null,
                            selectedCost: 0,
                            async fetchQuote() {
                                const pc = document.querySelector('[name=\'shipping_address[postal_code]\']').value.replace(/\s/g, '');
                                if (pc.length < 6) { this.methods = []; this.selectedUid = null; this.selectedCost = 0; return; }
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
                                    } else { this.selectedUid = null; this.selectedCost = 0; }
                                } catch (e) { this.methods = []; this.selectedUid = null; this.selectedCost = 0; }
                                this.loading = false;
                            },
                            select(m) { this.selectedUid = m.uid; this.selectedCost = m.price; },
                            init() {
                                const pcInput = document.querySelector('[name=\'shipping_address[postal_code]\']');
                                let tid;
                                pcInput.addEventListener('input', () => { clearTimeout(tid); tid = setTimeout(() => this.fetchQuote(), 800); });
                                this.fetchQuote();
                            }
                        }" style="margin-top: 12px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 8px;"><i class="fa fa-truck"></i> {{ __('Livraison') }}</label>
                            <div x-show="loading" style="color: #64748b;"><i class="fa fa-spinner fa-spin"></i> {{ __('Calcul en cours...') }}</div>
                            <div x-show="!loading && methods.length === 0" style="font-style: italic; color: #64748b; font-size: 13px;">
                                {{ __('Les frais seront calculés automatiquement à la saisie du code postal.') }}
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

                        {{-- Badges confiance --}}
                        <style>.shop-fa{font-family:FontAwesome!important;}</style>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-top: 16px; text-align: center;">
                            <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 8px;">
                                <i class="fa fa-shield shop-fa" style="color: #0CA678; font-size: 16px; margin-right: 8px;" aria-hidden="true"></i>
                                <span style="font-weight: 700; font-size: 14px; color: #1e293b;">{{ __('Paiement sécurisé') }}</span>
                                <span style="color: #cbd5e1; margin: 0 10px;">•</span>
                                <i class="fa fa-lock shop-fa" style="color: #475569; font-size: 14px; margin-right: 6px;" aria-hidden="true"></i>
                                <span style="font-size: 13px; color: #475569;">{{ __('Chiffrement SSL') }}</span>
                            </div>
                            <div style="display: flex; justify-content: center; align-items: center;">
                                <i class="fa fa-cc-visa shop-fa" style="font-size: 22px; color: #64748b; margin: 0 5px;" aria-hidden="true"></i>
                                <i class="fa fa-cc-mastercard shop-fa" style="font-size: 22px; color: #64748b; margin: 0 5px;" aria-hidden="true"></i>
                                <i class="fa fa-cc-amex shop-fa" style="font-size: 22px; color: #64748b; margin: 0 5px;" aria-hidden="true"></i>
                                <span style="font-size: 12px; color: #94a3b8; font-style: italic; margin-left: 10px;">{{ __('Propulsé par Stripe') }}</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

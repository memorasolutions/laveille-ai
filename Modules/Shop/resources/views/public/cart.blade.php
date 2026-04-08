@extends(fronttheme_layout())

@section('title', __('Panier'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Panier'), 'breadcrumbItems' => [__('Boutique'), __('Panier')]])
@endsection

@push('head')
<style>@keyframes spin-icon { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
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
                                <form action="{{ route('shop.cart.quantity') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                    <input type="hidden" name="variant_label" value="{{ $item['variant_label'] ?? '' }}">
                                    <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="0" max="99" @change="$el.closest('form').submit()" style="width: 60px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 4px; text-align: center;">
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
                <div x-data="shopCheckout()" style="background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>{{ __('Sous-total') }}</span>
                        <span>{{ number_format($subtotal, 2, ',', ' ') }} $</span>
                    </div>
                    <div x-show="country === 'CA'" style="display: flex; justify-content: space-between; margin-bottom: 8px; color: #64748b; font-size: 14px;">
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
                            <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">{{ __('Courriel') }} <span style="color:#ef4444;">*</span></label>
                            <input type="email" name="email" value="{{ auth()->user()?->email ?? old('email') }}" required autocomplete="email" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                        </div>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 10px;">
                            <div style="flex: 1; min-width: 120px;">
                                <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">{{ __('Prénom') }} <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="shipping_address[first_name]" value="{{ old('shipping_address.first_name') }}" required autocomplete="given-name" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                            </div>
                            <div style="flex: 1; min-width: 120px;">
                                <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">{{ __('Nom') }} <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="shipping_address[last_name]" value="{{ old('shipping_address.last_name') }}" required autocomplete="family-name" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                            </div>
                        </div>
                        <div style="margin-bottom: 10px; position: relative;" @click.outside="showSuggestions = false">
                            <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">{{ __('Adresse') }} <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="shipping_address[address_line1]" x-ref="addressLine1" value="{{ old('shipping_address.address_line1') }}" required autocomplete="off" @input="searchAddress($event.target.value)" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                            <div x-show="showSuggestions" x-transition style="position: absolute; top: 100%; left: 0; right: 0; z-index: 1000; background: #fff; border: 1px solid #cbd5e1; border-top: none; border-radius: 0 0 6px 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto;">
                                <template x-for="s in suggestions" :key="(s.osm_id || '') + (s.postcode || '')">
                                    <div @click="selectAddress(s)" style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: 13px;" onmouseenter="this.style.backgroundColor='#f8fafc'" onmouseleave="this.style.backgroundColor=''">
                                        <strong x-text="[s.street, s.housenumber].filter(Boolean).join(' ') || s.name || ''"></strong>
                                        <span style="color: #64748b;" x-text="', ' + (s.city || '') + (s.postcode ? ' ' + s.postcode : '') + ' — ' + (s.country || '')"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 10px;">
                            <div style="flex: 2; min-width: 100px;">
                                <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">{{ __('Ville') }} <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="shipping_address[city]" x-ref="cityInput" value="{{ old('shipping_address.city') }}" required autocomplete="address-level2" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                            </div>
                            <div style="flex: 1.5; min-width: 100px;">
                                <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;" x-text="provinceLabel + ' *'"></label>
                                {{-- Canada : select provinces --}}
                                <select x-show="country === 'CA'" :disabled="country !== 'CA'" name="shipping_address[state]" autocomplete="address-level1" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                                    <template x-for="(name, code) in provincesCA" :key="code">
                                        <option :value="code" x-text="name" :selected="code === 'QC'"></option>
                                    </template>
                                </select>
                                {{-- USA : select states --}}
                                <select x-show="country === 'US'" :disabled="country !== 'US'" name="shipping_address[state]" autocomplete="address-level1" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                                    <template x-for="(name, code) in statesUS" :key="code">
                                        <option :value="code" x-text="name"></option>
                                    </template>
                                </select>
                                {{-- Autres pays : champ texte libre --}}
                                <input x-show="country !== 'CA' && country !== 'US'" :disabled="country === 'CA' || country === 'US'" type="text" name="shipping_address[state]" autocomplete="address-level1" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;" :placeholder="'{{ __('Province / État / Région') }}'">
                            </div>
                            <div style="flex: 1; min-width: 90px;">
                                <label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">{{ __('Code postal') }} <span style="color:#ef4444;">*</span></label>
                                <input type="text" name="shipping_address[postal_code]" x-ref="postalCode" value="{{ old('shipping_address.postal_code') }}" :required="postalRequired" autocomplete="postal-code" :pattern="postalPattern" :title="postalTitle" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                            </div>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 4px;">{{ __('Pays') }} <span style="color:#ef4444;">*</span></label>
                            <select name="shipping_address[country]" id="country-select" x-model="country" required autocomplete="country" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                                <optgroup label="{{ __('Pays populaires') }}">
                                    <option value="CA" selected>Canada</option>
                                    <option value="US">États-Unis</option>
                                    <option value="FR">France</option>
                                    <option value="GB">Royaume-Uni</option>
                                </optgroup>
                                <optgroup label="{{ __('Tous les pays') }}">
                                    <option value="DE">Allemagne</option><option value="AU">Australie</option><option value="BE">Belgique</option><option value="BR">Brésil</option><option value="CL">Chili</option><option value="CN">Chine</option><option value="CO">Colombie</option><option value="KR">Corée du Sud</option><option value="DK">Danemark</option><option value="AE">Émirats arabes unis</option><option value="ES">Espagne</option><option value="FI">Finlande</option><option value="GR">Grèce</option><option value="IN">Inde</option><option value="ID">Indonésie</option><option value="IE">Irlande</option><option value="IL">Israël</option><option value="IT">Italie</option><option value="JP">Japon</option><option value="MY">Malaisie</option><option value="MX">Mexique</option><option value="NZ">Nouvelle-Zélande</option><option value="NL">Pays-Bas</option><option value="PL">Pologne</option><option value="PT">Portugal</option><option value="CZ">République tchèque</option><option value="SG">Singapour</option><option value="ZA">Afrique du Sud</option><option value="SE">Suède</option><option value="CH">Suisse</option><option value="TH">Thaïlande</option><option value="TR">Turquie</option><option value="VN">Viêt Nam</option>
                                </optgroup>
                            </select>
                        </div>

                        {{-- Estimation livraison dynamique (auto-calcul) --}}
                        <div style="margin-top: 12px;">
                            <label style="font-weight: 600; display: block; margin-bottom: 8px;"><i class="ti-truck" style="margin-right: 6px;"></i>{{ __('Livraison') }}</label>
                            <div x-show="loading" style="color: #64748b;"><i class="ti-reload" style="display: inline-block; animation: spin-icon 1s linear infinite;"></i> {{ __('Calcul en cours...') }}</div>
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

                        {{-- Checkbox newsletter (LCAP/Loi 25 — case vide par défaut) --}}
                        <div style="margin-top: 12px; margin-bottom: 4px;">
                            <label style="display: flex; gap: 8px; align-items: start;">
                                <input type="checkbox" name="newsletter" value="1" style="width: 18px; height: 18px; margin-top: 2px; accent-color: #0B7285; flex-shrink: 0;">
                                <span style="font-size: 13px; color: #475569; line-height: 1.4;">{{ __("J'accepte de recevoir des offres et nouvelles de La veille par courriel. Vous pouvez vous désabonner en tout temps.") }}</span>
                            </label>
                        </div>

                        <button type="submit" class="btn" style="width: 100%; background: #0B7285; color: #fff; padding: 12px; border-radius: 6px; font-weight: 700; font-size: 16px; margin-top: 12px;">{{ __('Passer la commande') }}</button>

                        {{-- Badges confiance --}}
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-top: 16px; text-align: center;">
                            <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 8px;">
                                <i class="ti-shield" style="color: #0CA678; font-size: 16px; margin-right: 8px;" aria-hidden="true"></i>
                                <span style="font-weight: 700; font-size: 14px; color: #1e293b;">{{ __('Paiement sécurisé') }}</span>
                                <span style="color: #cbd5e1; margin: 0 10px;">•</span>
                                <i class="ti-lock" style="color: #475569; font-size: 14px; margin-right: 6px;" aria-hidden="true"></i>
                                <span style="font-size: 13px; color: #475569;">{{ __('Chiffrement SSL') }}</span>
                            </div>
                            <div style="display: flex; justify-content: center; align-items: center; gap: 6px; font-size: 13px; color: #94a3b8;">
                                <span>Visa</span><span>•</span><span>Mastercard</span><span>•</span><span>Amex</span>
                                <span style="margin-left: 8px; font-style: italic;">{{ __('Propulsé par Stripe') }}</span>
                            </div>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 6px;">
                                {{ __('Sur votre relevé bancaire : LAVEILLE.AI') }}
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('shopCheckout', () => ({
        country: 'CA',
        loading: false,
        methods: [],
        selectedUid: null,
        selectedCost: 0,
        suggestions: [],
        showSuggestions: false,
        addrTid: null,
        provincesCA: {AB:'Alberta',BC:'Colombie-Britannique',MB:'Manitoba',NB:'Nouveau-Brunswick',NL:'Terre-Neuve-et-Labrador',NS:'Nouvelle-Écosse',NT:'Territoires du Nord-Ouest',NU:'Nunavut',ON:'Ontario',PE:'Île-du-Prince-Édouard',QC:'Québec',SK:'Saskatchewan',YT:'Yukon'},
        statesUS: {AL:'Alabama',AK:'Alaska',AZ:'Arizona',AR:'Arkansas',CA:'Californie',CO:'Colorado',CT:'Connecticut',DE:'Delaware',DC:'District de Columbia',FL:'Floride',GA:'Géorgie',HI:'Hawaï',ID:'Idaho',IL:'Illinois',IN:'Indiana',IA:'Iowa',KS:'Kansas',KY:'Kentucky',LA:'Louisiane',ME:'Maine',MD:'Maryland',MA:'Massachusetts',MI:'Michigan',MN:'Minnesota',MS:'Mississippi',MO:'Missouri',MT:'Montana',NE:'Nebraska',NV:'Nevada',NH:'New Hampshire',NJ:'New Jersey',NM:'Nouveau-Mexique',NY:'New York',NC:'Caroline du Nord',ND:'Dakota du Nord',OH:'Ohio',OK:'Oklahoma',OR:'Oregon',PA:'Pennsylvanie',RI:'Rhode Island',SC:'Caroline du Sud',SD:'Dakota du Sud',TN:'Tennessee',TX:'Texas',UT:'Utah',VT:'Vermont',VA:'Virginie',WA:'Washington',WV:'Virginie-Occidentale',WI:'Wisconsin',WY:'Wyoming'},
        get provinceLabel() {
            if (this.country === 'CA') return 'Province';
            if (this.country === 'US') return 'État';
            return 'Province / État / Région';
        },
        get postalPattern() {
            if (this.country === 'CA') return '[A-Za-z]\\d[A-Za-z]\\s?\\d[A-Za-z]\\d';
            if (this.country === 'US') return '\\d{5}(-\\d{4})?';
            return null;
        },
        get postalTitle() {
            if (this.country === 'CA') return 'Format : A1A 1A1';
            if (this.country === 'US') return 'Format : 12345 ou 12345-6789';
            return '';
        },
        get postalRequired() { return this.country === 'CA' || this.country === 'US'; },
        select(m) { this.selectedUid = m.uid; this.selectedCost = m.price; },
        searchAddress(query) {
            clearTimeout(this.addrTid);
            if (query.length < 4) { this.suggestions = []; this.showSuggestions = false; return; }
            this.addrTid = setTimeout(() => {
                fetch('https://photon.komoot.io/api/?q=' + encodeURIComponent(query) + '&limit=5&lang=fr')
                    .then(r => r.json())
                    .then(d => { this.suggestions = (d.features || []).map(f => f.properties); this.showSuggestions = this.suggestions.length > 0; })
                    .catch(() => { this.suggestions = []; this.showSuggestions = false; });
            }, 500);
        },
        selectAddress(s) {
            this.$refs.addressLine1.value = [s.street, s.housenumber].filter(Boolean).join(' ');
            this.$refs.cityInput.value = s.city || '';
            if (this.$refs.postalCode) {
                var ns = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
                ns.call(this.$refs.postalCode, s.postcode || '');
                this.$refs.postalCode.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (s.countrycode) { var cc = s.countrycode.toUpperCase(); if (cc !== this.country) { this.country = cc; } }
            if (s.state && this.country !== 'CA' && this.country !== 'US') {
                var stateInput = document.querySelector('input[name="shipping_address[state]"]:not([disabled])');
                if (stateInput) stateInput.value = s.state;
            }
            this.showSuggestions = false;
        },
        async fetchQuote() {
            var pc = this.$refs.postalCode ? this.$refs.postalCode.value.replace(/\s/g, '') : '';
            if (pc.length < 3) { this.methods = []; this.selectedUid = null; this.selectedCost = 0; return; }
            this.loading = true;
            try {
                var res = await fetch(@json(route('shop.shipping-quote')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                    body: JSON.stringify({ postal_code: pc, country: this.country })
                });
                var data = await res.json();
                this.methods = data.methods || [];
                if (this.methods.length) { this.selectedUid = this.methods[0].uid; this.selectedCost = this.methods[0].price; }
                else { this.selectedUid = null; this.selectedCost = 0; }
            } catch (e) { this.methods = []; this.selectedUid = null; this.selectedCost = 0; }
            this.loading = false;
        },
        init() {
            var tid;
            this.$watch('country', () => { this.fetchQuote(); });
            if (this.$refs.postalCode) {
                this.$refs.postalCode.addEventListener('input', () => { clearTimeout(tid); tid = setTimeout(() => this.fetchQuote(), 800); });
            }
        }
    }));
});
</script>
@endpush

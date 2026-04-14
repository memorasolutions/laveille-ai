@extends(fronttheme_layout())

@section('title', __('Panier'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
<link rel="stylesheet" href="/css/shop.css">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Panier'), 'breadcrumbItems' => [__('Boutique'), __('Panier')]])
@endsection

@section('content')
<div class="container sp-container">
    @if($errors->any())
        <div class="sp-error-box">
            <strong>{{ __('Veuillez corriger les erreurs suivantes :') }}</strong>
            <ul style="margin: 8px 0 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))
        <div class="sp-error-box">{{ session('error') }}</div>
    @endif
    <h1 class="sp-page-title" x-data="{ count: {{ $itemCount ?? 0 }} }" @cart-updated.window="count = $event.detail.itemCount">{{ __('Panier') }} (<span x-text="count"></span>)</h1>

    @if(empty($content))
        <div class="sp-empty">
            <p class="sp-empty-text">{{ __('Votre panier est vide.') }}</p>
            <a href="{{ route('shop.index') }}" class="sp-btn-primary">{{ __('Parcourir la boutique') }}</a>
        </div>
    @else
        <div class="row">
        <div class="col-md-7">
        <div class="sp-cart-list">
            @foreach($content as $item)
            <div class="sp-cart-card" x-data="cartItem({{ $item['product_id'] }}, '{{ addslashes($item['variant_label'] ?? '') }}', {{ $item['quantity'] }}, {{ $item['unit_price'] }})" x-show="!removed" x-transition>
                {{-- Supprimer --}}
                <button type="button" @click="removeItem()" aria-label="{{ __('Retirer') }} {{ $item['product_name'] }}" class="sp-cart-remove" title="{{ __('Retirer') }}"><i class="ti-trash" aria-hidden="true"></i></button>
                {{-- Ligne 1 : image + nom + variante --}}
                <div class="sp-cart-top">
                    @if(!empty($item['product_images'][0]))
                    <a href="{{ $item['product_slug'] ? route('shop.show', $item['product_slug']) : '#' }}">
                        <img src="{{ asset($item['product_images'][0]) }}" alt="{{ $item['product_name'] }}" class="sp-cart-img">
                    </a>
                    @endif
                    <div class="sp-cart-item-info">
                        <a href="{{ $item['product_slug'] ? route('shop.show', $item['product_slug']) : '#' }}" class="sp-cart-item-name">{{ $item['product_name'] }}</a>
                        @if(!empty($item['variant_label']))
                        @php
                            $parts = explode(' - ', $item['variant_label']);
                            $hasColors = collect($item['product_variants'])->contains(fn($v) => !empty($v['color']));
                            $currentColor = $hasColors ? ($parts[0] ?? null) : null;
                            $currentSize = count($parts) === 2 ? $parts[1] : (!$hasColors ? ($parts[0] ?? null) : null);
                            $itemIdx = $loop->index;
                            $sizeOptions = !empty($item['product_sizes']) ? $item['product_sizes'] : (!$hasColors ? array_map(fn($v) => $v['label'] ?? $v, $item['product_variants']) : []);
                        @endphp
                        <div x-data="variantPicker({{ $item['product_id'] }}, '{{ addslashes($currentColor ?? '') }}', '{{ addslashes($currentSize ?? '') }}', '{{ addslashes($item['variant_label']) }}', {{ $hasColors ? 'true' : 'false' }})" class="sp-variant-tags">
                            <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                                @if($currentColor)
                                <span @click="editing = editing === 'color' ? null : 'color'" x-text="currentColor + ' ✎'" class="sp-cart-variant-tag"></span>
                                @endif
                                @if($currentSize)
                                <span @click="editing = editing === 'size' ? null : 'size'" x-text="currentSize + ' ✎'" class="sp-cart-variant-tag"></span>
                                @endif
                                <span x-show="updating" class="sp-spin" style="font-size: 11px; color: var(--c-primary);"><i class="ti-reload" style="font-size: 10px;"></i></span>
                            </div>
                            {{-- Sélecteur couleur --}}
                            @if($hasColors)
                            <div x-show="editing === 'color'" x-transition class="sp-variant-picker">
                                @foreach($item['product_variants'] as $v)
                                @if(!empty($v['color']))
                                <button type="button" @click="pick('{{ $v['label'] }}{{ $currentSize ? ' - '.$currentSize : '' }}', '{{ $v['gelato_uid'] }}')" class="sp-color-circle" :class="currentColor==='{{ $v['label'] }}' ? 'active' : ''" style="background:{{ $v['color'] }}" title="{{ $v['label'] }}"></button>
                                @endif
                                @endforeach
                            </div>
                            @endif
                            {{-- Sélecteur taille --}}
                            @if(!empty($sizeOptions))
                            <div x-show="editing === 'size'" x-transition class="sp-variant-picker">
                                @foreach($sizeOptions as $sz)
                                @php
                                    $szVariant = collect($item['product_variants'])->first(fn($v) => ($v['label'] ?? '') === $sz);
                                    $szUid = $szVariant['gelato_uid'] ?? preg_replace('/_gsi_[^_]+_/', '_gsi_' . strtolower($sz) . '_', $item['gelato_variant_id'] ?? '');
                                    $newLabel = $currentColor ? $currentColor . ' - ' . $sz : $sz;
                                @endphp
                                <button type="button" @click="pick('{{ addslashes($newLabel) }}', '{{ $szUid }}')" class="sp-size-pill" :class="currentSize==='{{ $sz }}' ? 'active' : ''">{{ $sz }}</button>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                {{-- Ligne 2 : quantité + prix --}}
                <div class="sp-cart-line">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" @click="changeQty(-1)" class="sp-qty-circle">-</button>
                        <span class="sp-qty-value" x-text="qty"></span>
                        <button type="button" @click="changeQty(1)" class="sp-qty-circle">+</button>
                    </div>
                    <div style="text-align: right;">
                        <div class="sp-cart-unit-price" x-text="unitPrice.toFixed(2).replace('.', ',') + ' $ × ' + qty"></div>
                        <div class="sp-cart-total-price" x-text="(unitPrice * qty).toFixed(2).replace('.', ',') + ' $'"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        </div>{{-- end col-md-7 --}}
        <div class="col-md-5">
                <div class="sp-summary-box" x-data="shopCheckout()" @cart-updated.window="cartSubtotal=$event.detail.subtotal; cartTps=$event.detail.tps; cartTvq=$event.detail.tvq; cartTotal=$event.detail.total">
                    <div class="sp-summary-row">
                        <span>{{ __('Sous-total') }}</span>
                        <span x-text="cartSubtotal.toFixed(2).replace('.', ',') + ' $'"></span>
                    </div>
                    <div x-show="country === 'CA'" class="sp-tax-block">
                        <div class="sp-tax-row">
                            <span>{{ __('TPS') }} ({{ config('shop.tax.tps', 5) }}%) <span class="sp-tax-number">839145984</span></span>
                            <span x-text="cartTps.toFixed(2).replace('.', ',') + ' $'"></span>
                        </div>
                        <div class="sp-tax-row">
                            <span>{{ __('TVQ') }} ({{ config('shop.tax.tvq', 9.975) }}%) <span class="sp-tax-number">1221788059</span></span>
                            <span x-text="cartTvq.toFixed(2).replace('.', ',') + ' $'"></span>
                        </div>
                    </div>
                    <hr style="margin: 12px 0;">
                    <div class="sp-summary-total">
                        <span>{{ __('Total') }}</span>
                        <span class="sp-summary-total-value" x-text="cartTotal.toFixed(2).replace('.', ',') + ' $'"></span>
                    </div>

                    {{-- Formulaire checkout --}}
                    <form action="{{ route('shop.checkout') }}" method="POST" style="margin-top: 16px;">
                        @csrf
                        <div class="sp-form-group">
                            <label class="sp-form-label">{{ __('Courriel') }} <span class="sp-required">*</span></label>
                            <input type="email" name="email" value="{{ auth()->user()?->email ?? old('email') }}" required autocomplete="email" class="sp-form-input">
                        </div>
                        <div class="sp-form-row">
                            <div class="sp-form-col">
                                <label class="sp-form-label">{{ __('Prénom') }} <span class="sp-required">*</span></label>
                                <input type="text" name="shipping_address[first_name]" value="{{ old('shipping_address.first_name', $savedAddress['first_name'] ?? '') }}" required autocomplete="given-name" class="sp-form-input">
                            </div>
                            <div class="sp-form-col">
                                <label class="sp-form-label">{{ __('Nom') }} <span class="sp-required">*</span></label>
                                <input type="text" name="shipping_address[last_name]" value="{{ old('shipping_address.last_name', $savedAddress['last_name'] ?? '') }}" required autocomplete="family-name" class="sp-form-input">
                            </div>
                        </div>
                        <div class="sp-form-group sp-address-wrap" @click.outside="showSuggestions = false">
                            <label class="sp-form-label">{{ __('Adresse') }} <span class="sp-required">*</span></label>
                            <input type="text" name="shipping_address[address_line1]" x-ref="addressLine1" value="{{ old('shipping_address.address_line1', $savedAddress['address_line1'] ?? '') }}" required autocomplete="off" @input="searchAddress($event.target.value)" class="sp-form-input">
                            <div x-show="showSuggestions" x-transition class="sp-suggestions">
                                <template x-for="s in suggestions" :key="(s.osm_id || '') + (s.postcode || '')">
                                    <div @click="selectAddress(s)" class="sp-suggestion-item">
                                        <strong x-text="[s.street, s.housenumber].filter(Boolean).join(' ') || s.name || ''"></strong>
                                        <span style="color: var(--c-text-muted);" x-text="', ' + (s.city || '') + (s.postcode ? ' ' + s.postcode : '') + ' — ' + (s.country || '')"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="sp-form-row">
                            <div class="sp-form-col-city">
                                <label class="sp-form-label">{{ __('Ville') }} <span class="sp-required">*</span></label>
                                <input type="text" name="shipping_address[city]" x-ref="cityInput" value="{{ old('shipping_address.city', $savedAddress['city'] ?? '') }}" required autocomplete="address-level2" class="sp-form-input">
                            </div>
                            <div class="sp-form-col-state">
                                <label class="sp-form-label" x-text="provinceLabel + ' *'"></label>
                                {{-- Canada --}}
                                <select x-show="country === 'CA'" :disabled="country !== 'CA'" name="shipping_address[state]" autocomplete="address-level1" class="sp-form-select">
                                    <template x-for="(name, code) in provincesCA" :key="code">
                                        <option :value="code" x-text="name" :selected="code === '{{ $savedAddress['state'] ?? 'QC' }}'"></option>
                                    </template>
                                </select>
                                {{-- USA --}}
                                <select x-show="country === 'US'" :disabled="country !== 'US'" name="shipping_address[state]" autocomplete="address-level1" class="sp-form-select">
                                    <template x-for="(name, code) in statesUS" :key="code">
                                        <option :value="code" x-text="name"></option>
                                    </template>
                                </select>
                                {{-- Autres --}}
                                <input x-show="country !== 'CA' && country !== 'US'" :disabled="country === 'CA' || country === 'US'" type="text" name="shipping_address[state]" autocomplete="address-level1" class="sp-form-input" :placeholder="'{{ __('Province / État / Région') }}'">
                            </div>
                            <div class="sp-form-col-postal">
                                <label class="sp-form-label">{{ __('Code postal') }} <span class="sp-required">*</span></label>
                                <input type="text" name="shipping_address[postal_code]" x-ref="postalCode" value="{{ old('shipping_address.postal_code', $savedAddress['postal_code'] ?? '') }}" :required="postalRequired" autocomplete="postal-code" :pattern="postalPattern" :title="postalTitle" class="sp-form-input">
                            </div>
                        </div>
                        <div class="sp-form-group">
                            <label class="sp-form-label">{{ __('Pays') }} <span class="sp-required">*</span></label>
                            <select name="shipping_address[country]" id="country-select" x-model="country" required autocomplete="country" class="sp-form-select">
                                <optgroup label="{{ __('Pays populaires') }}">
                                    <option value="CA" {{ ($savedAddress['country'] ?? 'CA') === 'CA' ? 'selected' : '' }}>Canada</option>
                                    <option value="US">États-Unis</option>
                                    <option value="FR">France</option>
                                    <option value="GB">Royaume-Uni</option>
                                </optgroup>
                                <optgroup label="{{ __('Tous les pays') }}">
                                    <option value="DE">Allemagne</option><option value="AU">Australie</option><option value="BE">Belgique</option><option value="BR">Brésil</option><option value="CL">Chili</option><option value="CN">Chine</option><option value="CO">Colombie</option><option value="KR">Corée du Sud</option><option value="DK">Danemark</option><option value="AE">Émirats arabes unis</option><option value="ES">Espagne</option><option value="FI">Finlande</option><option value="GR">Grèce</option><option value="IN">Inde</option><option value="ID">Indonésie</option><option value="IE">Irlande</option><option value="IL">Israël</option><option value="IT">Italie</option><option value="JP">Japon</option><option value="MY">Malaisie</option><option value="MX">Mexique</option><option value="NZ">Nouvelle-Zélande</option><option value="NL">Pays-Bas</option><option value="PL">Pologne</option><option value="PT">Portugal</option><option value="CZ">République tchèque</option><option value="SG">Singapour</option><option value="ZA">Afrique du Sud</option><option value="SE">Suède</option><option value="CH">Suisse</option><option value="TH">Thaïlande</option><option value="TR">Turquie</option><option value="VN">Viêt Nam</option>
                                </optgroup>
                            </select>
                        </div>

                        {{-- Estimation livraison --}}
                        <div style="margin-top: 12px;">
                            <label class="sp-shipping-label"><i class="ti-truck" style="margin-right: 6px;"></i>{{ __('Livraison') }}</label>
                            <div x-show="loading" style="color: var(--c-text-muted);"><i class="ti-reload sp-spin" style="font-size: 14px;"></i> {{ __('Calcul en cours...') }}</div>
                            <div x-show="!loading && methods.length === 0 && !quoteFetched" class="sp-shipping-hint">
                                {{ __('Les frais seront calculés automatiquement à la saisie du code postal.') }}
                            </div>
                            <div x-show="!loading && methods.length === 0 && quoteFetched" class="sp-shipping-unavailable">
                                <i class="fa fa-exclamation-triangle" aria-hidden="true"></i> {{ __('Désolé, la livraison n\'est pas disponible pour cette destination. Veuillez vérifier votre code postal ou essayer un autre pays.') }}
                            </div>
                            <template x-for="m in methods" :key="m.uid">
                                <label class="sp-shipping-option">
                                    <input type="radio" name="shipping_method" :value="m.uid" x-model="selectedUid" @change="select(m)">
                                    <span x-text="m.name"></span> –
                                    <strong class="sp-shipping-price" x-text="parseFloat(m.price).toFixed(2) + ' $'"></strong>
                                    <span class="sp-shipping-days" x-text="'(' + m.min_days + '-' + m.max_days + ' jours)'"></span>
                                </label>
                            </template>
                            <input type="hidden" name="shipping_method_uid" x-model="selectedUid">
                            <input type="hidden" name="shipping_cost" x-model="selectedCost">
                        </div>

                        {{-- Sauvegarder adresse --}}
                        @auth
                        <div class="sp-checkbox-wrap">
                            <label class="sp-checkbox-label">
                                <input type="checkbox" name="save_address" value="1" class="sp-checkbox">
                                <span class="sp-checkbox-text">{{ __('Sauvegarder cette adresse pour mes prochaines commandes') }}</span>
                            </label>
                        </div>
                        @endauth

                        {{-- Newsletter --}}
                        <div class="sp-newsletter-box">
                            <label class="sp-checkbox-label-top">
                                <input type="checkbox" name="newsletter" value="1" class="sp-checkbox sp-checkbox-lg">
                                <div>
                                    <span class="sp-newsletter-title">{{ __('Restez informé') }} <i class="ti-email" style="color: var(--c-primary); font-size: 13px;"></i></span>
                                    <span class="sp-newsletter-desc">{{ __('Recevez notre veille IA chaque mercredi — outils, actualités, défi et astuces. Désabonnement en 1 clic.') }}</span>
                                </div>
                            </label>
                        </div>

                        <button type="submit" class="sp-btn-primary sp-btn-full" style="margin-top: 12px;">{{ __('Passer la commande') }}</button>
                        <p class="sp-legal-note">
                            {!! __('En passant commande, vous acceptez nos <a href=":url" target="_blank">conditions de vente</a>.', ['url' => route('legal.sales')]) !!}
                        </p>
                        <p class="sp-pod-note">{{ __('Produit fabriqué à la demande — non remboursable sauf défaut de fabrication.') }}</p>

                        {{-- Badges confiance --}}
                        <div class="sp-trust-box">
                            <div class="sp-trust-main">
                                <i class="ti-shield sp-trust-icon" aria-hidden="true"></i>
                                <span class="sp-trust-title">{{ __('Paiement sécurisé') }}</span>
                                <span class="sp-trust-sep">•</span>
                                <i class="ti-lock sp-trust-lock" aria-hidden="true"></i>
                                <span class="sp-trust-ssl">{{ __('Chiffrement SSL') }}</span>
                            </div>
                            <div class="sp-trust-methods">
                                <span>Visa</span><span>•</span><span>Mastercard</span><span>•</span><span>Amex</span>
                                <span class="sp-trust-stripe">{{ __('Propulsé par Stripe') }}</span>
                            </div>
                            <div class="sp-trust-statement">
                                {{ __('Sur votre relevé bancaire : MEMORA* LAVEILLE.AI') }}
                            </div>
                        </div>
                    </form>
                </div>
        </div>{{-- end col-md-5 --}}
        </div>{{-- end row --}}
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('cartItem', (productId, variantLabel, initialQty, price) => ({
        qty: initialQty,
        unitPrice: price,
        removed: false,
        async changeQty(delta) {
            this.qty = Math.max(0, Math.min(99, this.qty + delta));
            if (this.qty === 0) { this.removeItem(); return; }
            var res = await fetch(@json(route('shop.cart.quantity')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value, 'Accept': 'application/json' },
                body: JSON.stringify({ product_id: productId, quantity: this.qty, variant_label: variantLabel })
            }).then(r => r.json());
            if (res.success) this.$dispatch('cart-updated', res);
        },
        async removeItem() {
            this.removed = true;
            var res = await fetch(@json(route('shop.cart.remove')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value, 'Accept': 'application/json' },
                body: JSON.stringify({ product_id: productId, variant_label: variantLabel })
            }).then(r => r.json());
            if (res.success) this.$dispatch('cart-updated', res);
        }
    }));
    Alpine.data('variantPicker', (productId, color, size, oldLabel, hasColors) => ({
        editing: null,
        currentColor: color,
        currentSize: size,
        oldLabel: oldLabel,
        updating: false,
        async pick(newLabel, newUid) {
            this.updating = true;
            try {
                await fetch(@json(route('shop.cart.variant')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value, 'Accept': 'application/json' },
                    body: JSON.stringify({ product_id: productId, old_variant_label: this.oldLabel, new_variant_label: newLabel, new_gelato_uid: newUid })
                });
                this.oldLabel = newLabel;
                var parts = newLabel.split(' - ');
                if (parts.length === 2) { this.currentColor = parts[0]; this.currentSize = parts[1]; }
                else if (hasColors) { this.currentColor = parts[0]; }
                else { this.currentSize = parts[0]; }
            } catch(e) { console.error('Variant update failed', e); }
            this.editing = null;
            this.updating = false;
        }
    }));
    Alpine.data('shopCheckout', () => ({
        country: '{{ $savedAddress['country'] ?? 'CA' }}',
        loading: false,
        quoteFetched: false,
        methods: [],
        cartSubtotal: {{ $subtotal ?? 0 }},
        cartTps: {{ isset($subtotal) ? round($subtotal * config('shop.tax.tps', 5) / 100, 2) : 0 }},
        cartTvq: {{ isset($subtotal) ? round($subtotal * config('shop.tax.tvq', 9.975) / 100, 2) : 0 }},
        cartTotal: {{ $total ?? 0 }},
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
            if (pc.length < 3) { this.methods = []; this.selectedUid = null; this.selectedCost = 0; this.quoteFetched = false; return; }
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
            this.quoteFetched = true;
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

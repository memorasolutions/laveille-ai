@extends(fronttheme_layout())

@section('title', __('Panier'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Panier'), 'breadcrumbItems' => [__('Boutique'), __('Panier')]])
@endsection

@push('head')
<style>
@keyframes spin-icon { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.shop-cart-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.1) !important; }
@media (max-width: 768px) {
    .shop-cart-card { flex-wrap: wrap !important; }
    .shop-cart-card > a:first-of-type { width: 100%; text-align: center; margin-bottom: 12px; }
    .shop-cart-card > a:first-of-type img { width: 120px !important; height: 120px !important; margin: 0 auto; }
    .shop-cart-card > div:nth-child(4) { margin-left: 0 !important; width: 100%; }
    .shop-cart-card > div:last-child { margin-left: auto !important; margin-top: 12px; }
}
</style>
@endpush

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 40px;">
    <h1 x-data="{ count: {{ $itemCount ?? 0 }} }" @cart-updated.window="count = $event.detail.itemCount" style="font-size: 28px; font-weight: 700; margin-bottom: 24px;">{{ __('Panier') }} (<span x-text="count"></span>)</h1>

    @if(empty($content))
        <div style="text-align: center; padding: 60px 0;">
            <p style="font-size: 18px; color: #64748b; margin-bottom: 20px;">{{ __('Votre panier est vide.') }}</p>
            <a href="{{ route('shop.index') }}" class="btn" style="background: #0B7285; color: #fff; padding: 10px 24px; border-radius: 6px;">{{ __('Parcourir la boutique') }}</a>
        </div>
    @else
        <div class="row">
        <div class="col-md-7">
        <div style="display: flex; flex-direction: column; gap: 14px;">
            @foreach($content as $item)
            <div class="shop-cart-card" x-data="cartItem({{ $item['product_id'] }}, '{{ addslashes($item['variant_label'] ?? '') }}', {{ $item['quantity'] }}, {{ $item['unit_price'] }})" x-show="!removed" x-transition style="background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 18px; position: relative; transition: transform 0.2s, box-shadow 0.2s;">
                {{-- Supprimer --}}
                <button type="button" @click="removeItem()" aria-label="{{ __('Retirer') }} {{ $item['product_name'] }}" style="position: absolute; top: 12px; right: 14px; background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 15px; padding: 6px; border-radius: 6px; transition: all 0.2s; outline-offset: 2px;" title="{{ __('Retirer') }}" onmouseenter="this.style.color='#ef4444';this.style.background='#fef2f2'" onmouseleave="this.style.color='#94a3b8';this.style.background='none'"><i class="ti-trash" aria-hidden="true"></i></button>
                {{-- Ligne 1 : image + nom + variante --}}
                <div style="display: flex; align-items: center; margin-bottom: 14px;">
                    @if(!empty($item['product_images'][0]))
                    <a href="{{ $item['product_slug'] ? route('shop.show', $item['product_slug']) : '#' }}" style="flex-shrink: 0;">
                        <img src="{{ asset($item['product_images'][0]) }}" alt="{{ $item['product_name'] }}" style="width: 90px; height: 90px; border-radius: 10px; object-fit: cover;">
                    </a>
                    @endif
                    <div style="margin-left: 14px; min-width: 0;">
                        <a href="{{ $item['product_slug'] ? route('shop.show', $item['product_slug']) : '#' }}" style="font-weight: 700; font-size: 15px; color: #1e293b; text-decoration: none; display: block;">{{ $item['product_name'] }}</a>
                        @if(!empty($item['variant_label']))
                        @php
                            $parts = explode(' - ', $item['variant_label']);
                            $hasColors = collect($item['product_variants'])->contains(fn($v) => !empty($v['color']));
                            $currentColor = $hasColors ? ($parts[0] ?? null) : null;
                            $currentSize = count($parts) === 2 ? $parts[1] : (!$hasColors ? ($parts[0] ?? null) : null);
                            $itemIdx = $loop->index;
                            $sizeOptions = !empty($item['product_sizes']) ? $item['product_sizes'] : (!$hasColors ? array_map(fn($v) => $v['label'] ?? $v, $item['product_variants']) : []);
                        @endphp
                        <div x-data="variantPicker({{ $item['product_id'] }}, '{{ addslashes($currentColor ?? '') }}', '{{ addslashes($currentSize ?? '') }}', '{{ addslashes($item['variant_label']) }}', {{ $hasColors ? 'true' : 'false' }})" style="margin-top: 6px;">
                            <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                                @if($currentColor)
                                <span @click="editing = editing === 'color' ? null : 'color'" x-text="currentColor + ' ✎'" style="cursor: pointer; display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; background: #f1f5f9; color: #64748b; border-radius: 4px; font-size: 12px; transition: all 0.15s;" onmouseenter="this.style.background='#e0f2fe';this.style.color='#0B7285'" onmouseleave="this.style.background='#f1f5f9';this.style.color='#64748b'"></span>
                                @endif
                                @if($currentSize)
                                <span @click="editing = editing === 'size' ? null : 'size'" x-text="currentSize + ' ✎'" style="cursor: pointer; display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; background: #f1f5f9; color: #64748b; border-radius: 4px; font-size: 12px; transition: all 0.15s;" onmouseenter="this.style.background='#e0f2fe';this.style.color='#0B7285'" onmouseleave="this.style.background='#f1f5f9';this.style.color='#64748b'"></span>
                                @endif
                                <span x-show="updating" style="font-size: 11px; color: #0B7285;"><i class="ti-reload" style="display: inline-block; animation: spin-icon 1s linear infinite; font-size: 10px;"></i></span>
                            </div>
                            {{-- Sélecteur couleur --}}
                            @if($hasColors)
                            <div x-show="editing === 'color'" x-transition style="display: flex; gap: 8px; margin-top: 8px; padding: 8px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
                                @foreach($item['product_variants'] as $v)
                                @if(!empty($v['color']))
                                <button type="button" @click="pick('{{ $v['label'] }}{{ $currentSize ? ' - '.$currentSize : '' }}', '{{ $v['gelato_uid'] }}')" :style="'width:28px;height:28px;border-radius:50%;background:{{ $v['color'] }};cursor:pointer;transition:all 0.15s;border:2px solid '+(currentColor==='{{ $v['label'] }}'?'#0B7285':'#e2e8f0')" title="{{ $v['label'] }}" onmouseenter="this.style.transform='scale(1.15)'" onmouseleave="this.style.transform='scale(1)'"></button>
                                @endif
                                @endforeach
                            </div>
                            @endif
                            {{-- Sélecteur taille --}}
                            @if(!empty($sizeOptions))
                            <div x-show="editing === 'size'" x-transition style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; padding: 8px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
                                @foreach($sizeOptions as $sz)
                                @php
                                    $szVariant = collect($item['product_variants'])->first(fn($v) => ($v['label'] ?? '') === $sz);
                                    $szUid = $szVariant['gelato_uid'] ?? preg_replace('/_gsi_[^_]+_/', '_gsi_' . strtolower($sz) . '_', $item['gelato_variant_id'] ?? '');
                                    $newLabel = $currentColor ? $currentColor . ' - ' . $sz : $sz;
                                @endphp
                                <button type="button" @click="pick('{{ addslashes($newLabel) }}', '{{ $szUid }}')" :style="'padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;border:1.5px solid '+(currentSize==='{{ $sz }}'?'#0B7285':'#e2e8f0')+';background:'+(currentSize==='{{ $sz }}'?'#e0f2fe':'#f8fafc')+';color:'+(currentSize==='{{ $sz }}'?'#0B7285':'#64748b')" onmouseenter="this.style.borderColor='#0B7285'" onmouseleave="">{{ $sz }}</button>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                {{-- Ligne 2 : quantité + prix --}}
                <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" @click="changeQty(-1)" style="width: 34px; height: 34px; border-radius: 50%; border: 1.5px solid #0B7285; background: #fff; color: #0B7285; font-size: 18px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s;" onmouseenter="this.style.background='#0B7285';this.style.color='#fff'" onmouseleave="this.style.background='#fff';this.style.color='#0B7285'">-</button>
                        <span style="width: 28px; text-align: center; font-size: 18px; font-weight: 700; color: #1e293b;" x-text="qty"></span>
                        <button type="button" @click="changeQty(1)" style="width: 34px; height: 34px; border-radius: 50%; border: 1.5px solid #0B7285; background: #fff; color: #0B7285; font-size: 18px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s;" onmouseenter="this.style.background='#0B7285';this.style.color='#fff'" onmouseleave="this.style.background='#fff';this.style.color='#0B7285'">+</button>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 12px; color: #94a3b8;" x-text="unitPrice.toFixed(2).replace('.', ',') + ' $ × ' + qty"></div>
                        <div style="font-weight: 700; font-size: 18px; color: #0B7285;" x-text="(unitPrice * qty).toFixed(2).replace('.', ',') + ' $'"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        </div>{{-- end col-md-7 --}}
        <div class="col-md-5">
                <div x-data="shopCheckout()" @cart-updated.window="cartSubtotal=$event.detail.subtotal; cartTps=$event.detail.tps; cartTvq=$event.detail.tvq; cartTotal=$event.detail.total" style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; position: sticky; top: 100px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>{{ __('Sous-total') }}</span>
                        <span x-text="cartSubtotal.toFixed(2).replace('.', ',') + ' $'"></span>
                    </div>
                    <div x-show="country === 'CA'" style="margin-bottom: 8px; color: #64748b; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>{{ __('TPS') }} ({{ config('shop.tax.tps', 5) }}%) <span style="color: #94a3b8; font-size: 11px;">839145984</span></span>
                            <span x-text="cartTps.toFixed(2).replace('.', ',') + ' $'"></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>{{ __('TVQ') }} ({{ config('shop.tax.tvq', 9.975) }}%) <span style="color: #94a3b8; font-size: 11px;">1221788059</span></span>
                            <span x-text="cartTvq.toFixed(2).replace('.', ',') + ' $'"></span>
                        </div>
                    </div>
                    <hr style="margin: 12px 0;">
                    <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: 700;">
                        <span>{{ __('Total') }}</span>
                        <span style="color: #0B7285;" x-text="cartTotal.toFixed(2).replace('.', ',') + ' $'"></span>
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

                        {{-- Checkbox newsletter (LCAP/Loi 25 — case vide par défaut, opt-in explicite) --}}
                        <div style="margin-top: 16px; margin-bottom: 8px; background: #f0fdfa; border: 1px solid #d1fae5; border-radius: 8px; padding: 12px 14px;">
                            <label style="display: flex; gap: 10px; align-items: flex-start; cursor: pointer;">
                                <input type="checkbox" name="newsletter" value="1" style="display: inline-block !important; width: 20px !important; height: 20px !important; margin-top: 1px; accent-color: #0B7285; flex-shrink: 0; cursor: pointer; -webkit-appearance: checkbox !important; appearance: checkbox !important;">
                                <div>
                                    <span style="font-size: 14px; font-weight: 600; color: #1e293b; display: block;">{{ __('Restez informé') }} <i class="ti-email" style="color: #0B7285; font-size: 13px;"></i></span>
                                    <span style="font-size: 12px; color: #64748b; line-height: 1.4; display: block; margin-top: 2px;">{{ __('Recevez notre veille IA chaque mercredi — outils, actualités, défi et astuces. Désabonnement en 1 clic.') }}</span>
                                </div>
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
        country: 'CA',
        loading: false,
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

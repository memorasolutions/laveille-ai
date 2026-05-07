{{-- Mini-cart header — composant réutilisable du module Shop --}}
<style>.mini-cart-icon{font-size:26px!important;color:#333!important;padding:4px!important;cursor:pointer;transition:transform 0.2s ease;}.mini-cart-icon:hover{transform:scale(1.1);}</style>
@if(class_exists(\Modules\Shop\Models\Cart::class))
<div x-data="{ open: false }" @click.outside="open = false" @click="open = !open" style="position:relative; display:inline-flex; align-items:center; flex-shrink:0; margin-left:12px; margin-right:8px; cursor:pointer; vertical-align:middle;">
    <span class="mini-cart-icon ti-shopping-cart"></span>
    @include('fronttheme::partials.badge-count', ['count' => $cartItemCount ?? 0, 'color' => '#ef4444'])
    <div x-show="open" x-cloak x-transition style="position:absolute; right:0; top:40px; width:320px; background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.12); z-index:9999; padding:16px;">
        @if(($cartItemCount ?? 0) > 0)
            <h3 style="font-size:14px; font-weight:700; margin:0 0 12px;">{{ __('Panier') }} ({{ $cartItemCount }})</h3>
            <div style="max-height:240px; overflow-y:auto; margin-bottom:12px;">
                @foreach(($cartItems ?? []) as $item)
                    <div style="display:flex; align-items:center; margin-bottom:10px;">
                        @if(!empty($item['product_images'][0]))
                            <img src="{{ asset($item['product_images'][0]) }}" alt="" style="width:40px; height:40px; border-radius:4px; margin-right:10px; object-fit:cover;">
                        @endif
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $item['product_name'] }}</div>
                            @if(!empty($item['variant_label']))<div style="font-size:11px; color:#374151;">{{ $item['variant_label'] }}</div>@endif
                        </div>
                        <div style="font-size:13px; font-weight:600; margin-left:8px; white-space:nowrap;">{{ number_format($item['unit_price'] * $item['quantity'], 2, ',', ' ') }} $</div>
                    </div>
                @endforeach
            </div>
            <hr style="border:0; height:1px; background:#e5e7eb; margin:0 0 12px;">
            <div style="display:flex; justify-content:space-between; font-weight:700; margin-bottom:12px;">
                <span>{{ __('Sous-total') }}</span>
                <span style="color: var(--c-primary, #064E5A);">{{ number_format($cartTotal ?? 0, 2, ',', ' ') }} $</span>
            </div>
            <a href="{{ route('shop.cart') }}" style="display:block; background: var(--c-primary, #064E5A); color:#fff; border-radius:8px; padding:10px; text-align:center; font-weight:600; text-decoration:none;">{{ __('Voir le panier') }}</a>
        @else
            <div style="text-align:center; color:#374151; padding:20px 0;">
                <p style="margin-bottom:12px; color: var(--c-text-muted);">{{ __('Votre panier est vide') }}</p>
                <a href="{{ route('shop.index') }}" style="color: var(--c-primary, #064E5A); font-weight:600; text-decoration:none;">{{ __('Parcourir la boutique') }}</a>
            </div>
        @endif
    </div>
</div>
@endif

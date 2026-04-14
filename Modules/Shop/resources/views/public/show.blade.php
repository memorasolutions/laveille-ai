@extends(fronttheme_layout())

@php
    $metaDescription = $product->short_description
        ? Str::limit($product->short_description, 160)
        : Str::limit(strip_tags($product->description), 160);

    $productImage = file_exists(public_path('images/shop/tshirt-laveille-og.jpg'))
        ? asset('images/shop/tshirt-laveille-og.jpg')
        : (!empty($product->images)
            ? (Str::startsWith($product->images[0], ['http://', 'https://'])
                ? $product->images[0]
                : asset($product->images[0]))
            : null);
@endphp

@section('title', $product->name . ' - ' . __('Boutique'))
@section('meta_description', $metaDescription)
@section('og_type', 'product')
@if($productImage)
    @section('og_image', $productImage)
@endif

@php
    $schemaJson = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => $product->short_description ?: Str::limit(strip_tags($product->description), 300),
        'url' => route('shop.show', $product),
        'sku' => $product->slug,
        'brand' => ['@type' => 'Brand', 'name' => 'La veille'],
        'image' => $productImage,
        'offers' => [
            '@type' => 'Offer',
            'url' => route('shop.show', $product),
            'priceCurrency' => 'CAD',
            'price' => number_format($product->price, 2, '.', ''),
            'availability' => 'https://schema.org/InStock',
            'seller' => ['@type' => 'Organization', 'name' => 'La veille'],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

@push('head')
    <script type="application/ld+json">{!! $schemaJson !!}</script>
@endpush

@push('styles')
    <link rel="stylesheet" href="/css/shop.css">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $product->name,
        'breadcrumbItems' => [__('Boutique'), $product->name]
    ])
@endsection

@section('content')
<div class="container sp-container">

    @php
        $hasColorVariants = !empty($product->variants) && isset($product->variants[0]['color']);
        $hasSizeVariants = !empty($product->variants) && !isset($product->variants[0]['color']);
    @endphp

    <div class="row" x-data="{
        activeImage: 0,
        allVariants: {{ json_encode($product->variants ?? []) }},
        fallbackImages: {{ json_encode($product->images ?? []) }},
        selectedVariantIndex: 0,
        selectedSize: '{{ $product->metadata['sizes'][1] ?? 'M' }}',
        basePrice: {{ $product->price }},
        hasColors: {{ $hasColorVariants ? 'true' : 'false' }},
        get currentVariant() { return this.hasColors ? this.allVariants[this.selectedVariantIndex] : null; },
        get images() { return this.hasColors && this.currentVariant?.images?.length ? this.currentVariant.images : this.fallbackImages; },
        get currentPrice() { return this.currentVariant?.size_prices?.[this.selectedSize] ? parseFloat(this.currentVariant.size_prices[this.selectedSize]) : this.basePrice; },
        get availableSizes() { return this.currentVariant?.size_prices ? Object.keys(this.currentVariant.size_prices) : []; },
        selectColor(index) { this.selectedVariantIndex = index; this.activeImage = 0; },
        selectSize(size) { this.selectedSize = size; },
        formatPrice(v) { return parseFloat(v).toFixed(2).replace('.', ','); },
        surcharge(size) { if (!this.currentVariant?.size_prices?.[size]) return 0; const d = parseFloat(this.currentVariant.size_prices[size]) - this.basePrice; return d > 0.01 ? d : 0; }
    }">
        {{-- Image --}}
        <div class="col-md-6 sp-gallery-col">
            <template x-if="images.length > 0">
                <div>
                    <img :src="images[activeImage]" alt="{{ $product->name }}" class="sp-gallery-main">

                    {{-- Miniatures --}}
                    <template x-if="images.length > 1">
                        <div class="sp-gallery-thumbs">
                            <template x-for="(image, index) in images" :key="index">
                                <img :src="image" :alt="'{{ $product->name }}'" @click="activeImage = index" :class="'sp-gallery-thumb' + (activeImage === index ? ' active' : '')">
                            </template>
                        </div>
                    </template>

                    <div class="sp-gallery-notice">
                        <i class="fa fa-info-circle" aria-hidden="true"></i>
                        {{ __('Les images sont des simulations. Les couleurs du produit reçu peuvent légèrement varier en raison des différences entre les écrans et les procédés d\'impression.') }}
                    </div>
                </div>
            </template>
            <template x-if="images.length === 0">
                <div class="sp-gallery-fallback">{{ substr($product->name, 0, 1) }}</div>
            </template>
        </div>

        {{-- Infos produit --}}
        <div class="col-md-6">
            <h1 class="sp-product-title">{{ $product->name }}</h1>

            @if($product->description)
                <div class="rt-description sp-description">
                    {!! Str::markdown($product->description) !!}
                </div>
            @elseif($product->short_description)
                <p class="sp-description">{{ $product->short_description }}</p>
            @endif

            <div class="sp-price-block">
                <span class="sp-product-price" x-text="formatPrice(currentPrice) + ' $ CAD'">{{ number_format($product->price, 2, ',', ' ') }} $ CAD</span>
                @if($product->compare_price)
                    <span class="sp-product-price-old">{{ number_format($product->compare_price, 2, ',', ' ') }} $</span>
                @endif
                <div class="sp-product-tax-note">Taxes en sus (TPS + TVQ)</div>
            </div>

            @if($product->category)
                <p class="sp-category-badge-wrap"><span class="sp-category-badge">{{ $product->category }}</span></p>
            @endif

            {{-- Badges livraison + POD --}}
            <div class="sp-badge-group">
                <span class="sp-badge-pod">
                    <i class="fa fa-globe" aria-hidden="true"></i> {{ __('Livraison dans 200+ pays') }}
                </span>
                <span class="sp-badge-info">
                    <i class="fa fa-print" aria-hidden="true"></i> {{ __('Imprimé à la demande') }}
                </span>
            </div>

            {{-- Formulaire ajout au panier --}}
            <form action="{{ route('shop.cart.add') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                {{-- Sélecteur variante (couleur ou taille) --}}
                @if($hasColorVariants)
                    <div class="sp-variant-group">
                        <label class="sp-form-label">{{ __('Couleur') }}</label>
                        <div class="sp-variant-options">
                            <template x-for="(variant, index) in allVariants" :key="variant.gelato_uid">
                                <button type="button" @click="selectColor(index)"
                                    :style="'display:flex; align-items:center; padding:8px 16px; height:44px; border-radius:10px; cursor:pointer; transition:all 0.2s; font-size:14px; font-weight:600; outline:none; ' + (selectedVariantIndex === index ? 'background:#e0f7fa; border:2px solid #0B7285; color:#0B7285;' : 'background:#f1f5f9; border:1px solid #cbd5e1; color:#374151;')">
                                    <span class="sp-color-dot" :style="'background-color:' + variant.color + ';'"></span>
                                    <span x-text="variant.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <input type="hidden" name="variant_label" :value="(currentVariant?.label || '') + (selectedSize ? ' - ' + selectedSize : '')">
                    <input type="hidden" name="variant_gelato_uid" :value="currentVariant?.gelato_uid || ''">
                    {{-- Sélecteur tailles avec prix par taille --}}
                    <template x-if="availableSizes.length > 0">
                        <div class="sp-variant-group">
                            <label class="sp-form-label">{{ __('Taille') }}</label>
                            <div class="sp-size-options">
                                <template x-for="size in availableSizes" :key="size">
                                    <button type="button" @click="selectSize(size)"
                                        :style="'padding: 8px 16px; border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 600; outline: none; transition: all 0.2s; ' + (selectedSize === size ? 'background: #e0f7fa; border: 2px solid #0B7285; color: #0B7285;' : 'background: #f1f5f9; border: 1px solid #cbd5e1; color: #374151;')">
                                        <span x-text="size"></span>
                                        <span x-show="surcharge(size) > 0" x-text="' +' + formatPrice(surcharge(size)) + ' $'" class="sp-size-surcharge"></span>
                                    </button>
                                </template>
                            </div>
                            <input type="hidden" name="size_label" :value="selectedSize">
                        </div>
                    </template>
                @elseif($hasSizeVariants)
                    <div class="sp-variant-group" x-data="{ selectedSize: '{{ $product->variants[0]['label'] ?? 'M' }}' }">
                        @include('fronttheme::partials.pill-selector', [
                            'items' => array_map(fn($v) => $v['label'] ?? $v, $product->variants),
                            'alpineVar' => 'selectedSize',
                            'inputName' => 'variant_label',
                            'label' => in_array($product->category, ['t-shirts', 'hoodies']) ? __('Taille') : __('Variante'),
                            'extraData' => $product->variants,
                            'extraInput' => 'variant_gelato_uid',
                            'extraKey' => 'gelato_uid',
                            'matchKey' => 'label',
                        ])
                    </div>
                @endif

                <div class="sp-cart-actions">
                    <input type="number" name="quantity" value="1" min="1" max="10" class="sp-qty-input">
                    <button type="submit" class="sp-btn-primary">{{ __('Ajouter au panier') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Guide des tailles et specs --}}
    <div class="row sp-size-guide-row">
        <div class="col-sm-12">
            @include('shop::public.partials._size-guide', ['product' => $product])
        </div>
    </div>
</div>

@endsection

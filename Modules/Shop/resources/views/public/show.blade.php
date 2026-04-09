@extends(fronttheme_layout())

@section('title', $product->name . ' - ' . __('Boutique'))

@push('head')
    @php
        $metaDescription = $product->short_description
            ? Str::limit($product->short_description, 160)
            : Str::limit(strip_tags($product->description), 160);

        $productImage = !empty($product->images)
            ? (Str::startsWith($product->images[0], ['http://', 'https://'])
                ? $product->images[0]
                : asset($product->images[0]))
            : null;

        $productUrl = route('shop.show', $product);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->short_description ?: Str::limit(strip_tags($product->description), 300),
            'url' => $productUrl,
            'sku' => $product->slug,
            'brand' => ['@type' => 'Brand', 'name' => 'La veille'],
            'offers' => [
                '@type' => 'Offer',
                'url' => $productUrl,
                'priceCurrency' => 'CAD',
                'price' => number_format($product->price, 2, '.', ''),
                'availability' => 'https://schema.org/InStock',
                'seller' => ['@type' => 'Organization', 'name' => 'La veille'],
            ],
        ];

        if ($productImage) {
            $schema['image'] = $productImage;
        }

        if (!empty($product->compare_price) && $product->compare_price > $product->price) {
            $schema['offers']['priceValidUntil'] = now()->addDays(30)->toDateString();
        }
    @endphp

    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:title" content="{{ $product->name }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="product">
    <meta property="og:url" content="{{ $productUrl }}">
    @if($productImage)
        <meta property="og:image" content="{{ $productImage }}">
    @endif

    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $product->name,
        'breadcrumbItems' => [__('Boutique'), $product->name]
    ])
@endsection

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 40px;">

    @php
        $hasColorVariants = !empty($product->variants) && isset($product->variants[0]['color']);
        $hasSizeVariants = !empty($product->variants) && !isset($product->variants[0]['color']);
    @endphp

    <div class="row" x-data="{
        activeImage: 0,
        allVariants: {{ json_encode($product->variants ?? []) }},
        fallbackImages: {{ json_encode($product->images ?? []) }},
        selectedVariantIndex: 0,
        hasColors: {{ $hasColorVariants ? 'true' : 'false' }},
        get currentVariant() { return this.hasColors ? this.allVariants[this.selectedVariantIndex] : null; },
        get images() { return this.hasColors && this.currentVariant?.images?.length ? this.currentVariant.images : this.fallbackImages; },
        selectColor(index) { this.selectedVariantIndex = index; this.activeImage = 0; }
    }">
        {{-- Image --}}
        <div class="col-md-6" style="margin-bottom: 24px;">
            <template x-if="images.length > 0">
                <div>
                    <img :src="images[activeImage]" alt="{{ $product->name }}" style="width: 100%; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">

                    {{-- Sélecteur couleurs déplacé dans la zone formulaire (col droite) --}}

                    {{-- Miniatures (si plusieurs images pour la même couleur/produit) --}}
                    <template x-if="images.length > 1">
                        <div style="display: flex; gap: 10px; margin-top: 10px;">
                            <template x-for="(image, index) in images" :key="index">
                                <img :src="image" :alt="'{{ $product->name }}'" @click="activeImage = index" :style="'width:80px;height:80px;object-fit:cover;border-radius:8px;cursor:pointer;' + (activeImage === index ? 'border:2px solid #0B7285;' : 'border:2px solid transparent;')">
                            </template>
                        </div>
                    </template>

                    <div style="margin-top: 12px;">
                        <i class="fa fa-info-circle" style="font-size: 12px; color: #94a3b8;"></i>
                        <span style="font-size: 12px; color: #94a3b8; font-style: italic;">{{ __('Les images sont des simulations. Les couleurs du produit reçu peuvent légèrement varier en raison des différences entre les écrans et les procédés d\'impression.') }}</span>
                    </div>
                </div>
            </template>
            <template x-if="images.length === 0">
                <div style="width: 100%; height: 400px; background: linear-gradient(135deg, #0B7285, #0CA678); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 72px; font-weight: 700;">{{ substr($product->name, 0, 1) }}</div>
            </template>
        </div>

        {{-- Infos produit --}}
        <div class="col-md-6">
            <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 12px;">{{ $product->name }}</h1>

            @if($product->description)
                <div class="rt-description" style="font-size: 15px; line-height: 1.7; color: #475569; margin-bottom: 16px;">
                    {!! Str::markdown($product->description) !!}
                </div>
            @elseif($product->short_description)
                <p style="color: #64748b; font-size: 15px; margin-bottom: 16px;">{{ $product->short_description }}</p>
            @endif

            <div style="margin-bottom: 20px;">
                <span style="font-size: 28px; font-weight: 700; color: #0B7285;">{{ number_format($product->price, 2, ',', ' ') }} $ CAD</span>
                @if($product->compare_price)
                    <span style="font-size: 16px; color: #94a3b8; text-decoration: line-through; margin-left: 8px;">{{ number_format($product->compare_price, 2, ',', ' ') }} $</span>
                @endif
                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Taxes en sus (TPS + TVQ)</div>
            </div>

            @if($product->category)
                <p style="margin-bottom: 16px;"><span style="background: #f1f5f9; padding: 4px 12px; border-radius: 20px; font-size: 13px; color: #475569;">{{ $product->category }}</span></p>
            @endif

            {{-- Badges livraison + POD --}}
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
                <span style="display: inline-flex; align-items: center; gap: 5px; background: #f0fdfa; border: 1px solid #d1fae5; padding: 5px 12px; border-radius: 20px; font-size: 12px; color: #0B7285; font-weight: 600;">
                    <i class="fa fa-globe" aria-hidden="true"></i> {{ __('Livraison dans 200+ pays') }}
                </span>
                <span style="display: inline-flex; align-items: center; gap: 5px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 5px 12px; border-radius: 20px; font-size: 12px; color: #64748b;">
                    <i class="fa fa-print" aria-hidden="true"></i> {{ __('Imprimé à la demande') }}
                </span>
            </div>

            {{-- Formulaire ajout au panier --}}
            <form action="{{ route('shop.cart.add') }}" method="POST" style="margin-top: 24px;">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                {{-- Sélecteur variante (couleur ou taille) --}}
                @if($hasColorVariants)
                    <div style="margin-bottom: 16px;">
                        <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 6px;">{{ __('Couleur') }}</label>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <template x-for="(variant, index) in allVariants" :key="variant.gelato_uid">
                                <button type="button" @click="selectColor(index)"
                                    :style="'display:flex; align-items:center; padding:8px 16px; height:44px; border-radius:10px; cursor:pointer; transition:all 0.2s; font-size:14px; font-weight:600; outline:none; ' + (selectedVariantIndex === index ? 'background:#e0f7fa; border:2px solid #0B7285; color:#0B7285;' : 'background:#f1f5f9; border:1px solid #cbd5e1; color:#374151;')">
                                    <span :style="'display:inline-block; width:16px; height:16px; border-radius:50%; margin-right:8px; background-color:' + variant.color + ';'"></span>
                                    <span x-text="variant.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <input type="hidden" name="variant_label" :value="currentVariant?.label || ''">
                    <input type="hidden" name="variant_gelato_uid" :value="currentVariant?.gelato_uid || ''">
                    {{-- Sélecteur tailles (si le produit a aussi des tailles, ex: hoodies) --}}
                    @if($product->metadata['sizes'] ?? null)
                        <div x-data="{ selectedSize: '{{ $product->metadata['sizes'][1] ?? 'M' }}' }" style="margin-bottom: 16px;">
                            @include('fronttheme::partials.pill-selector', [
                                'items' => $product->metadata['sizes'],
                                'alpineVar' => 'selectedSize',
                                'inputName' => 'size_label',
                                'label' => __('Taille'),
                            ])
                        </div>
                    @endif
                @elseif($hasSizeVariants)
                    <div style="margin-bottom: 16px;" x-data="{ selectedSize: '{{ $product->variants[0]['label'] ?? 'M' }}' }">
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

                <div style="display: flex; align-items: center; gap: 12px;">
                    <input type="number" name="quantity" value="1" min="1" max="10" style="width: 80px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center;">
                    <button type="submit" class="btn" style="background: #0B7285; color: #fff; padding: 10px 32px; border-radius: 6px; font-weight: 600;">{{ __('Ajouter au panier') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Guide des tailles et specs --}}
    <div class="row" style="margin-top: 30px;">
        <div class="col-sm-12">
            @include('shop::public.partials._size-guide', ['product' => $product])
        </div>
    </div>

    {{-- Description déplacée en haut dans la zone info produit --}}
</div>

{{-- Schema.org Product --}}
@if(isset($schema))
    <script type="application/ld+json">{!! $schema !!}</script>
@endif
@endsection

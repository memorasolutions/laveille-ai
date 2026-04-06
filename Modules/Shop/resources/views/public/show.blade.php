@extends(fronttheme_layout())

@section('title', $product->name . ' - ' . __('Boutique'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $product->name,
        'breadcrumbItems' => [__('Boutique'), $product->name]
    ])
@endsection

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 40px;">

    <div class="row">
        {{-- Image --}}
        <div class="col-md-6" style="margin-bottom: 24px;">
            @if(!empty($product->images))
                <div x-data="{ activeImage: 0, images: {{ json_encode($product->images) }} }">
                    <img :src="images[activeImage]" alt="{{ $product->name }}" style="width: 100%; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">

                    @if(count($product->images) > 1)
                        <div style="display: flex; gap: 10px; margin-top: 10px;">
                            <template x-for="(image, index) in images" :key="index">
                                <img :src="image" :alt="'{{ $product->name }}'" @click="activeImage = index" :style="'width:80px;height:80px;object-fit:cover;border-radius:8px;cursor:pointer;' + (activeImage === index ? 'border:2px solid #0B7285;' : 'border:2px solid transparent;')">
                            </template>
                        </div>
                        <div style="margin-top: 12px;">
                            <i class="fa fa-info-circle" style="font-size: 12px; color: #94a3b8;"></i>
                            <span style="font-size: 12px; color: #94a3b8; font-style: italic;">{{ __('Les images sont des simulations. Les couleurs du produit reçu peuvent légèrement varier en raison des différences entre les écrans et les procédés d\'impression.') }}</span>
                        </div>
                    @endif
                </div>
            @else
                <div style="width: 100%; height: 400px; background: linear-gradient(135deg, #0B7285, #0CA678); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 72px; font-weight: 700;">{{ substr($product->name, 0, 1) }}</div>
            @endif
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

            {{-- Formulaire ajout au panier --}}
            <form action="{{ route('shop.cart.add') }}" method="POST" style="margin-top: 24px;">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                @if(!empty($product->variants))
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

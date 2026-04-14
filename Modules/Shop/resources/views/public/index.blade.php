@extends(fronttheme_layout())

@push('styles')
    <link rel="stylesheet" href="/css/shop.css">
@endpush

@section('title', __('Boutique'))

@section('meta_description', __('Boutique La veille - T-shirts, tasses et hoodies pour les passionnés de technologie et d\'IA.'))
@section('og_image', asset('images/shop/tshirt-laveille-og.jpg'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => __('Boutique'),
        'breadcrumbItems' => [__('Boutique')]
    ])
@endsection

@section('content')
<div class="container sp-container">
    <div class="sp-page-header">
        <h1 class="sp-page-title">{{ __('Boutique') }}</h1>
        <p class="sp-page-subtitle">{{ $products->total() }} {{ trans_choice('produit imprimé à la demande au Canada|produits imprimés à la demande au Canada', $products->total()) }}</p>
    </div>

    @if(config('shop.maintenance', false))
        <div class="sp-maintenance">
            <div class="sp-maintenance-icon">🛠️</div>
            <h2 class="sp-maintenance-title">{{ __('Boutique en préparation') }}</h2>
            <p class="sp-maintenance-desc">{{ __('Nous préparons de nouveaux produits pour vous. Revenez bientôt pour découvrir notre collection mise à jour.') }}</p>
        </div>
    @elseif($products->isEmpty())
        <div class="alert alert-info">{{ __('Aucun produit disponible pour le moment.') }}</div>
    @else
        <div class="row">
            @foreach ($products as $product)
                <div class="col-md-4 col-sm-6" style="margin-bottom: 30px;">
                    <div class="sp-card">
                        <a href="{{ route('shop.show', $product) }}">
                            @if(!empty($product->images) && isset($product->images[0]))
                                <img src="{{ $product->images[0] }}" alt="{{ $product->name }}" loading="lazy" class="sp-card-img">
                            @else
                                <div class="sp-card-fallback">{{ substr($product->name, 0, 1) }}</div>
                            @endif
                        </a>
                        <div class="sp-card-body">
                            <h3 class="sp-card-title">
                                <a href="{{ route('shop.show', $product) }}">{{ $product->name }}</a>
                            </h3>
                            @if($product->description)
                                <p class="sp-card-desc">{{ Str::limit(strip_tags($product->description), 100) }}</p>
                            @elseif($product->short_description)
                                <p class="sp-card-desc">{{ Str::limit($product->short_description, 100) }}</p>
                            @endif
                            <div class="sp-card-footer">
                                <div>
                                    <span class="sp-card-price">{{ number_format($product->price, 2, ',', ' ') }} $ CAD</span>
                                    @if($product->compare_price)
                                        <span class="sp-card-price-old">{{ number_format($product->compare_price, 2, ',', ' ') }} $</span>
                                    @endif
                                </div>
                                <a href="{{ route('shop.show', $product) }}" class="sp-btn-primary sp-btn-sm">{{ __('Voir') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-center" style="margin-top: 20px;">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection

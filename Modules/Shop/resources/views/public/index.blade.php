@extends(fronttheme_layout())

@section('title', __('Boutique'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 40px;">
    <div class="row">
        <div class="col-sm-12">
            <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 24px;">{{ __('Boutique') }}</h1>
        </div>
    </div>

    @if($products->isEmpty())
        <div class="alert alert-info">{{ __('Aucun produit disponible pour le moment.') }}</div>
    @else
        <div class="row">
            @foreach ($products as $product)
                <div class="col-md-4 col-sm-6" style="margin-bottom: 30px;">
                    <div style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; height: 100%;">
                        <a href="{{ route('shop.show', $product) }}">
                            @if(!empty($product->images) && isset($product->images[0]))
                                <img src="{{ $product->images[0] }}" alt="{{ $product->name }}" style="width: 100%; height: 220px; object-fit: cover;">
                            @else
                                <div style="width: 100%; height: 220px; background: linear-gradient(135deg, #0B7285, #0CA678); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 48px; font-weight: 700;">{{ substr($product->name, 0, 1) }}</div>
                            @endif
                        </a>
                        <div style="padding: 16px;">
                            <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 8px;">
                                <a href="{{ route('shop.show', $product) }}" style="color: #1e293b; text-decoration: none;">{{ $product->name }}</a>
                            </h3>
                            @if($product->short_description)
                                <p style="color: #64748b; font-size: 13px; margin-bottom: 12px; line-height: 1.4;">{{ Str::limit($product->short_description, 80) }}</p>
                            @endif
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <span style="font-size: 18px; font-weight: 700; color: #0B7285;">{{ number_format($product->price, 2, ',', ' ') }} $</span>
                                    @if($product->compare_price)
                                        <span style="font-size: 13px; color: #94a3b8; text-decoration: line-through; margin-left: 6px;">{{ number_format($product->compare_price, 2, ',', ' ') }} $</span>
                                    @endif
                                </div>
                                <a href="{{ route('shop.show', $product) }}" class="btn btn-sm" style="background: #0B7285; color: #fff; border-radius: 6px; padding: 6px 16px; font-size: 13px;">{{ __('Voir') }}</a>
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

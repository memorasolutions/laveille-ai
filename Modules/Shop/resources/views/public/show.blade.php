@extends(fronttheme_layout())

@section('title', $product->name . ' - ' . __('Boutique'))

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 40px;">
    {{-- Breadcrumb --}}
    <ol class="breadcrumb" style="background: transparent; padding: 0; margin-bottom: 20px;">
        <li><a href="{{ route('home') }}">{{ __('Accueil') }}</a></li>
        <li><a href="{{ route('shop.index') }}">{{ __('Boutique') }}</a></li>
        <li class="active">{{ $product->name }}</li>
    </ol>

    <div class="row">
        {{-- Image --}}
        <div class="col-md-6" style="margin-bottom: 24px;">
            @if(!empty($product->images) && isset($product->images[0]))
                <img src="{{ $product->images[0] }}" alt="{{ $product->name }}" style="width: 100%; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            @else
                <div style="width: 100%; height: 400px; background: linear-gradient(135deg, #0B7285, #0CA678); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 72px; font-weight: 700;">{{ substr($product->name, 0, 1) }}</div>
            @endif
        </div>

        {{-- Infos produit --}}
        <div class="col-md-6">
            <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 12px;">{{ $product->name }}</h1>

            @if($product->short_description)
                <p style="color: #64748b; font-size: 15px; margin-bottom: 16px;">{{ $product->short_description }}</p>
            @endif

            <div style="margin-bottom: 20px;">
                <span style="font-size: 28px; font-weight: 700; color: #0B7285;">{{ number_format($product->price, 2, ',', ' ') }} $</span>
                @if($product->compare_price)
                    <span style="font-size: 16px; color: #94a3b8; text-decoration: line-through; margin-left: 8px;">{{ number_format($product->compare_price, 2, ',', ' ') }} $</span>
                @endif
            </div>

            @if($product->category)
                <p style="margin-bottom: 16px;"><span style="background: #f1f5f9; padding: 4px 12px; border-radius: 20px; font-size: 13px; color: #475569;">{{ $product->category }}</span></p>
            @endif

            {{-- Formulaire ajout au panier --}}
            <form action="{{ route('shop.cart.add') }}" method="POST" style="margin-top: 24px;">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                @if(!empty($product->variants))
                    <div style="margin-bottom: 16px;">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;">{{ __('Variante') }}</label>
                        @foreach($product->variants as $variant)
                            <label style="display: inline-block; margin-right: 12px; cursor: pointer;">
                                <input type="radio" name="variant_label" value="{{ $variant['label'] ?? $variant }}" {{ $loop->first ? 'checked' : '' }}>
                                {{ $variant['label'] ?? $variant }}
                            </label>
                        @endforeach
                    </div>
                @endif

                <div style="display: flex; align-items: center; gap: 12px;">
                    <input type="number" name="quantity" value="1" min="1" max="10" style="width: 80px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center;">
                    <button type="submit" class="btn" style="background: #0B7285; color: #fff; padding: 10px 32px; border-radius: 6px; font-weight: 600;">{{ __('Ajouter au panier') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Description --}}
    @if($product->description)
        <div class="row" style="margin-top: 40px;">
            <div class="col-sm-12">
                <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 16px;">{{ __('Description') }}</h2>
                <div class="rt-description" style="font-size: 15px; line-height: 1.8; color: #475569;">
                    {!! Str::markdown($product->description) !!}
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Schema.org Product --}}
@if(isset($schema))
    <script type="application/ld+json">{!! $schema !!}</script>
@endif
@endsection

@extends(fronttheme_layout())

@section('title', __('Confirmation de commande'))

@section('content')
<div class="container" style="padding-top: 40px; padding-bottom: 60px;">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div style="background: #fff; padding: 40px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 48px; margin-bottom: 16px; color: #0CA678;">&#10003;</div>
                <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 12px;">{{ __('Merci pour votre achat!') }}</h1>
                <p style="color: #64748b; font-size: 16px; margin-bottom: 24px;">{{ __('Votre commande a été enregistrée avec succès.') }}</p>

                <div style="background: #f8fafc; padding: 20px; border-radius: 8px; text-align: left; margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-weight: 600;">{{ __('Commande') }}</span>
                        <span>#{{ $order->id }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-weight: 600;">{{ __('Statut') }}</span>
                        <span style="color: #f59e0b;">{{ ucfirst($order->status) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-weight: 600;">{{ __('Total') }}</span>
                        <span style="font-weight: 700; color: #0B7285;">{{ number_format($order->total, 2, ',', ' ') }} $</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="font-weight: 600;">{{ __('Courriel') }}</span>
                        <span>{{ $order->email }}</span>
                    </div>
                </div>

                @if($order->items->isNotEmpty())
                    <div style="text-align: left; margin-bottom: 24px;">
                        <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">{{ __('Articles commandés') }}</h3>
                        @foreach($order->items as $item)
                            <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9;">
                                <span>{{ $item->product?->name ?? __('Produit') }} {{ $item->variant_label ? '('.$item->variant_label.')' : '' }} x{{ $item->quantity }}</span>
                                <span style="font-weight: 600;">{{ number_format($item->unit_price * $item->quantity, 2, ',', ' ') }} $</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <p style="color: #64748b; font-size: 14px;">{{ __('Un courriel de confirmation sera envoyé à') }} {{ $order->email }}.</p>

                <a href="{{ route('shop.index') }}" class="btn" style="background: #0B7285; color: #fff; padding: 10px 24px; border-radius: 6px; margin-top: 16px;">{{ __('Retourner à la boutique') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection

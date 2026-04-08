@extends(fronttheme_layout())

@section('title', __('Confirmation de commande'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Confirmation'), 'breadcrumbItems' => [__('Boutique'), __('Confirmation')]])
@endsection

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 40px;">
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
                <p style="color: #94a3b8; font-size: 12px; margin-top: 8px;">{{ __('Sur votre relevé bancaire, cette transaction apparaîtra sous le nom MEMORA* LAVEILLE.AI') }}</p>

                <a href="{{ route('shop.index') }}" class="btn" style="background: #0B7285; color: #fff; padding: 10px 24px; border-radius: 6px; margin-top: 16px;">{{ __('Retourner à la boutique') }}</a>

                {{-- Account nudge (guest seulement) --}}
                @guest
                <div style="background: #f0fdfa; border: 1px solid #0B7285; border-radius: 8px; padding: 16px; margin-top: 20px; text-align: center;">
                    <i class="ti-user" style="color: #0B7285; font-size: 20px;"></i>
                    <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin-top: 8px; margin-bottom: 4px;">{{ __('Créer un compte') }}</h3>
                    <p style="font-size: 13px; color: #475569; margin-bottom: 16px;">{{ __('Suivez vos commandes et accélérez vos prochains achats.') }}</p>
                    <a href="{{ route('login', ['email' => $order->email]) }}" style="background: #0B7285; color: #fff; padding: 8px 20px; border-radius: 6px; font-weight: 600; font-size: 14px; text-decoration: none;">{{ __('Créer mon compte') }}</a>
                </div>
                @endguest
            </div>
        </div>
    </div>
</div>
@endsection

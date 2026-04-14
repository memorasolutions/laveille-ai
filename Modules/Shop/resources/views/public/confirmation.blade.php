@extends(fronttheme_layout())

@section('title', __('Confirmation de commande'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
<link rel="stylesheet" href="/css/shop.css">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Confirmation'), 'breadcrumbItems' => [__('Boutique'), __('Confirmation')]])
@endsection

@section('content')
<div class="container sp-container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="sp-confirm-box">
                <div class="sp-confirm-icon">&#10003;</div>
                <h1 class="sp-confirm-title">{{ __('Merci pour votre achat!') }}</h1>
                <p class="sp-confirm-subtitle">{{ __('Votre commande a été enregistrée avec succès.') }}</p>

                <div class="sp-detail-box">
                    <div class="sp-detail-row">
                        <span class="sp-detail-label">{{ __('Commande') }}</span>
                        <span>#{{ $order->order_number ?? $order->id }}</span>
                    </div>
                    <div class="sp-detail-row">
                        <span class="sp-detail-label">{{ __('Statut') }}</span>
                        <span style="color: #f59e0b;">{{ __(['pending' => 'En attente', 'paid' => 'Payé', 'shipped' => 'Expédié', 'fulfilled' => 'Complété', 'cancelled' => 'Annulé', 'refunded' => 'Remboursé'][$order->status] ?? ucfirst($order->status)) }}</span>
                    </div>
                    <div class="sp-detail-row">
                        <span class="sp-detail-label">{{ __('Total') }}</span>
                        <span style="font-weight: 700; color: var(--c-primary);">{{ number_format($order->total, 2, ',', ' ') }} $</span>
                    </div>
                    <div class="sp-detail-row">
                        <span class="sp-detail-label">{{ __('Courriel') }}</span>
                        <span>{{ $order->email }}</span>
                    </div>
                </div>

                @if($order->items->isNotEmpty())
                    <div class="sp-items-list">
                        <h3 class="sp-items-title">{{ __('Articles commandés') }}</h3>
                        @foreach($order->items as $item)
                            <div class="sp-item-row">
                                <span>{{ $item->product?->name ?? __('Produit') }} {{ $item->variant_label ? '('.$item->variant_label.')' : '' }} x{{ $item->quantity }}</span>
                                <span class="sp-item-price">{{ number_format($item->unit_price * $item->quantity, 2, ',', ' ') }} $</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <p style="color: var(--c-text-muted); font-size: 14px;">{{ __('Un courriel de confirmation sera envoyé à') }} {{ $order->email }}.</p>
                <p class="sp-statement-note">{{ __('Sur votre relevé bancaire, cette transaction apparaîtra sous le nom MEMORA* LAVEILLE.AI') }}</p>

                <a href="{{ route('shop.index') }}" class="sp-btn-primary" style="margin-top: 16px;">{{ __('Retourner à la boutique') }}</a>

                {{-- Account nudge (guest seulement) --}}
                @guest
                <div class="sp-nudge">
                    <i class="ti-user" style="color: var(--c-primary); font-size: 20px;"></i>
                    <h3 class="sp-nudge-title">{{ __('Créer un compte') }}</h3>
                    <p class="sp-nudge-desc">{{ __('Suivez vos commandes et accélérez vos prochains achats.') }}</p>
                    <a href="{{ route('login', ['email' => $order->email]) }}" class="sp-btn-primary sp-btn-sm">{{ __('Créer mon compte') }}</a>
                </div>
                @endguest
            </div>
        </div>
    </div>
</div>
@endsection

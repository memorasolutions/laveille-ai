@extends('backoffice::layouts.admin', ['title' => __('Commande') . ' #' . ($order->order_number ?? $order->id)])


@section('content')
<div class="row">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ __('Commande') }} #{{ $order->order_number ?? $order->id }}</h4>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>{{ __('Client') }}</h6>
                        <p>{{ $order->email }}</p>
                        @if($order->user)
                            <p class="text-muted">{{ __('Utilisateur') }} #{{ $order->user->id }} - {{ $order->user->name ?? $order->user->email }}</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6>{{ __('Adresse de livraison') }}</h6>
                        @php $addr = $order->shipping_address ?? []; @endphp
                        <p>
                            {{ $addr['first_name'] ?? '' }} {{ $addr['last_name'] ?? '' }}<br>
                            {{ $addr['address_line1'] ?? '' }}<br>
                            {{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }} {{ $addr['postal_code'] ?? '' }}<br>
                            {{ $addr['country'] ?? 'CA' }}
                        </p>
                    </div>
                </div>

                <h6>{{ __('Articles') }}</h6>
                <div class="table-responsive mb-3">
                    <table class="table">
                        <thead><tr><th>{{ __('Produit') }}</th><th>{{ __('Variante') }}</th><th>{{ __('Qte') }}</th><th>{{ __('Prix') }}</th><th>{{ __('Total') }}</th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                            <tr>
                                <td>{{ $item->product?->name ?? __('Produit supprime') }}</td>
                                <td>{{ $item->variant_label ?? '-' }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->unit_price, 2, ',', ' ') }} $</td>
                                <td>{{ number_format($item->unit_price * $item->quantity, 2, ',', ' ') }} $</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <table class="table table-borderless">
                            <tr><td>{{ __('Sous-total') }}</td><td class="text-end">{{ number_format($order->subtotal, 2, ',', ' ') }} $</td></tr>
                            <tr><td>{{ __('Taxes') }}</td><td class="text-end">{{ number_format($order->tax_amount, 2, ',', ' ') }} $</td></tr>
                            <tr><td>{{ __('Livraison') }}</td><td class="text-end">{{ number_format($order->shipping_cost, 2, ',', ' ') }} $</td></tr>
                            <tr class="fw-bold"><td>{{ __('Total') }}</td><td class="text-end">{{ number_format($order->total, 2, ',', ' ') }} $</td></tr>
                        </table>
                    </div>
                </div>

                @if($order->tracking_number)
                    <div class="alert alert-info">
                        {{ __('Suivi') }} : <strong>{{ $order->tracking_number }}</strong>
                        @if($order->tracking_url)
                            - <a href="{{ $order->tracking_url }}" target="_blank">{{ __('Suivre le colis') }}</a>
                        @endif
                    </div>
                @endif

                <div class="mt-3">
                    <a href="{{ route('admin.shop.orders.index') }}" class="btn btn-outline-primary">{{ __('Retour') }}</a>
                    @if(!in_array($order->status, ['shipped', 'delivered', 'cancelled']))
                        <form action="{{ route('admin.shop.orders.cancel', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-danger" onclick="return confirm('{{ __('Annuler cette commande ?') }}')">{{ __('Annuler') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h6>{{ __('Identifiants') }}</h6>
                <p><small class="text-muted">Stripe session : {{ $order->stripe_session_id ?? '-' }}</small></p>
                <p><small class="text-muted">Stripe PI : {{ $order->stripe_payment_intent_id ?? '-' }}</small></p>
                <p><small class="text-muted">Gelato : {{ $order->gelato_order_id ?? '-' }}</small></p>
                @if($order->notes)
                    <h6 class="mt-3">{{ __('Notes') }}</h6>
                    <p>{{ $order->notes }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

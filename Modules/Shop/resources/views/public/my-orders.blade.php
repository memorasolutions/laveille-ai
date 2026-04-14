@extends(fronttheme_layout())

@section('title', __('Mes commandes'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
<link rel="stylesheet" href="/css/shop.css">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Mes commandes'), 'breadcrumbItems' => [__('Boutique'), __('Mes commandes')]])
@endsection

@section('content')
<div class="container sp-container">
    <h1 class="sp-page-title" style="margin-bottom: 24px;">{{ __('Mes commandes') }}</h1>

    @if($orders->isEmpty())
        <div class="alert alert-info">{{ __('Vous n\'avez pas encore de commande.') }}</div>
    @else
        <div class="table-responsive">
            <table class="table table-striped sp-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Montant') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->order_number ?? $order->id }}</td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ number_format($order->total, 2, ',', ' ') }} $</td>
                        <td>
                            @php
                                $badges = [
                                    'pending' => ['label' => 'En attente', 'class' => 'label-warning'],
                                    'paid' => ['label' => 'Payée', 'class' => 'label-success'],
                                    'processing' => ['label' => 'En production', 'class' => 'label-info'],
                                    'shipped' => ['label' => 'Expédiée', 'class' => 'label-info'],
                                    'fulfilled' => ['label' => 'Complétée', 'class' => 'label-success'],
                                    'delivered' => ['label' => 'Livrée', 'class' => 'label-success'],
                                    'cancelled' => ['label' => 'Annulée', 'class' => 'label-danger'],
                                    'refunded' => ['label' => 'Remboursée', 'class' => 'label-default'],
                                ];
                                $badge = $badges[$order->status] ?? ['label' => ucfirst($order->status), 'class' => 'label-default'];
                            @endphp
                            <span class="label {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        </td>
                        <td>
                            <a href="{{ route('shop.confirmation', $order) }}" class="sp-btn-primary sp-btn-xs">{{ __('Voir') }}</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="text-center" style="margin-top: 20px;">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection

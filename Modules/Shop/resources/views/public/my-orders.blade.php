@extends(fronttheme_layout())

@section('title', __('Mes commandes'))

@push('head')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Mes commandes'), 'breadcrumbItems' => [__('Boutique'), __('Mes commandes')]])
@endsection

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 40px;">
    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 24px;">{{ __('Mes commandes') }}</h1>

    @if($orders->isEmpty())
        <div class="alert alert-info">{{ __('Vous n\'avez pas encore de commande.') }}</div>
    @else
        <div class="table-responsive">
            <table class="table table-striped" style="background: #fff; border-radius: 8px; overflow: hidden;">
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
                        <td>{{ $order->id }}</td>
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
                            <a href="{{ route('shop.confirmation', $order) }}" class="btn btn-xs" style="background: #0B7285; color: #fff; border-radius: 4px;">{{ __('Voir') }}</a>
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

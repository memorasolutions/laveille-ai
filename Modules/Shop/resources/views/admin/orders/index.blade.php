@extends('backoffice::layouts.admin', ['title' => __('Commandes boutique')])

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">{{ __('Commandes') }}</h4>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <form action="{{ route('admin.shop.orders.index') }}" method="GET">
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">{{ __('Tous les statuts') }}</option>
                                @foreach ($statuses as $s)
                                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Courriel') }}</th>
                                <th>{{ __('Statut') }}</th>
                                <th>{{ __('Total') }}</th>
                                <th>{{ __('Articles') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                            <tr>
                                <td>{{ $order->order_number ?? $order->id }}</td>
                                <td>{{ $order->email }}</td>
                                <td>
                                    @switch($order->status)
                                        @case('pending') <span class="badge bg-secondary">{{ __('En attente') }}</span> @break
                                        @case('paid') <span class="badge bg-success">{{ __('Payé') }}</span> @break
                                        @case('processing') <span class="badge bg-warning text-dark">{{ __('En production') }}</span> @break
                                        @case('fulfilled') <span class="badge bg-info">{{ __('Produit') }}</span> @break
                                        @case('shipped') <span class="badge bg-primary">{{ __('Expédié') }}</span> @break
                                        @case('delivered') <span class="badge bg-success">{{ __('Livré') }}</span> @break
                                        @case('cancelled') <span class="badge bg-danger">{{ __('Annulé') }}</span> @break
                                        @case('refunded') <span class="badge bg-dark">{{ __('Remboursé') }}</span> @break
                                    @endswitch
                                </td>
                                <td>{{ number_format($order->total, 2, ',', ' ') }} $</td>
                                <td>{{ $order->items_count }}</td>
                                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td><a href="{{ route('admin.shop.orders.show', $order) }}" class="btn btn-outline-primary btn-sm">{{ __('Details') }}</a></td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center">{{ __('Aucune commande.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3">{{ $orders->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection

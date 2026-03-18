<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Remboursements')

@section('content')
<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Tableau de bord</a></li>
        <li class="breadcrumb-item active" aria-current="page">Remboursements</li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Demandes de remboursement</h6>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Commande</th>
                                <th>Client</th>
                                <th>Montant</th>
                                <th>Raison</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($refunds as $refund)
                            <tr>
                                <td>{{ $refund->id }}</td>
                                <td>
                                    <a href="{{ route('admin.ecommerce.orders.show', $refund->order_id) }}">
                                        {{ $refund->order->order_number ?? '-' }}
                                    </a>
                                </td>
                                <td>{{ $refund->user->name ?? '-' }}</td>
                                <td>{{ number_format($refund->amount, 2) }} $</td>
                                <td class="text-truncate" style="max-width:200px;">{{ $refund->reason ?? '-' }}</td>
                                <td>
                                    @switch($refund->status)
                                        @case('pending')
                                            <span class="badge bg-warning text-dark">En attente</span>
                                            @break
                                        @case('approved')
                                            <span class="badge bg-success">Approuvé</span>
                                            @break
                                        @case('rejected')
                                            <span class="badge bg-danger">Refusé</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ $refund->status }}</span>
                                    @endswitch
                                </td>
                                <td>{{ $refund->created_at->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    @if($refund->status === 'pending')
                                    <div class="d-flex justify-content-end gap-2">
                                        <form action="{{ route('admin.ecommerce.refunds.approve', $refund) }}" method="POST" onsubmit="return confirm('Approuver ce remboursement ?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success btn-icon" title="Approuver">
                                                <i data-lucide="check"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.ecommerce.refunds.reject', $refund) }}" method="POST" onsubmit="return confirm('Refuser ce remboursement ?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Refuser">
                                                <i data-lucide="x"></i>
                                            </button>
                                        </form>
                                    </div>
                                    @else
                                        <span class="text-muted small">{{ $refund->processed_at?->format('Y-m-d') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">Aucune demande de remboursement.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $refunds->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

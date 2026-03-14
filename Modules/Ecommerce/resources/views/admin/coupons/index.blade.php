@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Coupons', 'subtitle' => 'Boutique'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.dashboard') }}">Boutique</a></li>
        <li class="breadcrumb-item active" aria-current="page">Coupons</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="ticket" class="icon-md text-primary"></i> Coupons
    </h4>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

<div class="card">
    <div class="card-header py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Codes promo</h5>
        <a href="{{ route('admin.ecommerce.coupons.create') }}" class="btn btn-primary btn-sm">
            <i data-lucide="plus" class="me-1"></i> Nouveau coupon
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Code</th>
                        <th>Type</th>
                        <th>Valeur</th>
                        <th>Utilisations</th>
                        <th>Expiration</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                    <tr>
                        <td class="ps-4"><code class="fw-bold">{{ $coupon->code }}</code></td>
                        <td>
                            @if($coupon->type === 'fixed') Montant fixe
                            @elseif($coupon->type === 'percent') Pourcentage
                            @else Livraison gratuite @endif
                        </td>
                        <td>
                            @if($coupon->type === 'percent') {{ $coupon->value }}%
                            @elseif($coupon->type === 'fixed') {{ config('modules.ecommerce.currency_symbol') }}{{ number_format($coupon->value, 2) }}
                            @else - @endif
                        </td>
                        <td>{{ $coupon->used_count ?? 0 }} / {{ $coupon->max_uses ?? '&infin;' }}</td>
                        <td>{{ $coupon->expires_at ? $coupon->expires_at->format('d/m/Y') : 'Jamais' }}</td>
                        <td>
                            <span class="badge {{ $coupon->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $coupon->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.ecommerce.coupons.edit', $coupon) }}" class="btn btn-sm btn-light"><i data-lucide="edit-2" class="icon-sm"></i></a>
                            <form action="{{ route('admin.ecommerce.coupons.destroy', $coupon) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce coupon ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light text-danger"><i data-lucide="trash-2" class="icon-sm"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun coupon.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

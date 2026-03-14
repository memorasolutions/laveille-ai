<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Coupons')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Coupons</h4>
    <a href="{{ route('admin.booking.coupons.create') }}" class="btn btn-primary">
        <i data-lucide="plus" class="icon-sm me-1"></i> Nouveau coupon
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Valeur</th>
                        <th>Utilisations</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                    <tr>
                        <td><code>{{ $coupon->code }}</code></td>
                        <td>
                            @if($coupon->type === 'percent')
                            <span class="badge bg-info">Pourcentage</span>
                            @else
                            <span class="badge bg-warning">Montant fixe</span>
                            @endif
                        </td>
                        <td>
                            @if($coupon->type === 'percent')
                                {{ $coupon->value }}%
                            @else
                                {{ number_format($coupon->value, 2) }} $
                            @endif
                        </td>
                        <td>{{ $coupon->times_used }}/{{ $coupon->max_uses ?? '&infin;' }}</td>
                        <td>
                            <span class="badge bg-{{ $coupon->is_active ? 'success' : 'secondary' }}">
                                {{ $coupon->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.booking.coupons.edit', $coupon) }}" class="btn btn-sm btn-outline-primary">
                                <i data-lucide="edit-2" class="icon-sm"></i>
                            </a>
                            <form action="{{ route('admin.booking.coupons.destroy', $coupon) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce coupon ?')">
                                    <i data-lucide="trash-2" class="icon-sm"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted">Aucun coupon configuré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

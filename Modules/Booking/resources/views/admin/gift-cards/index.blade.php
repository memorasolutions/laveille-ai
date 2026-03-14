<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', 'Cartes-cadeaux')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Cartes-cadeaux</h4>
    <a href="{{ route('admin.booking.gift-cards.create') }}" class="btn btn-primary">
        <i data-lucide="plus" class="icon-sm me-1"></i> Nouvelle carte-cadeau
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Acheteur</th>
                        <th>Destinataire</th>
                        <th>Montant initial</th>
                        <th>Solde</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($giftCards as $giftCard)
                    <tr>
                        <td><code>{{ $giftCard->code }}</code></td>
                        <td>{{ $giftCard->purchaser_name }}</td>
                        <td>{{ $giftCard->recipient_name ?? '—' }}</td>
                        <td>{{ number_format($giftCard->initial_amount, 2) }} $</td>
                        <td>{{ number_format($giftCard->remaining_amount, 2) }} $</td>
                        <td>
                            @php
                                $badgeClass = match($giftCard->status) {
                                    'active' => 'bg-success',
                                    'used' => 'bg-info',
                                    'expired' => 'bg-warning',
                                    'exhausted' => 'bg-secondary',
                                    default => 'bg-secondary',
                                };
                                $statusLabel = match($giftCard->status) {
                                    'active' => 'Active',
                                    'used' => 'Utilisée',
                                    'expired' => 'Expirée',
                                    'exhausted' => 'Épuisée',
                                    default => $giftCard->status,
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.booking.gift-cards.edit', $giftCard) }}" class="btn btn-sm btn-outline-primary">
                                <i data-lucide="edit-2" class="icon-sm"></i>
                            </a>
                            <form action="{{ route('admin.booking.gift-cards.destroy', $giftCard) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette carte-cadeau ?')">
                                    <i data-lucide="trash-2" class="icon-sm"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">Aucune carte-cadeau.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

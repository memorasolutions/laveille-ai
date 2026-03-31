@extends('backoffice::layouts.admin', ['title' => 'Demandes de droits', 'subtitle' => 'RGPD / Loi 25'])

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-light h-100">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $total }}</h3>
                    <small class="text-muted">Total demandes</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $pending }}</h3>
                    <small>En attente</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white h-100">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $overdue }}</h3>
                    <small>En retard</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Liste des demandes</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Type</th>
                        <th>Demandeur</th>
                        <th>Juridiction</th>
                        <th>Statut</th>
                        <th>Échéance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $r)
                        <tr>
                            <td><code>{{ $r->reference }}</code></td>
                            <td>
                                @php
                                    $typeLabel = match ($r->request_type) {
                                        'access' => 'Accès',
                                        'rectification' => 'Rectification',
                                        'erasure' => 'Effacement',
                                        'portability' => 'Portabilité',
                                        'opposition' => 'Opposition',
                                        'limitation' => 'Limitation',
                                        'withdrawal' => 'Retrait',
                                        default => ucfirst($r->request_type),
                                    };
                                @endphp
                                {{ $typeLabel }}
                            </td>
                            <td>{{ $r->name }}</td>
                            <td>
                                @php
                                    $jurisdictionLabel = match ($r->jurisdiction) {
                                        'gdpr' => 'RGPD (UE)',
                                        'canada_quebec' => 'Loi 25 (Québec)',
                                        'pipeda' => 'LPRPDE (Canada)',
                                        'ccpa' => 'CCPA (Californie)',
                                        default => ucfirst($r->jurisdiction),
                                    };
                                @endphp
                                {{ $jurisdictionLabel }}
                            </td>
                            <td>
                                @if ($r->isOverdue())
                                    <span class="badge bg-danger">En retard</span>
                                @endif
                                @switch($r->status)
                                    @case('pending')
                                        <span class="badge bg-warning text-dark">En attente</span>
                                        @break
                                    @case('in_progress')
                                        <span class="badge bg-info">En cours</span>
                                        @break
                                    @case('completed')
                                        <span class="badge bg-success">Terminée</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ ucfirst($r->status) }}</span>
                                @endswitch
                            </td>
                            <td class="{{ $r->isOverdue() ? 'text-danger fw-bold' : '' }}">
                                {{ $r->deadline_at?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td>
                                <a href="{{ route('admin.rights-requests.show', $r) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Voir
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Aucune demande de droits pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
@endsection

@extends('backoffice::layouts.admin', ['title' => 'Santé système', 'subtitle' => 'Outils'])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Vérifications de santé</h3>
        <a href="{{ route('admin.health') }}" class="btn btn-outline-primary btn-sm">
            <i class="ti ti-refresh me-1"></i> Actualiser
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Vérification</th>
                    <th>Statut</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                @forelse($checks ?? [] as $check)
                <tr>
                    <td>
                        <i class="ti ti-{{ $check['status'] === 'ok' ? 'circle-check text-success' : ($check['status'] === 'warning' ? 'alert-triangle text-warning' : 'circle-x text-danger') }} me-2"></i>
                        {{ $check['name'] ?? '' }}
                    </td>
                    <td>
                        <span class="badge bg-{{ $check['status'] === 'ok' ? 'success' : ($check['status'] === 'warning' ? 'warning' : 'danger') }}">
                            {{ $check['status'] ?? 'unknown' }}
                        </span>
                    </td>
                    <td class="text-muted">{{ $check['message'] ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-muted py-4">
                        <i class="ti ti-heartbeat mb-2 d-block" style="font-size: 2rem;"></i>
                        Cliquez sur Actualiser pour lancer les vérifications
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

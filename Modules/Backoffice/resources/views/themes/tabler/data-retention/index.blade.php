@extends('backoffice::layouts.admin', ['title' => 'Rétention données', 'subtitle' => 'Outils'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Politique de rétention des données</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead>
                    <tr>
                        <th>Type de données</th>
                        <th>Rétention</th>
                        <th>Enregistrements</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($retentionPolicies ?? [] as $policy)
                    <tr>
                        <td>{{ $policy['label'] ?? $policy['type'] ?? '' }}</td>
                        <td>{{ $policy['retention_days'] ?? '-' }} jours</td>
                        <td>{{ $policy['count'] ?? 0 }}</td>
                        <td>
                            <form action="{{ route('admin.data-retention.purge', ['type' => $policy['type'] ?? '']) }}" method="POST" onsubmit="return confirm('Purger les données expirées ?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="ti ti-trash me-1"></i> Purger
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="ti ti-database-off mb-2 d-block" style="font-size: 2rem;"></i>
                            Aucune politique définie
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@extends('backoffice::layouts.admin', ['title' => 'Historique connexions', 'subtitle' => 'Sécurité'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Historique des connexions</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>IP</th>
                    <th>Navigateur</th>
                    <th>Statut</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loginHistory ?? [] as $entry)
                <tr>
                    <td>{{ $entry->user?->name ?? $entry->email ?? '-' }}</td>
                    <td><code>{{ $entry->ip_address }}</code></td>
                    <td class="text-muted" style="max-width: 200px;">{{ Str::limit($entry->user_agent ?? '-', 50) }}</td>
                    <td>
                        <span class="badge bg-{{ $entry->successful ? 'success' : 'danger' }}">
                            {{ $entry->successful ? 'Succès' : 'Échec' }}
                        </span>
                    </td>
                    <td class="text-muted">{{ $entry->created_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="ti ti-login mb-2 d-block" style="font-size: 2rem;"></i>
                        Aucune connexion enregistrée
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($loginHistory ?? collect(), 'links'))
    <div class="card-footer d-flex align-items-center">
        {{ $loginHistory->links() }}
    </div>
    @endif
</div>
@endsection

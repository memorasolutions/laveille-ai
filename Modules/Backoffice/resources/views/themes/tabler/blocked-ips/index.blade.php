@extends('backoffice::layouts.admin', ['title' => 'IPs bloquées', 'subtitle' => 'Sécurité'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">IPs bloquées</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Adresse IP</th>
                    <th>Raison</th>
                    <th>Bloquée le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($blockedIps ?? [] as $ip)
                <tr>
                    <td><code>{{ $ip->ip_address }}</code></td>
                    <td>{{ $ip->reason ?? '-' }}</td>
                    <td class="text-muted">{{ $ip->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <form action="{{ route('admin.blocked-ips.destroy', $ip) }}" method="POST" onsubmit="return confirm('Débloquer cette IP ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                <i class="ti ti-lock-open me-1"></i> Débloquer
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="ti ti-shield-check mb-2 d-block" style="font-size: 2rem;"></i>
                        Aucune IP bloquée
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

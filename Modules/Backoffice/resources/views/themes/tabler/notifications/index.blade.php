@extends('backoffice::layouts.admin', ['title' => 'Notifications', 'subtitle' => 'Outils'])

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title">Notifications</h3>
        <span class="text-muted small"><i class="ti ti-bell me-1"></i> Gestion des notifications</span>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications ?? [] as $notification)
                <tr class="{{ $notification->read_at ? '' : 'bg-azure-lt' }}">
                    <td>
                        <span class="badge bg-secondary">{{ class_basename($notification->type) }}</span>
                    </td>
                    <td>{{ $notification->data['message'] ?? $notification->data['title'] ?? '-' }}</td>
                    <td class="text-muted">{{ $notification->created_at->diffForHumans() }}</td>
                    <td>
                        @if($notification->read_at)
                            <span class="badge bg-muted">Lu</span>
                        @else
                            <span class="badge bg-primary">Non lu</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="ti ti-bell-off mb-2 d-block" style="font-size: 2rem;"></i>
                        Aucune notification
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($notifications ?? collect(), 'links'))
    <div class="card-footer d-flex align-items-center">
        {{ $notifications->links() }}
    </div>
    @endif
</div>
@endsection

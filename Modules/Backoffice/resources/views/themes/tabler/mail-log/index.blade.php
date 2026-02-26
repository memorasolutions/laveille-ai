@extends('backoffice::layouts.admin', ['title' => 'Emails envoyés', 'subtitle' => 'Outils'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Emails envoyés</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Destinataire</th>
                    <th>Sujet</th>
                    <th>Statut</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mailLogs ?? [] as $mail)
                <tr>
                    <td>{{ $mail->to ?? '-' }}</td>
                    <td>{{ $mail->subject ?? '-' }}</td>
                    <td>
                        <span class="badge bg-{{ ($mail->status ?? '') === 'sent' ? 'success' : 'warning' }}">
                            {{ $mail->status ?? '-' }}
                        </span>
                    </td>
                    <td class="text-muted">{{ $mail->created_at?->diffForHumans() ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="ti ti-mail-off mb-2 d-block" style="font-size: 2rem;"></i>
                        Aucun email envoyé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($mailLogs ?? collect(), 'links'))
    <div class="card-footer d-flex align-items-center">
        {{ $mailLogs->links() }}
    </div>
    @endif
</div>
@endsection

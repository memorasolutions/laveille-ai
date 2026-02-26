@extends('backoffice::layouts.admin', ['title' => $title, 'subtitle' => $subtitle])

@section('content')
<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:letter-outline" class="icon text-xl"></iconify-icon>
            {{ __('Emails envoyés') }} ({{ $emails->total() }})
        </h6>
    </div>
    <div class="card-body p-0">
        @if($emails->isEmpty())
            <div class="text-center py-40">
                <iconify-icon icon="solar:letter-outline" class="text-6xl text-secondary-light mb-16"></iconify-icon>
                <p class="text-secondary-light">{{ __('Aucun email envoyé pour le moment.') }}</p>
            </div>
        @else
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Destinataire') }}</th>
                            <th>{{ __('Sujet') }}</th>
                            <th>{{ __('Classe') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>{{ __("Date d'envoi") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($emails as $email)
                            <tr>
                                <td>{{ $email->to }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($email->subject, 50) }}</td>
                                <td>
                                    @if($email->mailable_class)
                                        <code class="text-primary-600 text-sm">{{ class_basename($email->mailable_class) }}</code>
                                    @else
                                        <span class="text-secondary-light">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $email->status === 'sent' ? 'bg-success-focus text-success-main' : 'bg-danger-focus text-danger-main' }}">
                                        {{ $email->status === 'sent' ? __('Envoyé') : __('Échoué') }}
                                    </span>
                                </td>
                                <td class="text-sm text-secondary-light">{{ $email->sent_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center px-24 py-16">
                <span class="text-secondary-light text-sm">{{ $emails->total() }} {{ __('entrée(s)') }}</span>
                {{ $emails->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

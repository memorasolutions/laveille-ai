{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
<div>
    @if($emails->isEmpty())
        <div class="text-center py-5">
            <i data-lucide="mail" class="text-muted mb-3" style="width:64px;height:64px;opacity:.3;"></i>
            <p class="text-muted">{{ __('Aucun email envoyé pour le moment.') }}</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Destinataire') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Sujet') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Classe') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Statut') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __("Date d'envoi") }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($emails as $email)
                    <tr>
                        <td class="py-3 px-4 fw-semibold small text-body">{{ $email->to }}</td>
                        <td class="py-3 px-4 small text-muted">
                            {{ \Illuminate\Support\Str::limit($email->subject, 50) }}
                        </td>
                        <td class="py-3 px-4">
                            @if($email->mailable_class)
                                <code class="text-primary small bg-primary bg-opacity-10 px-2 py-1 rounded">
                                    {{ class_basename($email->mailable_class) }}
                                </code>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <span class="badge {{ $email->status === 'sent' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                                {{ $email->status === 'sent' ? __('Envoyé') : __('Échoué') }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-muted small">
                            {{ $email->sent_at?->format('Y-m-d H:i:s') ?? '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-3">
            <span class="text-muted small">{{ $emails->total() }} {{ __('entrée(s)') }}</span>
            @include('backoffice::partials.infinite-scroll', ['paginator' => $emails])
        </div>
    @endif
</div>

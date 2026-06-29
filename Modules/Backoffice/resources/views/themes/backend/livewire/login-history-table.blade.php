{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
<div>
    @if($attempts->isEmpty())
        <div class="text-center py-5">
            <i data-lucide="shield-check" class="text-muted mb-3" style="width:64px;height:64px;opacity:.3;"></i>
            <p class="text-muted">{{ __('Aucune tentative de connexion enregistrée.') }}</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Utilisateur') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Email') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('IP') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Navigateur') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Statut') }}</th>
                        <th class="py-3 px-4 fw-semibold text-body">{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attempts as $attempt)
                    <tr>
                        <td class="py-3 px-4 fw-semibold small text-body">
                            {{ $attempt->user?->name ?? '-' }}
                        </td>
                        <td class="py-3 px-4 small text-muted">{{ $attempt->email }}</td>
                        <td class="py-3 px-4">
                            <code class="text-primary small bg-primary bg-opacity-10 px-2 py-1 rounded">
                                {{ $attempt->ip_address }}
                            </code>
                        </td>
                        <td class="py-3 px-4 text-muted small">
                            {{ \Illuminate\Support\Str::limit($attempt->user_agent, 40) }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="badge {{ $attempt->status === 'success' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                                {{ $attempt->status === 'success' ? __('Succès') : __('Échec') }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-muted small">
                            {{ $attempt->logged_in_at->format('Y-m-d H:i:s') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-3">
            <span class="text-muted small">{{ $attempts->total() }} {{ __('entrée(s)') }}</span>
            @include('backoffice::partials.infinite-scroll', ['paginator' => $attempts])
        </div>
    @endif
</div>

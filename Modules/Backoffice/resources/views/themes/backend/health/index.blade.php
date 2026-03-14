<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Santé système')])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Santé système') }}</li>
    </ol>
</nav>

@php
    $totalChecks = $results ? count($results->storedCheckResults) : 0;
    $okCount = $results ? $results->storedCheckResults->where('status', 'ok')->count() : 0;
    $warningCount = $results ? $results->storedCheckResults->where('status', 'warning')->count() : 0;
    $failedCount = $results ? $results->storedCheckResults->whereIn('status', ['failed', 'crashed'])->count() : 0;
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="heart-pulse" class="icon-md text-primary"></i>{{ __('Santé du système') }}</h4>
    <x-backoffice::help-modal id="helpHealthModal" :title="__('Santé du système')" icon="heart-pulse" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.health._help')
    </x-backoffice::help-modal>
</div>

{{-- Stats cards --}}
<div class="row mb-4">
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="heart" class="text-primary icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('Total') }}</p>
                    <h4 class="fw-bold mb-0">{{ $totalChecks }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="check-circle" class="text-success icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('OK') }}</p>
                    <h4 class="fw-bold mb-0 text-success">{{ $okCount }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="alert-triangle" class="text-warning icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('Avertissements') }}</p>
                    <h4 class="fw-bold mb-0 text-warning">{{ $warningCount }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="x-circle" class="text-danger icon-md"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('Échecs') }}</p>
                    <h4 class="fw-bold mb-0 text-danger">{{ $failedCount }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Health checks table --}}
<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i data-lucide="heart-pulse" class="text-primary icon-md"></i>
                {{ __('Santé système') }}
            </h4>
            <form action="{{ route('admin.health.refresh') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="refresh-cw" class="icon-sm"></i>
                    {{ __('Lancer les vérifications') }}
                </button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        @php
            $remediation = [
                'Database' => __('Vérifiez les identifiants dans <code>.env</code> (<code>DB_HOST</code>, <code>DB_DATABASE</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>). Assurez-vous que le serveur MySQL est démarré.'),
                'UsedDiskSpace' => __('Libérez de l\'espace disque : supprimez les anciens logs, purgez les backups obsolètes (<code>php artisan backup:clean</code>), videz le cache (<code>php artisan cache:clear</code>).'),
                'DebugMode' => __('En production, définissez <code>APP_DEBUG=false</code> dans votre fichier <code>.env</code> pour éviter d\'exposer des informations sensibles.'),
                'Environment' => __('Vérifiez que <code>APP_ENV=production</code> est bien défini dans <code>.env</code> sur le serveur de production.'),
                'Cache' => __('Vérifiez la configuration du driver de cache dans <code>.env</code> (<code>CACHE_STORE</code>). Essayez <code>php artisan cache:clear</code> puis <code>php artisan config:cache</code>.'),
                'OptimizedApp' => __('Optimisez l\'application : <code>php artisan config:cache</code>, <code>php artisan route:cache</code>, <code>php artisan view:cache</code>, <code>php artisan event:cache</code>.'),
                'Schedule' => __('Le scheduler ne s\'exécute pas. Vérifiez le cron job : <code>* * * * * cd /chemin/projet && php artisan schedule:run >> /dev/null 2>&1</code>.'),
            ];
        @endphp

        @if($results === null || $results->storedCheckResults->isEmpty())
            <div class="text-center py-5">
                <i data-lucide="heart" class="text-muted mb-3" style="width:64px;height:64px;opacity:.3;"></i>
                <p class="text-muted">{{ __('Aucune vérification effectuée.') }}</p>
                <p class="text-muted small">{{ __('Cliquez sur « Lancer les vérifications » pour analyser l\'état du système.') }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0 health-table">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 fw-semibold text-body" style="width:15%;">{{ __('Vérification') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body" style="width:8%;">{{ __('Statut') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body" style="width:17%;">{{ __('Message') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body" style="width:42%;">{{ __('Instructions') }}</th>
                            <th class="py-3 px-4 fw-semibold text-body" style="width:18%;">{{ __('Dernière exécution') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results->storedCheckResults as $result)
                        @php
                            $status = $result->status ?? 'unknown';
                            $statusText = match($status) {
                                'ok'      => __('OK'),
                                'warning' => __('Avertissement'),
                                'failed'  => __('Échoué'),
                                'crashed' => __('Crash'),
                                default   => ucfirst($status),
                            };
                            $badgeClass = match($status) {
                                'ok'      => 'bg-success',
                                'warning' => 'bg-warning text-dark',
                                'failed', 'crashed' => 'bg-danger',
                                default   => 'bg-secondary',
                            };
                            $instruction = null;
                            $checkKey = null;
                            if (in_array($status, ['warning', 'failed', 'crashed'])) {
                                foreach ($remediation as $key => $text) {
                                    if (str_contains($result->name, $key)) {
                                        $instruction = $text;
                                        $checkKey = $key;
                                        break;
                                    }
                                }
                            }
                            $fixableChecks = ['OptimizedApp', 'DebugMode', 'Cache'];
                        @endphp
                        <tr>
                            <td class="py-3 px-4 fw-semibold text-body">{{ $result->name }}</td>
                            <td class="py-3 px-4">
                                <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                            </td>
                            <td class="py-3 px-4 text-muted small">
                                {{ $result->shortSummary ?? $result->notificationMessage ?? '—' }}
                            </td>
                            <td class="py-3 px-4 small" style="max-width:300px;overflow-wrap:break-word;word-break:break-word;">
                                @if($status === 'ok')
                                    <span class="text-success d-flex align-items-center gap-1">
                                        <i data-lucide="check" class="icon-sm"></i>
                                        {{ __('Aucune action requise') }}
                                    </span>
                                @else
                                    @if($instruction)
                                        <div class="p-2 rounded small health-instruction mb-2 {{ $status === 'warning' ? 'bg-warning bg-opacity-10 text-warning' : 'bg-danger bg-opacity-10 text-danger' }}" style="word-break:break-word;">
                                            {!! $instruction !!}
                                        </div>
                                    @endif
                                    <div class="d-flex gap-2 flex-wrap">
                                        @if($checkKey && in_array($checkKey, $fixableChecks))
                                            <button type="button" class="btn btn-sm btn-warning d-inline-flex align-items-center gap-1"
                                                    onclick="healthFix('{{ $checkKey }}', this)">
                                                <i data-lucide="wrench" class="icon-sm"></i>
                                                {{ __('Corriger') }}
                                            </button>
                                        @endif
                                        @if($checkKey)
                                            <button type="button" class="btn btn-sm btn-outline-info d-inline-flex align-items-center gap-1"
                                                    onclick="healthExplain('{{ $checkKey }}')">
                                                <i data-lucide="info" class="icon-sm"></i>
                                                {{ __('Expliquer') }}
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-muted small">
                                {{ $results->finishedAt ? \Carbon\Carbon::parse($results->finishedAt)->format('d/m/Y H:i:s') : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Modal explication --}}
<div class="modal fade" id="healthExplainModal" tabindex="-1" aria-labelledby="healthExplainModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="healthExplainModalLabel">{{ __('Explication') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Fermer') }}"></button>
            </div>
            <div class="modal-body">
                <div id="explainLoading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('Chargement...') }}</span>
                    </div>
                </div>
                <div id="explainContent">
                    <div class="mb-3">
                        <h6 class="text-muted small fw-semibold">{{ __('Description') }}</h6>
                        <div id="explainText" class="p-3 bg-light rounded small"></div>
                    </div>
                    <div class="d-flex gap-3">
                        <div>
                            <span class="text-muted small fw-semibold">{{ __('Corrigeable automatiquement') }}</span>
                            <span id="explainFixable" class="badge ms-1"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Fermer') }}</button>
            </div>
        </div>
    </div>
</div>

@push('custom-scripts')
<script>
function healthFix(check, btn) {
    if (!confirm('{{ __("Lancer la correction automatique ?") }}')) return;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> {{ __("Correction...") }}';
    fetch('{{ route("admin.health.fix") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ check: check })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { location.reload(); }
        else { alert(data.message); btn.disabled = false; btn.innerHTML = '<i data-lucide="wrench" class="icon-sm"></i> {{ __("Corriger") }}'; lucide.createIcons(); }
    })
    .catch(() => { alert('{{ __("Erreur réseau") }}'); btn.disabled = false; });
}
function healthExplain(check) {
    var modal = new bootstrap.Modal(document.getElementById('healthExplainModal'));
    document.getElementById('explainLoading').classList.remove('d-none');
    document.getElementById('explainContent').classList.add('d-none');
    modal.show();
    fetch('{{ route("admin.health.explain") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ check: check })
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('explainText').textContent = data.explanation || '{{ __("Aucune explication disponible.") }}';
        var badge = document.getElementById('explainFixable');
        badge.textContent = data.fixable ? '{{ __("Oui") }}' : '{{ __("Non") }}';
        badge.className = 'badge ms-1 ' + (data.fixable ? 'bg-success' : 'bg-secondary');
        document.getElementById('explainLoading').classList.add('d-none');
        document.getElementById('explainContent').classList.remove('d-none');
    })
    .catch(() => {
        document.getElementById('explainText').textContent = '{{ __("Erreur lors du chargement.") }}';
        document.getElementById('explainLoading').classList.add('d-none');
        document.getElementById('explainContent').classList.remove('d-none');
    });
}
</script>
@endpush

@push('plugin-styles')
<style>
.health-table { table-layout: fixed; width: 100%; }
.health-table td { overflow: hidden; text-overflow: ellipsis; overflow-wrap: anywhere; word-break: break-word; }
.health-table code { white-space: pre-wrap !important; word-break: break-word !important; overflow-wrap: break-word !important; display: inline; max-width: 100%; }
.health-instruction { overflow: hidden; overflow-wrap: break-word; word-break: break-word; }
.health-instruction code { white-space: pre-wrap !important; word-break: break-word !important; overflow-wrap: break-word !important; }
@media (max-width: 767.98px) {
    .health-table th:nth-child(4), .health-table td:nth-child(4),
    .health-table th:nth-child(5), .health-table td:nth-child(5) { display: none; }
}
</style>
@endpush

@endsection

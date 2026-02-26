@extends('backoffice::layouts.admin', ['title' => 'Santé système', 'subtitle' => 'Monitoring'])

@section('content')

@php
    $totalChecks = $results ? count($results->storedCheckResults) : 0;
    $okCount = $results ? $results->storedCheckResults->where('status', 'ok')->count() : 0;
    $warningCount = $results ? $results->storedCheckResults->where('status', 'warning')->count() : 0;
    $failedCount = $results ? $results->storedCheckResults->whereIn('status', ['failed', 'crashed'])->count() : 0;
@endphp

{{-- Summary cards --}}
<div class="row gy-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="card radius-12 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="w-40-px h-40-px bg-primary-100 text-primary-600 d-flex align-items-center justify-content-center rounded-circle flex-shrink-0">
                    <iconify-icon icon="solar:heart-pulse-outline" class="icon text-xl"></iconify-icon>
                </div>
                <div>
                    <h6 class="mb-4 text-secondary-light">Total</h6>
                    <h4 class="fw-bold mb-0">{{ $totalChecks }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card radius-12 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="w-40-px h-40-px bg-success-100 text-success-600 d-flex align-items-center justify-content-center rounded-circle flex-shrink-0">
                    <iconify-icon icon="solar:check-circle-outline" class="icon text-xl"></iconify-icon>
                </div>
                <div>
                    <h6 class="mb-4 text-secondary-light">OK</h6>
                    <h4 class="fw-bold mb-0">{{ $okCount }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card radius-12 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="w-40-px h-40-px bg-warning-100 text-warning-600 d-flex align-items-center justify-content-center rounded-circle flex-shrink-0">
                    <iconify-icon icon="solar:danger-triangle-outline" class="icon text-xl"></iconify-icon>
                </div>
                <div>
                    <h6 class="mb-4 text-secondary-light">Avertissements</h6>
                    <h4 class="fw-bold mb-0">{{ $warningCount }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card radius-12 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="w-40-px h-40-px bg-danger-100 text-danger-600 d-flex align-items-center justify-content-center rounded-circle flex-shrink-0">
                    <iconify-icon icon="solar:close-circle-outline" class="icon text-xl"></iconify-icon>
                </div>
                <div>
                    <h6 class="mb-4 text-secondary-light">Échecs</h6>
                    <h4 class="fw-bold mb-0">{{ $failedCount }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Health checks table --}}
<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h6 class="mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:heart-pulse-outline" class="icon text-xl"></iconify-icon>
            Santé système
        </h6>
        <form action="{{ route('admin.health.refresh') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary-600 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:refresh-outline" class="icon text-xl"></iconify-icon>
                Lancer les vérifications
            </button>
        </form>
    </div>
    <div class="card-body p-0">
        @php
            $remediation = [
                'Database' => 'Vérifiez les identifiants dans <code>.env</code> (<code>DB_HOST</code>, <code>DB_DATABASE</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>). Assurez-vous que le serveur MySQL est démarré.',
                'UsedDiskSpace' => 'Libérez de l\'espace disque : supprimez les anciens logs, purgez les backups obsolètes (<code>php artisan backup:clean</code>), videz le cache (<code>php artisan cache:clear</code>).',
                'DebugMode' => 'En production, définissez <code>APP_DEBUG=false</code> dans votre fichier <code>.env</code> pour éviter d\'exposer des informations sensibles.',
                'Environment' => 'Vérifiez que <code>APP_ENV=production</code> est bien défini dans <code>.env</code> sur le serveur de production.',
                'Cache' => 'Vérifiez la configuration du driver de cache dans <code>.env</code> (<code>CACHE_STORE</code>). Essayez <code>php artisan cache:clear</code> puis <code>php artisan config:cache</code>.',
                'OptimizedApp' => 'Optimisez l\'application : <code>php artisan config:cache</code>, <code>php artisan route:cache</code>, <code>php artisan view:cache</code>, <code>php artisan event:cache</code>.',
                'Schedule' => 'Le scheduler ne s\'exécute pas. Vérifiez le cron job : <code>* * * * * cd /chemin/projet && php artisan schedule:run >> /dev/null 2>&1</code>.',
            ];
        @endphp

        @if($results === null || $results->storedCheckResults->isEmpty())
            <div class="text-center py-40">
                <iconify-icon icon="solar:health-outline" class="text-6xl text-secondary-light mb-16"></iconify-icon>
                <p class="text-secondary-light">Aucune vérification effectuée.</p>
                <p class="text-sm text-secondary-light">Cliquez sur « Lancer les vérifications » pour analyser l'état du système.</p>
            </div>
        @else
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th>Vérification</th>
                            <th>Statut</th>
                            <th>Message</th>
                            <th>Instructions</th>
                            <th>Dernière exécution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results->storedCheckResults as $result)
                        @php
                            $status = $result->status ?? 'unknown';
                            $statusText = match($status) {
                                'ok'      => 'OK',
                                'warning' => 'Avertissement',
                                'failed'  => 'Échoué',
                                'crashed' => 'Crash',
                                default   => ucfirst($status),
                            };
                            $badgeClass = match($status) {
                                'ok'      => 'bg-success-focus text-success-main',
                                'warning' => 'bg-warning-focus text-warning-main',
                                'failed', 'crashed' => 'bg-danger-focus text-danger-main',
                                default   => 'bg-neutral-200 text-neutral-600',
                            };
                            $icon = match($status) {
                                'ok'      => 'solar:check-circle-outline',
                                'warning' => 'solar:danger-triangle-outline',
                                'failed', 'crashed' => 'solar:close-circle-outline',
                                default   => 'solar:question-circle-outline',
                            };

                            // Find remediation instruction
                            $instruction = null;
                            if (in_array($status, ['warning', 'failed', 'crashed'])) {
                                foreach ($remediation as $key => $text) {
                                    if (str_contains($result->name, $key)) {
                                        $instruction = $text;
                                        break;
                                    }
                                }
                            }
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $result->name }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }} d-flex align-items-center gap-1" style="width:fit-content">
                                    <iconify-icon icon="{{ $icon }}" class="icon"></iconify-icon>
                                    {{ $statusText }}
                                </span>
                            </td>
                            <td class="text-secondary-light text-sm">{{ $result->shortSummary ?? $result->notificationMessage ?? '—' }}</td>
                            <td class="text-sm" style="max-width: 300px;">
                                @if($status === 'ok')
                                    <span class="text-success-main d-flex align-items-center gap-1">
                                        <iconify-icon icon="solar:check-circle-outline" class="icon"></iconify-icon>
                                        Aucune action requise
                                    </span>
                                @elseif($instruction)
                                    <div class="p-8 rounded {{ $status === 'warning' ? 'bg-warning-100' : 'bg-danger-100' }}">
                                        <small class="{{ $status === 'warning' ? 'text-warning-600' : 'text-danger-600' }}">{!! $instruction !!}</small>
                                    </div>
                                @else
                                    <span class="text-secondary-light">—</span>
                                @endif
                            </td>
                            <td class="text-secondary-light text-sm">
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

@endsection

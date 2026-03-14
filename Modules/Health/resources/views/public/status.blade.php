<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Statut du service') }} - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --status-ok: #198754;
            --status-warning: #ffc107;
            --status-failed: #dc3545;
            --status-degraded: #fd7e14;
        }
        .status-banner {
            border-radius: 0.5rem;
            padding: 2rem 1rem;
            margin-bottom: 2rem;
            color: white;
            text-align: center;
        }
        .status-banner.ok { background-color: var(--status-ok); }
        .status-banner.degraded { background-color: var(--status-degraded); }
        .status-banner.outage { background-color: var(--status-failed); }
        .check-card { transition: transform 0.2s; height: 100%; }
        .check-card:hover { transform: translateY(-3px); }
        .status-badge { font-size: 0.85rem; padding: 0.35rem 0.75rem; }
        .severity-badge { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
        .incident-timeline { position: relative; padding-left: 2rem; }
        .incident-timeline::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #dee2e6;
        }
        .timeline-item { position: relative; margin-bottom: 1.5rem; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 3px solid white;
            z-index: 1;
        }
        .timeline-item.resolved::before { background-color: var(--status-ok); }
        .timeline-item.investigating::before { background-color: var(--status-warning); }
        .timeline-item.identified::before { background-color: var(--status-degraded); }
        .timeline-item.monitoring::before { background-color: #6c757d; }
        .uptime-display { font-size: 2.5rem; font-weight: 700; line-height: 1; }
        footer { border-top: 1px solid #dee2e6; margin-top: 3rem; }
    </style>
</head>
<body>
    <div class="container py-4">
        <header class="mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">{{ __('Statut du service') }}</h1>
                <div class="text-muted">
                    <i class="bi bi-clock me-1"></i>
                    {{ __('Dernière mise à jour') }} : {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>
            <hr>
        </header>

        @php
            $bannerClass = 'ok';
            $bannerText = __('Tous les systèmes fonctionnent normalement');

            if ($okCount < $totalChecks && $okCount > 0) {
                $bannerClass = 'degraded';
                $bannerText = __('Service dégradé — certains systèmes rencontrent des problèmes');
            } elseif ($totalChecks > 0 && $okCount === 0) {
                $bannerClass = 'outage';
                $bannerText = __('Panne majeure — service indisponible');
            }
        @endphp
        <div class="status-banner {{ $bannerClass }}">
            <div class="row align-items-center">
                <div class="col-md-8 text-md-start">
                    <h2 class="h4 mb-2">{{ $bannerText }}</h2>
                    <p class="mb-0 opacity-75">
                        {{ $okCount }}/{{ $totalChecks }} {{ __('services opérationnels') }}
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="uptime-display">{{ number_format($uptimePercentage, 1) }}%</div>
                    <small class="opacity-75">{{ __('Disponibilité') }}</small>
                </div>
            </div>
        </div>

        <section class="mb-5">
            <h3 class="h5 mb-3">{{ __('Vérification des systèmes') }}</h3>
            <div class="row g-3">
                @foreach($checks as $check)
                    @php
                        $statusColor = match($check['status']) {
                            'ok' => 'success',
                            'warning' => 'warning',
                            'failed' => 'danger',
                            default => 'secondary',
                        };
                        $statusIcon = match($check['status']) {
                            'ok' => 'bi-check-circle-fill',
                            'warning' => 'bi-exclamation-triangle-fill',
                            'failed' => 'bi-x-circle-fill',
                            default => 'bi-question-circle-fill',
                        };
                    @endphp
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="card check-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0">{{ $check['name'] }}</h5>
                                    <span class="badge status-badge bg-{{ $statusColor }}">
                                        <i class="bi {{ $statusIcon }} me-1"></i>
                                        {{ __(ucfirst($check['status'])) }}
                                    </span>
                                </div>
                                <p class="card-text text-muted small mb-0">{{ $check['summary'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @if($incidents->count() > 0)
        <section class="mb-5">
            <h3 class="h5 mb-3">{{ __('Historique des incidents') }}</h3>
            <div class="incident-timeline">
                @foreach($incidents as $incident)
                    @php
                        $severityColor = match($incident->severity) {
                            'critical' => 'danger',
                            'major' => 'warning',
                            'minor' => 'info',
                            default => 'secondary',
                        };
                        $statusLabel = match($incident->status) {
                            'investigating' => __('Investigation'),
                            'identified' => __('Identifié'),
                            'monitoring' => __('Surveillance'),
                            'resolved' => __('Résolu'),
                            default => $incident->status,
                        };
                    @endphp
                    <div class="timeline-item {{ $incident->status }}">
                        <div class="card mb-2">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0">{{ $incident->title }}</h6>
                                    <div>
                                        <span class="badge severity-badge bg-{{ $severityColor }} me-1">
                                            {{ __(ucfirst($incident->severity)) }}
                                        </span>
                                        <span class="badge severity-badge bg-secondary">
                                            {{ $statusLabel }}
                                        </span>
                                    </div>
                                </div>
                                @if($incident->description)
                                    <p class="card-text small">{{ $incident->description }}</p>
                                @endif
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>
                                        <i class="bi bi-calendar me-1"></i>
                                        {{ $incident->created_at->format('d/m/Y H:i') }}
                                    </span>
                                    @if($incident->resolved_at)
                                        <span>
                                            <i class="bi bi-check-circle me-1"></i>
                                            {{ __('Résolu le') }} {{ $incident->resolved_at->format('d/m/Y H:i') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        <footer class="pt-4">
            <div class="row">
                <div class="col-md-6">
                    <p class="small text-muted">{{ __('Cette page est mise à jour automatiquement toutes les minutes.') }}</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="small text-muted">{{ __('Propulsé par') }} <strong>{{ config('app.name') }}</strong></p>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>setTimeout(function() { window.location.reload(); }, 60000);</script>
</body>
</html>

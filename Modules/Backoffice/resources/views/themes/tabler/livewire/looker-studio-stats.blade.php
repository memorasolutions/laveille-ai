<div>
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <div class="d-flex"><i class="ti ti-check me-2"></i>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- En-tête avec sélecteur de période --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar bg-primary-lt rounded">
                    <i class="ti ti-chart-bar text-primary"></i>
                </span>
                <div>
                    <h4 class="mb-0">Statistiques</h4>
                    <small class="text-muted">Données analytiques en temps réel</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex justify-content-md-end gap-2">
                <select wire:model.live="period" class="form-select" style="width:auto;">
                    <option value="7">7 derniers jours</option>
                    <option value="30">30 derniers jours</option>
                    <option value="90">3 derniers mois</option>
                    <option value="365">12 derniers mois</option>
                </select>
                <button wire:click="refresh" class="btn btn-outline-secondary">
                    <i class="ti ti-refresh {{ $loading ? 'ti-spin' : '' }}"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Pages vues</div>
                    </div>
                    <div class="h1 mb-3">{{ number_format($stats['page_views'] ?? 0) }}</div>
                    <div class="d-flex mb-2">
                        <div>
                            @if(($stats['page_views_change'] ?? 0) >= 0)
                            <span class="text-green d-inline-flex align-items-center lh-1">
                                <i class="ti ti-trending-up me-1"></i>
                                +{{ $stats['page_views_change'] ?? 0 }}%
                            </span>
                            @else
                            <span class="text-red d-inline-flex align-items-center lh-1">
                                <i class="ti ti-trending-down me-1"></i>
                                {{ $stats['page_views_change'] ?? 0 }}%
                            </span>
                            @endif
                            <span class="text-muted ms-1 small">vs période précédente</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Visiteurs uniques</div>
                    <div class="h1 mb-3">{{ number_format($stats['unique_visitors'] ?? 0) }}</div>
                    <div class="d-flex mb-2">
                        @if(($stats['visitors_change'] ?? 0) >= 0)
                        <span class="text-green d-inline-flex align-items-center lh-1">
                            <i class="ti ti-trending-up me-1"></i>+{{ $stats['visitors_change'] ?? 0 }}%
                        </span>
                        @else
                        <span class="text-red d-inline-flex align-items-center lh-1">
                            <i class="ti ti-trending-down me-1"></i>{{ $stats['visitors_change'] ?? 0 }}%
                        </span>
                        @endif
                        <span class="text-muted ms-1 small">vs période précédente</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Taux de rebond</div>
                    <div class="h1 mb-3">{{ $stats['bounce_rate'] ?? 0 }}%</div>
                    <div class="progress mb-2" style="height:4px;">
                        <div class="progress-bar bg-warning" style="width:{{ $stats['bounce_rate'] ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Durée moy. session</div>
                    <div class="h1 mb-3">{{ $stats['avg_session'] ?? '0:00' }}</div>
                    <div class="text-muted small">
                        <i class="ti ti-clock me-1"></i>minutes:secondes
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Embed Looker Studio / iframe --}}
    @if($embedUrl ?? null)
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">
                <i class="ti ti-chart-dots me-2 text-muted"></i>Rapport Looker Studio
            </h4>
            <a href="{{ $embedUrl }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-external-link me-1"></i> Ouvrir dans Looker Studio
            </a>
        </div>
        <div class="card-body p-0">
            <div class="ratio" style="--bs-aspect-ratio: 56.25%;">
                <iframe
                    src="{{ $embedUrl }}"
                    frameborder="0"
                    style="border:0; width:100%; height:100%;"
                    allowfullscreen
                    loading="lazy"
                    sandbox="allow-storage-access-by-user-activation allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox">
                </iframe>
            </div>
        </div>
    </div>
    @else
    <div class="card mb-4">
        <div class="card-body text-center py-5">
            <div class="avatar avatar-lg bg-primary-lt rounded mb-3 mx-auto">
                <i class="ti ti-chart-dots text-primary fs-2"></i>
            </div>
            <h3>Looker Studio non configuré</h3>
            <p class="text-muted mb-4">
                Configurez l'URL de votre rapport Looker Studio dans les paramètres pour afficher les données analytiques ici.
            </p>
            <a href="{{ route('admin.settings.index', ['group' => 'analytics']) }}" class="btn btn-primary">
                <i class="ti ti-settings me-1"></i> Configurer
            </a>
        </div>
    </div>
    @endif

    {{-- Pages les plus visitées --}}
    @if(!empty($topPages ?? []))
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">
                <i class="ti ti-file-analytics me-2 text-muted"></i>Pages les plus visitées
            </h4>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover mb-0">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Vues</th>
                        <th>Uniques</th>
                        <th style="width:120px;">Part du trafic</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topPages as $page)
                    <tr>
                        <td>
                            <div class="text-truncate small" style="max-width:300px;" title="{{ $page['path'] ?? '' }}">
                                <code>{{ $page['path'] ?? '/' }}</code>
                            </div>
                        </td>
                        <td>{{ number_format($page['views'] ?? 0) }}</td>
                        <td>{{ number_format($page['uniques'] ?? 0) }}</td>
                        <td>
                            @php $pct = $page['percentage'] ?? 0; @endphp
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-fill" style="height:5px;">
                                    <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
                                </div>
                                <small>{{ $pct }}%</small>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => $title ?? __('Statistiques'), 'subtitle' => $subtitle ?? __('Analytiques')])

@section('content')

<nav class="page-breadcrumb" aria-label="{{ __('Fil d\'Ariane') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Statistiques') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="bar-chart-3" class="icon-md text-primary"></i>{{ __('Statistiques') }}</h4>
    <x-backoffice::help-modal id="helpStatsModal" :title="__('Statistiques')" icon="bar-chart-3" :buttonLabel="__('Aide')">
        @include('backoffice::themes.backend.stats._help')
    </x-backoffice::help-modal>
</div>

{{-- Period Selector --}}
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="?days=7" class="btn btn-sm {{ $days == 7 ? 'btn-primary' : 'btn-outline-primary' }}">
        {{ __('7 jours') }}
    </a>
    <a href="?days=30" class="btn btn-sm {{ $days == 30 ? 'btn-primary' : 'btn-outline-primary' }}">
        {{ __('30 jours') }}
    </a>
    <a href="?days=90" class="btn btn-sm {{ $days == 90 ? 'btn-primary' : 'btn-outline-primary' }}">
        {{ __('90 jours') }}
    </a>
</div>

{{-- Stats Cards --}}
<div class="row g-3 mb-3">

    <div class="col-xl-2 col-sm-4 col-6">
        <div class="card h-100">
            <div class="card-body text-center p-3">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width:56px;height:56px;">
                    <i data-lucide="users" class="text-primary" style="width:24px;height:24px;"></i>
                </div>
                <h2 class="fw-bold fs-4 text-body mb-1">{{ number_format($overview['total_users']) }}</h2>
                <span class="small text-muted">{{ __('Total utilisateurs') }}</span>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-sm-4 col-6">
        <div class="card h-100">
            <div class="card-body text-center p-3">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width:56px;height:56px;">
                    <i data-lucide="user-check" class="text-success" style="width:24px;height:24px;"></i>
                </div>
                <h2 class="fw-bold fs-4 text-body mb-1">{{ number_format($overview['active_users']) }}</h2>
                <span class="small text-muted">{{ __('Utilisateurs actifs') }}</span>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-sm-4 col-6">
        <div class="card h-100">
            <div class="card-body text-center p-3">
                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width:56px;height:56px;">
                    <i data-lucide="user-plus" class="text-warning" style="width:24px;height:24px;"></i>
                </div>
                <h2 class="fw-bold fs-4 text-body mb-1">{{ number_format($overview['new_users']) }}</h2>
                <span class="small text-muted">{{ __('Nouveaux') }} ({{ $days }}j)</span>
                @php($d = $deltaKpis['new_users'] ?? null)
                @if($d)
                    <div class="mt-1" aria-label="{{ __('Variation vs période précédente') }}">
                        <span class="badge bg-{{ $d['direction'] === 'up' ? 'success' : ($d['direction'] === 'down' ? 'danger' : 'secondary') }} bg-opacity-15 text-{{ $d['direction'] === 'up' ? 'success' : ($d['direction'] === 'down' ? 'danger' : 'secondary') }} fw-semibold" style="font-size:0.7rem;">
                            {{ $d['direction'] === 'up' ? '↑' : ($d['direction'] === 'down' ? '↓' : '→') }} {{ $d['delta_pct'] }}%
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-sm-4 col-6">
        <div class="card h-100">
            <div class="card-body text-center p-3">
                <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width:56px;height:56px;">
                    <i data-lucide="file-text" class="text-info" style="width:24px;height:24px;"></i>
                </div>
                <h2 class="fw-bold fs-4 text-body mb-1">{{ number_format($overview['published_articles']) }}</h2>
                <span class="small text-muted">{{ __('Articles publiés') }}</span>
                @php($d = $deltaKpis['published_articles'] ?? null)
                @if($d)
                    <div class="mt-1" aria-label="{{ __('Variation vs période précédente') }}">
                        <span class="badge bg-{{ $d['direction'] === 'up' ? 'success' : ($d['direction'] === 'down' ? 'danger' : 'secondary') }} bg-opacity-15 text-{{ $d['direction'] === 'up' ? 'success' : ($d['direction'] === 'down' ? 'danger' : 'secondary') }} fw-semibold" style="font-size:0.7rem;">
                            {{ $d['direction'] === 'up' ? '↑' : ($d['direction'] === 'down' ? '↓' : '→') }} {{ $d['delta_pct'] }}%
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-sm-4 col-6">
        <div class="card h-100">
            <div class="card-body text-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width:56px;height:56px;background-color:rgba(139,92,246,0.12);">
                    <i data-lucide="mail" style="width:24px;height:24px;color:#8B5CF6;"></i>
                </div>
                <h2 class="fw-bold fs-4 text-body mb-1">{{ number_format($overview['total_subscribers']) }}</h2>
                <span class="small text-muted">{{ __('Abonnés newsletter') }}</span>
                @php($d = $deltaKpis['subscribers'] ?? null)
                @if($d)
                    <div class="mt-1" aria-label="{{ __('Variation vs période précédente') }}">
                        <span class="badge bg-{{ $d['direction'] === 'up' ? 'success' : ($d['direction'] === 'down' ? 'danger' : 'secondary') }} bg-opacity-15 text-{{ $d['direction'] === 'up' ? 'success' : ($d['direction'] === 'down' ? 'danger' : 'secondary') }} fw-semibold" style="font-size:0.7rem;">
                            {{ $d['direction'] === 'up' ? '↑' : ($d['direction'] === 'down' ? '↓' : '→') }} {{ $d['delta_pct'] }}%
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-sm-4 col-6">
        <div class="card h-100">
            <div class="card-body text-center p-3">
                <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width:56px;height:56px;">
                    <i data-lucide="bar-chart-2" class="text-danger" style="width:24px;height:24px;"></i>
                </div>
                <h2 class="fw-bold fs-4 text-body mb-1">{{ number_format($overview['total_activities']) }}</h2>
                <span class="small text-muted">{{ __('Activités') }}</span>
                @php($d = $deltaKpis['activities'] ?? null)
                @if($d)
                    <div class="mt-1" aria-label="{{ __('Variation vs période précédente') }}">
                        <span class="badge bg-{{ $d['direction'] === 'up' ? 'success' : ($d['direction'] === 'down' ? 'danger' : 'secondary') }} bg-opacity-15 text-{{ $d['direction'] === 'up' ? 'success' : ($d['direction'] === 'down' ? 'danger' : 'secondary') }} fw-semibold" style="font-size:0.7rem;">
                            {{ $d['direction'] === 'up' ? '↑' : ($d['direction'] === 'down' ? '↓' : '→') }} {{ $d['delta_pct'] }}%
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- Charts Row 0: Newsletter Growth + Top Articles --}}
<div class="row g-3 mb-3">

    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i data-lucide="mail" style="color:#8B5CF6;"></i>
                <h6 class="fw-semibold mb-0">{{ __('Croissance newsletter') }} ({{ $days }}j)</h6>
            </div>
            <div class="card-body">
                <div id="chart-newsletter-growth"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i data-lucide="trophy" class="text-warning"></i>
                <h6 class="fw-semibold mb-0">{{ __('Top 5 articles') }} ({{ $days }}j)</h6>
            </div>
            <div class="card-body">
                @if(count($topArticles) > 0)
                    <ol class="list-unstyled mb-0">
                        @foreach($topArticles as $i => $art)
                            <li class="d-flex align-items-start gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold flex-shrink-0" style="min-width:28px;">{{ $i + 1 }}</span>
                                <div class="flex-grow-1 min-width-0">
                                    <a href="{{ url('/blog/' . $art['slug']) }}" class="text-body fw-semibold text-decoration-none small" target="_blank" rel="noopener" style="word-break:break-word;">{{ $art['title'] }}</a>
                                    <div class="d-flex align-items-center gap-2 mt-1 small text-muted">
                                        <span><i data-lucide="message-circle" style="width:12px;height:12px;"></i> {{ $art['engagement'] }}</span>
                                        @if($art['published_at'])<span>· {{ $art['published_at'] }}</span>@endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-center small text-muted py-4 mb-0">{{ __('Aucun article publié sur la période') }}</p>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- Charts Row 0b: Top tools annuaire + Top termes glossaire + Activité outils internes --}}
<div class="row g-3 mb-3">

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i data-lucide="award" class="text-primary"></i>
                <h6 class="fw-semibold mb-0">{{ __('Top 10 outils annuaire') }}</h6>
            </div>
            <div class="card-body">
                @if(count($topDirectoryTools ?? []) > 0)
                    <ol class="list-unstyled mb-0">
                        @foreach($topDirectoryTools as $i => $t)
                            <li class="d-flex align-items-start gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold flex-shrink-0" style="min-width:28px;">{{ $i + 1 }}</span>
                                <div class="flex-grow-1 min-width-0">
                                    <a href="{{ url('/annuaire/' . $t['slug']) }}" class="text-body fw-semibold text-decoration-none small" target="_blank" rel="noopener" style="word-break:break-word;">{{ $t['name'] }}</a>
                                    <div class="d-flex align-items-center gap-2 mt-1 small text-muted">
                                        <span><i data-lucide="mouse-pointer-click" style="width:12px;height:12px;"></i> {{ $t['clicks'] }}</span>
                                        @if($t['pricing'])<span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:0.65rem;">{{ $t['pricing'] }}</span>@endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-center small text-muted py-4 mb-0">{{ __('Aucun clic enregistré sur la période') }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i data-lucide="book-open" class="text-info"></i>
                <h6 class="fw-semibold mb-0">{{ __('Top 10 termes glossaire') }}</h6>
            </div>
            <div class="card-body">
                @if(count($topDictionaryTerms ?? []) > 0)
                    <ol class="list-unstyled mb-0">
                        @foreach($topDictionaryTerms as $i => $t)
                            <li class="d-flex align-items-start gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                <span class="badge bg-info bg-opacity-10 text-info fw-bold flex-shrink-0" style="min-width:28px;">{{ $i + 1 }}</span>
                                <div class="flex-grow-1 min-width-0">
                                    <a href="{{ url('/glossaire/' . $t['slug']) }}" class="text-body fw-semibold text-decoration-none small" target="_blank" rel="noopener" style="word-break:break-word;">{{ $t['name'] }}</a>
                                    <div class="d-flex align-items-center gap-2 mt-1 small text-muted">
                                        <span><i data-lucide="eye" style="width:12px;height:12px;"></i> {{ $t['views'] }}</span>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-center small text-muted py-4 mb-0">{{ __('Tracking activé — données à venir') }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i data-lucide="wrench" class="text-warning"></i>
                <h6 class="fw-semibold mb-0">{{ __('Activité outils internes') }} ({{ $days }}j)</h6>
            </div>
            <div class="card-body">
                @if(count(($publicToolsActivity ?? [])['by_tool'] ?? []) > 0)
                    <div class="mb-2 small text-muted">{{ __('Total') }} : <strong>{{ number_format($publicToolsActivity['total']) }}</strong> {{ __('vues') }}</div>
                    <ol class="list-unstyled mb-0">
                        @foreach($publicToolsActivity['by_tool'] as $i => $t)
                            @php($pctMax = (int) ($publicToolsActivity['by_tool'][0]['count'] ?? 1))
                            @php($pct = $pctMax > 0 ? min(100, round(($t['count'] / $pctMax) * 100)) : 0)
                            <li class="py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                    <a href="{{ url('/outils/' . $t['slug']) }}" class="text-body fw-semibold text-decoration-none small" target="_blank" rel="noopener">{{ $t['name'] }}</a>
                                    <span class="small text-muted fw-semibold">{{ $t['count'] }}</span>
                                </div>
                                <div class="progress" style="height:5px;" role="progressbar" aria-label="{{ $t['name'] }} {{ $t['count'] }} vues" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-warning" style="width: {{ $pct }}%;"></div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-center small text-muted py-4 mb-0">{{ __('Tracking activé — données à venir') }}</p>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- 2026-05-05 #98 : Statistiques shorturl --}}
@if(($shortUrlStats['available'] ?? false))
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2 flex-wrap">
                <i data-lucide="link" class="text-primary"></i>
                <h6 class="fw-semibold mb-0">{{ __('Liens raccourcis (shorturl)') }} — {{ __('Période') }} : {{ $days }}j</h6>
                <a href="{{ url('/admin/shorturls') }}" class="btn btn-sm btn-outline-primary ms-auto" aria-label="{{ __('Voir tous les liens raccourcis dans l\'admin') }}">
                    {{ __('Gérer →') }}
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted mb-1">{{ __('Total actifs') }}</div>
                            <div class="h4 mb-0 fw-bold text-primary">{{ number_format($shortUrlStats['total_active']) }}</div>
                            <div class="small text-muted mt-1">{{ __('sur') }} {{ number_format($shortUrlStats['total_all']) }} {{ __('au total') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted mb-1">{{ __('Nouveaux') }} ({{ $days }}j)</div>
                            <div class="h4 mb-0 fw-bold text-success">{{ number_format($shortUrlStats['new_on_period']) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted mb-1">{{ __('Total clics') }}</div>
                            <div class="h4 mb-0 fw-bold text-info">{{ number_format($shortUrlStats['total_clicks']) }}</div>
                            <div class="small text-muted mt-1">{{ __('Moy.') }} {{ $shortUrlStats['avg_clicks_per_link'] }} / {{ __('lien') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted mb-1">{{ __('Slugs personnalisés') }}</div>
                            <div class="h4 mb-0 fw-bold">{{ $shortUrlStats['user_named_ratio_pct'] }}%</div>
                            <div class="small text-muted mt-1">{{ number_format($shortUrlStats['user_named']) }} {{ __('vs') }} {{ number_format($shortUrlStats['auto_style']) }} {{ __('auto') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted mb-1">{{ __('Authentifiés') }}</div>
                            <div class="h4 mb-0 fw-bold">{{ number_format($shortUrlStats['authenticated']) }}</div>
                            <div class="small text-muted mt-1">{{ number_format($shortUrlStats['anonymous']) }} {{ __('anonymes') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="border rounded p-3 h-100">
                            <div class="small text-muted mb-1">{{ __('Expirés') }}</div>
                            <div class="h4 mb-0 fw-bold text-warning">{{ number_format($shortUrlStats['expired']) }}</div>
                            <div class="small text-muted mt-1">{{ __('Lifetime moy.') }} {{ $shortUrlStats['lifetime_avg_days'] }} {{ __('j') }}</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-xl-6">
                        <h6 class="small text-muted text-uppercase mb-2">{{ __('Liens créés') }} (30j)</h6>
                        <div id="chart-shorturl-created" style="min-height:180px"></div>
                    </div>
                    <div class="col-12 col-xl-6">
                        <h6 class="small text-muted text-uppercase mb-2">{{ __('Clics quotidiens') }} (30j)</h6>
                        <div id="chart-shorturl-clicks" style="min-height:180px"></div>
                    </div>
                </div>

                @if(count($shortUrlStats['top_users'] ?? []) > 0)
                <div class="mt-4">
                    <h6 class="small text-muted text-uppercase mb-2">{{ __('Top 10 créateurs') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle small mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-0">#</th>
                                    <th>{{ __('Utilisateur') }}</th>
                                    <th class="text-end">{{ __('Liens') }}</th>
                                    <th class="text-end pe-0">{{ __('Clics totaux') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shortUrlStats['top_users'] as $i => $u)
                                <tr>
                                    <td class="ps-0 text-muted">{{ $i + 1 }}</td>
                                    <td><span class="fw-semibold">{{ $u['name'] }}</span> @if($u['email'])<span class="text-muted small d-block">{{ $u['email'] }}</span>@endif</td>
                                    <td class="text-end fw-semibold">{{ number_format($u['count']) }}</td>
                                    <td class="text-end pe-0">{{ number_format($u['total_clicks']) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

{{-- Charts Row 1: User Growth + Activity Timeline --}}
<div class="row g-3 mb-3">

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i data-lucide="users" class="text-primary"></i>
                <h6 class="fw-semibold mb-0">{{ __('Croissance utilisateurs') }}</h6>
            </div>
            <div class="card-body">
                <div id="chart-user-growth"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i data-lucide="activity" class="text-success"></i>
                <h6 class="fw-semibold mb-0">{{ __('Activité quotidienne') }}</h6>
            </div>
            <div class="card-body">
                <div id="chart-activity"></div>
            </div>
        </div>
    </div>

</div>

{{-- Charts Row 2: Content + Webhooks --}}
<div class="row g-3 mb-3">

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i data-lucide="file-text" class="text-info"></i>
                <h6 class="fw-semibold mb-0">{{ __('Contenu créé') }}</h6>
            </div>
            <div class="card-body">
                <div id="chart-content"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <i data-lucide="link" class="text-warning"></i>
                <h6 class="fw-semibold mb-0">{{ __('Webhooks') }}</h6>
            </div>
            <div class="card-body">
                <div id="chart-webhooks"></div>
            </div>
        </div>
    </div>

</div>

{{-- Categories Chart --}}
<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i data-lucide="tag" style="color:#8B5CF6;"></i>
                <h6 class="fw-semibold mb-0">{{ __('Articles par catégorie') }}</h6>
            </div>
            <div class="card-body">
                <div id="chart-categories"></div>
            </div>
        </div>
    </div>
</div>

@push('plugin-scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@4.4.0/dist/apexcharts.min.js" crossorigin="anonymous"></script>
@endpush

@push('custom-scripts')
<script>
window.addEventListener('load', function () {
    // Theme auto-detect (mai 2026 best practice — prefers-color-scheme)
    const apexTheme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    const gridColor = apexTheme === 'dark' ? '#374151' : '#f1f1f1';

    // Toolbar download (CSV/PNG/SVG) — standard SaaS 2026
    const commonToolbar = {
        show: true,
        offsetY: -8,
        tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false },
        export: { csv: { filename: 'laveille-stats', headerCategory: 'Date' }, svg: { filename: 'laveille-stats' }, png: { filename: 'laveille-stats' } },
    };

    // Newsletter Growth - Area Chart (subscribed vs unsubscribed)
    new ApexCharts(document.querySelector("#chart-newsletter-growth"), {
        series: [
            { name: "{{ __('Inscriptions') }}", data: @json(array_column($newsletterGrowth, 'subscribed')) },
            { name: "{{ __('Désabonnements') }}", data: @json(array_column($newsletterGrowth, 'unsubscribed')) },
        ],
        chart: { type: 'area', height: 280, toolbar: commonToolbar, stacked: false, group: 'daily', id: 'newsletter', animations: { enabled: true, speed: 600 } },
        xaxis: {
            categories: @json(array_column($newsletterGrowth, 'date')),
            labels: { style: { fontSize: '11px' }, rotate: -45, rotateAlways: @json(count($newsletterGrowth) > 14) }
        },
        yaxis: { labels: { formatter: (v) => Math.round(v) }, decimalsInFloat: 0 },
        colors: ['#8B5CF6', '#ef4444'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 100] } },
        dataLabels: { enabled: false },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        tooltip: { theme: apexTheme, shared: true, intersect: false },
        markers: { size: 2 },
        legend: { position: 'bottom' },
        theme: { mode: apexTheme },
    }).render();

    // User Growth - Area Chart
    new ApexCharts(document.querySelector("#chart-user-growth"), {
        series: [{ name: "{{ __('Inscriptions') }}", data: @json(array_column($userGrowth, 'count')) }],
        chart: { type: 'area', height: 300, toolbar: commonToolbar, animations: { enabled: true, speed: 600 } },
        xaxis: { categories: @json(array_column($userGrowth, 'label')), labels: { style: { fontSize: '12px' } } },
        yaxis: { labels: { formatter: (v) => Math.round(v) }, decimalsInFloat: 0 },
        colors: ['#7B2CF5'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 100] } },
        dataLabels: { enabled: false },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        tooltip: { theme: apexTheme, y: { formatter: (v) => v + ' {{ __("utilisateurs") }}' } },
        markers: { size: 3 },
        theme: { mode: apexTheme },
    }).render();

    // Activity Timeline - Stacked Area Chart by type (mai 2026 — split sources)
    @php($activityByTypeSeries = collect($activityByType['series'] ?? [])->map(fn($data, $name) => ['name' => $name, 'data' => $data])->values()->all())
    new ApexCharts(document.querySelector("#chart-activity"), {
        series: @json($activityByTypeSeries),
        chart: { type: 'area', height: 300, toolbar: commonToolbar, stacked: true, group: 'daily', id: 'activity', animations: { enabled: true, speed: 600 } },
        xaxis: {
            categories: @json($activityByType['dates'] ?? []),
            labels: { style: { fontSize: '11px' }, rotate: -45, rotateAlways: @json(count($activityByType['dates'] ?? []) > 14) }
        },
        yaxis: { labels: { formatter: (v) => Math.round(v) }, decimalsInFloat: 0 },
        colors: ['#0ea5e9', '#7B2CF5', '#f59e0b', '#10b981'],
        stroke: { curve: 'smooth', width: 1 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.55, opacityTo: 0.15, stops: [0, 100] } },
        dataLabels: { enabled: false },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        tooltip: { theme: apexTheme, shared: true, intersect: false, y: { formatter: (v) => v + ' {{ __("activités") }}' } },
        markers: { size: 0 },
        legend: { position: 'bottom' },
        theme: { mode: apexTheme },
    }).render();

    // Content Stats - Bar Chart
    new ApexCharts(document.querySelector("#chart-content"), {
        series: [
            { name: "{{ __('Articles créés') }}", data: [{{ $contentStats['articles_created'] }}] },
            { name: "{{ __('Articles publiés') }}", data: [{{ $contentStats['articles_published'] }}] },
            { name: "{{ __('Commentaires créés') }}", data: [{{ $contentStats['comments_created'] }}] },
            { name: "{{ __('Commentaires approuvés') }}", data: [{{ $contentStats['comments_approved'] }}] },
        ],
        chart: { type: 'bar', height: 300, toolbar: commonToolbar, animations: { enabled: true, speed: 600 } },
        xaxis: { categories: ["{{ __('Période') }} ({{ $days }}j)"] },
        yaxis: { labels: { formatter: (v) => Math.round(v) }, decimalsInFloat: 0 },
        colors: ['#7B2CF5', '#0ea5e9', '#2bc155', '#8B5CF6'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        legend: { position: 'bottom' },
        tooltip: { theme: apexTheme },
        theme: { mode: apexTheme },
    }).render();

    // Webhooks - Donut Chart
    new ApexCharts(document.querySelector("#chart-webhooks"), {
        series: [{{ $webhookStats['successful'] }}, {{ $webhookStats['failed'] }}, {{ $webhookStats['pending'] }}],
        chart: { type: 'donut', height: 300, toolbar: commonToolbar, animations: { enabled: true, speed: 600 } },
        labels: ["{{ __('Réussis') }}", "{{ __('Échoués') }}", "{{ __('En attente') }}"],
        colors: ['#2bc155', '#ef4444', '#ffbc11'],
        plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', formatter: () => '{{ $webhookStats['total'] }}' } } } } },
        dataLabels: { enabled: false },
        legend: { position: 'bottom' },
        tooltip: { theme: apexTheme },
        theme: { mode: apexTheme },
    }).render();

    // Categories - Horizontal Bar Chart (filtré : exclure 0, axe X integer-only, labels wrap)
    const catDataRaw = @json($contentStats['by_category'] ?? []);
    const catEntries = Object.entries(catDataRaw).filter(([_, v]) => v > 0).sort((a, b) => b[1] - a[1]);
    const catLabels = catEntries.map(e => e[0]);
    const catValues = catEntries.map(e => e[1]);
    const catMax = catValues.length > 0 ? Math.max(...catValues) : 1;

    if (catLabels.length > 0) {
        new ApexCharts(document.querySelector("#chart-categories"), {
            series: [{ name: "{{ __('Articles') }}", data: catValues }],
            chart: { type: 'bar', height: Math.max(200, catLabels.length * 50), toolbar: commonToolbar, animations: { enabled: true, speed: 600 } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%', distributed: false } },
            colors: ['#7B2CF5'],
            dataLabels: { enabled: true, style: { fontSize: '12px', colors: ['#fff'] } },
            grid: { borderColor: gridColor, strokeDashArray: 4 },
            xaxis: {
                categories: catLabels,
                tickAmount: Math.min(catMax, 10),
                labels: { formatter: (v) => Math.round(v).toString(), style: { fontSize: '11px' } },
                decimalsInFloat: 0,
            },
            yaxis: {
                labels: {
                    style: { fontSize: '11px' },
                    maxWidth: 180,
                    formatter: (v) => {
                        const s = String(v);
                        return s.length > 22 ? s.substring(0, 20).replace(/\s+\S*$/, '') + '…' : s;
                    },
                },
            },
            tooltip: { theme: apexTheme, y: { formatter: (v) => v + ' {{ __("articles") }}' } },
            theme: { mode: apexTheme },
        }).render();
    } else {
        document.querySelector("#chart-categories").innerHTML = '<p class="text-center small text-muted py-5">{{ __("Aucune catégorie avec des articles sur la période") }}</p>';
    }

    // 2026-05-05 #98 : Charts shorturl (créés + clics 30j)
    @if(($shortUrlStats['available'] ?? false))
    const shortCreated = @json($shortUrlStats['sparkline_created_30d']);
    const shortClicks = @json($shortUrlStats['sparkline_clicks_30d']);

    if (document.querySelector("#chart-shorturl-created")) {
        new ApexCharts(document.querySelector("#chart-shorturl-created"), {
            series: [{ name: "{{ __('Liens créés') }}", data: shortCreated.map(d => d.count) }],
            chart: { type: 'area', height: 180, toolbar: commonToolbar, animations: { enabled: true, speed: 600 }, sparkline: { enabled: false } },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
            colors: ['#0B7285'],
            dataLabels: { enabled: false },
            grid: { borderColor: gridColor, strokeDashArray: 4 },
            xaxis: {
                categories: shortCreated.map(d => d.date),
                labels: { style: { fontSize: '10px' }, rotate: -45, formatter: (v) => v?.slice(5) },
                tickAmount: 6,
            },
            yaxis: { labels: { style: { fontSize: '10px' }, formatter: (v) => Math.round(v).toString() } },
            tooltip: { theme: apexTheme, y: { formatter: (v) => v + ' {{ __("liens") }}' } },
            theme: { mode: apexTheme },
        }).render();
    }

    if (document.querySelector("#chart-shorturl-clicks")) {
        new ApexCharts(document.querySelector("#chart-shorturl-clicks"), {
            series: [{ name: "{{ __('Clics') }}", data: shortClicks.map(d => d.count) }],
            chart: { type: 'bar', height: 180, toolbar: commonToolbar, animations: { enabled: true, speed: 600 } },
            plotOptions: { bar: { borderRadius: 3, columnWidth: '70%' } },
            colors: ['#C2410C'],
            dataLabels: { enabled: false },
            grid: { borderColor: gridColor, strokeDashArray: 4 },
            xaxis: {
                categories: shortClicks.map(d => d.date),
                labels: { style: { fontSize: '10px' }, rotate: -45, formatter: (v) => v?.slice(5) },
                tickAmount: 6,
            },
            yaxis: { labels: { style: { fontSize: '10px' }, formatter: (v) => Math.round(v).toString() } },
            tooltip: { theme: apexTheme, y: { formatter: (v) => v + ' {{ __("clics") }}' } },
            theme: { mode: apexTheme },
        }).render();
    }
    @endif
});
</script>
@endpush

@endsection

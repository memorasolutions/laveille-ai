<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Statistiques', 'subtitle' => '/s/' . $shortUrl->slug])

@section('breadcrumbs')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.short-urls.index') }}">Liens courts</a></li>
        <li class="breadcrumb-item active" aria-current="page">Statistiques</li>
    </ol>
</nav>
@endsection

@section('content')

{{-- Barre info --}}
<div class="card mb-4">
    <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div>
                <code class="text-primary fs-5">/s/{{ $shortUrl->slug }}</code>
                <button type="button" class="btn btn-sm btn-outline-primary ms-2" aria-label="Copier le lien"
                        onclick="navigator.clipboard.writeText('{{ url('/s/' . $shortUrl->slug) }}');this.textContent='Copié !';setTimeout(()=>this.textContent='Copier',1500);">
                    Copier
                </button>
            </div>
            <span class="badge {{ $shortUrl->is_active ? 'bg-success' : 'bg-secondary' }}">
                {{ $shortUrl->is_active ? 'Actif' : 'Inactif' }}
            </span>
            @if($shortUrl->isExpired())
                <span class="badge bg-warning text-dark">Expiré</span>
            @endif
            <a href="{{ $shortUrl->original_url }}" target="_blank" rel="noopener" class="text-decoration-none small">
                <i data-lucide="external-link" style="width:14px;height:14px;" class="me-1"></i>
                {{ Str::limit($shortUrl->original_url, 60) }}
            </a>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.short-urls.edit', $shortUrl) }}" class="btn btn-primary btn-sm">
                <i data-lucide="edit" style="width:14px;height:14px;" class="me-1"></i>Modifier
            </a>
            <a href="{{ route('admin.short-urls.index') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
        </div>
    </div>
</div>

{{-- Stat cards --}}
<div class="row g-4 mb-4">
    @php
        $lastClick = $shortUrl->clicks()->orderByDesc('clicked_at')->first();
        $stats = [
            ['icon' => 'mouse-pointer', 'label' => 'Total clics', 'value' => number_format($analytics['total_clicks']), 'color' => 'primary'],
            ['icon' => 'smartphone', 'label' => 'Appareils', 'value' => count($analytics['devices']), 'color' => 'info'],
            ['icon' => 'globe', 'label' => 'Pays', 'value' => count($analytics['countries']), 'color' => 'success'],
            ['icon' => 'clock', 'label' => 'Dernier clic', 'value' => $lastClick ? $lastClick->clicked_at->diffForHumans() : 'Aucun', 'color' => 'warning'],
        ];
    @endphp
    @foreach($stats as $stat)
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center bg-{{ $stat['color'] }} bg-opacity-10"
                         style="width:48px;height:48px;flex-shrink:0;">
                        <i data-lucide="{{ $stat['icon'] }}" style="width:22px;height:22px;" class="text-{{ $stat['color'] }}"></i>
                    </div>
                    <div>
                        <div class="small text-muted">{{ $stat['label'] }}</div>
                        <div class="fs-5 fw-semibold">{{ $stat['value'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    {{-- Graphique clics par jour --}}
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Clics par jour (30 derniers jours)</h5>
            </div>
            <div class="card-body p-4">
                @php
                    $clicksByDay = $analytics['clicks_by_day'];
                    $maxClicks = count($clicksByDay) > 0 ? max(array_column($clicksByDay, 'count')) : 0;
                @endphp
                @if($maxClicks > 0)
                    <div class="d-flex align-items-end gap-1" style="height:200px;" aria-hidden="true">
                        @foreach($clicksByDay as $day)
                            @php $height = ($day['count'] / $maxClicks) * 100; @endphp
                            <div class="flex-fill rounded-top bg-primary bg-opacity-75"
                                 style="height:{{ $height }}%;min-width:4px;transition:height .3s;"
                                 title="{{ \Carbon\Carbon::parse($day['date'])->format('d/m') }} : {{ $day['count'] }} clic{{ $day['count'] > 1 ? 's' : '' }}"
                                 data-bs-toggle="tooltip"></div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted">{{ \Carbon\Carbon::parse($clicksByDay[0]['date'])->format('d/m') }}</small>
                        <small class="text-muted">{{ \Carbon\Carbon::parse(end($clicksByDay)['date'])->format('d/m') }}</small>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i data-lucide="bar-chart" style="width:48px;height:48px;color:#adb5bd;" class="mb-2"></i>
                        <p class="text-muted mb-0">Aucun clic sur les 30 derniers jours.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Appareils et navigateurs --}}
    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-header py-3 px-4 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Appareils</h5>
            </div>
            <div class="card-body p-4">
                @forelse($analytics['devices'] as $device)
                    @php $pct = $analytics['total_clicks'] > 0 ? round(($device['count'] / $analytics['total_clicks']) * 100) : 0; @endphp
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-capitalize">{{ $device['device_type'] ?? 'Inconnu' }}</span>
                        <span class="small text-muted">{{ $device['count'] }} ({{ $pct }}%)</span>
                    </div>
                    <div class="progress mb-3" style="height:4px;">
                        <div class="progress-bar bg-primary" style="width:{{ $pct }}%;" role="progressbar"
                             aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Aucune donnée.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header py-3 px-4 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Navigateurs</h5>
            </div>
            <div class="card-body p-4">
                @forelse($analytics['browsers'] as $browser)
                    @php $pct = $analytics['total_clicks'] > 0 ? round(($browser['count'] / $analytics['total_clicks']) * 100) : 0; @endphp
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>{{ $browser['browser'] ?? 'Inconnu' }}</span>
                        <span class="small text-muted">{{ $browser['count'] }} ({{ $pct }}%)</span>
                    </div>
                    <div class="progress mb-3" style="height:4px;">
                        <div class="progress-bar bg-info" style="width:{{ $pct }}%;" role="progressbar"
                             aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Aucune donnée.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Sources et pays --}}
<div class="row g-4">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header py-3 px-4 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Sources de trafic</h5>
            </div>
            <div class="card-body p-0">
                @if(count($analytics['top_referrers']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr><th class="ps-4">Referrer</th><th class="text-end pe-4">Clics</th></tr>
                            </thead>
                            <tbody>
                                @foreach($analytics['top_referrers'] as $ref)
                                    <tr>
                                        <td class="ps-4">{{ Str::limit($ref['referrer'], 50) }}</td>
                                        <td class="text-end pe-4 fw-semibold">{{ $ref['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">Aucun referrer enregistré.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card">
            <div class="card-header py-3 px-4 border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Pays</h5>
            </div>
            <div class="card-body p-0">
                @if(count($analytics['countries']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr><th class="ps-4">Code pays</th><th class="text-end pe-4">Clics</th></tr>
                            </thead>
                            <tbody>
                                @foreach($analytics['countries'] as $country)
                                    <tr>
                                        <td class="ps-4">{{ $country['country_code'] ?? 'Inconnu' }}</td>
                                        <td class="text-end pe-4 fw-semibold">{{ $country['count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">Aucune donnée géographique.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@extends('backoffice::themes.backend.layouts.admin', ['title' => $title ?? 'Statistiques', 'subtitle' => $subtitle ?? 'Analytiques'])

@section('content')

<nav class="page-breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Statistiques') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2"><i data-lucide="bar-chart-3" class="icon-md text-primary"></i>{{ __('Statistiques') }}</h4>
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
            </div>
        </div>
    </div>

</div>

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
<script src="{{ asset('build/nobleui/plugins/apexcharts/apexcharts.min.js') }}"></script>
@endpush

@push('custom-scripts')
<script>
window.addEventListener('load', function () {
    // User Growth - Area Chart
    new ApexCharts(document.querySelector("#chart-user-growth"), {
        series: [{ name: "{{ __('Inscriptions') }}", data: @json(array_column($userGrowth, 'count')) }],
        chart: { type: 'area', height: 300, toolbar: { show: false } },
        xaxis: { categories: @json(array_column($userGrowth, 'label')), labels: { style: { fontSize: '12px' } } },
        yaxis: { labels: { formatter: (v) => Math.round(v) } },
        colors: ['#7B2CF5'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 100] } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
        tooltip: { theme: 'light', y: { formatter: (v) => v + ' {{ __("utilisateurs") }}' } },
        markers: { size: 3 },
    }).render();

    // Activity Timeline - Area Chart
    new ApexCharts(document.querySelector("#chart-activity"), {
        series: [{ name: "{{ __('Activités') }}", data: @json(array_column($activityTimeline, 'count')) }],
        chart: { type: 'area', height: 300, toolbar: { show: false } },
        xaxis: {
            categories: @json(array_column($activityTimeline, 'date')),
            labels: { style: { fontSize: '11px' }, rotate: -45, rotateAlways: @json(count($activityTimeline) > 14) }
        },
        yaxis: { labels: { formatter: (v) => Math.round(v) } },
        colors: ['#2bc155'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 100] } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
        tooltip: { theme: 'light', y: { formatter: (v) => v + ' {{ __("activités") }}' } },
        markers: { size: 2 },
    }).render();

    // Content Stats - Bar Chart
    new ApexCharts(document.querySelector("#chart-content"), {
        series: [
            { name: "{{ __('Articles créés') }}", data: [{{ $contentStats['articles_created'] }}] },
            { name: "{{ __('Articles publiés') }}", data: [{{ $contentStats['articles_published'] }}] },
            { name: "{{ __('Commentaires créés') }}", data: [{{ $contentStats['comments_created'] }}] },
            { name: "{{ __('Commentaires approuvés') }}", data: [{{ $contentStats['comments_approved'] }}] },
        ],
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        xaxis: { categories: ["{{ __('Période') }} ({{ $days }}j)"] },
        colors: ['#7B2CF5', '#0ea5e9', '#2bc155', '#8B5CF6'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
        legend: { position: 'bottom' },
    }).render();

    // Webhooks - Donut Chart
    new ApexCharts(document.querySelector("#chart-webhooks"), {
        series: [{{ $webhookStats['successful'] }}, {{ $webhookStats['failed'] }}, {{ $webhookStats['pending'] }}],
        chart: { type: 'donut', height: 300 },
        labels: ["{{ __('Réussis') }}", "{{ __('Échoués') }}", "{{ __('En attente') }}"],
        colors: ['#2bc155', '#ef4444', '#ffbc11'],
        plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', formatter: () => '{{ $webhookStats['total'] }}' } } } } },
        dataLabels: { enabled: false },
        legend: { position: 'bottom' },
    }).render();

    // Categories - Horizontal Bar Chart
    const catData = @json($contentStats['by_category'] ?? []);
    const catLabels = Object.keys(catData);
    const catValues = Object.values(catData);
    if (catLabels.length > 0) {
        new ApexCharts(document.querySelector("#chart-categories"), {
            series: [{ name: "{{ __('Articles') }}", data: catValues }],
            chart: { type: 'bar', height: Math.max(200, catLabels.length * 40), toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
            colors: ['#7B2CF5'],
            dataLabels: { enabled: true, style: { fontSize: '12px' } },
            grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
            xaxis: { categories: catLabels, labels: { formatter: (v) => Math.round(v) } },
        }).render();
    } else {
        document.querySelector("#chart-categories").innerHTML = '<p class="text-center small text-muted py-5">{{ __("Aucune catégorie") }}</p>';
    }
});
</script>
@endpush

@endsection

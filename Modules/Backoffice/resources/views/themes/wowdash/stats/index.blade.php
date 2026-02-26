@extends('backoffice::layouts.admin', ['title' => $title ?? 'Statistiques', 'subtitle' => $subtitle ?? 'Analytiques'])

@section('content')

{{-- Period Selector --}}
<div class="d-flex gap-2 mb-24">
    <a href="?days=7" class="btn btn-sm {{ $days == 7 ? 'btn-primary-600' : 'btn-outline-primary-600' }} radius-8">7 jours</a>
    <a href="?days=30" class="btn btn-sm {{ $days == 30 ? 'btn-primary-600' : 'btn-outline-primary-600' }} radius-8">30 jours</a>
    <a href="?days=90" class="btn btn-sm {{ $days == 90 ? 'btn-primary-600' : 'btn-outline-primary-600' }} radius-8">90 jours</a>
</div>

{{-- Stats Cards --}}
<div class="row mb-24">
    <div class="col-md-4 col-xl-2 mb-16">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body p-20 text-center">
                <iconify-icon icon="solar:users-group-two-rounded-outline" class="icon text-primary-600 mb-8" style="font-size: 36px"></iconify-icon>
                <h3 class="fw-bold mb-4">{{ number_format($overview['total_users']) }}</h3>
                <span class="text-sm text-secondary-light">{{ __('Total utilisateurs') }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2 mb-16">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body p-20 text-center">
                <iconify-icon icon="solar:user-check-outline" class="icon text-success-600 mb-8" style="font-size: 36px"></iconify-icon>
                <h3 class="fw-bold mb-4">{{ number_format($overview['active_users']) }}</h3>
                <span class="text-sm text-secondary-light">{{ __('Utilisateurs actifs') }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2 mb-16">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body p-20 text-center">
                <iconify-icon icon="solar:user-plus-outline" class="icon text-warning-main mb-8" style="font-size: 36px"></iconify-icon>
                <h3 class="fw-bold mb-4">{{ number_format($overview['new_users']) }}</h3>
                <span class="text-sm text-secondary-light">{{ __('Nouveaux') }} ({{ $days }}j)</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2 mb-16">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body p-20 text-center">
                <iconify-icon icon="solar:document-text-outline" class="icon text-info-main mb-8" style="font-size: 36px"></iconify-icon>
                <h3 class="fw-bold mb-4">{{ number_format($overview['published_articles']) }}</h3>
                <span class="text-sm text-secondary-light">{{ __('Articles publiés') }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2 mb-16">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body p-20 text-center">
                <iconify-icon icon="solar:letter-outline" class="icon mb-8" style="font-size: 36px; color: #8B5CF6"></iconify-icon>
                <h3 class="fw-bold mb-4">{{ number_format($overview['total_subscribers']) }}</h3>
                <span class="text-sm text-secondary-light">{{ __('Abonnés newsletter') }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2 mb-16">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body p-20 text-center">
                <iconify-icon icon="solar:chart-2-outline" class="icon text-danger-600 mb-8" style="font-size: 36px"></iconify-icon>
                <h3 class="fw-bold mb-4">{{ number_format($overview['total_activities']) }}</h3>
                <span class="text-sm text-secondary-light">{{ __('Activités') }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Charts Row 1: User Growth + Activity Timeline --}}
<div class="row mb-24">
    <div class="col-lg-6 mb-16">
        <div class="card h-100 p-0 radius-12">
            <div class="card-header border-bottom bg-base py-16 px-24">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:users-group-two-rounded-outline" class="icon text-xl text-primary-600"></iconify-icon>
                    {{ __('Croissance utilisateurs') }}
                </h6>
            </div>
            <div class="card-body p-24">
                <div id="chart-user-growth"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-16">
        <div class="card h-100 p-0 radius-12">
            <div class="card-header border-bottom bg-base py-16 px-24">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:chart-2-outline" class="icon text-xl text-success-600"></iconify-icon>
                    {{ __('Activité quotidienne') }}
                </h6>
            </div>
            <div class="card-body p-24">
                <div id="chart-activity"></div>
            </div>
        </div>
    </div>
</div>

{{-- Charts Row 2: Content + Webhooks --}}
<div class="row mb-24">
    <div class="col-lg-6 mb-16">
        <div class="card h-100 p-0 radius-12">
            <div class="card-header border-bottom bg-base py-16 px-24">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:document-text-outline" class="icon text-xl text-info-main"></iconify-icon>
                    {{ __('Contenu créé') }}
                </h6>
            </div>
            <div class="card-body p-24">
                <div id="chart-content"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-16">
        <div class="card h-100 p-0 radius-12">
            <div class="card-header border-bottom bg-base py-16 px-24">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:link-round-angle-outline" class="icon text-xl text-warning-main"></iconify-icon>
                    {{ __('Webhooks') }}
                </h6>
            </div>
            <div class="card-body p-24">
                <div id="chart-webhooks"></div>
            </div>
        </div>
    </div>
</div>

{{-- Categories Chart --}}
<div class="row">
    <div class="col-12">
        <div class="card h-100 p-0 radius-12">
            <div class="card-header border-bottom bg-base py-16 px-24">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:tag-outline" class="icon text-xl" style="color: #8B5CF6"></iconify-icon>
                    {{ __('Articles par catégorie') }}
                </h6>
            </div>
            <div class="card-body p-24">
                <div id="chart-categories"></div>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    // User Growth - Area Chart
    new ApexCharts(document.querySelector("#chart-user-growth"), {
        series: [{ name: "{{ __('Inscriptions') }}", data: @json(array_column($userGrowth, 'count')) }],
        chart: { type: 'area', height: 300, toolbar: { show: false } },
        xaxis: { categories: @json(array_column($userGrowth, 'label')), labels: { style: { fontSize: '12px' } } },
        yaxis: { labels: { formatter: (v) => Math.round(v) } },
        colors: ['#487fff'],
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
        colors: ['#10b981'],
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
        colors: ['#487fff', '#0ea5e9', '#10b981', '#8B5CF6'],
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
        colors: ['#10b981', '#ef4444', '#f59e0b'],
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
            colors: ['#487fff'],
            dataLabels: { enabled: true, style: { fontSize: '12px' } },
            grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
            xaxis: { categories: catLabels, labels: { formatter: (v) => Math.round(v) } },
        }).render();
    } else {
        document.querySelector("#chart-categories").innerHTML = '<p class="text-center text-secondary-light py-40">{{ __("Aucune catégorie") }}</p>';
    }
});
</script>
@endsection

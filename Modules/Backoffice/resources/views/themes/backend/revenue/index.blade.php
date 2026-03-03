<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Revenus', 'subtitle' => 'Tableau de bord SaaS'])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Revenus') }}</li>
    </ol>
</nav>

{{-- KPI Cards --}}
<div class="row mb-4">
    <div class="col-sm-6 col-xl-4 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="user" class="icon-md text-primary"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('Actifs') }}</p>
                    <h4 class="fw-bold mb-0">{{ $activeCount }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="clock" class="icon-md text-info"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('En essai') }}</p>
                    <h4 class="fw-bold mb-0 text-info">{{ $trialCount }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="bell" class="icon-md text-warning"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('Grâce') }}</p>
                    <h4 class="fw-bold mb-0 text-warning">{{ $graceCount }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="dollar-sign" class="icon-md text-success"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">MRR</p>
                    <h4 class="fw-bold mb-0 text-success">{{ number_format((float) $mrr, 2) }} {{ strtoupper(config('saas.currency', 'cad')) }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="credit-card" class="icon-md text-primary"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">ARR</p>
                    <h4 class="fw-bold mb-0">{{ number_format((float) $arr, 2) }} {{ strtoupper(config('saas.currency', 'cad')) }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4 mb-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;min-width:48px;">
                    <i data-lucide="trending-down" class="icon-md text-danger"></i>
                </div>
                <div>
                    <p class="text-muted small mb-1">{{ __('Churn') }}</p>
                    <h4 class="fw-bold mb-0 text-danger">{{ $churnRate }}%</h4>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Charts Row --}}
<div class="row mb-4">
    <div class="col-xl-6 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom">
                <h4 class="fw-semibold mb-0">{{ __('Répartition des abonnements') }}</h4>
            </div>
            <div class="card-body p-4">
                <div id="subscriptionChart"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-6 mb-4">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom">
                <h4 class="fw-semibold mb-0">{{ __('Revenus par plan') }}</h4>
            </div>
            <div class="card-body p-4">
                <div id="revenueByPlanChart"></div>
            </div>
        </div>
    </div>
</div>

{{-- Key Indicators --}}
<div class="card">
    <div class="card-header py-3 px-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <h4 class="fw-semibold mb-0">{{ __('Indicateurs clés') }}</h4>
            <span class="text-muted small">{{ $newSubsThisMonth }} {{ __('nouveaux ce mois-ci') }}</span>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row">
            <div class="col-xl-6 mb-4">
                <div class="border rounded-3 p-4 h-100">
                    <h6 class="fw-semibold mb-4 small">{{ __('Répartition') }}</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex align-items-center justify-content-between py-3 border-bottom">
                            <span class="text-muted small">{{ __('Actifs') }}</span>
                            <span class="badge bg-success bg-opacity-10 text-success">{{ $activeCount }}</span>
                        </li>
                        <li class="d-flex align-items-center justify-content-between py-3 border-bottom">
                            <span class="text-muted small">{{ __('En essai') }}</span>
                            <span class="badge bg-info bg-opacity-10 text-info">{{ $trialCount }}</span>
                        </li>
                        <li class="d-flex align-items-center justify-content-between py-3 border-bottom">
                            <span class="text-muted small">{{ __('Période de grâce') }}</span>
                            <span class="badge bg-warning bg-opacity-10 text-warning">{{ $graceCount }}</span>
                        </li>
                        <li class="d-flex align-items-center justify-content-between py-3">
                            <span class="text-muted small">{{ __('Annulés ce mois') }}</span>
                            <span class="badge bg-danger bg-opacity-10 text-danger">{{ $cancelledThisMonth }}</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-xl-6 mb-4">
                <div class="border rounded-3 p-4 h-100">
                    <h6 class="fw-semibold mb-4 small">{{ __('Indicateurs') }}</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex align-items-center justify-content-between py-3 border-bottom">
                            <span class="text-muted small">{{ __('Revenus mensuels récurrents') }}</span>
                            <strong class="small">{{ number_format((float) $mrr, 2) }} {{ strtoupper(config('saas.currency', 'cad')) }}</strong>
                        </li>
                        <li class="d-flex align-items-center justify-content-between py-3 border-bottom">
                            <span class="text-muted small">{{ __('Revenus annuels récurrents') }}</span>
                            <strong class="small">{{ number_format((float) $arr, 2) }} {{ strtoupper(config('saas.currency', 'cad')) }}</strong>
                        </li>
                        <li class="d-flex align-items-center justify-content-between py-3 border-bottom">
                            <span class="text-muted small">{{ __('Taux de désabonnement') }}</span>
                            <strong class="small">{{ $churnRate }}%</strong>
                        </li>
                        <li class="d-flex align-items-center justify-content-between py-3">
                            <span class="text-muted small">{{ __('Total abonnés') }}</span>
                            <strong class="small">{{ $activeCount + $trialCount + $graceCount }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('build/nobleui/plugins/apexcharts/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Donut Chart - Subscription Distribution
    var subscriptionOptions = {
        series: [{{ $activeCount }}, {{ $trialCount }}, {{ $graceCount }}, {{ $cancelledThisMonth }}],
        chart: { type: 'donut', height: 350 },
        colors: ['#7B2CF5', '#0ea5e9', '#f59e0b', '#ef4444'],
        labels: ['{{ __("Actifs") }}', '{{ __("En essai") }}', '{{ __("Grâce") }}', '{{ __("Annulés") }}'],
        legend: { position: 'bottom' },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: function(w) {
                                return w.globals.seriesTotals.reduce(function(a, b) { return a + b; }, 0);
                            }
                        }
                    }
                }
            }
        },
        responsive: [{ breakpoint: 480, options: { chart: { height: 300 } } }]
    };
    new ApexCharts(document.querySelector("#subscriptionChart"), subscriptionOptions).render();

    // Bar Chart - Revenue by Plan
    var revenueOptions = {
        series: [{ name: '{{ __("Revenus") }}', data: @json(collect($revenueByPlan)->pluck('revenue')->values()) }],
        chart: { type: 'bar', height: 350, toolbar: { show: false } },
        colors: ['#7B2CF5'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: @json(collect($revenueByPlan)->pluck('plan_name')->values()) },
        yaxis: {
            title: { text: '{{ strtoupper(config("saas.currency", "cad")) }}' },
            labels: { formatter: function(v) { return v.toFixed(2); } }
        },
        grid: { borderColor: '#E5E7EB', strokeDashArray: 3 },
        tooltip: { y: { formatter: function(v) { return v.toFixed(2) + ' {{ strtoupper(config("saas.currency", "cad")) }}'; } } }
    };
    new ApexCharts(document.querySelector("#revenueByPlanChart"), revenueOptions).render();
});
</script>
@endpush

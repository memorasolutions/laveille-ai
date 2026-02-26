@extends('backoffice::layouts.admin', ['title' => 'Revenus', 'subtitle' => 'Tableau de bord SaaS'])

@section('content')

{{-- Row 1: 6 KPI Cards --}}
<div class="row gy-4 mb-24">
    <div class="col-xxl-2 col-sm-4">
        <div class="card shadow-none border bg-gradient-start-1 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">{{ __('Actifs') }}</p>
                        <h6 class="mb-0">{{ $activeCount }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-primary-600 rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:user-check-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-sm-4">
        <div class="card shadow-none border bg-gradient-start-2 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">{{ __('En essai') }}</p>
                        <h6 class="mb-0 text-info-main">{{ $trialCount }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-info-main rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:clock-circle-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-sm-4">
        <div class="card shadow-none border bg-gradient-start-3 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">{{ __('Grâce') }}</p>
                        <h6 class="mb-0 text-warning-main">{{ $graceCount }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-warning-main rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:alarm-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-sm-4">
        <div class="card shadow-none border bg-gradient-start-4 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">MRR</p>
                        <h6 class="mb-0 text-success-main">{{ number_format((float) $mrr, 2) }} {{ strtoupper(config('saas.currency', 'cad')) }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-success-main rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:dollar-minimalistic-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-sm-4">
        <div class="card shadow-none border bg-gradient-start-1 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">ARR</p>
                        <h6 class="mb-0">{{ number_format((float) $arr, 2) }} {{ strtoupper(config('saas.currency', 'cad')) }}</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-primary-600 rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:wallet-money-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-2 col-sm-4">
        <div class="card shadow-none border bg-gradient-start-2 h-100">
            <div class="card-body p-20">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <p class="fw-medium text-primary-light mb-1">{{ __('Churn') }}</p>
                        <h6 class="mb-0 text-danger-main">{{ $churnRate }}%</h6>
                    </div>
                    <div class="w-50-px h-50-px bg-danger-main rounded-circle d-flex justify-content-center align-items-center">
                        <iconify-icon icon="solar:graph-down-outline" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Row 2: Charts --}}
<div class="row gy-4 mb-24">
    <div class="col-md-6">
        <div class="card h-100 p-0 radius-12">
            <div class="card-header border-bottom bg-base py-16 px-24">
                <h6 class="text-lg fw-semibold mb-0">{{ __('Répartition des abonnements') }}</h6>
            </div>
            <div class="card-body p-24">
                <div id="subscriptionChart" class="apexcharts-tooltip-style-1"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 p-0 radius-12">
            <div class="card-header border-bottom bg-base py-16 px-24">
                <h6 class="text-lg fw-semibold mb-0">{{ __('Revenus par plan') }}</h6>
            </div>
            <div class="card-body p-24">
                <div id="revenueByPlanChart" class="apexcharts-tooltip-style-1"></div>
            </div>
        </div>
    </div>
</div>

{{-- Row 3: Indicateurs clés --}}
<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center justify-content-between">
        <h6 class="text-lg fw-semibold mb-0">{{ __('Indicateurs clés') }}</h6>
        <span class="text-secondary-light text-sm">{{ $newSubsThisMonth }} {{ __('nouveaux ce mois-ci') }}</span>
    </div>
    <div class="card-body p-24">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border radius-8 p-16 h-100">
                    <h6 class="text-md fw-semibold mb-12">{{ __('Répartition') }}</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between py-8 border-bottom">
                            <span class="text-secondary-light">{{ __('Actifs') }}</span>
                            <span class="badge bg-success-100 text-success-600">{{ $activeCount }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-8 border-bottom">
                            <span class="text-secondary-light">{{ __('En essai') }}</span>
                            <span class="badge bg-info-100 text-info-600">{{ $trialCount }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-8 border-bottom">
                            <span class="text-secondary-light">{{ __('Période de grâce') }}</span>
                            <span class="badge bg-warning-100 text-warning-600">{{ $graceCount }}</span>
                        </li>
                        <li class="d-flex justify-content-between py-8">
                            <span class="text-secondary-light">{{ __('Annulés ce mois') }}</span>
                            <span class="badge bg-danger-100 text-danger-600">{{ $cancelledThisMonth }}</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border radius-8 p-16 h-100">
                    <h6 class="text-md fw-semibold mb-12">{{ __('Indicateurs') }}</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between py-8 border-bottom">
                            <span class="text-secondary-light">{{ __('Revenus mensuels récurrents') }}</span>
                            <strong>{{ number_format((float) $mrr, 2) }} {{ strtoupper(config('saas.currency', 'cad')) }}</strong>
                        </li>
                        <li class="d-flex justify-content-between py-8 border-bottom">
                            <span class="text-secondary-light">{{ __('Revenus annuels récurrents') }}</span>
                            <strong>{{ number_format((float) $arr, 2) }} {{ strtoupper(config('saas.currency', 'cad')) }}</strong>
                        </li>
                        <li class="d-flex justify-content-between py-8 border-bottom">
                            <span class="text-secondary-light">{{ __('Taux de désabonnement') }}</span>
                            <strong>{{ $churnRate }}%</strong>
                        </li>
                        <li class="d-flex justify-content-between py-8">
                            <span class="text-secondary-light">{{ __('Total abonnés') }}</span>
                            <strong>{{ $activeCount + $trialCount + $graceCount }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Donut Chart - Subscription Distribution
    var subscriptionOptions = {
        series: [{{ $activeCount }}, {{ $trialCount }}, {{ $graceCount }}, {{ $cancelledThisMonth }}],
        chart: { type: 'donut', height: 350 },
        colors: ['#487FFF', '#00B8D9', '#FFAB00', '#FF5630'],
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
        colors: ['#487FFF'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: @json(collect($revenueByPlan)->pluck('plan_name')->values()) },
        yaxis: {
            title: { text: '{{ strtoupper(config("saas.currency", "cad")) }}' },
            labels: { formatter: function(v) { return v.toFixed(2); } }
        },
        grid: { borderColor: '#D1D5DB', strokeDashArray: 3 },
        tooltip: { y: { formatter: function(v) { return v.toFixed(2) + ' {{ strtoupper(config("saas.currency", "cad")) }}'; } } }
    };
    new ApexCharts(document.querySelector("#revenueByPlanChart"), revenueOptions).render();
});
</script>
@endpush


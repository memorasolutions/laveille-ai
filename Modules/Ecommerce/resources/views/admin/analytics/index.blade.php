<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => __('Analytique ventes'), 'subtitle' => __('Boutique')])

@section('content')

<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Administration') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ecommerce.dashboard') }}">{{ __('Boutique') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Analytique') }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i data-lucide="bar-chart-3" class="icon-md text-primary"></i> {{ __('Analytique ventes') }}
    </h4>
    <form class="d-flex gap-2 align-items-center" method="GET">
        <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="form-control form-control-sm">
        <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="form-control form-control-sm">
        <button class="btn btn-sm btn-primary" type="submit"><i data-lucide="filter" class="icon-sm"></i></button>
    </form>
</div>

{{-- Summary cards --}}
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i data-lucide="shopping-bag" class="icon-lg text-primary mb-2"></i>
                <h3 class="fw-bold">{{ $summary['total_orders'] }}</h3>
                <p class="text-muted mb-0">{{ __('Commandes') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i data-lucide="dollar-sign" class="icon-lg text-success mb-2"></i>
                <h3 class="fw-bold">{{ number_format($summary['total_revenue'], 2) }} $</h3>
                <p class="text-muted mb-0">{{ __('Revenus') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i data-lucide="trending-up" class="icon-lg text-info mb-2"></i>
                <h3 class="fw-bold">{{ number_format($summary['average_order_value'], 2) }} $</h3>
                <p class="text-muted mb-0">{{ __('Panier moyen') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <i data-lucide="clock" class="icon-lg text-warning mb-2"></i>
                <h3 class="fw-bold">{{ $summary['pending_orders'] }}</h3>
                <p class="text-muted mb-0">{{ __('En attente') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    {{-- Revenue chart --}}
    <div class="col-lg-8 mb-3">
        <div class="card">
            <div class="card-header py-3 border-bottom"><h5 class="mb-0">{{ __('Revenus par jour') }}</h5></div>
            <div class="card-body">
                <div id="revenueChart"></div>
            </div>
        </div>
    </div>

    {{-- Orders by status --}}
    <div class="col-lg-4 mb-3">
        <div class="card">
            <div class="card-header py-3 border-bottom"><h5 class="mb-0">{{ __('Commandes par statut') }}</h5></div>
            <div class="card-body">
                <div id="statusChart"></div>
            </div>
        </div>
    </div>
</div>

{{-- Top products --}}
<div class="card">
    <div class="card-header py-3 border-bottom"><h5 class="mb-0">{{ __('Produits les plus vendus') }}</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('Produit') }}</th>
                        <th class="text-center">{{ __('Quantité') }}</th>
                        <th class="text-end">{{ __('Revenus') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $i => $product)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $product->product_name }}</td>
                        <td class="text-center">{{ $product->total_quantity }}</td>
                        <td class="text-end">{{ number_format((float) $product->total_revenue, 2) }} $</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">{{ __('Aucune donnée pour cette période.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue by day
    const revenueData = @json($revenueByDay);
    if (document.getElementById('revenueChart')) {
        new ApexCharts(document.getElementById('revenueChart'), {
            chart: { type: 'area', height: 300, toolbar: { show: false } },
            series: [{
                name: '{{ __("Revenus") }}',
                data: revenueData.map(d => ({ x: d.date, y: parseFloat(d.revenue) }))
            }],
            xaxis: { type: 'datetime' },
            yaxis: { labels: { formatter: v => v.toFixed(2) + ' $' } },
            tooltip: { y: { formatter: v => v.toFixed(2) + ' $' } },
            colors: ['#6571ff'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.1 } }
        }).render();
    }

    // Orders by status donut
    const statusData = @json($ordersByStatus);
    const labels = Object.keys(statusData);
    const values = Object.values(statusData);
    if (document.getElementById('statusChart') && labels.length) {
        new ApexCharts(document.getElementById('statusChart'), {
            chart: { type: 'donut', height: 300 },
            series: values,
            labels: labels,
            colors: ['#fbbc06', '#05a34a', '#0dcaf0', '#6571ff', '#dc3545', '#6c757d'],
            legend: { position: 'bottom' }
        }).render();
    }
});
</script>
@endpush

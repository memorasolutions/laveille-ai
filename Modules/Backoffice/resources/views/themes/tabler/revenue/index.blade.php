@extends('backoffice::layouts.admin', ['title' => 'Revenus', 'subtitle' => 'Monétisation'])
@section('content')
<div class="row row-deck row-cards">
    <div class="col-sm-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="subheader">MRR</div><div class="h1 mb-0 mt-2">{{ number_format($mrr ?? 0, 2) }} $</div></div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="subheader">ARR</div><div class="h1 mb-0 mt-2">{{ number_format($arr ?? 0, 2) }} $</div></div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="subheader">Churn</div><div class="h1 mb-0 mt-2">{{ number_format($churn ?? 0, 1) }}%</div></div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card"><div class="card-body"><div class="subheader">Abonnements actifs</div><div class="h1 mb-0 mt-2">{{ $activeSubscriptions ?? 0 }}</div></div></div>
    </div>
</div>
<div class="card mt-4">
    <div class="card-header"><h3 class="card-title">Revenus mensuels</h3></div>
    <div class="card-body"><div id="chart-revenue"></div></div>
</div>
@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var data = @json($revenueByMonth ?? []);
    if (document.querySelector("#chart-revenue") && data.length) {
        new ApexCharts(document.querySelector("#chart-revenue"), {
            series: [{ name: "Revenus", data: data.map(function(d) { return d.amount || 0; }) }],
            chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
            xaxis: { categories: data.map(function(d) { return d.label || ''; }) },
            colors: ['#206bc4'],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
            dataLabels: { enabled: false }
        }).render();
    }
});
</script>
@endpush
@endsection

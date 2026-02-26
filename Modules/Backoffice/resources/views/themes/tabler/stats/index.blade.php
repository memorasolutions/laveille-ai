@extends('backoffice::layouts.admin', ['title' => 'Statistiques', 'subtitle' => 'Vue d\'ensemble'])

@section('content')

<div class="row row-deck row-cards">
    @foreach($stats ?? [] as $stat)
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">{{ $stat['label'] ?? '' }}</div>
                </div>
                <div class="h1 mb-0 mt-2">{{ $stat['value'] ?? 0 }}</div>
                @if(isset($stat['change']))
                <div class="mt-1">
                    <span class="text-{{ $stat['change'] >= 0 ? 'success' : 'danger' }}">
                        {{ $stat['change'] >= 0 ? '+' : '' }}{{ $stat['change'] }}%
                    </span>
                    <span class="text-muted">vs mois dernier</span>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tendances</h3>
            </div>
            <div class="card-body">
                <div id="chart-stats-overview" style="height: 350px;"></div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chartData = @json($charts ?? []);
    if (document.querySelector("#chart-stats-overview") && chartData.labels) {
        new ApexCharts(document.querySelector("#chart-stats-overview"), {
            series: chartData.series || [],
            chart: { type: 'line', height: 350, toolbar: { show: true }, fontFamily: 'inherit' },
            xaxis: { categories: chartData.labels || [] },
            colors: ['#206bc4', '#2fb344', '#f76707', '#d63939'],
            stroke: { curve: 'smooth', width: 2 },
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 },
            legend: { position: 'top' }
        }).render();
    }
});
</script>
@endpush

@endsection

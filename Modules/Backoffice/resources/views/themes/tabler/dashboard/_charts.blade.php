<div class="row mt-4">
    <div class="col-md-6 mb-4 mb-md-0">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Inscriptions utilisateurs (12 mois)</h3>
            </div>
            <div class="card-body">
                <div id="chart-users-monthly"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Articles créés (12 mois)</h3>
            </div>
            <div class="card-body">
                <div id="chart-articles-monthly"></div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
window.addEventListener('load', function () {
    var usersData = @json(array_column($usersByMonth, 'count'));
    var usersLabels = @json(array_column($usersByMonth, 'label'));
    var articlesData = @json(array_column($articlesByMonth, 'count'));
    var articlesLabels = @json(array_column($articlesByMonth, 'label'));

    if (document.querySelector("#chart-users-monthly")) {
        new ApexCharts(document.querySelector("#chart-users-monthly"), {
            series: [{ name: "Inscriptions", data: usersData }],
            chart: { type: 'area', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
            xaxis: { categories: usersLabels },
            colors: ['#206bc4'],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 }
        }).render();
    }

    if (document.querySelector("#chart-articles-monthly")) {
        new ApexCharts(document.querySelector("#chart-articles-monthly"), {
            series: [{ name: "Articles", data: articlesData }],
            chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'inherit' },
            xaxis: { categories: articlesLabels },
            colors: ['#2fb344'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 }
        }).render();
    }
});
</script>
@endpush

<div class="row mt-24">
    <div class="col-md-6 mb-4 mb-md-0">
        <div class="card shadow-none border h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <iconify-icon icon="solar:chart-2-outline" class="text-xl text-primary-600"></iconify-icon>
                <h6 class="mb-0">Inscriptions utilisateurs (12 mois)</h6>
            </div>
            <div class="card-body">
                <div id="chart-users-monthly"></div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-none border h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <iconify-icon icon="solar:document-text-outline" class="text-xl text-success-600"></iconify-icon>
                <h6 class="mb-0">Articles créés (12 mois)</h6>
            </div>
            <div class="card-body">
                <div id="chart-articles-monthly"></div>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    const usersData = @json(array_column($usersByMonth, 'count'));
    const usersLabels = @json(array_column($usersByMonth, 'label'));
    const articlesData = @json(array_column($articlesByMonth, 'count'));
    const articlesLabels = @json(array_column($articlesByMonth, 'label'));

    new ApexCharts(document.querySelector("#chart-users-monthly"), {
        series: [{ name: "Inscriptions", data: usersData }],
        chart: { type: 'area', height: 250, toolbar: { show: false }, sparkline: { enabled: false } },
        xaxis: { categories: usersLabels, labels: { style: { fontSize: '12px' } } },
        yaxis: { labels: { formatter: (v) => Math.round(v) } },
        colors: ['#487fff'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 100] } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
        tooltip: { theme: 'light', y: { formatter: (v) => v + ' utilisateurs' } },
        markers: { size: 3 },
    }).render();

    new ApexCharts(document.querySelector("#chart-articles-monthly"), {
        series: [{ name: "Articles", data: articlesData }],
        chart: { type: 'bar', height: 250, toolbar: { show: false } },
        xaxis: { categories: articlesLabels, labels: { style: { fontSize: '12px' } } },
        yaxis: { labels: { formatter: (v) => Math.round(v) } },
        colors: ['#45b369'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
        tooltip: { theme: 'light', y: { formatter: (v) => v + ' articles' } },
    }).render();
});
</script>

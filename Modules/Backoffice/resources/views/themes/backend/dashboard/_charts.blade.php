<div class="row mt-4">

    <div class="col-xl-6 mb-4 mb-xl-0">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="trending-up" class="text-primary icon-md"></i>
                <h4 class="fw-bold mb-0">{{ __('Inscriptions utilisateurs (12 mois)') }}</h4>
            </div>
            <div class="card-body p-4">
                <div id="chart-users-monthly"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header py-3 px-4 border-bottom d-flex align-items-center gap-2">
                <i data-lucide="bar-chart-3" class="text-success icon-md"></i>
                <h4 class="fw-bold mb-0">{{ __('Articles créés (12 mois)') }}</h4>
            </div>
            <div class="card-body p-4">
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
        colors: ['#7B2CF5'],
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
        colors: ['#2bc155'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
        tooltip: { theme: 'light', y: { formatter: (v) => v + ' articles' } },
    }).render();
});
</script>

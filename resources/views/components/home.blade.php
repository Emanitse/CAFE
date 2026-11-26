@extends('layouts.app')

@section('content')
<div class="container-fluid pb-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Dashboard Overview</h3>
            <small class="text-muted">Welcome back, {{ session('username') ?? 'Staff' }} — {{ $systemName }} performance metrics</small>
        </div>
        <button type="button" id="refreshDashboardBtn" class="btn bg-coffee text-white fw-bold shadow-sm px-3">
            Refresh Data
        </button>
    </div>

    <!-- Top KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Today's Sales</small>
                    <h4 class="fw-bold text-success mb-0 mt-1" id="kpi-today-sales">₱{{ $dashboardData['kpis']['todaySales'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Today's Orders</small>
                    <h4 class="fw-bold text-dark mb-0 mt-1" id="kpi-today-orders">{{ $dashboardData['kpis']['todayOrders'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-coffee text-white">
                <div class="card-body">
                    <small class="opacity-75 fw-bold text-uppercase" style="font-size: 0.72rem;">Total Revenue</small>
                    <h4 class="fw-bold mb-0 mt-1" id="kpi-total-revenue">₱{{ $dashboardData['kpis']['totalRevenue'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Active Menu Items</small>
                    <h4 class="fw-bold text-dark mb-0 mt-1" id="kpi-active-items">{{ $dashboardData['kpis']['activeItems'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 1: Line Chart (7-Day Revenue) + Doughnut Chart (Category Share) -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Daily Revenue Trend</h6>
                        <small class="text-muted">Sales performance over the last 7 days</small>
                    </div>
                    <span class="badge bg-light text-dark border">Line Chart</span>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 280px;">
                        <canvas id="revenueLineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Sales by Category</h6>
                        <small class="text-muted">Revenue share per category</small>
                    </div>
                    <span class="badge bg-light text-dark border">Doughnut</span>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div style="position: relative; height: 280px; width: 100%;">
                        <canvas id="categoryDoughnutChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Bar Chart (Top Items) + Pie Chart (Payment Methods) -->
    <div class="row g-3">
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Top-Selling Menu Items</h6>
                        <small class="text-muted">Highest quantity sold across orders</small>
                    </div>
                    <span class="badge bg-light text-dark border">Bar Chart</span>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 280px;">
                        <canvas id="topItemsBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Payment Methods</h6>
                        <small class="text-muted">Order count &amp; volume by method</small>
                    </div>
                    <span class="badge bg-light text-dark border">Pie Chart</span>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div style="position: relative; height: 280px; width: 100%;">
                        <canvas id="paymentPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const initialData = @json($dashboardData);

    // Cohesive Cafe Color Palette
    const coffeePalette = [
        '#66402E', // Espresso (Primary)
        '#B07D56', // Caramel
        '#D4A373', // Latte
        '#3D251E', // Dark Roast
        '#8C5A3C', // Mocha
        '#E6CCB2', // Cream
        '#9C6644'  // Cinnamon
    ];

    let lineChart, doughnutChart, barChart, pieChart;

    function formatPeso(value) {
        return '₱' + parseFloat(value || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function renderCharts(data) {
        // Update KPI Cards
        document.getElementById('kpi-today-sales').textContent = `₱${data.kpis.todaySales}`;
        document.getElementById('kpi-today-orders').textContent = data.kpis.todayOrders;
        document.getElementById('kpi-total-revenue').textContent = `₱${data.kpis.totalRevenue}`;
        document.getElementById('kpi-active-items').textContent = data.kpis.activeItems;

        // Destroy existing instances before re-rendering on refresh
        if (lineChart) lineChart.destroy();
        if (doughnutChart) doughnutChart.destroy();
        if (barChart) barChart.destroy();
        if (pieChart) pieChart.destroy();

        // 1. LINE CHART: Daily Revenue Trend
        lineChart = new Chart(document.getElementById('revenueLineChart'), {
            type: 'line',
            data: {
                labels: data.line.labels,
                datasets: [{
                    label: 'Daily Sales (₱)',
                    data: data.line.values,
                    borderColor: '#66402E',
                    backgroundColor: 'rgba(102, 64, 46, 0.12)',
                    borderWidth: 3,
                    pointBackgroundColor: '#66402E',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    tension: 0.35,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` Sales: ${formatPeso(ctx.parsed.y)}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (val) => '₱' + val.toLocaleString()
                        },
                        grid: { color: '#f1f1f1' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        // 2. DOUGHNUT CHART: Sales Revenue by Category
        const hasCategoryData = data.doughnut.labels.length > 0;
        doughnutChart = new Chart(document.getElementById('categoryDoughnutChart'), {
            type: 'doughnut',
            data: {
                labels: hasCategoryData ? data.doughnut.labels : ['No Sales Yet'],
                datasets: [{
                    data: hasCategoryData ? data.doughnut.values : [1],
                    backgroundColor: hasCategoryData ? coffeePalette : ['#e9ecef'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 14 }
                    },
                    tooltip: {
                        enabled: hasCategoryData,
                        callbacks: {
                            label: (ctx) => ` ${ctx.label}: ${formatPeso(ctx.parsed)}`
                        }
                    }
                }
            }
        });

        // 3. BAR CHART: Top-Selling Menu Items
        const hasBarData = data.bar.labels.length > 0;
        barChart = new Chart(document.getElementById('topItemsBarChart'), {
            type: 'bar',
            data: {
                labels: hasBarData ? data.bar.labels : ['No Orders Yet'],
                datasets: [{
                    label: 'Units Sold',
                    data: hasBarData ? data.bar.values : [0],
                    backgroundColor: coffeePalette,
                    borderRadius: 6,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` Sold: ${ctx.parsed.y} pcs`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: '#f1f1f1' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        // 4. PIE CHART: Payment Methods
        const hasPieData = data.pie.labels.length > 0;
        pieChart = new Chart(document.getElementById('paymentPieChart'), {
            type: 'pie',
            data: {
                labels: hasPieData ? data.pie.labels : ['No Payments Yet'],
                datasets: [{
                    data: hasPieData ? data.pie.values : [1],
                    backgroundColor: hasPieData ? ['#66402E', '#D4A373', '#8C5A3C'] : ['#e9ecef'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 14 }
                    },
                    tooltip: {
                        enabled: hasPieData,
                        callbacks: {
                            label: (ctx) => {
                                const count = ctx.parsed;
                                const totalPaid = data.pie.amounts[ctx.dataIndex] ?? 0;
                                return ` ${ctx.label}: ${count} orders (${formatPeso(totalPaid)})`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Initial render
    renderCharts(initialData);

    // Live Refresh without page reload
    document.getElementById('refreshDashboardBtn')?.addEventListener('click', async function () {
        this.disabled = true;
        this.textContent = 'Refreshing...';

        try {
            const res = await fetch('/home/data', {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();
            if (json.success) {
                renderCharts(json.data);
            }
        } catch (err) {
            console.error('Failed to refresh dashboard data:', err);
        } finally {
            this.disabled = false;
            this.textContent = 'Refresh Data';
        }
    });
});
</script>
@endpush
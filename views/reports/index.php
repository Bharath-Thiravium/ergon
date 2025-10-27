<?php
$title = 'Analytics';
$active_page = 'reports';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📈</span> Analytics & Reports</h1>
        <p>Comprehensive analytics and reporting dashboard</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--primary">
            <span>📄</span> Export Report
        </button>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📈</div>
            <div class="kpi-card__trend">↗ +15%</div>
        </div>
        <div class="kpi-card__value">4</div>
        <div class="kpi-card__label">Active Reports</div>
        <div class="kpi-card__status">Generated</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📊</div>
            <div class="kpi-card__trend">↗ +8%</div>
        </div>
        <div class="kpi-card__value">87%</div>
        <div class="kpi-card__label">Data Accuracy</div>
        <div class="kpi-card__status">Verified</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🔄</div>
            <div class="kpi-card__trend">↗ +12%</div>
        </div>
        <div class="kpi-card__value">24h</div>
        <div class="kpi-card__label">Last Updated</div>
        <div class="kpi-card__status">Real-time</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>📈</span> Attendance Report
            </h2>
        </div>
        <div class="card__body">
            <div id="attendanceChart"></div>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>✅</span> Task Completion Report
            </h2>
        </div>
        <div class="card__body">
            <div id="taskChart"></div>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>📅</span> Leave Statistics
            </h2>
        </div>
        <div class="card__body">
            <div id="leaveChart"></div>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>💰</span> Expense Summary
            </h2>
        </div>
        <div class="card__body">
            <div id="expenseChart"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attendance Chart - Advanced Area Chart
    const attendanceOptions = {
        series: [{
            name: 'Present',
            data: [8, 7, 9, 8, 6, 3, 2]
        }],
        chart: {
            type: 'area',
            height: 200,
            toolbar: { show: false },
            sparkline: { enabled: false }
        },
        colors: ['#1e40af'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.1
            }
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        xaxis: {
            categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            labels: { style: { fontSize: '12px' } }
        },
        yaxis: {
            labels: { style: { fontSize: '12px' } }
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 3
        },
        tooltip: {
            theme: 'light'
        }
    };
    new ApexCharts(document.querySelector('#attendanceChart'), attendanceOptions).render();

    // Task Chart - Modern Donut
    const taskOptions = {
        series: [65, 25, 10],
        chart: {
            type: 'donut',
            height: 200
        },
        labels: ['Completed', 'In Progress', 'Pending'],
        colors: ['#059669', '#d97706', '#dc2626'],
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            fontSize: '16px',
                            fontWeight: 600
                        }
                    }
                }
            }
        },
        legend: {
            position: 'bottom',
            fontSize: '12px'
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: { height: 180 },
                legend: { position: 'bottom' }
            }
        }]
    };
    new ApexCharts(document.querySelector('#taskChart'), taskOptions).render();

    // Leave Chart - Gradient Bar
    const leaveOptions = {
        series: [{
            name: 'Leave Requests',
            data: [12, 8, 15, 3]
        }],
        chart: {
            type: 'bar',
            height: 200,
            toolbar: { show: false }
        },
        colors: ['#1e40af'],
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.25,
                gradientToColors: ['#3b82f6'],
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 0.85
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '60%'
            }
        },
        xaxis: {
            categories: ['Casual', 'Sick', 'Annual', 'Emergency'],
            labels: { style: { fontSize: '12px' } }
        },
        yaxis: {
            labels: { style: { fontSize: '12px' } }
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 3
        }
    };
    new ApexCharts(document.querySelector('#leaveChart'), leaveOptions).render();

    // Expense Chart - Radial Bar
    const expenseOptions = {
        series: [40, 25, 20, 15],
        chart: {
            type: 'radialBar',
            height: 200
        },
        plotOptions: {
            radialBar: {
                dataLabels: {
                    name: {
                        fontSize: '12px'
                    },
                    value: {
                        fontSize: '14px',
                        formatter: function (val) {
                            return val + '%'
                        }
                    },
                    total: {
                        show: true,
                        label: 'Total',
                        formatter: function () {
                            return '100%'
                        }
                    }
                }
            }
        },
        labels: ['Travel', 'Food', 'Supplies', 'Other'],
        colors: ['#1e40af', '#059669', '#d97706', '#dc2626']
    };
    new ApexCharts(document.querySelector('#expenseChart'), expenseOptions).render();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

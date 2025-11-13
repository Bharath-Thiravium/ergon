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
        <a href="/ergon/reports/export" class="btn btn--primary">
            <span>📄</span> Export Report
        </a>
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
            <div class="legends-grid">
                <div class="legend-section">
                    <div class="legend-items">
                        <div class="legend-item">
                            <div class="category-tag" style="background: #1e40af; color: white;">Travel</div>
                            <span>40% - Transportation & fuel</span>
                        </div>
                        <div class="legend-item">
                            <div class="category-tag" style="background: #059669; color: white;">Food</div>
                            <span>25% - Meals & dining</span>
                        </div>
                        <div class="legend-item">
                            <div class="category-tag" style="background: #d97706; color: white;">Supplies</div>
                            <span>20% - Office materials</span>
                        </div>
                        <div class="legend-item">
                            <div class="category-tag" style="background: #dc2626; color: white;">Other</div>
                            <span>15% - Miscellaneous</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
<script>
/**
 * Common chart configuration constants
 */
const CHART_COMMON_CONFIG = {
    height: 200,
    toolbar: { show: false }
};

const AXIS_LABEL_STYLE = { style: { fontSize: '12px' } };
const GRID_CONFIG = { borderColor: '#f1f5f9', strokeDashArray: 3 };

/**
 * Creates and renders an ApexChart with the given options
 * @param {string} selector - CSS selector for the chart container
 * @param {Object} options - Chart configuration options
 */
function createChart(selector, options) {
    const chartElement = document.querySelector(selector);
    if (chartElement) {
        new ApexCharts(chartElement, options).render();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Attendance Chart - Advanced Area Chart
    const attendanceOptions = {
        series: [{ name: 'Present', data: [8, 7, 9, 8, 6, 3, 2] }],
        chart: { ...CHART_COMMON_CONFIG, type: 'area', sparkline: { enabled: false } },
        colors: ['#1e40af'],
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 }
        },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], labels: AXIS_LABEL_STYLE },
        yaxis: { labels: AXIS_LABEL_STYLE },
        grid: GRID_CONFIG,
        tooltip: { theme: 'light' }
    };
    createChart('#attendanceChart', attendanceOptions);

    // Task Chart - Modern Donut
    const taskOptions = {
        series: [65, 25, 10],
        chart: { type: 'donut', height: 200 },
        labels: ['Completed', 'In Progress', 'Pending'],
        colors: ['#059669', '#d97706', '#dc2626'],
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: { show: true, fontSize: '16px', fontWeight: 600 }
                    }
                }
            }
        },
        legend: { position: 'bottom', fontSize: '12px' },
        responsive: [{
            breakpoint: 480,
            options: { chart: { height: 180 }, legend: { position: 'bottom' } }
        }]
    };
    createChart('#taskChart', taskOptions);

    // Leave Chart - Gradient Bar
    const leaveOptions = {
        series: [{ name: 'Leave Requests', data: [12, 8, 15, 3] }],
        chart: { ...CHART_COMMON_CONFIG, type: 'bar' },
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
        plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
        xaxis: { categories: ['Casual', 'Sick', 'Annual', 'Emergency'], labels: AXIS_LABEL_STYLE },
        yaxis: { labels: AXIS_LABEL_STYLE },
        grid: GRID_CONFIG
    };
    createChart('#leaveChart', leaveOptions);

    // Expense Chart - Radial Bar
    const expenseOptions = {
        series: [40, 25, 20, 15],
        chart: { type: 'radialBar', height: 200 },
        plotOptions: {
            radialBar: {
                dataLabels: {
                    name: { fontSize: '12px' },
                    value: {
                        fontSize: '14px',
                        formatter: (val) => val + '%'
                    },
                    total: {
                        show: true,
                        label: 'Total',
                        formatter: () => '100%'
                    }
                }
            }
        },
        labels: ['Travel', 'Food', 'Supplies', 'Other'],
        colors: ['#1e40af', '#059669', '#d97706', '#dc2626']
    };
    createChart('#expenseChart', expenseOptions);
});
</script>

<style>
.expense-legends {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
}
.legend-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
}
.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
}
.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}
.legend-label {
    font-weight: 600;
    color: #374151;
    min-width: 80px;
}
.legend-desc {
    color: #6b7280;
    font-size: 0.8rem;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

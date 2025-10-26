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
            <canvas id="attendanceChart" height="200"></canvas>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>✅</span> Task Completion Report
            </h2>
        </div>
        <div class="card__body">
            <canvas id="taskChart" height="200"></canvas>
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
            <canvas id="leaveChart" height="200"></canvas>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>💰</span> Expense Summary
            </h2>
        </div>
        <div class="card__body">
            <canvas id="expenseChart" height="200"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Attendance Chart
    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(attendanceCtx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Present',
                data: [8, 7, 9, 8, 6, 3, 2],
                borderColor: '#1e40af',
                backgroundColor: 'rgba(30, 64, 175, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true
                }
            }
        }
    });

    // Task Chart
    const taskCtx = document.getElementById('taskChart').getContext('2d');
    new Chart(taskCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'In Progress', 'Pending'],
            datasets: [{
                data: [65, 25, 10],
                backgroundColor: ['#059669', '#d97706', '#dc2626']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Leave Chart
    const leaveCtx = document.getElementById('leaveChart').getContext('2d');
    new Chart(leaveCtx, {
        type: 'bar',
        data: {
            labels: ['Casual', 'Sick', 'Annual', 'Emergency'],
            datasets: [{
                label: 'Leave Requests',
                data: [12, 8, 15, 3],
                backgroundColor: '#1e40af'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Expense Chart
    const expenseCtx = document.getElementById('expenseChart').getContext('2d');
    new Chart(expenseCtx, {
        type: 'pie',
        data: {
            labels: ['Travel', 'Food', 'Office Supplies', 'Other'],
            datasets: [{
                data: [40, 25, 20, 15],
                backgroundColor: ['#1e40af', '#059669', '#d97706', '#dc2626']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

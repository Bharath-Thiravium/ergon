<?php
$title = 'User Dashboard';
$active_page = 'dashboard';

ob_start();
?>

            
            <!-- Header Actions -->
            <div class="header-actions">
                <button class="btn btn--primary" onclick="clockIn()">
                    <span>▶️</span> <span class="btn-text">Clock In</span>
                </button>
                <button class="btn btn--secondary" onclick="clockOut()">
                    <span>⏹️</span> <span class="btn-text">Clock Out</span>
                </button>
            </div>
            
            <!-- KPI Dashboard Grid -->
            <div class="dashboard-grid">
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <div class="kpi-card__icon">👤</div>
                        <div class="kpi-card__trend">Active</div>
                    </div>
                    <div class="kpi-card__value"><?= $stats['today_status'] ?? 'Not Clocked In' ?></div>
                    <div class="kpi-card__label">Today's Status</div>
                    <div class="kpi-card__status">Current</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <div class="kpi-card__icon">📋</div>
                        <div class="kpi-card__trend">+2</div>
                    </div>
                    <div class="kpi-card__value"><?= $stats['active_tasks'] ?? 0 ?></div>
                    <div class="kpi-card__label">Active Tasks</div>
                    <div class="kpi-card__status">In Progress</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <div class="kpi-card__icon">⏰</div>
                        <div class="kpi-card__trend">0</div>
                    </div>
                    <div class="kpi-card__value">0</div>
                    <div class="kpi-card__label">Pending Requests</div>
                    <div class="kpi-card__status">None</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <div class="kpi-card__icon">📅</div>
                        <div class="kpi-card__trend">95%</div>
                    </div>
                    <div class="kpi-card__value">22</div>
                    <div class="kpi-card__label">Days This Month</div>
                    <div class="kpi-card__status">Present</div>
                </div>
            </div>
            
            <!-- Content Cards -->
            <div class="dashboard-grid">
                <!-- Performance Chart -->
                <div class="card card-standard">
                    <div class="card__header">
                        <h3 class="card__title">
                            <span>📈</span> My Performance
                        </h3>
                    </div>
                    <div class="card__body">
                        <canvas id="userChart"></canvas>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card card-standard">
                    <div class="card__header">
                        <h3 class="card__title">
                            <span>⚡</span> Quick Actions
                        </h3>
                    </div>
                    <div class="card__body">
                        <div class="quick-actions-grid">
                            <button class="btn btn--primary btn--block" onclick="clockIn()">
                                <span>▶️</span> Clock In
                            </button>
                            <button class="btn btn--secondary btn--block" onclick="clockOut()">
                                <span>⏹️</span> Clock Out
                            </button>
                            <a href="/ergon/leaves/create" class="btn btn--secondary btn--block">
                                <span>📅</span> Request Leave
                            </a>
                            <a href="/ergon/expenses/create" class="btn btn--secondary btn--block">
                                <span>💰</span> Submit Expense
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Tasks -->
            <div class="card recent-activities">
                <div class="card__header">
                    <h3 class="card__title">
                        <span>⚡</span> Recent Activities
                    </h3>
                </div>
                <div class="card__body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Priority</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Complete project documentation</td>
                                    <td><span class="alert alert--warning alert--badge">High</span></td>
                                    <td>Today</td>
                                    <td><span class="alert alert--info alert--badge">In Progress</span></td>
                                    <td>75%</td>
                                </tr>
                                <tr>
                                    <td>Review client feedback</td>
                                    <td><span class="alert alert--success alert--badge">Medium</span></td>
                                    <td>Tomorrow</td>
                                    <td><span class="alert alert--warning alert--badge">Pending</span></td>
                                    <td>0%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Performance Chart
const ctx = document.getElementById('userChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        datasets: [{
            label: 'Tasks Completed',
            data: [2, 4, 3, 5, 2],
            borderColor: '#1e40af',
            backgroundColor: 'rgba(30, 64, 175, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.1)' } },
            x: { grid: { color: 'rgba(0,0,0,0.1)' } }
        }
    }
});

function clockIn() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            fetch('/ergon/attendance/clock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `type=in&latitude=${position.coords.latitude}&longitude=${position.coords.longitude}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.success ? 'Clocked in successfully!' : data.error);
                if (data.success) location.reload();
            });
        });
    }
}

function clockOut() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            fetch('/ergon/attendance/clock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `type=out&latitude=${position.coords.latitude}&longitude=${position.coords.longitude}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.success ? 'Clocked out successfully!' : data.error);
                if (data.success) location.reload();
            });
        });
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

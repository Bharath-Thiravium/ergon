<?php
$title = 'User Dashboard';
$active_page = 'dashboard';

ob_start();
?>

            
            <!-- Header Actions -->
            <div class="header-actions">
                <button class="btn btn--primary" onclick="clockIn()">
                    <i class="fas fa-play"></i>
                    Clock In
                </button>
                <button class="btn btn--secondary" onclick="clockOut()">
                    <i class="fas fa-stop"></i>
                    Clock Out
                </button>
            </div>
            
            <!-- KPI Dashboard Grid -->
            <div class="dashboard-grid">
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-user-check text-success"></i>
                        <span class="kpi-card__trend kpi-card__trend--up">Active</span>
                    </div>
                    <div class="kpi-card__value"><?= $stats['today_status'] ?? 'Not Clocked In' ?></div>
                    <div class="kpi-card__label">Today's Status</div>
                    <div class="kpi-card__status kpi-card__status--active">Current</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-tasks text-primary"></i>
                        <span class="kpi-card__trend kpi-card__trend--up">+2</span>
                    </div>
                    <div class="kpi-card__value"><?= $stats['active_tasks'] ?? 0 ?></div>
                    <div class="kpi-card__label">Active Tasks</div>
                    <div class="kpi-card__status kpi-card__status--active">In Progress</div>
                </div>
                
                <div class="kpi-card kpi-card--warning">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-clock text-warning"></i>
                        <span class="kpi-card__trend kpi-card__trend--down">0</span>
                    </div>
                    <div class="kpi-card__value">0</div>
                    <div class="kpi-card__label">Pending Requests</div>
                    <div class="kpi-card__status kpi-card__status--pending">None</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-calendar-check text-info"></i>
                        <span class="kpi-card__trend kpi-card__trend--up">95%</span>
                    </div>
                    <div class="kpi-card__value">22</div>
                    <div class="kpi-card__label">Days This Month</div>
                    <div class="kpi-card__status kpi-card__status--active">Present</div>
                </div>
            </div>
            
            <!-- Content Cards -->
            <div class="dashboard-grid">
                <!-- Performance Chart -->
                <div class="card">
                    <div class="card__header">
                        <h3 class="card__title">
                            <i class="fas fa-chart-line"></i>
                            My Performance
                        </h3>
                    </div>
                    <div class="card__body">
                        <canvas id="userChart" style="height: 300px;"></canvas>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card__header">
                        <h3 class="card__title">
                            <i class="fas fa-bolt"></i>
                            Quick Actions
                        </h3>
                    </div>
                    <div class="card__body">
                        <div class="card__body">
                            <button class="btn btn--primary" onclick="clockIn()">
                                <i class="fas fa-play"></i>
                                Clock In
                            </button>
                            <button class="btn btn--secondary" onclick="clockOut()">
                                <i class="fas fa-stop"></i>
                                Clock Out
                            </button>
                            <a href="/ergon/leaves/create" class="btn btn--secondary">
                                <i class="fas fa-calendar-alt"></i>
                                Request Leave
                            </a>
                            <a href="/ergon/expenses/create" class="btn btn--secondary">
                                <i class="fas fa-receipt"></i>
                                Submit Expense
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Tasks -->
            <div class="card">
                <div class="card__header">
                    <h3 class="card__title">
                        <i class="fas fa-tasks"></i>
                        Recent Tasks
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
                                    <td><span class="alert alert--warning" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">High</span></td>
                                    <td>Today</td>
                                    <td><span class="alert alert--info" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">In Progress</span></td>
                                    <td>75%</td>
                                </tr>
                                <tr>
                                    <td>Review client feedback</td>
                                    <td><span class="alert alert--success" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Medium</span></td>
                                    <td>Tomorrow</td>
                                    <td><span class="alert alert--warning" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Pending</span></td>
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

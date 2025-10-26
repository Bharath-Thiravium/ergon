<?php
$title = 'Admin Dashboard';
$active_page = 'dashboard';

ob_start();
?>

            
            <!-- Header Actions -->
            <div class="header-actions">
                <button class="btn btn--primary">
                    <i class="fas fa-plus"></i>
                    Create Task
                </button>
                <button class="btn btn--secondary">
                    <i class="fas fa-download"></i>
                    Export Report
                </button>
            </div>
            
            <!-- KPI Dashboard Grid -->
            <div class="dashboard-grid">
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-tasks text-primary"></i>
                        <span class="kpi-card__trend kpi-card__trend--up">+3</span>
                    </div>
                    <div class="kpi-card__value"><?= $stats['my_tasks'] ?? 0 ?></div>
                    <div class="kpi-card__label">My Tasks</div>
                    <div class="kpi-card__status kpi-card__status--active">Active</div>
                </div>
                
                <div class="kpi-card kpi-card--warning">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-clock text-warning"></i>
                        <span class="kpi-card__trend kpi-card__trend--down">-2</span>
                    </div>
                    <div class="kpi-card__value">8</div>
                    <div class="kpi-card__label">Pending Approvals</div>
                    <div class="kpi-card__status kpi-card__status--pending">Review Required</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-users text-info"></i>
                        <span class="kpi-card__trend kpi-card__trend--up">+1</span>
                    </div>
                    <div class="kpi-card__value"><?= $stats['team_members'] ?? 0 ?></div>
                    <div class="kpi-card__label">Team Members</div>
                    <div class="kpi-card__status kpi-card__status--active">Active</div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-card__header">
                        <i class="kpi-card__icon fas fa-chart-line text-success"></i>
                        <span class="kpi-card__trend kpi-card__trend--up">+12%</span>
                    </div>
                    <div class="kpi-card__value">94%</div>
                    <div class="kpi-card__label">Team Performance</div>
                    <div class="kpi-card__status kpi-card__status--active">Excellent</div>
                </div>
            </div>
            
            <!-- Content Cards -->
            <div class="dashboard-grid">
                <!-- Team Performance Chart -->
                <div class="card">
                    <div class="card__header">
                        <h3 class="card__title">
                            <i class="fas fa-chart-bar"></i>
                            Team Performance
                        </h3>
                    </div>
                    <div class="card__body">
                        <canvas id="teamChart" style="height: 300px;"></canvas>
                    </div>
                </div>
                
                <!-- Recent Activities -->
                <div class="card">
                    <div class="card__header">
                        <h3 class="card__title">
                            <i class="fas fa-bell"></i>
                            Recent Activities
                        </h3>
                    </div>
                    <div class="card__body card__body--scrollable">
                        <div style="margin-top: 1rem;">
                            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">New leave request submitted</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">John Doe - 1 hour ago</div>
                            </div>
                            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">Task completed</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">Jane Smith - 2 hours ago</div>
                            </div>
                            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">Expense claim submitted</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">Mike Johnson - 3 hours ago</div>
                            </div>
                            <div style="padding: 0.75rem 0;">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">New team member added</div>
                                <div style="font-size: 0.75rem; color: var(--text-secondary);">Sarah Wilson - 4 hours ago</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Approvals Table -->
            <div class="card">
                <div class="card__header">
                    <h3 class="card__title">
                        <i class="fas fa-clock"></i>
                        Pending Approvals
                    </h3>
                </div>
                <div class="card__body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Employee</th>
                                    <th>Request</th>
                                    <th>Date</th>
                                    <th>Priority</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="fas fa-calendar-alt text-warning"></i> Leave</td>
                                    <td>John Doe</td>
                                    <td>Annual Leave - 3 days</td>
                                    <td>Dec 15-17, 2024</td>
                                    <td><span class="alert alert--warning" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">High</span></td>
                                    <td>
                                        <button class="btn btn--primary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Approve</button>
                                        <button class="btn btn--secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Reject</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-receipt text-info"></i> Expense</td>
                                    <td>Jane Smith</td>
                                    <td>Travel Expense - $250</td>
                                    <td>Dec 10, 2024</td>
                                    <td><span class="alert alert--success" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Medium</span></td>
                                    <td>
                                        <button class="btn btn--primary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Approve</button>
                                        <button class="btn btn--secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Reject</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Team Performance Chart
const ctx = document.getElementById('teamChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Tasks', 'Attendance', 'Leaves', 'Expenses'],
        datasets: [{
            label: 'Pending Items',
            data: [5, 2, 3, 1],
            backgroundColor: ['#1e40af', '#059669', '#d97706', '#dc2626']
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
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

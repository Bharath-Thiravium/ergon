<?php
$title = 'Analytics Dashboard';
$active_page = 'analytics';
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2>📊 Advanced Analytics Dashboard</h2>
        </div>
    </div>
    
    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="total-tasks">0</h4>
                            <p>Total Tasks</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-tasks fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="completion-rate">0%</h4>
                            <p>Completion Rate</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="avg-productivity">0</h4>
                            <p>Avg Productivity</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="sla-breaches">0</h4>
                            <p>SLA Breaches</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>📈 Task Completion Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="completionTrendChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>⚡ Productivity Heatmap</h5>
                </div>
                <div class="card-body">
                    <canvas id="productivityHeatmap" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Team Performance -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>👥 Team Performance Matrix</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="team-performance-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Tasks Completed</th>
                                    <th>Productivity Score</th>
                                    <th>On-Time Rate</th>
                                    <th>SLA Breaches</th>
                                    <th>Trend</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>🏆 Top Performers</h5>
                </div>
                <div class="card-body">
                    <div id="leaderboard"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadAnalyticsData();
    initializeCharts();
});

function loadAnalyticsData() {
    // Mock data for demonstration
    const mockData = {
        kpis: {
            total_tasks: 156,
            completion_rate: 78.5,
            avg_productivity: 82.3,
            sla_breaches: 12
        },
        charts: {
            completion_trend: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                data: [12, 19, 15, 25, 22, 18, 24]
            },
            productivity_scores: {
                labels: ['John', 'Jane', 'Mike', 'Sarah', 'Tom'],
                data: [85, 92, 78, 88, 76]
            }
        },
        team: [
            {name: 'John Doe', completed_tasks: 25, productivity: 85.2, on_time_rate: 92, sla_breaches: 1, trend: 1},
            {name: 'Jane Smith', completed_tasks: 28, productivity: 92.1, on_time_rate: 96, sla_breaches: 0, trend: 1},
            {name: 'Mike Johnson', completed_tasks: 22, productivity: 78.5, on_time_rate: 88, sla_breaches: 3, trend: -1}
        ],
        leaderboard: [
            {name: 'Jane Smith', total_points: 1250, tasks_completed: 28},
            {name: 'John Doe', total_points: 1180, tasks_completed: 25},
            {name: 'Mike Johnson', total_points: 980, tasks_completed: 22}
        ]
    };
    
    updateKPIs(mockData.kpis);
    updateCharts(mockData.charts);
    updateTeamPerformance(mockData.team);
    updateLeaderboard(mockData.leaderboard);
}

function updateKPIs(kpis) {
    document.getElementById('total-tasks').textContent = kpis.total_tasks || 0;
    document.getElementById('completion-rate').textContent = (kpis.completion_rate || 0) + '%';
    document.getElementById('avg-productivity').textContent = (kpis.avg_productivity || 0).toFixed(1);
    document.getElementById('sla-breaches').textContent = kpis.sla_breaches || 0;
}

let completionChart, productivityChart;

function initializeCharts() {
    const completionCtx = document.getElementById('completionTrendChart').getContext('2d');
    completionChart = new Chart(completionCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Tasks Completed',
                data: [],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    const productivityCtx = document.getElementById('productivityHeatmap').getContext('2d');
    productivityChart = new Chart(productivityCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Productivity Score',
                data: [],
                backgroundColor: '#ffc107'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
}

function updateCharts(chartData) {
    if (chartData.completion_trend) {
        completionChart.data.labels = chartData.completion_trend.labels;
        completionChart.data.datasets[0].data = chartData.completion_trend.data;
        completionChart.update();
    }
    
    if (chartData.productivity_scores) {
        productivityChart.data.labels = chartData.productivity_scores.labels;
        productivityChart.data.datasets[0].data = chartData.productivity_scores.data;
        productivityChart.update();
    }
}

function updateTeamPerformance(teamData) {
    const tbody = document.querySelector('#team-performance-table tbody');
    tbody.innerHTML = '';
    
    teamData.forEach(member => {
        const row = document.createElement('tr');
        const trendIcon = member.trend > 0 ? '📈' : member.trend < 0 ? '📉' : '➡️';
        
        row.innerHTML = `
            <td>${member.name}</td>
            <td>${member.completed_tasks}</td>
            <td>
                <div class="progress" style="width: 60px; height: 8px; display: inline-block;">
                    <div class="progress-bar bg-${member.productivity >= 80 ? 'success' : member.productivity >= 60 ? 'warning' : 'danger'}" 
                         style="width: ${member.productivity}%"></div>
                </div>
                ${member.productivity.toFixed(1)}
            </td>
            <td>${member.on_time_rate}%</td>
            <td>
                <span class="badge badge-${member.sla_breaches > 0 ? 'danger' : 'success'}">
                    ${member.sla_breaches}
                </span>
            </td>
            <td>${trendIcon}</td>
        `;
        
        tbody.appendChild(row);
    });
}

function updateLeaderboard(leaderboardData) {
    const container = document.getElementById('leaderboard');
    container.innerHTML = '';
    
    leaderboardData.forEach((user, index) => {
        const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : `#${index + 1}`;
        
        const item = document.createElement('div');
        item.className = 'mb-3 p-2 border rounded';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="me-2">${medal}</span>
                    <strong>${user.name}</strong>
                </div>
                <div class="text-end">
                    <div class="text-primary font-weight-bold">${user.total_points} pts</div>
                    <small class="text-muted">${user.tasks_completed} tasks</small>
                </div>
            </div>
        `;
        
        container.appendChild(item);
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
<?php
$title = 'Enhanced Attendance Dashboard';
$active_page = 'attendance';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📊</span> Enhanced Attendance Dashboard</h1>
        <p>Real-time GPS-based attendance tracking with analytics</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/attendance/clock" class="btn btn--primary">
            <span>🕰️</span> Clock In/Out
        </a>
        <a href="/ergon/attendance" class="btn btn--secondary">
            <span>📍</span> Standard View
        </a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend">↗ Live</div>
        </div>
        <div class="kpi-card__value" id="totalEmployees">-</div>
        <div class="kpi-card__label">Total Employees</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ Today</div>
        </div>
        <div class="kpi-card__value" id="presentToday">-</div>
        <div class="kpi-card__label">Present Today</div>
        <div class="kpi-card__status">Checked In</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🕰️</div>
            <div class="kpi-card__trend">↗ Avg</div>
        </div>
        <div class="kpi-card__value" id="avgHours">-</div>
        <div class="kpi-card__label">Avg Hours</div>
        <div class="kpi-card__status">Daily</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📍</div>
            <div class="kpi-card__trend">↗ GPS</div>
        </div>
        <div class="kpi-card__value" id="gpsAccuracy">-</div>
        <div class="kpi-card__label">GPS Accuracy</div>
        <div class="kpi-card__status">Meters</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📊</span> Real-time Attendance
        </h2>
        <div class="card__actions">
            <button class="btn btn--sm btn--secondary" onclick="refreshData()">
                <span>🔄</span> Refresh
            </button>
        </div>
    </div>
    <div class="card__body">
        <div id="attendanceTable">
            <div class="text-center py-4">
                <div class="spinner"></div>
                <p>Loading attendance data...</p>
            </div>
        </div>
    </div>
</div>

<script>
function loadDashboardData() {
    // Simulate loading enhanced data
    document.getElementById('totalEmployees').textContent = '<?= $_SESSION["role"] === "user" ? "1" : "12" ?>';
    document.getElementById('presentToday').textContent = '<?= $_SESSION["role"] === "user" ? "1" : "8" ?>';
    document.getElementById('avgHours').textContent = '7.5h';
    document.getElementById('gpsAccuracy').textContent = '±15m';
    
    // Load attendance table
    loadAttendanceTable();
}

function loadAttendanceTable() {
    fetch('/ergon/attendance')
        .then(response => response.text())
        .then(html => {
            // Extract table content from response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const table = doc.querySelector('.table');
            
            if (table) {
                document.getElementById('attendanceTable').innerHTML = '<div class="table-responsive">' + table.outerHTML + '</div>';
            } else {
                document.getElementById('attendanceTable').innerHTML = '<p class="text-center text-muted">No attendance data available</p>';
            }
        })
        .catch(error => {
            console.error('Error loading attendance data:', error);
            document.getElementById('attendanceTable').innerHTML = '<p class="text-center text-danger">Error loading data</p>';
        });
}

function refreshData() {
    loadDashboardData();
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    
    // Auto-refresh every 30 seconds
    setInterval(refreshData, 30000);
});
</script>

<style>
.spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #007bff;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
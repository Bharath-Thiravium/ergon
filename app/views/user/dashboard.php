<?php
// Authentication guard
require_once __DIR__ . '/../../guards/auth_guard.php';

$title = 'My Dashboard';
$active_page = 'dashboard';
ob_start();
?>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
<div class="header-actions" style="margin-bottom: var(--space-6);">
    <button id="clockBtn" class="btn btn--success">📍 Clock In</button>
    <a href="/ergon/user/requests" class="btn btn--primary">View Requests</a>
</div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="kpi-card <?= $data['attendance_status'] ? 'kpi-card--success' : 'kpi-card--warning' ?>">
        <div class="kpi-card__header">
            <div class="kpi-card__icon"><?= $data['attendance_status'] ? '🟢' : '🔴' ?></div>
            <div class="kpi-card__trend">Today</div>
        </div>
        <div class="kpi-card__value"><?= $data['attendance_status'] ? 'IN' : 'OUT' ?></div>
        <div class="kpi-card__label">Attendance Status</div>
        <div class="kpi-card__status"><?= $data['attendance_status'] ? 'Clocked In' : 'Not Clocked In' ?></div>
    </div>
    
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +12%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['active_tasks'] ?></div>
        <div class="kpi-card__label">Active Tasks</div>
        <div class="kpi-card__status kpi-card__status--active">In Progress</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏖️</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending_leaves'] ?></div>
        <div class="kpi-card__label">Pending Leaves</div>
        <div class="kpi-card__status kpi-card__status--pending">Awaiting Approval</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💰</div>
            <div class="kpi-card__trend kpi-card__trend--down">↘ -8%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending_expenses'] ?></div>
        <div class="kpi-card__label">Pending Expenses</div>
        <div class="kpi-card__status kpi-card__status--review">Under Review</div>
    </div>
</div>

<div class="reports-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">My Tasks</h2>
        </div>
        <div class="card__body">
            <?php if (empty($data['tasks'])): ?>
            <p>No active tasks assigned.</p>
            <?php else: ?>
            <?php foreach ($data['tasks'] as $task): ?>
            <div class="timeline-item">
                <div class="timeline-date"><?= $task['deadline'] ? date('M d', strtotime($task['deadline'])) : 'No deadline' ?></div>
                <div class="timeline-content">
                    <h4><?= htmlspecialchars($task['title']) ?></h4>
                    <p><?= htmlspecialchars($task['description']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Company Updates</h2>
        </div>
        <div class="card__body">
            <?php if (empty($data['circulars'])): ?>
            <p>No recent updates.</p>
            <?php else: ?>
            <?php foreach ($data['circulars'] as $circular): ?>
            <div class="timeline-item">
                <div class="timeline-date"><?= date('M d', strtotime($circular['created_at'])) ?></div>
                <div class="timeline-content">
                    <h4><?= htmlspecialchars($circular['title']) ?></h4>
                    <p><?= htmlspecialchars($circular['message']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let userLocation = null;
let isCheckedIn = <?= json_encode($data['attendance_status'] ?? false) ?>;

// Get location
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(pos => {
        userLocation = { latitude: pos.coords.latitude, longitude: pos.coords.longitude };
    });
}

// Update button state
function updateClockButton() {
    const btn = document.getElementById('clockBtn');
    if (isCheckedIn) {
        btn.textContent = '🔴 Clock Out';
        btn.className = 'btn btn--danger';
    } else {
        btn.textContent = '📍 Clock In';
        btn.className = 'btn btn--success';
    }
}

// Clock action
document.getElementById('clockBtn').onclick = function() {
    const action = isCheckedIn ? 'clock_out' : 'clock_in';
    const btn = this;
    btn.disabled = true;
    btn.textContent = isCheckedIn ? 'Clocking out...' : 'Clocking in...';
    
    const requestData = {
        action,
        latitude: userLocation?.latitude || 0,
        longitude: userLocation?.longitude || 0,
        location_name: 'Office'
    };
    
    fetch('/ergon/api_attendance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned HTML instead of JSON');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            isCheckedIn = !isCheckedIn;
            updateClockButton();
            updateAttendanceCard();
            showAlert(data.message || 'Success', 'success');
        } else {
            showAlert(data.message || 'Operation failed', 'error');
        }
        btn.disabled = false;
    })
    .catch(error => {
        console.error('Clock error:', error);
        showAlert('Connection failed. Please try again.', 'error');
        btn.disabled = false;
        updateClockButton();
    });
};

updateClockButton();
function updateAttendanceCard() {
    const card = document.querySelector('.kpi-card');
    const icon = card.querySelector('.kpi-card__icon');
    const value = card.querySelector('.kpi-card__value');
    const status = card.querySelector('.kpi-card__status');
    
    if (isCheckedIn) {
        card.className = 'kpi-card kpi-card--success';
        icon.textContent = '🟢';
        value.textContent = 'IN';
        status.textContent = 'Clocked In';
    } else {
        card.className = 'kpi-card kpi-card--warning';
        icon.textContent = '🔴';
        value.textContent = 'OUT';
        status.textContent = 'Not Clocked In';
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
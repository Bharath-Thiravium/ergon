<?php
$title = 'My Dashboard';
$active_page = 'dashboard';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🏠</span> Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>!</h1>
        <p>Here's what's happening with your work today</p>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ Active</div>
        </div>
        <div class="kpi-card__value"><?= $stats['my_tasks']['total'] ?? 0 ?></div>
        <div class="kpi-card__label">My Tasks</div>
        <div class="kpi-card__status"><?= $stats['my_tasks']['pending'] ?? 0 ?> pending</div>
    </div>

    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend">This Month</div>
        </div>
        <div class="kpi-card__value"><?= $stats['attendance_this_month'] ?? 0 ?></div>
        <div class="kpi-card__label">Attendance</div>
        <div class="kpi-card__status">Days Present</div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard_clean.php';
?>
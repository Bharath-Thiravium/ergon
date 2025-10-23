<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mock session for testing
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['role'] = 'owner';

$title = 'Sidebar Test';
$active_page = 'dashboard';

ob_start();
?>

<div class="page-header">
    <h1>🧪 Sidebar Navigation Test</h1>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Navigation Links Test</h2>
    </div>
    <div class="card__body">
        <p>Testing all sidebar navigation links:</p>
        <ul>
            <li><a href="/ergon/dashboard">Dashboard</a></li>
            <li><a href="/ergon/users">Users</a></li>
            <li><a href="/ergon/tasks">Tasks</a></li>
            <li><a href="/ergon/planner/calendar">Daily Planner</a></li>
            <li><a href="/ergon/reports">Reports</a></li>
            <li><a href="/ergon/reports/activity">Activity Reports</a></li>
            <li><a href="/ergon/settings">Settings</a></li>
        </ul>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/app/views/layouts/dashboard.php';
?>
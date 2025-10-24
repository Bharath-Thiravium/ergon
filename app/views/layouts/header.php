<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /ergon/login');
    exit;
}

$active_page = $active_page ?? '';
$title = $title ?? 'ERGON';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - ERGON</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #f8f9fa; }
        .progress { height: 20px; }
        .notification-badge { background: #dc3545; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar p-3">
                <h5>🧭 ERGON</h5>
                <hr>
                <nav class="nav flex-column">
                    <a class="nav-link" href="/ergon/dashboard">📊 Dashboard</a>
                    <a class="nav-link" href="/ergon/daily-planner">📝 Daily Planner</a>
                    <?php if ($_SESSION['role'] !== 'user'): ?>
                    <a class="nav-link" href="/ergon/daily-planner/dashboard">📊 Task Dashboard</a>
                    <?php endif; ?>
                    <a class="nav-link" href="/ergon/tasks">✅ Tasks</a>
                    <a class="nav-link" href="/ergon/attendance">📍 Attendance</a>
                    <hr>
                    <a class="nav-link text-danger" href="/ergon/logout">🚪 Logout</a>
                </nav>
            </div>
            <div class="col-md-10">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
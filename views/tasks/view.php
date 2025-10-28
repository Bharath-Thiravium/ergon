<?php
$title = 'Task Details';
$active_page = 'tasks';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>✅</span> Task Details</h1>
        <p>View task information and progress</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/tasks" class="btn btn--secondary">
            <span>←</span> Back to Tasks
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>✅</span> <?= htmlspecialchars($task['title'] ?? 'Task') ?>
        </h2>
    </div>
    <div class="card__body">
        <div class="detail-grid">
            <div class="detail-item">
                <label>Title</label>
                <span><?= htmlspecialchars($task['title'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Description</label>
                <span><?= htmlspecialchars($task['description'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-item">
                <label>Assigned To</label>
                <span><?= htmlspecialchars($task['assigned_user'] ?? 'Unassigned') ?></span>
            </div>
            <div class="detail-item">
                <label>Priority</label>
                <span class="badge badge--warning"><?= ucfirst($task['priority'] ?? 'medium') ?></span>
            </div>
            <div class="detail-item">
                <label>Status</label>
                <span class="badge badge--success"><?= ucfirst($task['status'] ?? 'pending') ?></span>
            </div>
            <div class="detail-item">
                <label>Due Date</label>
                <span><?= $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'No due date' ?></span>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
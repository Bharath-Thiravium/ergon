<?php
$title = 'Tasks';
$active_page = 'tasks';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>✅</span> Task Management</h1>
        <p>Manage and track all project tasks and assignments</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/tasks/create" class="btn btn--primary">
            <span>➕</span> Create Task
        </a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ +12%</div>
        </div>
        <div class="kpi-card__value"><?= count($tasks) ?></div>
        <div class="kpi-card__label">Total Tasks</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚙️</div>
            <div class="kpi-card__trend">↗ +8%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress')) ?></div>
        <div class="kpi-card__label">In Progress</div>
        <div class="kpi-card__status">Working</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
            <div class="kpi-card__trend kpi-card__trend--down">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($tasks, fn($t) => $t['priority'] === 'high')) ?></div>
        <div class="kpi-card__label">High Priority</div>
        <div class="kpi-card__status kpi-card__status--pending">Urgent</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>✅</span> Tasks
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Assigned To</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks ?? [] as $task): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['title']) ?></td>
                        <td><?= htmlspecialchars($task['assigned_user'] ?? 'Unassigned') ?></td>
                        <td><span class="badge badge--warning"><?= ucfirst($task['priority']) ?></span></td>
                        <td><span class="badge badge--success"><?= ucfirst($task['status']) ?></span></td>
                        <td><?= $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'No due date' ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="/ergon/tasks/view/<?= $task['id'] ?>" class="btn btn--sm btn--primary" title="View Details">
                                    <span>👁️</span> View
                                </a>
                                <button onclick="deleteRecord('tasks', <?= $task['id'] ?>, '<?= htmlspecialchars($task['title']) ?>')" class="btn btn--sm btn--danger" title="Delete Task">
                                    <span>🗑️</span> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

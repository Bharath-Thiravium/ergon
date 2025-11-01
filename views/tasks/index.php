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
                        <th>Description</th>
                        <th>Assigned To</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks ?? [] as $task): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($task['title']) ?></strong></td>
                        <td><?= htmlspecialchars(substr($task['description'] ?? '', 0, 50)) ?><?= strlen($task['description'] ?? '') > 50 ? '...' : '' ?></td>
                        <td><?= htmlspecialchars($task['assigned_user'] ?? 'Unassigned') ?></td>
                        <td><span class="badge badge--<?= $task['priority'] === 'high' ? 'danger' : ($task['priority'] === 'medium' ? 'warning' : 'info') ?>"><?= ucfirst($task['priority']) ?></span></td>
                        <td><span class="badge badge--<?= $task['status'] === 'completed' ? 'success' : ($task['status'] === 'in_progress' ? 'info' : 'secondary') ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span></td>
                        <td><?= ($task['deadline'] ?? $task['due_date']) ? date('M d, Y', strtotime($task['deadline'] ?? $task['due_date'])) : 'No due date' ?></td>
                        <td><?= $task['created_at'] ? date('M d, Y', strtotime($task['created_at'])) : '' ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="/ergon/tasks/view/<?= $task['id'] ?>" class="btn btn--sm btn--primary" title="View Details">
                                    <span>👁️</span> View
                                </a>
                                <a href="/ergon/tasks/edit/<?= $task['id'] ?>" class="btn btn--sm btn--secondary" title="Edit Task">
                                    <span>✏️</span> Edit
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

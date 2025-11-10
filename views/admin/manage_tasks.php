<?php
$title = 'Manage Tasks';
$active_page = 'tasks';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="bi bi-list-task"></i> Manage Tasks</h1>
        <p>View and manage all tasks</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/admin/create-task" class="btn btn--primary">
            <i class="bi bi-plus-circle"></i> Create Task
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title"><i class="bi bi-table"></i> Tasks List</h2>
    </div>
    <div class="card__body">
        <?php if (!empty($tasks)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Assigned To</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['title']) ?></td>
                            <td><?= htmlspecialchars($task['assigned_to_name'] ?? 'Unassigned') ?></td>
                            <td>
                                <span class="badge badge--<?= $task['priority'] ?>">
                                    <?= ucfirst($task['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge--<?= $task['status'] ?>">
                                    <?= ucfirst($task['status']) ?>
                                </span>
                            </td>
                            <td><?= $task['progress'] ?? 0 ?>%</td>
                            <td><?= $task['deadline'] ? date('M j, Y', strtotime($task['deadline'])) : 'No deadline' ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="/ergon/tasks/view/<?= $task['id'] ?>" class="btn btn--sm btn--secondary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h3>No Tasks Found</h3>
                <p>There are no tasks to display.</p>
                <a href="/ergon/admin/create-task" class="btn btn--primary">Create First Task</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
<?php
$title = 'Project Tasks Overview';
$active_page = 'dashboard';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📋</span> Project Tasks Overview</h1>
        <p>View all tasks organized by project</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/dashboard" class="btn btn--secondary">
            <span>←</span> Back to Dashboard
        </a>
    </div>
</div>

<?php if (empty($projects)): ?>
<div class="card">
    <div class="card__body">
        <div class="empty-state">
            <div class="empty-icon">📁</div>
            <h3>No Projects Found</h3>
            <p>No active projects with tasks available.</p>
            <a href="/ergon/project-management" class="btn btn--primary">Create Project</a>
        </div>
    </div>
</div>
<?php else: ?>

<?php foreach ($projects as $project): ?>
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📁</span> <?= htmlspecialchars($project['name']) ?>
        </h2>
        <?php if ($project['department']): ?>
        <span class="badge badge--info"><?= htmlspecialchars($project['department']) ?></span>
        <?php endif; ?>
    </div>
    <div class="card__body">
        <?php if ($project['description']): ?>
        <p class="project-description"><?= htmlspecialchars($project['description']) ?></p>
        <?php endif; ?>
        
        <?php if (empty($project['tasks'])): ?>
        <div class="empty-state-small">
            <p>No tasks assigned to this project yet.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Assigned To</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($project['tasks'] as $task): ?>
                    <tr>
                        <td>
                            <div class="task-info">
                                <strong><?= htmlspecialchars($task['title']) ?></strong>
                                <?php if ($task['description']): ?>
                                <div class="task-description"><?= htmlspecialchars(substr($task['description'], 0, 100)) ?><?= strlen($task['description']) > 100 ? '...' : '' ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php
                            $statusClass = [
                                'completed' => 'success',
                                'in_progress' => 'info', 
                                'assigned' => 'warning',
                                'pending' => 'warning',
                                'not_started' => 'secondary'
                            ][$task['status']] ?? 'secondary';
                            ?>
                            <span class="badge badge--<?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $task['status'])) ?></span>
                        </td>
                        <td>
                            <?php
                            $priorityClass = [
                                'high' => 'danger',
                                'medium' => 'warning', 
                                'low' => 'info'
                            ][$task['priority']] ?? 'secondary';
                            ?>
                            <span class="badge badge--<?= $priorityClass ?>"><?= ucfirst($task['priority']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($task['assigned_user'] ?: 'Unassigned') ?></td>
                        <td>
                            <?php 
                            $dueDate = $task['due_date'] ?: $task['deadline'];
                            if ($dueDate): 
                                $isOverdue = strtotime($dueDate) < time() && $task['status'] !== 'completed';
                            ?>
                            <span class="<?= $isOverdue ? 'text-danger' : '' ?>">
                                <?= date('M j, Y', strtotime($dueDate)) ?>
                                <?php if ($isOverdue): ?>
                                <small>(Overdue)</small>
                                <?php endif; ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">No due date</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/ergon/tasks/view/<?= $task['id'] ?>" class="btn btn--sm btn--primary">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<style>
.project-description {
    color: var(--text-secondary);
    margin-bottom: 1rem;
    font-style: italic;
}

.task-info {
    max-width: 300px;
}

.task-description {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.empty-state-small {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}

.text-danger {
    color: #dc3545 !important;
}

.text-muted {
    color: #6c757d !important;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
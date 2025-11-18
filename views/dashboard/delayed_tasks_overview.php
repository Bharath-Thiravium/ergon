<?php
$title = 'Delayed Tasks Overview';
$active_page = 'dashboard';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>⏰</span> Delayed Tasks Overview</h1>
        <p>Tasks that are overdue and need immediate attention</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/dashboard" class="btn btn--secondary">
            <span>←</span> Back to Dashboard
        </a>
    </div>
</div>

<?php if (!empty($delayed_tasks)): ?>
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>⚠️</span> Delayed Tasks Overview
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Assigned To</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($delayed_tasks as $task): ?>
                    <tr>
                        <td>
                            <div class="task-info">
                                <strong><?= htmlspecialchars($task['title']) ?></strong>
                                <?php if (!empty($task['description'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars(substr($task['description'], 0, 100)) ?>...</small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="user-info">
                                <strong><?= htmlspecialchars($task['assigned_user'] ?? 'Unassigned') ?></strong>
                            </div>
                        </td>
                        <td>
                            <div class="date-info">
                                <?php 
                                $dueDate = $task['due_date'] ?: $task['deadline'] ?: null;
                                if ($dueDate): 
                                ?>
                                <strong class="text-danger"><?= date('M d, Y', strtotime($dueDate)) ?></strong>
                                <br><small><?= date('l', strtotime($dueDate)) ?></small>
                                <?php else: ?>
                                <strong class="text-muted">No due date</strong>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge--danger">
                                <?= $task['days_overdue'] ?> day<?= $task['days_overdue'] != 1 ? 's' : '' ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $priorityClass = match(strtolower($task['priority'] ?? 'medium')) {
                                'high' => 'badge--danger',
                                'medium' => 'badge--warning',
                                'low' => 'badge--info',
                                default => 'badge--secondary'
                            };
                            ?>
                            <span class="badge <?= $priorityClass ?>">
                                <?= ucfirst($task['priority'] ?? 'Medium') ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $statusClass = match(strtolower($task['status'] ?? 'pending')) {
                                'completed' => 'badge--success',
                                'in_progress' => 'badge--info',
                                'blocked' => 'badge--danger',
                                default => 'badge--warning'
                            };
                            ?>
                            <span class="badge <?= $statusClass ?>">
                                <?= ucfirst(str_replace('_', ' ', $task['status'] ?? 'Pending')) ?>
                            </span>
                        </td>
                        <td>
                            <div class="ab-container">
                                <a href="/ergon/tasks/view/<?= $task['id'] ?>" class="ab-btn ab-btn--view" title="View Task">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                <a href="/ergon/tasks/edit/<?= $task['id'] ?>" class="ab-btn ab-btn--edit" title="Edit Task">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                        <path d="M15 5l4 4"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<div class="empty-state">
    <div class="empty-state__icon">✅</div>
    <h3>No Delayed Tasks</h3>
    <p>Great! All tasks are on schedule or completed.</p>
    <a href="/ergon/tasks" class="btn btn--primary">View All Tasks</a>
</div>
<?php endif; ?>

<style>
.task-info strong {
    color: #1f2937;
    font-weight: 600;
}

.user-info strong {
    color: #374151;
}

.date-info strong {
    font-weight: 600;
}

.text-danger {
    color: #dc2626 !important;
}

.text-muted {
    color: #6b7280;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-top: 2rem;
}

.empty-state__icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: #374151;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 1.5rem;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
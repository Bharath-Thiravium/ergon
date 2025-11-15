<?php
$title = 'Tasks';
$active_page = 'tasks';
ob_start();
?>



<?php

// Error handling: Ensure $tasks is an array
if (!is_array($tasks)) {
    $tasks = [];
}

// Calculate KPI values for better readability and performance
$totalTasks = count($tasks);
$inProgressTasks = count(array_filter($tasks, fn($t) => ($t['status'] ?? '') === 'in_progress'));
$highPriorityTasks = count(array_filter($tasks, fn($t) => ($t['priority'] ?? '') === 'high'));
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
        <div class="kpi-card__value"><?= $totalTasks ?></div>
        <div class="kpi-card__label">Total Tasks</div>
        <div class="kpi-card__status">Active</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚙️</div>
            <div class="kpi-card__trend">↗ +8%</div>
        </div>
        <div class="kpi-card__value"><?= $inProgressTasks ?></div>
        <div class="kpi-card__label">In Progress</div>
        <div class="kpi-card__status">Working</div>
    </div>

    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
            <div class="kpi-card__trend kpi-card__trend--down">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= $highPriorityTasks ?></div>
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
                        <th style="width: 35%;">Title</th>
                        <th>Assigned To & Priority</th>
                        <th>Progress</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks ?? [])): ?>
                    <tr>
                        <td colspan="5" class="text-center">
                            <div class="empty-state">
                                <div class="empty-icon">✅</div>
                                <h3>No Tasks Found</h3>
                                <p>No tasks have been created yet.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($task['title']) ?></strong>
                            <?php if ($task['description'] ?? ''): ?>
                                <br><small class="text-muted"><?= htmlspecialchars(substr($task['description'], 0, 80)) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="assignment-info">
                                <div class="assigned-user"><?= htmlspecialchars($task['assigned_user'] ?? 'Unassigned') ?></div>
                                <div class="priority-badge">
                                    <?php 
                                    $priorityClass = match($task['priority']) {
                                        'high' => 'danger',
                                        'medium' => 'warning',
                                        default => 'info'
                                    };
                                    ?>
                                    <span class="badge badge--<?= $priorityClass ?>"><?= ucfirst($task['priority']) ?></span>
                                </div>
                            </div>
                        </td>

                        <td>
                            <?php 
                            $progress = $task['progress'] ?? 0;
                            $status = $task['status'] ?? 'assigned';
                            $statusIcon = match($status) {
                                'completed' => '✅',
                                'in_progress' => '⚡',
                                'blocked' => '🚫',
                                default => '📋'
                            };
                            ?>
                            <div class="progress-container" data-task-id="<?= $task['id'] ?>">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $progress ?>%; background: <?= $progress >= 100 ? '#10b981' : ($progress >= 75 ? '#8b5cf6' : ($progress >= 50 ? '#3b82f6' : ($progress >= 25 ? '#f59e0b' : '#e2e8f0'))) ?>"></div>
                                </div>
                                <div class="progress-info">
                                    <span class="progress-percentage"><?= $progress ?>%</span>
                                    <span class="progress-status"><?= $statusIcon ?> <?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="cell-meta">
                                <div class="cell-primary"><?= ($task['deadline'] ?? $task['due_date']) ? date('M d, Y', strtotime($task['deadline'] ?? $task['due_date'])) : 'No due date' ?></div>
                                <?php if (isset($task['assigned_at']) && $task['assigned_at']): ?>
                                    <div class="cell-secondary">Assigned for <?= date('M d, Y', strtotime($task['assigned_at'])) ?></div>
                                <?php elseif (isset($task['created_at']) && $task['created_at']): ?>
                                    <div class="cell-secondary">Assigned for <?= date('M d, Y', strtotime($task['created_at'])) ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="ab-container">
                                <a class="ab-btn ab-btn--view" data-action="view" data-module="tasks" data-id="<?= $task['id'] ?>" title="View Details">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14,2 14,8 20,8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                    </svg>
                                </a>
                                <?php if ($task['status'] !== 'completed'): ?>
                                <button class="ab-btn ab-btn--progress" onclick="openProgressModal(<?= $task['id'] ?>, <?= $task['progress'] ?? 0 ?>, 'assigned')" title="Update Progress">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <polyline points="22,7 13.5,15.5 8.5,10.5 2,17"/>
                                        <polyline points="16,7 22,7 22,13"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <a class="ab-btn ab-btn--edit" data-action="edit" data-module="tasks" data-id="<?= $task['id'] ?>" title="Edit Task">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                        <path d="M15 5l4 4"/>
                                    </svg>
                                </a>
                                <button class="ab-btn ab-btn--delete" data-action="delete" data-module="tasks" data-id="<?= $task['id'] ?>" data-name="<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>" title="Delete Task">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M3 6h18"/>
                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="progressDialog" class="dialog" style="display: none;">
    <div class="dialog-content">
        <h4>Update Progress</h4>
        <p>Progress: <span id="progressValue">0</span>%</p>
        <input type="range" id="progressSlider" min="0" max="100" value="0">
        <div class="dialog-buttons">
            <button onclick="closeDialog()">Cancel</button>
            <button onclick="saveProgress()">Save</button>
        </div>
    </div>
</div>

<script src="/ergon/assets/js/table-utils.js"></script>

<script>
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.ab-btn');
    if (!btn) return;
    const action = btn.dataset.action;
    const module = btn.dataset.module;
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    if (action === 'view' && module && id) {
        window.location.href = `/ergon/${module}/view/${id}`;
    } else if (action === 'edit' && module && id) {
        window.location.href = `/ergon/${module}/edit/${id}`;
    } else if (action === 'delete' && module && id && name) {
        deleteRecord(module, id, name);
    }
});
var currentTaskId;
function openProgressModal(taskId, progress, status) {
    currentTaskId = taskId;
    var container = document.querySelector('[data-task-id="' + taskId + '"]');
    var currentProgress = container ? container.querySelector('.progress-percentage').textContent.replace('%', '') : progress;
    document.getElementById('progressSlider').value = currentProgress;
    document.getElementById('progressValue').textContent = currentProgress;
    document.getElementById('progressDialog').style.display = 'flex';
}
function closeDialog() {
    document.getElementById('progressDialog').style.display = 'none';
}
function saveProgress() {
    var progress = document.getElementById('progressSlider').value;
    var status = progress >= 100 ? 'completed' : progress > 0 ? 'in_progress' : 'assigned';
    fetch('/ergon/tasks/update-status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: currentTaskId, progress: progress, status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            var container = document.querySelector('[data-task-id="' + currentTaskId + '"]');
            if (container) {
                var fill = container.querySelector('.progress-fill');
                var percentage = container.querySelector('.progress-percentage');
                var statusEl = container.querySelector('.progress-status');
                fill.style.width = progress + '%';
                fill.style.background = progress >= 100 ? '#10b981' : (progress >= 75 ? '#8b5cf6' : (progress >= 50 ? '#3b82f6' : (progress >= 25 ? '#f59e0b' : '#e2e8f0')));
                percentage.textContent = progress + '%';
                var icon = status === 'completed' ? '✅' : status === 'in_progress' ? '⚡' : '📋';
                statusEl.textContent = icon + ' ' + status.replace('_', ' ');
            }
            closeDialog();
        } else {
            alert('Error updating task');
        }
    })
    .catch(() => alert('Error updating task'));
}
document.addEventListener('DOMContentLoaded', function() {
    var slider = document.getElementById('progressSlider');
    if (slider) {
        slider.oninput = function() {
            document.getElementById('progressValue').textContent = this.value;
        }
    }
});
</script>



<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

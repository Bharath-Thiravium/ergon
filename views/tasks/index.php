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
                                <?php if (isset($task['created_at']) && $task['created_at']): ?>
                                    <div class="cell-secondary">Created <?= date('M d, Y', strtotime($task['created_at'])) ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="/ergon/tasks/view/<?= $task['id'] ?>" class="btn-icon btn-icon--view" title="View Task Details">
                                    👁️
                                </a>
                                <?php if ($task['status'] !== 'completed'): ?>
                                <button onclick="openProgressModal(<?= $task['id'] ?>, <?= $task['progress'] ?? 0 ?>, '<?= addslashes($task['status'] ?? 'assigned') ?>')" class="btn-icon btn-icon--status" title="Update Progress & Status">
                                    📊
                                </button>
                                <?php endif; ?>
                                <a href="/ergon/tasks/edit/<?= $task['id'] ?>" class="btn-icon btn-icon--edit" title="Edit Task Details">
                                    ✏️
                                </a>
                                <button onclick="deleteRecord('tasks', <?= $task['id'] ?>, '<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>')" class="btn-icon btn-icon--delete" title="Delete Task Permanently">
                                    🗑️
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
// Tooltip functionality for action buttons
document.addEventListener('DOMContentLoaded', function() {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    document.body.appendChild(tooltip);
    
    document.querySelectorAll('[title]').forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const title = this.getAttribute('title');
            if (title) {
                tooltip.textContent = title;
                tooltip.style.display = 'block';
                
                const rect = this.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
            }
        });
        
        element.addEventListener('mouseleave', function() {
            tooltip.style.display = 'none';
        });
    });
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

document.getElementById('progressSlider').oninput = function() {
    document.getElementById('progressValue').textContent = this.value;
}
</script>

<style>


.progress-container {
    width: 140px;
    padding: 0.5rem;
    border-radius: 8px;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.25rem;
}

.progress-fill {
    height: 100%;
    border-radius: 4px;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.progress-percentage {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-primary);
}

.progress-status {
    font-size: 0.7rem;
    color: var(--text-secondary);
}

.dialog {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.dialog-content {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    width: 300px;
    text-align: center;
}

.dialog-content h4 {
    margin: 0 0 1rem 0;
}

.dialog-content input[type="range"] {
    width: 100%;
    margin: 1rem 0;
}

.dialog-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 1rem;
}

.dialog-buttons button {
    padding: 0.5rem 1rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: white;
    cursor: pointer;
}

.dialog-buttons button:last-child {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.assignment-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.assigned-user {
    font-weight: 500;
    color: var(--text-primary);
    font-size: 0.875rem;
}

.priority-badge {
    display: flex;
    align-items: center;
}

.priority-badge .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-overlay--hidden {
    display: none;
}

.modal-content {
    background: var(--bg-primary);
    border-radius: 8px;
    width: 400px;
    max-width: 90vw;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h4 {
    margin: 0;
    color: var(--primary);
}

.modal-body {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem;
    border-top: 1px solid var(--border-color);
}

.progress-control label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.progress-slider {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: var(--bg-secondary);
    outline: none;
}

.progress-slider::-webkit-slider-thumb {
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--primary);
    cursor: pointer;
}

.status-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 500;
    font-size: 0.85rem;
}

.status-assigned { background: #fff3cd; color: #856404; }
.status-in_progress { background: #d1ecf1; color: #0c5460; }
.status-completed { background: #d4edda; color: #155724; }
.status-blocked { background: #f8d7da; color: #721c24; }

.block-btn {
    padding: 6px 12px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    background: var(--bg-primary);
    cursor: pointer;
    font-size: 0.8rem;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.tooltip {
    position: absolute;
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 6px 8px;
    border-radius: 4px;
    font-size: 11px;
    white-space: nowrap;
    z-index: 10000;
    pointer-events: none;
    display: none;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

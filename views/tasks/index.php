<?php
$title = 'Tasks';
$active_page = 'tasks';
ob_start();

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
                            $progressClass = match(true) {
                                $progress >= 100 => 'progress--completed',
                                $progress >= 75 => 'progress--high',
                                $progress >= 50 => 'progress--medium',
                                $progress >= 25 => 'progress--low',
                                default => 'progress--start'
                            };
                            $statusIcon = match($status) {
                                'completed' => '✅',
                                'in_progress' => '⚡',
                                'blocked' => '🚫',
                                default => '📋'
                            };
                            ?>
                            <div class="progress-container <?= $progressClass ?>">
                                <div class="progress-visual">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                                    </div>
                                    <div class="progress-info">
                                        <span class="progress-percentage"><?= $progress ?>%</span>
                                        <span class="progress-status"><?= $statusIcon ?> <?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                                    </div>
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
                                <a href="/ergon/tasks/view/<?= $task['id'] ?>" class="btn-icon btn-icon--view" title="View Details">
                                    👁️
                                </a>
                                <?php if ($task['status'] !== 'completed'): ?>
                                <button onclick="updateTaskStatus(<?= $task['id'] ?>, '<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>', <?= $task['progress'] ?? 0 ?>)" class="btn-icon btn-icon--status" title="Update Progress">
                                    📊
                                </button>
                                <?php endif; ?>
                                <a href="/ergon/tasks/edit/<?= $task['id'] ?>" class="btn-icon btn-icon--edit" title="Edit Task">
                                    ✏️
                                </a>
                                <button onclick="deleteRecord('tasks', <?= $task['id'] ?>, '<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>')" class="btn-icon btn-icon--delete" title="Delete Task">
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

<script src="/ergon/assets/js/table-utils.js"></script>

<!-- Task Status Update Modal -->
<div id="statusModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📊 Update Task Progress</h3>
            <button class="modal-close" onclick="closeStatusModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Task: <strong id="taskTitle"></strong></label>
            </div>
            <div class="form-group">
                <label for="taskProgress">Progress: <span id="progressValue">0%</span></label>
                <input type="range" id="taskProgress" min="0" max="100" value="0" oninput="updateProgressValue(this.value)" class="progress-slider">
            </div>
            <div class="form-group">
                <label for="taskStatus">Status</label>
                <select id="taskStatus">
                    <option value="assigned">📋 Assigned</option>
                    <option value="in_progress">⚡ In Progress</option>
                    <option value="completed">✅ Completed</option>
                    <option value="blocked">🚫 Blocked</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="saveTaskStatus()" class="btn btn--primary">💾 Update</button>
            <button onclick="closeStatusModal()" class="btn btn--secondary">❌ Cancel</button>
        </div>
    </div>
</div>

<script>
let currentTaskId = null;

function updateTaskStatus(taskId, taskTitle, currentProgress) {
    currentTaskId = taskId;
    document.getElementById('taskTitle').textContent = taskTitle;
    document.getElementById('taskProgress').value = currentProgress || 0;
    document.getElementById('progressValue').textContent = (currentProgress || 0) + '%';
    
    // Auto-set status based on progress
    const statusSelect = document.getElementById('taskStatus');
    if (currentProgress >= 100) {
        statusSelect.value = 'completed';
    } else if (currentProgress > 0) {
        statusSelect.value = 'in_progress';
    } else {
        statusSelect.value = 'assigned';
    }
    
    document.getElementById('statusModal').style.display = 'flex';
}

function updateProgressValue(value) {
    document.getElementById('progressValue').textContent = value + '%';
    
    // Auto-update status based on progress
    const statusSelect = document.getElementById('taskStatus');
    if (value >= 100) {
        statusSelect.value = 'completed';
    } else if (value > 0) {
        statusSelect.value = 'in_progress';
    } else {
        statusSelect.value = 'assigned';
    }
}

function saveTaskStatus() {
    const progress = document.getElementById('taskProgress').value;
    const status = document.getElementById('taskStatus').value;
    
    fetch('/ergon/tasks/update-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: currentTaskId,
            progress: progress,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating task: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating task status');
    });
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
    currentTaskId = null;
}

// Close modal when clicking outside
document.getElementById('statusModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatusModal();
    }
});
</script>

<style>
.modal {
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

.modal-content {
    background: var(--bg-primary);
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h3 {
    margin: 0;
    color: var(--primary);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid var(--border-color);
    justify-content: flex-end;
}

.progress-slider {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: var(--bg-secondary);
    outline: none;
    margin-top: 0.5rem;
}

.progress-slider::-webkit-slider-thumb {
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--primary);
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.progress-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--primary);
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.btn-icon--status {
    background: var(--bg-secondary);
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
    transition: all 0.2s ease;
}

.btn-icon--status:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    border-color: var(--primary-light);
    transform: translateY(-1px);
}

.progress-container {
    width: 140px;
    padding: 0.5rem;
    border-radius: 8px;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.progress-container:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.progress-visual {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.progress-bar {
    position: relative;
    width: 100%;
    height: 8px;
    background: var(--bg-tertiary);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 4px;
    position: relative;
}

.progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
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
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* Progress state colors */
.progress--start .progress-fill {
    background: linear-gradient(90deg, #e2e8f0, #cbd5e1);
}

.progress--low .progress-fill {
    background: linear-gradient(90deg, #fbbf24, #f59e0b);
}

.progress--medium .progress-fill {
    background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.progress--high .progress-fill {
    background: linear-gradient(90deg, #8b5cf6, #7c3aed);
}

.progress--completed .progress-fill {
    background: linear-gradient(90deg, #10b981, #059669);
}

.progress--completed {
    border-color: #10b981;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), var(--bg-secondary));
}

.progress--high {
    border-color: #8b5cf6;
}

.progress--medium {
    border-color: #3b82f6;
}

.progress--low {
    border-color: #f59e0b;
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
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

<?php
$title = 'Tasks';
$active_page = 'tasks';
ob_start();
?>

<style>
.progress-fill { transition: none !important; }
.progress-fill-mini { transition: none !important; }
<?php for($i = 0; $i <= 100; $i += 5): ?>
.progress-<?= $i ?> { width: <?= $i ?>% !important; background: <?= match(true) { $i >= 100 => 'linear-gradient(90deg, #10b981, #059669)', $i >= 75 => 'linear-gradient(90deg, #8b5cf6, #7c3aed)', $i >= 50 => 'linear-gradient(90deg, #3b82f6, #2563eb)', $i >= 25 => 'linear-gradient(90deg, #fbbf24, #f59e0b)', default => 'linear-gradient(90deg, #e2e8f0, #cbd5e1)' } ?> !important; }
<?php endfor; ?>
</style>

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
                                        <div class="progress-fill progress-<?= $progress ?>" data-width="<?= $progress ?>"></div>
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

<!-- Progress Modal -->
<div id="progressModal" class="modal-overlay modal-overlay--hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h4>📊 Update Progress</h4>
            <button onclick="closeProgressModal()" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
            <div class="progress-control">
                <label>Progress: <span id="modalProgressValue">0%</span></label>
                <input type="range" id="modalTaskProgress" min="0" max="100" value="0" oninput="updateModalProgress(this.value)" class="progress-slider">
            </div>
            <div class="status-display">
                <span>Status: <span id="modalCurrentStatus" class="status-badge">Assigned</span></span>
                <button id="modalBlockBtn" onclick="toggleModalBlock()" class="block-btn">🚫 Block</button>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="closeProgressModal()" class="btn btn--secondary">Cancel</button>
            <button onclick="saveModalProgress()" class="btn btn--primary">💾 Save</button>
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

// Set exact progress widths for values not in CSS
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.progress-fill[data-width]').forEach(function(el) {
        var width = el.getAttribute('data-width');
        if (width % 5 !== 0) {
            el.style.width = width + '%';
            if (width >= 100) el.style.background = 'linear-gradient(90deg, #10b981, #059669)';
            else if (width >= 75) el.style.background = 'linear-gradient(90deg, #8b5cf6, #7c3aed)';
            else if (width >= 50) el.style.background = 'linear-gradient(90deg, #3b82f6, #2563eb)';
            else if (width >= 25) el.style.background = 'linear-gradient(90deg, #fbbf24, #f59e0b)';
            else el.style.background = 'linear-gradient(90deg, #e2e8f0, #cbd5e1)';
        }
    });
    
    // Check for updated progress from localStorage
    var updatedTask = localStorage.getItem('taskUpdated');
    if (updatedTask) {
        var taskData = JSON.parse(updatedTask);
        updateTableProgress(taskData.id, taskData.progress, taskData.status);
        localStorage.removeItem('taskUpdated');
    }
});

var currentModalTaskId = null;
var currentModalStatus = 'assigned';

function openProgressModal(taskId, progress, status) {
    currentModalTaskId = taskId;
    currentModalStatus = status;
    
    document.getElementById('modalTaskProgress').value = progress;
    document.getElementById('modalProgressValue').textContent = progress + '%';
    updateModalStatusDisplay(status);
    document.getElementById('progressModal').classList.remove('modal-overlay--hidden');
}

function closeProgressModal() {
    document.getElementById('progressModal').classList.add('modal-overlay--hidden');
}

function updateModalProgress(value) {
    document.getElementById('modalProgressValue').textContent = value + '%';
    if (currentModalStatus !== 'blocked') {
        var newStatus = value >= 100 ? 'completed' : value > 0 ? 'in_progress' : 'assigned';
        updateModalStatusDisplay(newStatus);
    }
}

function updateModalStatusDisplay(status) {
    currentModalStatus = status;
    var statusEl = document.getElementById('modalCurrentStatus');
    var statusText = {
        'assigned': 'Assigned',
        'in_progress': 'In Progress', 
        'completed': 'Completed',
        'blocked': 'Blocked'
    };
    statusEl.textContent = statusText[status];
    statusEl.className = 'status-badge status-' + status;
    
    var blockBtn = document.getElementById('modalBlockBtn');
    if (status === 'blocked') {
        blockBtn.textContent = '✅ Unblock';
        blockBtn.onclick = function() { toggleModalBlock(); };
    } else {
        blockBtn.textContent = '🚫 Block';
        blockBtn.onclick = function() { toggleModalBlock(); };
    }
}

function toggleModalBlock() {
    if (currentModalStatus === 'blocked') {
        var progress = document.getElementById('modalTaskProgress').value;
        var newStatus = progress >= 100 ? 'completed' : progress > 0 ? 'in_progress' : 'assigned';
        updateModalStatusDisplay(newStatus);
    } else {
        updateModalStatusDisplay('blocked');
    }
}

function saveModalProgress() {
    var progress = document.getElementById('modalTaskProgress').value;
    
    fetch('/ergon/tasks/update-status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            task_id: currentModalTaskId,
            progress: progress,
            status: currentModalStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTableProgress(currentModalTaskId, progress, currentModalStatus);
            closeProgressModal();
        } else alert('Error: ' + (data.message || 'Unknown error'));
    })
    .catch(() => alert('Error updating task'));
}

function updateTableProgress(taskId, progress, status) {
    var rows = document.querySelectorAll('tbody tr');
    rows.forEach(function(row) {
        var viewLink = row.querySelector('a[href*="/view/' + taskId + '"]');
        if (viewLink) {
            var progressFill = row.querySelector('.progress-fill');
            var progressText = row.querySelector('.progress-percentage');
            var statusText = row.querySelector('.progress-status');
            
            if (progressFill) {
                progressFill.style.width = progress + '%';
                if (progress >= 100) progressFill.style.background = 'linear-gradient(90deg, #10b981, #059669)';
                else if (progress >= 75) progressFill.style.background = 'linear-gradient(90deg, #8b5cf6, #7c3aed)';
                else if (progress >= 50) progressFill.style.background = 'linear-gradient(90deg, #3b82f6, #2563eb)';
                else if (progress >= 25) progressFill.style.background = 'linear-gradient(90deg, #fbbf24, #f59e0b)';
                else progressFill.style.background = 'linear-gradient(90deg, #e2e8f0, #cbd5e1)';
            }
            if (progressText) progressText.textContent = progress + '%';
            if (statusText) {
                var statusIcon = status === 'completed' ? '✅' : status === 'in_progress' ? '⚡' : status === 'blocked' ? '🚫' : '📋';
                statusText.textContent = statusIcon + ' ' + status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
            }
        }
    });
}
</script>

<style>


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
    border-radius: 4px;
    position: relative;
    width: 0%;
    background: linear-gradient(90deg, #e2e8f0, #cbd5e1);
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

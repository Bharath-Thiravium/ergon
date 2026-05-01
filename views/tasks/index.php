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
        <div class="view-options">
            <a href="/ergon/tasks" class="view-btn view-btn--active" data-view="list">
                <span>📋</span> List
            </a>
            <a href="/ergon/tasks/kanban" class="view-btn" data-view="kanban">
                <span>📏</span> Kanban
            </a>
            <a href="/ergon/tasks/calendar" class="view-btn" data-view="calendar">
                <span>📆</span> Calendar
            </a>
        </div>
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
            <span>✅</span> Tasks - List View
        </h2>
    </div>
    <div class="card__body">
        <!-- Desktop Table View -->
        <div class="table-responsive desktop-view">
            <table class="table">
                <thead>
                    <tr>
                        <th class="col-title">Title</th>
                        <th class="col-assignment">Assigned To & Priority</th>
                        <th class="col-progress">Progress</th>
                        <th class="col-date">Due Date</th>
                        <th class="col-actions">Actions</th>
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
                                <!-- View Details - Always available -->
                                <a class="ab-btn ab-btn--view" data-action="view" data-module="tasks" data-id="<?= $task['id'] ?>" title="View Details">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14,2 14,8 20,8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                    </svg>
                                </a>
                                
                                <?php 
                                // Enhanced permission logic for different actions
                                $currentUserId = $_SESSION['user_id'] ?? 0;
                                $currentUserRole = $_SESSION['role'] ?? 'user';
                                $isAssignedUser = ($task['assigned_to'] ?? 0) == $currentUserId;
                                $isTaskCreator = ($task['assigned_by'] ?? 0) == $currentUserId;
                                $isAdmin = in_array($currentUserRole, ['admin', 'owner', 'system_admin']);
                                
                                // Update Progress - Available for assigned users and admins
                                $canUpdateProgress = $isAssignedUser || $isAdmin;
                                // View History - Available for assigned users, creators, and admins
                                $canViewHistory = $isAssignedUser || $isTaskCreator || $isAdmin;
                                // Edit Task - Available for assigned users, creators, and admins
                                $canEdit = $isAssignedUser || $isTaskCreator || $isAdmin;
                                // Delete Task - Available for creators and admins only
                                $canDelete = $isTaskCreator || $isAdmin;
                                ?>
                                
                                <!-- Update Progress Button -->
                                <?php if ($canUpdateProgress && $task['status'] !== 'completed'): ?>
                                <button class="ab-btn ab-btn--progress" onclick="openProgressModal(<?= $task['id'] ?>, <?= $task['progress'] ?? 0 ?>, 'assigned')" title="Update Progress">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <polyline points="22,7 13.5,15.5 8.5,10.5 2,17"/>
                                        <polyline points="16,7 22,7 22,13"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                
                                <!-- View Progress History Button -->
                                <?php if ($canViewHistory): ?>
                                <button class="ab-btn history-btn" onclick="showProgressHistory(<?= $task['id'] ?>)" title="View Progress History">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12,6 12,12 16,14"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                
                                <!-- Edit Task Button -->
                                <?php if ($canEdit): ?>
                                <a class="ab-btn ab-btn--edit" data-action="edit" data-module="tasks" data-id="<?= $task['id'] ?>" title="Edit Task">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                        <path d="M15 5l4 4"/>
                                    </svg>
                                </a>
                                <?php endif; ?>
                                
                                <!-- Delete Task Button -->
                                <?php if ($canDelete): ?>
                                <button class="ab-btn ab-btn--delete" data-action="delete" data-module="tasks" data-id="<?= $task['id'] ?>" data-name="<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>" title="Delete Task">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M3 6h18"/>
                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Mobile Card View -->
        <div class="mobile-card-view" style="display:none;padding:16px;">
            <?php if (empty($tasks ?? [])): ?>
                <div class="empty-state">
                    <div class="empty-icon">✅</div>
                    <h3>No Tasks Found</h3>
                    <p>No tasks have been created yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                <?php 
                $progress = $task['progress'] ?? 0;
                $status = $task['status'] ?? 'assigned';
                $statusIcon = match($status) {
                    'completed' => '✅',
                    'in_progress' => '⚡',
                    'blocked' => '🚫',
                    default => '📋'
                };
                $priorityClass = match($task['priority']) {
                    'high' => 'danger',
                    'medium' => 'warning',
                    default => 'info'
                };
                ?>
                <div class="task-card">
                    <div class="task-card__header">
                        <div>
                            <div class="task-card__title"><?= htmlspecialchars($task['title']) ?></div>
                            <?php if ($task['description'] ?? ''): ?>
                            <div style="font-size:14px;color:#6b7280;margin-top:4px;"><?= htmlspecialchars(substr($task['description'], 0, 60)) ?>...</div>
                            <?php endif; ?>
                        </div>
                        <span class="task-card__priority badge badge--<?= $priorityClass ?>"><?= ucfirst($task['priority']) ?></span>
                    </div>
                    
                    <div class="task-card__progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $progress ?>%; background: <?= $progress >= 100 ? '#10b981' : ($progress >= 75 ? '#8b5cf6' : ($progress >= 50 ? '#3b82f6' : ($progress >= 25 ? '#f59e0b' : '#e2e8f0'))) ?>"></div>
                        </div>
                        <div class="progress-text" style="font-size:12px;color:#6b7280;text-align:right;margin-top:4px;">
                            <?= $statusIcon ?> <?= $progress ?>% - <?= ucfirst(str_replace('_', ' ', $status)) ?>
                        </div>
                    </div>
                    
                    <div class="task-card__meta">
                        <div class="task-card__field">
                            <span class="task-card__label">Assigned To</span>
                            <span class="task-card__value"><?= htmlspecialchars($task['assigned_user'] ?? 'Unassigned') ?></span>
                        </div>
                        <div class="task-card__field">
                            <span class="task-card__label">Due Date</span>
                            <span class="task-card__value"><?= ($task['deadline'] ?? $task['due_date']) ? date('M d, Y', strtotime($task['deadline'] ?? $task['due_date'])) : 'No due date' ?></span>
                        </div>
                    </div>
                    
                    <div class="task-card__actions">
                        <a class="ab-btn ab-btn--view" data-action="view" data-module="tasks" data-id="<?= $task['id'] ?>" data-tooltip="View Details">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14,2 14,8 20,8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                        </a>
                        
                        <?php 
                        $currentUserId = $_SESSION['user_id'] ?? 0;
                        $currentUserRole = $_SESSION['role'] ?? 'user';
                        $isAssignedUser = ($task['assigned_to'] ?? 0) == $currentUserId;
                        $isTaskCreator = ($task['assigned_by'] ?? 0) == $currentUserId;
                        $isAdmin = in_array($currentUserRole, ['admin', 'owner', 'system_admin']);
                        $canUpdateProgress = $isAssignedUser || $isAdmin;
                        $canViewHistory = $isAssignedUser || $isTaskCreator || $isAdmin;
                        $canEdit = $isAssignedUser || $isTaskCreator || $isAdmin;
                        $canDelete = $isTaskCreator || $isAdmin;
                        ?>
                        
                        <?php if ($canUpdateProgress && $task['status'] !== 'completed'): ?>
                        <button class="ab-btn ab-btn--progress" onclick="openProgressModal(<?= $task['id'] ?>, <?= $task['progress'] ?? 0 ?>, 'assigned')" data-tooltip="Update Progress">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <polyline points="22,7 13.5,15.5 8.5,10.5 2,17"/>
                                <polyline points="16,7 22,7 22,13"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($canViewHistory): ?>
                        <button class="ab-btn history-btn" onclick="showProgressHistory(<?= $task['id'] ?>)" data-tooltip="View Progress History">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12,6 12,12 16,14"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($canEdit): ?>
                        <a class="ab-btn ab-btn--edit" data-action="edit" data-module="tasks" data-id="<?= $task['id'] ?>" data-tooltip="Edit Task">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                <path d="M15 5l4 4"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($canDelete): ?>
                        <button class="ab-btn ab-btn--delete" data-action="delete" data-module="tasks" data-id="<?= $task['id'] ?>" data-name="<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>" data-tooltip="Delete Task">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M3 6h18"/>
                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                <line x1="10" y1="11" x2="10" y2="17"/>
                                <line x1="14" y1="11" x2="14" y2="17"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Enhanced Progress Update Modal -->
<div id="progressDialog" class="progress-dialog" style="display: none;">
    <div class="progress-modal">
        <h3>📊 Update Task Progress</h3>
        
        <div class="progress-form-group">
            <label for="progressSlider">Progress Level</label>
            <div class="progress-slider-container">
                <input type="range" id="progressSlider" class="progress-slider" min="0" max="100" value="0">
                <span id="progressValue" class="progress-value">0%</span>
            </div>
        </div>
        
        <div class="progress-form-group">
            <label for="progressDescription">Progress Description *</label>
            <textarea id="progressDescription" class="progress-description" 
                      placeholder="Describe what you've accomplished, current status, or next steps..." 
                      required></textarea>
        </div>
        
        <div class="progress-actions">
            <button type="button" class="progress-btn progress-btn-secondary" onclick="closeDialog()">Cancel</button>
            <button type="button" class="progress-btn progress-btn-primary" onclick="saveProgress()">Update Progress</button>
        </div>
    </div>
</div>

<!-- Progress History Modal -->
<div id="progressHistoryDialog" class="progress-dialog" style="display: none;">
    <div class="progress-modal">
        <h3>📈 Progress History</h3>
        <div id="progressHistoryContent">
            <!-- History content will be loaded here -->
        </div>
        <div class="progress-actions">
            <button type="button" class="progress-btn progress-btn-secondary" onclick="closeDialog()">Close</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/ergon/assets/css/task-progress-enhanced.css">

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
        if (confirm('Are you sure you want to delete "' + name + '"?')) {
            fetch(`/ergon/${module}/delete/${id}`, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(() => showMessage('Delete failed', 'error'));
        }
    }
});

// Check for URL parameters and show messages
function checkUrlMessages() {
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');
    
    if (success) {
        showMessage(success, 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (error) {
        showMessage(error, 'error');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    checkUrlMessages();
});

// Load enhanced progress functionality
const script = document.createElement('script');
script.src = '/ergon/assets/js/task-progress-enhanced.js';
document.head.appendChild(script);
</script>

<style>
@media (max-width: 768px) {
    /* Show mobile card view, hide desktop table */
    .desktop-view { display: none !important; }
    .mobile-card-view { display: block !important; }
}

@media (min-width: 769px) {
    /* Show desktop table, hide mobile card view */
    .desktop-view { display: block !important; }
    .mobile-card-view { display: none !important; }
}
</style>



<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

<?php
$title = 'Task Kanban Board';
$active_page = 'tasks';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>✅</span> Task Management</h1>
        <p>Manage and track all project tasks and assignments</p>
    </div>
    <div class="page-actions">
        <div class="view-options">
            <a href="/ergon/tasks" class="view-btn" data-view="list">
                <span>📋</span> List
            </a>
            <a href="/ergon/tasks/kanban" class="view-btn view-btn--active" data-view="kanban">
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

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📏</span> Tasks - Kanban View
        </h2>
        <div class="kanban-filters">
            <select id="timeFilter" onchange="filterTasks()">
                <option value="all">All Tasks</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
            </select>
            <button class="btn btn--secondary" onclick="refreshBoard()">
                <span>🔄</span> Refresh
            </button>
        </div>

    </div>
    <div class="card__body">
                    <div class="kanban-board" id="kanban-board">
                        <div class="kanban-column" data-status="assigned">
                            <div class="kanban-header bg-info">
                                <h5>📝 Assigned</h5>
                                <span class="badge badge-light" id="assigned-count">0</span>
                            </div>
                            <div class="kanban-tasks" id="assigned-tasks"></div>
                        </div>
                        
                        <div class="kanban-column" data-status="in_progress">
                            <div class="kanban-header bg-warning">
                                <h5>⚡ In Progress</h5>
                                <span class="badge badge-light" id="in_progress-count">0</span>
                            </div>
                            <div class="kanban-tasks" id="in_progress-tasks"></div>
                        </div>
                        
                        
                        <div class="kanban-column" data-status="completed">
                            <div class="kanban-header bg-success">
                                <h5>✅ Completed</h5>
                                <span class="badge badge-light" id="completed-count">0</span>
                            </div>
                            <div class="kanban-tasks" id="completed-tasks"></div>
                        </div>
                        
                        <div class="kanban-column" data-status="suspended">
                            <div class="kanban-header bg-warning">
                                <h5>⏸️ Suspended</h5>
                                <span class="badge badge-light" id="suspended-count">0</span>
                            </div>
                            <div class="kanban-tasks" id="suspended-tasks"></div>
                        </div>
                        
                        <div class="kanban-column" data-status="cancelled">
                            <div class="kanban-header bg-danger">
                                <h5>❌ Cancelled</h5>
                                <span class="badge badge-light" id="cancelled-count">0</span>
                            </div>
                            <div class="kanban-tasks" id="cancelled-tasks"></div>
                        </div>
                    </div>
    </div>
</div>

<style>
.kanban-board {
    display: flex;
    gap: var(--space-2);
    width: 100%;
    padding: var(--space-3) 0;
}

.kanban-column {
    flex: 1;
    min-width: 0;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
}

.kanban-header {
    padding: var(--space-3) var(--space-4);
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    background: var(--bg-secondary);
    color: var(--text-primary);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
}

.kanban-header h5 {
    margin: 0;
    font-size: var(--font-size-sm);
    font-weight: 600;
}

.kanban-header .badge {
    background: var(--primary);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.kanban-tasks {
    padding: var(--space-3);
    min-height: 400px;
}

.task-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: var(--space-3);
    margin-bottom: var(--space-3);
    cursor: move;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.task-card:hover {
    box-shadow: var(--shadow);
    transform: translateY(-1px);
}

.task-title {
    font-weight: 600;
    margin-bottom: var(--space-2);
    font-size: var(--font-size-sm);
    color: var(--text-primary);
}

.task-meta {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: var(--space-2);
}

.task-progress {
    width: 100%;
    height: 4px;
    background: var(--gray-200);
    border-radius: 2px;
    margin: var(--space-2) 0;
    overflow: hidden;
}

.task-progress-bar {
    height: 100%;
    border-radius: 2px;
    transition: width 0.3s ease;
}

.priority-high { border-left: 2px solid var(--error); }
.priority-medium { border-left: 2px solid var(--warning); }
.priority-low { border-left: 2px solid var(--success); }

.kanban-column.drag-over {
    background: rgba(59, 130, 246, 0.05);
    border: 2px dashed var(--primary);
}

.kanban-column[data-status="suspended"] .kanban-tasks {
    pointer-events: none;
}

.task-card[data-task-id^="f_"] {
    border-left: 2px solid #17a2b8;
    background: linear-gradient(135deg, rgba(23, 162, 184, 0.05), var(--bg-primary));
}

.task-card[data-task-id^="f_"]:hover {
    transform: none;
    cursor: default;
}

.task-card.sortable-ghost {
    opacity: 0.4;
}

.task-card.sortable-chosen {
    transform: rotate(2deg);
    box-shadow: var(--shadow-lg);
}

.task-card.sortable-drag {
    transform: rotate(5deg);
    opacity: 0.8;
}

.kanban-filters {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.kanban-filters select {
    padding: var(--space-2) var(--space-3);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: var(--bg-primary);
    color: var(--text-primary);
    font-size: var(--font-size-sm);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
let tasks = <?= json_encode($tasks ?? []) ?>;
let followups = <?= json_encode($followups ?? []) ?>;
let allTasks = [...tasks];
let allFollowups = [...followups];

document.addEventListener('DOMContentLoaded', function() {
    renderTasks();
    initializeDragAndDrop();
});

function loadTasks() {
    fetch('/ergon/api/tasks')
        .then(response => response.json())
        .then(data => {
            tasks = data.tasks || [];
            renderTasks();
        })
        .catch(error => console.error('Error loading tasks:', error));
}

function renderTasks() {
    const statuses = ['assigned', 'in_progress', 'completed', 'suspended', 'cancelled'];
    
    statuses.forEach(status => {
        const container = document.getElementById(`${status}-tasks`);
        if (!container) return;
        
        let statusTasks = tasks.filter(task => task.status === status);
        
        // Add postponed/rescheduled followups to suspended column
        if (status === 'suspended') {
            const postponedFollowups = followups.filter(f => 
                f.status === 'postponed' || f.status === 'rescheduled'
            );
            postponedFollowups.forEach(followup => {
                statusTasks.push({
                    id: 'f_' + followup.id,
                    title: followup.title,
                    assigned_user: followup.assigned_user,
                    priority: 'medium',
                    progress: 0,
                    deadline: followup.follow_up_date,
                    type: 'followup',
                    status: followup.status
                });
            });
        }
        
        // Add cancelled followups to cancelled column
        if (status === 'cancelled') {
            const cancelledFollowups = followups.filter(f => 
                f.status === 'cancelled'
            );
            cancelledFollowups.forEach(followup => {
                statusTasks.push({
                    id: 'f_' + followup.id,
                    title: followup.title,
                    assigned_user: followup.assigned_user,
                    priority: 'medium',
                    progress: 0,
                    deadline: followup.follow_up_date,
                    type: 'followup',
                    status: followup.status
                });
            });
        }
        
        container.innerHTML = '';
        const countElement = document.getElementById(`${status}-count`);
        if (countElement) countElement.textContent = statusTasks.length;
        
        statusTasks.forEach(task => {
            const taskCard = createTaskCard(task);
            container.appendChild(taskCard);
        });
    });
}

function createTaskCard(task) {
    const card = document.createElement('div');
    card.className = `task-card priority-${task.priority}`;
    card.draggable = task.type !== 'followup';
    card.dataset.taskId = task.id;
    
    const progressColor = task.progress >= 75 ? '#28a745' : 
                         task.progress >= 50 ? '#ffc107' : 
                         task.progress >= 25 ? '#fd7e14' : '#dc3545';
    
    const typeIcon = task.type === 'followup' ? '📞' : '✅';
    const statusText = task.type === 'followup' ? task.status : `${task.progress || 0}%`;
    
    card.innerHTML = `
        <div class="task-title">${typeIcon} ${task.title}</div>
        ${task.type !== 'followup' ? `
        <div class="task-progress">
            <div class="task-progress-bar" style="width: ${task.progress}%; background: ${progressColor}"></div>
        </div>
        ` : ''}
        <div class="task-meta">
            <span>👤 ${task.assigned_user || 'Unassigned'}</span>
            <span class="badge badge-${task.priority === 'high' ? 'danger' : task.priority === 'medium' ? 'warning' : 'info'}">${task.priority}</span>
        </div>
        <div class="task-meta">
            <small>📅 ${task.deadline ? new Date(task.deadline).toLocaleDateString() : 'No deadline'}</small>
            <small>${statusText}</small>
        </div>
    `;
    
    return card;
}

function initializeDragAndDrop() {
    const columns = document.querySelectorAll('.kanban-tasks');
    
    columns.forEach(column => {
        new Sortable(column, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'task-card-ghost',
            chosenClass: 'task-card-chosen',
            dragClass: 'task-card-drag',
            onEnd: function(evt) {
                const taskId = evt.item.dataset.taskId;
                const newColumn = evt.to.closest('.kanban-column');
                const newStatus = newColumn.dataset.status;
                const oldColumn = evt.from.closest('.kanban-column');
                const oldStatus = oldColumn.dataset.status;
                
                // Restrict suspended column access
                if (newStatus === 'suspended') {
                    evt.item.remove();
                    evt.from.appendChild(evt.item);
                    showToast('Cannot move to suspended. Use task edit form to suspend with reason.', 'error');
                    return;
                }
                
                // Allow only specific transitions
                const allowedTransitions = {
                    'assigned': ['in_progress', 'cancelled'],
                    'in_progress': ['assigned', 'completed', 'cancelled'],
                    'completed': ['in_progress'],
                    'cancelled': ['assigned'],
                    'suspended': ['assigned', 'in_progress', 'cancelled']
                };
                
                if (!allowedTransitions[oldStatus]?.includes(newStatus)) {
                    evt.item.remove();
                    evt.from.appendChild(evt.item);
                    showToast('Invalid status transition', 'error');
                    return;
                }
                
                if (newStatus === 'cancelled') {
                    // Temporarily move card back while getting reason
                    evt.item.remove();
                    evt.from.appendChild(evt.item);
                    promptCancellationReason(taskId, newStatus, evt.to);
                } else {
                    updateTaskStatus(taskId, newStatus);
                }
            }
        });
    });
}

function updateTaskStatus(taskId, newStatus, reason = '') {
    // Update local data immediately
    const task = tasks.find(t => t.id == taskId);
    if (task) {
        task.status = newStatus;
    }
    
    // Update server
    fetch('/ergon/tasks/update-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: taskId,
            status: newStatus,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            location.reload();
        }
    })
    .catch(error => {
        location.reload();
    });
}

function promptCancellationReason(taskId, newStatus, targetColumn) {
    const reason = prompt('Please provide a reason for cancellation:');
    if (reason && reason.trim()) {
        updateTaskStatus(taskId, newStatus, reason.trim());
        // Move card to cancelled column after successful update
        setTimeout(() => {
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard && targetColumn) {
                taskCard.remove();
                targetColumn.appendChild(taskCard);
                renderTasks(); // Re-render to update counts
            }
        }, 100);
    }
}

function refreshBoard() {
    location.reload();
}

function filterTasks() {
    const filter = document.getElementById('timeFilter').value;
    const now = new Date();
    
    if (filter === 'all') {
        tasks = [...allTasks];
    } else {
        tasks = allTasks.filter(task => {
            const taskDate = new Date(task.deadline || task.due_date || task.created_at);
            
            switch(filter) {
                case 'today':
                    return taskDate.toDateString() === now.toDateString();
                case 'week':
                    const weekStart = new Date(now.setDate(now.getDate() - now.getDay()));
                    const weekEnd = new Date(now.setDate(now.getDate() - now.getDay() + 6));
                    return taskDate >= weekStart && taskDate <= weekEnd;
                case 'month':
                    return taskDate.getMonth() === now.getMonth() && taskDate.getFullYear() === now.getFullYear();
                default:
                    return true;
            }
        });
    }
    
    renderTasks();
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} toast-notification`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        min-width: 250px; padding: 12px 20px; border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>

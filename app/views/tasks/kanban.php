<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📋 Task Kanban Board</h3>
                    <div class="card-tools">
                        <button class="btn btn-primary btn-sm" onclick="refreshBoard()">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
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
                        
                        <div class="kanban-column" data-status="review">
                            <div class="kanban-header bg-primary">
                                <h5>👀 Review</h5>
                                <span class="badge badge-light" id="review-count">0</span>
                            </div>
                            <div class="kanban-tasks" id="review-tasks"></div>
                        </div>
                        
                        <div class="kanban-column" data-status="completed">
                            <div class="kanban-header bg-success">
                                <h5>✅ Completed</h5>
                                <span class="badge badge-light" id="completed-count">0</span>
                            </div>
                            <div class="kanban-tasks" id="completed-tasks"></div>
                        </div>
                        
                        <div class="kanban-column" data-status="blocked">
                            <div class="kanban-header bg-danger">
                                <h5>🚫 Blocked</h5>
                                <span class="badge badge-light" id="blocked-count">0</span>
                            </div>
                            <div class="kanban-tasks" id="blocked-tasks"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.kanban-board {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    padding: 10px 0;
}

.kanban-column {
    min-width: 280px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.kanban-header {
    padding: 15px;
    border-radius: 8px 8px 0 0;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.kanban-tasks {
    padding: 10px;
    min-height: 400px;
}

.task-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 10px;
    cursor: move;
    transition: all 0.2s;
}

.task-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.task-card.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
}

.task-title {
    font-weight: bold;
    margin-bottom: 5px;
    font-size: 14px;
}

.task-meta {
    font-size: 12px;
    color: #6c757d;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.task-progress {
    width: 100%;
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    margin: 8px 0;
}

.task-progress-bar {
    height: 100%;
    border-radius: 2px;
    transition: width 0.3s;
}

.priority-high { border-left: 4px solid #dc3545; }
.priority-medium { border-left: 4px solid #ffc107; }
.priority-low { border-left: 4px solid #28a745; }

.kanban-column.drag-over {
    background: #e3f2fd;
    border: 2px dashed #2196f3;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
let tasks = [];

document.addEventListener('DOMContentLoaded', function() {
    loadTasks();
    initializeDragAndDrop();
});

function loadTasks() {
    fetch('/ergon/api/tasks/kanban')
        .then(response => response.json())
        .then(data => {
            tasks = data.tasks || [];
            renderTasks();
        })
        .catch(error => console.error('Error loading tasks:', error));
}

function renderTasks() {
    const statuses = ['assigned', 'in_progress', 'review', 'completed', 'blocked'];
    
    statuses.forEach(status => {
        const container = document.getElementById(`${status}-tasks`);
        const statusTasks = tasks.filter(task => task.status === status);
        
        container.innerHTML = '';
        document.getElementById(`${status}-count`).textContent = statusTasks.length;
        
        statusTasks.forEach(task => {
            const taskCard = createTaskCard(task);
            container.appendChild(taskCard);
        });
    });
}

function createTaskCard(task) {
    const card = document.createElement('div');
    card.className = `task-card priority-${task.priority}`;
    card.draggable = true;
    card.dataset.taskId = task.id;
    
    const progressColor = task.progress >= 75 ? '#28a745' : 
                         task.progress >= 50 ? '#ffc107' : 
                         task.progress >= 25 ? '#fd7e14' : '#dc3545';
    
    card.innerHTML = `
        <div class="task-title">${task.title}</div>
        <div class="task-progress">
            <div class="task-progress-bar" style="width: ${task.progress}%; background: ${progressColor}"></div>
        </div>
        <div class="task-meta">
            <span>👤 ${task.assigned_to_name}</span>
            <span class="badge badge-${task.priority === 'high' ? 'danger' : task.priority === 'medium' ? 'warning' : 'info'}">${task.priority}</span>
        </div>
        <div class="task-meta">
            <small>📅 ${new Date(task.deadline).toLocaleDateString()}</small>
            <small>${task.progress}%</small>
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
                const newStatus = evt.to.parentElement.dataset.status;
                updateTaskStatus(taskId, newStatus);
            }
        });
    });
}

function updateTaskStatus(taskId, newStatus) {
    fetch(`/ergon/api/tasks/update-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: taskId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update local task data
            const task = tasks.find(t => t.id == taskId);
            if (task) {
                task.status = newStatus;
            }
            showToast('Task status updated successfully', 'success');
        } else {
            showToast('Failed to update task status', 'error');
            loadTasks(); // Reload to revert changes
        }
    })
    .catch(error => {
        console.error('Error updating task:', error);
        showToast('Error updating task status', 'error');
        loadTasks();
    });
}

function refreshBoard() {
    loadTasks();
    showToast('Board refreshed', 'info');
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

<?php include __DIR__ . '/../layouts/footer.php'; ?>
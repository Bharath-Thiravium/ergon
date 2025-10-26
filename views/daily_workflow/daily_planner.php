<div class="page-header">
    <div class="page-title">
        <h1><span>📋</span> Daily Planner</h1>
        <p>Plan, track, and update your daily tasks - <?= $data['userDept']['dept_name'] ?? 'General' ?> Department</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--primary" onclick="showAddTaskModal()">
            <span>➕</span> Add Task
        </button>
        <input type="date" id="planDate" class="form-control" value="<?= date('Y-m-d') ?>" onchange="loadTasksForDate(this.value)" style="width: auto;">
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
        </div>
        <div class="kpi-card__value"><?= count($data['todayPlans']) ?></div>
        <div class="kpi-card__label">Total Tasks</div>
        <div class="kpi-card__status">Today</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['todayPlans'], function($t) { return $t['status'] === 'completed'; })) ?></div>
        <div class="kpi-card__label">Completed</div>
        <div class="kpi-card__status">Done</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏱️</div>
        </div>
        <div class="kpi-card__value"><?= array_sum(array_column($data['todayPlans'], 'estimated_hours')) ?>h</div>
        <div class="kpi-card__label">Planned Hours</div>
        <div class="kpi-card__status">Estimated</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🎯</div>
        </div>
        <div class="kpi-card__value"><?= array_sum(array_column($data['todayPlans'], 'actual_hours')) ?>h</div>
        <div class="kpi-card__label">Actual Hours</div>
        <div class="kpi-card__status">Worked</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📝</span> My Tasks - <?= date('M j, Y') ?>
        </h2>
    </div>
    <div class="card__body">
        <?php if (empty($data['todayPlans'])): ?>
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h3>No Tasks Yet</h3>
                <p>Start your day by adding your first task.</p>
                <button class="btn btn--primary" onclick="showAddTaskModal()">
                    <span>➕</span> Add First Task
                </button>
            </div>
        <?php else: ?>
            <div id="tasksList">
                <?php foreach ($data['todayPlans'] as $task): ?>
                    <div class="task-card" data-task-id="<?= $task['id'] ?>" style="border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 1rem; margin-bottom: 1rem; <?= $task['status'] === 'completed' ? 'opacity: 0.7; background: rgba(34, 197, 94, 0.05);' : '' ?>">
                        <div class="task-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                            <div style="flex: 1;">
                                <h4 style="margin: 0; color: var(--text-primary); <?= $task['status'] === 'completed' ? 'text-decoration: line-through;' : '' ?>"><?= htmlspecialchars($task['title']) ?></h4>
                                <?php if ($task['description']): ?>
                                    <p style="margin: 0.25rem 0 0 0; color: var(--text-secondary); font-size: 0.875rem;"><?= htmlspecialchars($task['description']) ?></p>
                                <?php endif; ?>
                                <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <?php if ($task['project_name']): ?>
                                        <span class="badge badge--secondary">📁 <?= htmlspecialchars($task['project_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($task['task_category']): ?>
                                        <span class="badge badge--info">🏷️ <?= htmlspecialchars($task['task_category']) ?></span>
                                    <?php endif; ?>
                                    <span class="badge badge--<?= $task['priority'] === 'urgent' ? 'danger' : ($task['priority'] === 'high' ? 'warning' : 'success') ?>">
                                        <?= ucfirst($task['priority']) ?> Priority
                                    </span>
                                    <span class="badge badge--secondary">Est: <?= $task['estimated_hours'] ?>h</span>
                                    <?php if ($task['actual_hours'] > 0): ?>
                                        <span class="badge badge--success">Actual: <?= $task['actual_hours'] ?>h</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="task-actions" style="display: flex; gap: 0.25rem;">
                                <button class="btn btn--sm btn--secondary" onclick="editTask(<?= $task['id'] ?>)" title="Edit Task">
                                    ✏️
                                </button>
                                <button class="btn btn--sm btn--danger" onclick="deleteTask(<?= $task['id'] ?>)" title="Delete Task">
                                    🗑️
                                </button>
                            </div>
                        </div>
                        
                        <div class="task-progress" style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <label style="font-weight: 500; font-size: 0.875rem;">Progress: <span id="progress-display-<?= $task['id'] ?>"><?= $task['progress'] ?>%</span></label>
                                <select onchange="updateTaskStatus(<?= $task['id'] ?>, this.value)" style="padding: 0.25rem; border-radius: 4px; border: 1px solid var(--border-color);">
                                    <option value="pending" <?= $task['status'] === 'pending' ? 'selected' : '' ?>>📋 Pending</option>
                                    <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>⏳ In Progress</option>
                                    <option value="completed" <?= $task['status'] === 'completed' ? 'selected' : '' ?>>✅ Completed</option>
                                    <option value="blocked" <?= $task['status'] === 'blocked' ? 'selected' : '' ?>>🚫 Blocked</option>
                                </select>
                            </div>
                            <input type="range" min="0" max="100" value="<?= $task['progress'] ?>" 
                                   onchange="updateTaskProgress(<?= $task['id'] ?>, this.value)" 
                                   style="width: 100%; margin-bottom: 0.5rem;"
                                   <?= $task['status'] === 'completed' ? 'disabled' : '' ?>>
                        </div>
                        
                        <div class="task-details" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label style="font-size: 0.875rem; font-weight: 500;">Actual Hours Worked</label>
                                <input type="number" min="0" max="12" step="0.25" value="<?= $task['actual_hours'] ?>" 
                                       onchange="updateTaskHours(<?= $task['id'] ?>, this.value)"
                                       style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 4px;">
                            </div>
                            <div>
                                <label style="font-size: 0.875rem; font-weight: 500;">Notes / Blockers</label>
                                <textarea onchange="updateTaskNotes(<?= $task['id'] ?>, this.value)" 
                                          placeholder="Add completion notes or blockers..."
                                          style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 4px; resize: vertical; min-height: 60px;"><?= htmlspecialchars($task['completion_notes']) ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Task Modal -->
<div class="modal" id="taskModal">
    <div class="modal-content modal-content--large">
        <div class="modal-header">
            <h3><span>📝</span> <span id="modalTitle">Add New Task</span></h3>
            <button class="modal-close" onclick="closeTaskModal()">&times;</button>
        </div>
        <form id="taskForm">
            <div class="modal-body">
                <input type="hidden" id="taskId" name="task_id">
                
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">Task Title *</label>
                        <input type="text" id="taskTitle" name="title" class="form-control" required placeholder="What will you work on?">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select id="taskPriority" name="priority" class="form-control">
                            <option value="low">🟢 Low</option>
                            <option value="medium" selected>🟡 Medium</option>
                            <option value="high">🟠 High</option>
                            <option value="urgent">🔴 Urgent</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Project</label>
                        <select id="taskProject" name="project_name" class="form-control">
                            <option value="">Select Project</option>
                            <?php foreach ($data['projects'] as $project): ?>
                                <option value="<?= htmlspecialchars($project['name']) ?>"><?= htmlspecialchars($project['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Task Category</label>
                        <select id="taskCategory" name="task_category" class="form-control">
                            <option value="">Select Category</option>
                            <?php foreach ($data['taskCategories'] as $category): ?>
                                <option value="<?= htmlspecialchars($category['category_name']) ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="taskDescription" name="description" class="form-control" rows="3" placeholder="Describe the task in detail"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Estimated Hours</label>
                        <input type="number" id="taskHours" name="estimated_hours" class="form-control" min="0.5" max="8" step="0.5" value="1.0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" id="taskDate" name="plan_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Task Type</label>
                    <select id="taskType" name="category" class="form-control" onchange="toggleTaskType()">
                        <option value="planned">📋 Planned Task</option>
                        <option value="unplanned">⚡ Unplanned Task</option>
                    </select>
                </div>
                
                <div id="unplannedFields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Actual Hours Worked</label>
                            <input type="number" id="actualHours" name="actual_hours" class="form-control" min="0" max="12" step="0.25" value="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Progress %</label>
                            <input type="number" id="taskProgress" name="progress" class="form-control" min="0" max="100" value="100">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Completion Notes</label>
                        <textarea id="completionNotes" name="completion_notes" class="form-control" rows="2" placeholder="What was accomplished?"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeTaskModal()">Cancel</button>
                <button type="submit" class="btn btn--primary">
                    <span id="submitText">Add Task</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let isEditing = false;

function showAddTaskModal() {
    isEditing = false;
    document.getElementById('modalTitle').textContent = 'Add New Task';
    document.getElementById('submitText').textContent = 'Add Task';
    document.getElementById('taskForm').reset();
    document.getElementById('taskId').value = '';
    document.getElementById('taskDate').value = document.getElementById('planDate').value;
    document.getElementById('taskModal').style.display = 'block';
}

function editTask(taskId) {
    isEditing = true;
    document.getElementById('modalTitle').textContent = 'Edit Task';
    document.getElementById('submitText').textContent = 'Update Task';
    
    // Get task data from the card
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    // This would need to be populated with actual task data
    // For now, just show the modal
    document.getElementById('taskId').value = taskId;
    document.getElementById('taskModal').style.display = 'block';
}

function closeTaskModal() {
    document.getElementById('taskModal').style.display = 'none';
}

function toggleTaskType() {
    const taskType = document.getElementById('taskType').value;
    const unplannedFields = document.getElementById('unplannedFields');
    const estimatedHours = document.getElementById('taskHours');
    
    if (taskType === 'unplanned') {
        unplannedFields.style.display = 'block';
        estimatedHours.value = '0';
        estimatedHours.disabled = true;
    } else {
        unplannedFields.style.display = 'none';
        estimatedHours.value = '1.0';
        estimatedHours.disabled = false;
    }
}

function updateTaskProgress(taskId, progress) {
    document.getElementById(`progress-display-${taskId}`).textContent = progress + '%';
    
    // Auto-update status based on progress
    const statusSelect = document.querySelector(`[data-task-id="${taskId}"] select`);
    if (progress == 100) {
        statusSelect.value = 'completed';
        updateTaskStatus(taskId, 'completed');
    } else if (progress > 0 && statusSelect.value === 'pending') {
        statusSelect.value = 'in_progress';
        updateTaskStatus(taskId, 'in_progress');
    }
    
    saveTaskUpdate(taskId, {progress: progress});
}

function updateTaskStatus(taskId, status) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    const progressSlider = taskCard.querySelector('input[type="range"]');
    
    if (status === 'completed') {
        progressSlider.value = 100;
        progressSlider.disabled = true;
        document.getElementById(`progress-display-${taskId}`).textContent = '100%';
        taskCard.style.opacity = '0.7';
        taskCard.style.background = 'rgba(34, 197, 94, 0.05)';
        taskCard.querySelector('h4').style.textDecoration = 'line-through';
    } else {
        progressSlider.disabled = false;
        taskCard.style.opacity = '1';
        taskCard.style.background = '';
        taskCard.querySelector('h4').style.textDecoration = 'none';
    }
    
    saveTaskUpdate(taskId, {status: status, progress: progressSlider.value});
}

function updateTaskHours(taskId, hours) {
    saveTaskUpdate(taskId, {actual_hours: hours});
}

function updateTaskNotes(taskId, notes) {
    saveTaskUpdate(taskId, {completion_notes: notes});
}

function saveTaskUpdate(taskId, data) {
    const formData = new FormData();
    formData.append('task_id', taskId);
    for (const [key, value] of Object.entries(data)) {
        formData.append(key, value);
    }
    
    fetch('/ergon/daily-workflow/update-task', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Failed to update task: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function deleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task?')) {
        const formData = new FormData();
        formData.append('task_id', taskId);
        
        fetch('/ergon/daily-workflow/delete-task', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`[data-task-id="${taskId}"]`).remove();
                location.reload(); // Refresh to update stats
            } else {
                alert('Failed to delete task: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete task');
        });
    }
}

function loadTasksForDate(date) {
    window.location.href = `?date=${date}`;
}

// Handle form submission
document.getElementById('taskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const url = isEditing ? '/ergon/daily-workflow/update-task' : '/ergon/daily-workflow/add-task';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeTaskModal();
            location.reload(); // Refresh to show new/updated task
        } else {
            alert('Failed to save task: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save task');
    });
});

// Auto-save progress updates every 30 seconds
setInterval(function() {
    const taskCards = document.querySelectorAll('.task-card');
    taskCards.forEach(card => {
        const taskId = card.dataset.taskId;
        const progress = card.querySelector('input[type="range"]').value;
        const hours = card.querySelector('input[type="number"]').value;
        const notes = card.querySelector('textarea').value;
        
        if (progress > 0 || hours > 0 || notes.trim()) {
            saveTaskUpdate(taskId, {
                progress: progress,
                actual_hours: hours,
                completion_notes: notes
            });
        }
    });
}, 30000);
</script>
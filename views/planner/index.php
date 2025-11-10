<?php
$title = 'Daily Planner';
$active_page = 'planner';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📅</span> Daily Planner</h1>
        <p>Plan your day - <?= date('M d, Y', strtotime($current_date)) ?></p>
    </div>
    <div class="page-actions">
        <input type="date" id="plannerDate" value="<?= $current_date ?>" onchange="changePlannerDate(this.value)" class="form-control" style="width: auto; margin-right: 1rem;">
        <a href="/ergon/planner/create?date=<?= $current_date ?>" class="btn btn--primary">
            <span>➕</span> Add Task
        </a>
        <button class="btn btn--secondary" onclick="showAddTaskModal()">
            <span>⚡</span> Quick Add
        </button>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert--success">
        <?php if ($_GET['success'] === '1'): ?>
            <strong>✅ Success!</strong> Task has been added to your planner.
        <?php elseif ($_GET['success'] === 'updated'): ?>
            <strong>✅ Success!</strong> Task has been updated.
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert--danger">
        <?php if ($_GET['error'] === 'invalid_data'): ?>
            <strong>❌ Error:</strong> Invalid data provided.
        <?php elseif ($_GET['error'] === 'update_failed'): ?>
            <strong>❌ Error:</strong> Failed to update task.
        <?php elseif ($_GET['error'] === 'database_error'): ?>
            <strong>❌ Error:</strong> Database error occurred.
        <?php else: ?>
            <strong>❌ Error:</strong> An error occurred. Please try again.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="dashboard-grid" style="grid-template-columns: 2fr 1fr;">
    <!-- Today's Plan -->
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>📋</span> Today's Plan
            </h2>
        </div>
        <div class="card__body">
            <?php if (empty($planned_tasks)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📅</div>
                    <h3>No tasks planned</h3>
                    <p>Add tasks from your assigned work or create personal tasks.</p>
                </div>
            <?php else: ?>
                <div id="plannedTasksList">
                    <?php foreach ($planned_tasks as $task): ?>
                        <div class="task-item" data-id="<?= $task['id'] ?>">
                            <div class="task-header">
                                <div class="task-info">
                                    <h4><?= htmlspecialchars($task['title']) ?></h4>
                                    <span class="task-type badge badge--<?= $task['task_type'] === 'assigned' ? 'info' : 'secondary' ?>">
                                        <?= $task['task_type'] === 'assigned' ? '📋 Assigned' : '📝 Personal' ?>
                                    </span>
                                </div>
                                <div class="task-actions">
                                    <select onchange="updateTaskStatus(<?= $task['id'] ?>, this.value)" class="form-control form-control--sm">
                                        <option value="planned" <?= $task['status'] === 'planned' ? 'selected' : '' ?>>📋 Planned</option>
                                        <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : '' ?>>⏳ In Progress</option>
                                        <option value="completed" <?= $task['status'] === 'completed' ? 'selected' : '' ?>>✅ Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="task-details">
                                <?php if ($task['description']): ?>
                                    <p><?= htmlspecialchars($task['description']) ?></p>
                                <?php endif; ?>
                                <div class="task-meta">
                                    <?php if ($task['planned_start_time']): ?>
                                        <span>🕐 <?= date('H:i', strtotime($task['planned_start_time'])) ?></span>
                                    <?php endif; ?>
                                    <span>⏱️ <?= $task['planned_duration'] ?>min</span>
                                    <?php if ($task['deadline']): ?>
                                        <span>📅 Due: <?= date('M d', strtotime($task['deadline'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Available Tasks -->
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>📥</span> Available Tasks
            </h2>
        </div>
        <div class="card__body">
            <?php if (empty($available_tasks)): ?>
                <p class="text-muted">No pending assigned tasks</p>
            <?php else: ?>
                <?php foreach ($available_tasks as $task): ?>
                    <div class="available-task" onclick="addAssignedTaskToPlan(<?= $task['id'] ?>, '<?= htmlspecialchars($task['title']) ?>')">
                        <h5><?= htmlspecialchars($task['title']) ?></h5>
                        <div class="task-meta">
                            <span class="badge badge--<?= $task['priority'] === 'high' ? 'danger' : ($task['priority'] === 'medium' ? 'warning' : 'secondary') ?>">
                                <?= ucfirst($task['priority']) ?>
                            </span>
                            <?php if ($task['deadline']): ?>
                                <span>📅 <?= date('M d', strtotime($task['deadline'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div id="addTaskModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Task to Plan</h3>
            <button onclick="closeAddTaskModal()" class="btn btn--secondary">×</button>
        </div>
        <form id="addTaskForm">
            <div class="form-group">
                <label>Task Type</label>
                <select id="taskType" name="task_type" class="form-control" onchange="toggleTaskFields()">
                    <option value="personal">📝 Personal Task</option>
                    <option value="assigned">📋 From Assigned Tasks</option>
                </select>
            </div>
            <div class="form-group" id="titleGroup">
                <label>Title</label>
                <input type="text" id="taskTitle" name="title" class="form-control" required>
            </div>
            <div class="form-group" id="descriptionGroup">
                <label>Description</label>
                <textarea id="taskDescription" name="description" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Start Time</label>
                <input type="time" id="startTime" name="planned_start_time" class="form-control">
            </div>
            <div class="form-group">
                <label>Duration (minutes)</label>
                <input type="number" id="duration" name="planned_duration" class="form-control" value="60" min="15" max="480">
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn--primary">Add to Plan</button>
                <button type="button" onclick="closeAddTaskModal()" class="btn btn--secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function changePlannerDate(date) {
    window.location.href = '/ergon/planner?date=' + date;
}

function showAddTaskModal() {
    document.getElementById('addTaskModal').style.display = 'flex';
}

function closeAddTaskModal() {
    document.getElementById('addTaskModal').style.display = 'none';
    document.getElementById('addTaskForm').reset();
}

function toggleTaskFields() {
    const taskType = document.getElementById('taskType').value;
    const titleGroup = document.getElementById('titleGroup');
    const descriptionGroup = document.getElementById('descriptionGroup');
    
    if (taskType === 'assigned') {
        titleGroup.style.display = 'none';
        descriptionGroup.style.display = 'none';
    } else {
        titleGroup.style.display = 'block';
        descriptionGroup.style.display = 'block';
    }
}

function addAssignedTaskToPlan(taskId, title) {
    const formData = new FormData();
    formData.append('task_id', taskId);
    formData.append('task_type', 'assigned');
    formData.append('title', title);
    formData.append('date', '<?= $current_date ?>');
    
    fetch('/ergon/planner/add-task', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function updateTaskStatus(plannerId, status) {
    const formData = new FormData();
    formData.append('planner_id', plannerId);
    formData.append('status', status);
    
    fetch('/ergon/planner/update-status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Error updating status');
        }
    });
}

document.getElementById('addTaskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('date', '<?= $current_date ?>');
    
    fetch('/ergon/planner/add-task', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAddTaskModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
});
</script>

<style>
.task-item {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    background: #f9fafb;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.task-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.available-task {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.available-task:hover {
    background-color: #f3f4f6;
}

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
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    width: 90%;
    max-width: 500px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.modal-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    margin-top: 1rem;
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    border: 1px solid;
}

.alert--success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert--danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.task-item {
    transition: all 0.2s ease;
}

.task-item:hover {
    border-color: #007cba;
    box-shadow: 0 2px 8px rgba(0, 124, 186, 0.1);
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
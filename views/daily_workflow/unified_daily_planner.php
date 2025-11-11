<?php
$content = ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="bi bi-calendar-day"></i> Daily Planner</h1>
        <p>Plan and track your daily tasks - <?= date('l, F j, Y', strtotime($selected_date)) ?></p>
    </div>
    <div class="page-actions">
        <input type="date" id="dateSelector" value="<?= $selected_date ?>" onchange="changeDate(this.value)" class="form-control" style="width: auto; display: inline-block;">
        <a href="/ergon/workflow/create-task" class="btn btn--primary">
            <i class="bi bi-plus-circle"></i> Add Task
        </a>
    </div>
</div>

<div class="planner-grid">
    <!-- Morning Planning Section -->
    <div class="card">
        <div class="card__header">
            <h3 class="card__title"><i class="bi bi-sunrise"></i> Today's Plan</h3>
            <span class="badge badge--info"><?= count($planned_tasks) ?> tasks</span>
        </div>
        <div class="card__body">
            <?php if (empty($planned_tasks)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <h4>No tasks planned for today</h4>
                    <p>Start by adding tasks to your daily planner</p>
                    <a href="/ergon/workflow/create-task?planned_date=<?= $selected_date ?>" class="btn btn--primary">
                        <i class="bi bi-plus"></i> Plan First Task
                    </a>
                </div>
            <?php else: ?>
                <div class="task-timeline">
                    <?php foreach ($planned_tasks as $task): ?>
                        <div class="task-item" data-task-id="<?= $task['id'] ?>">
                            <div class="task-time">
                                <?= $task['planned_start_time'] ? date('H:i', strtotime($task['planned_start_time'])) : 'Flexible' ?>
                            </div>
                            <div class="task-content">
                                <div class="task-header">
                                    <h4 class="task-title"><?= htmlspecialchars($task['title']) ?></h4>
                                    <div class="task-badges">
                                        <?php if ($task['priority']): ?>
                                            <span class="badge badge--<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span>
                                        <?php endif; ?>
                                        <span class="badge badge--<?= $task['completion_status'] ?? 'not_started' ?>">
                                            <?= ucfirst(str_replace('_', ' ', $task['completion_status'] ?? 'not_started')) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if ($task['description']): ?>
                                    <p class="task-description"><?= htmlspecialchars($task['description']) ?></p>
                                <?php endif; ?>
                                <div class="task-actions">
                                    <button class="btn btn--sm btn--success" onclick="updateTaskStatus(<?= $task['id'] ?>, 'in_progress')">
                                        <i class="bi bi-play"></i> Start
                                    </button>
                                    <button class="btn btn--sm btn--primary" onclick="updateTaskStatus(<?= $task['id'] ?>, 'completed')">
                                        <i class="bi bi-check"></i> Complete
                                    </button>
                                    <button class="btn btn--sm btn--warning" onclick="updateTaskStatus(<?= $task['id'] ?>, 'postponed')">
                                        <i class="bi bi-pause"></i> Postpone
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="card">
        <div class="card__header">
            <h3 class="card__title"><i class="bi bi-graph-up"></i> Today's Progress</h3>
        </div>
        <div class="card__body">
            <?php
            $totalTasks = count($planned_tasks);
            $completedTasks = array_filter($planned_tasks, fn($task) => ($task['completion_status'] ?? '') === 'completed');
            $inProgressTasks = array_filter($planned_tasks, fn($task) => ($task['completion_status'] ?? '') === 'in_progress');
            $completionRate = $totalTasks > 0 ? (count($completedTasks) / $totalTasks) * 100 : 0;
            ?>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?= count($completedTasks) ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= count($inProgressTasks) ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $totalTasks ?></div>
                    <div class="stat-label">Total Tasks</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= round($completionRate) ?>%</div>
                    <div class="stat-label">Completion</div>
                </div>
            </div>

            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $completionRate ?>%"></div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Task Modal -->
<div id="quickTaskModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Quick Add Task</h3>
            <button class="modal-close" onclick="closeQuickTaskModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="quickTaskForm">
                <div class="form-group">
                    <label for="quickTitle">Task Title</label>
                    <input type="text" id="quickTitle" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="quickTime">Planned Time</label>
                    <input type="time" id="quickTime" name="planned_time" class="form-control">
                </div>
                <div class="form-group">
                    <label for="quickPriority">Priority</label>
                    <select id="quickPriority" name="priority" class="form-control">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Add Task</button>
                    <button type="button" class="btn btn--secondary" onclick="closeQuickTaskModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function changeDate(newDate) {
    window.location.href = `/ergon/workflow/daily-planner/${newDate}`;
}

function updateTaskStatus(taskId, status) {
    fetch('/ergon/api/update-task-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: taskId,
            status: status,
            date: '<?= $selected_date ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update task status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating task status');
    });
}

function openQuickTaskModal() {
    document.getElementById('quickTaskModal').style.display = 'flex';
}

function closeQuickTaskModal() {
    document.getElementById('quickTaskModal').style.display = 'none';
}

document.getElementById('quickTaskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('planned_date', '<?= $selected_date ?>');
    
    fetch('/ergon/workflow/quick-add-task', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to add task');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding task');
    });
});
</script>

<style>
.planner-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-top: 1rem;
}

.task-timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.task-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: var(--bg-primary);
    transition: all 0.2s ease;
}

.task-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.task-time {
    flex-shrink: 0;
    width: 60px;
    font-weight: 600;
    color: var(--primary);
    text-align: center;
    padding: 0.5rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.task-content {
    flex: 1;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.task-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.task-badges {
    display: flex;
    gap: 0.5rem;
}

.task-description {
    margin: 0.5rem 0;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.task-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
}

.stat-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: var(--bg-secondary);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--success), var(--primary));
    transition: width 0.3s ease;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
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
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
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

@media (max-width: 768px) {
    .planner-grid {
        grid-template-columns: 1fr;
    }
    
    .task-item {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .task-time {
        width: auto;
        text-align: left;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .task-actions {
        flex-wrap: wrap;
    }
}
</style>

<?php
$content = ob_get_clean();
$title = 'Daily Planner';
$active_page = 'daily-planner';
include __DIR__ . '/../layouts/dashboard.php';
?>
<?php
$content = ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="bi bi-moon-stars"></i> Evening Update</h1>
        <p>Review your day and update task completion - <?= date('l, F j, Y', strtotime($selected_date)) ?></p>
    </div>
    <div class="page-actions">
        <input type="date" id="dateSelector" value="<?= $selected_date ?>" onchange="changeDate(this.value)" class="form-control" style="width: auto; display: inline-block;">
    </div>
</div>

<form id="eveningUpdateForm" method="POST" action="/ergon/workflow/evening-update/<?= $selected_date ?>">
    <div class="update-grid">
        <!-- Task Completion Section -->
        <div class="card">
            <div class="card__header">
                <h3 class="card__title"><i class="bi bi-check-square"></i> Today's Task Updates</h3>
                <span class="badge badge--info"><?= count($today_tasks) ?> tasks</span>
            </div>
            <div class="card__body">
                <?php if (empty($today_tasks)): ?>
                    <div class="empty-state">
                        <i class="bi bi-calendar-x"></i>
                        <h4>No tasks planned for today</h4>
                        <p>You didn't have any tasks scheduled for today</p>
                    </div>
                <?php else: ?>
                    <div class="task-updates">
                        <?php foreach ($today_tasks as $task): ?>
                            <div class="task-update-item">
                                <div class="task-info">
                                    <h4 class="task-title"><?= htmlspecialchars($task['title']) ?></h4>
                                    <?php if ($task['description']): ?>
                                        <p class="task-description"><?= htmlspecialchars($task['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="task-update-controls">
                                    <div class="form-group">
                                        <label>Completion Status</label>
                                        <select name="task_updates[<?= $task['id'] ?>][completion_status]" class="form-control">
                                            <option value="not_started" <?= ($task['completion_status'] ?? '') === 'not_started' ? 'selected' : '' ?>>Not Started</option>
                                            <option value="in_progress" <?= ($task['completion_status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                            <option value="completed" <?= ($task['completion_status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="postponed" <?= ($task['completion_status'] ?? '') === 'postponed' ? 'selected' : '' ?>>Postponed</option>
                                        </select>
                                    </div>
                                    
                                    <?php if ($task['task_id']): ?>
                                        <div class="form-group">
                                            <label>Progress %</label>
                                            <input type="range" name="task_updates[<?= $task['id'] ?>][progress]" 
                                                   min="0" max="100" value="<?= $task['task_progress'] ?? 0 ?>" 
                                                   class="form-control progress-slider"
                                                   oninput="updateProgressDisplay(this)">
                                            <span class="progress-display"><?= $task['task_progress'] ?? 0 ?>%</span>
                                            <input type="hidden" name="task_updates[<?= $task['id'] ?>][task_id]" value="<?= $task['task_id'] ?>">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="form-group">
                                        <label>Notes</label>
                                        <textarea name="task_updates[<?= $task['id'] ?>][notes]" 
                                                  class="form-control" rows="2" 
                                                  placeholder="Any notes about this task..."><?= htmlspecialchars($task['notes'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Daily Reflection Section -->
        <div class="card">
            <div class="card__header">
                <h3 class="card__title"><i class="bi bi-journal-text"></i> Daily Reflection</h3>
            </div>
            <div class="card__body">
                <div class="form-group">
                    <label for="title" class="form-label">Update Title</label>
                    <input type="text" id="title" name="title" class="form-control" 
                           value="<?= htmlspecialchars($existing_update['title'] ?? 'Daily Update - ' . date('M j, Y', strtotime($selected_date))) ?>" 
                           placeholder="Daily Update Title">
                </div>

                <div class="form-group">
                    <label for="accomplishments" class="form-label">
                        <i class="bi bi-trophy"></i> What did you accomplish today?
                    </label>
                    <textarea id="accomplishments" name="accomplishments" class="form-control" rows="4" 
                              placeholder="List your key accomplishments and completed tasks..."><?= htmlspecialchars($existing_update['accomplishments'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="challenges" class="form-label">
                        <i class="bi bi-exclamation-triangle"></i> What challenges did you face?
                    </label>
                    <textarea id="challenges" name="challenges" class="form-control" rows="4" 
                              placeholder="Describe any obstacles, blockers, or difficulties..."><?= htmlspecialchars($existing_update['challenges'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="tomorrow_plan" class="form-label">
                        <i class="bi bi-calendar-plus"></i> What's your plan for tomorrow?
                    </label>
                    <textarea id="tomorrow_plan" name="tomorrow_plan" class="form-control" rows="4" 
                              placeholder="Outline your priorities and goals for tomorrow..."><?= htmlspecialchars($existing_update['tomorrow_plan'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="overall_productivity" class="form-label">
                        <i class="bi bi-speedometer2"></i> Overall Productivity (1-10)
                    </label>
                    <div class="productivity-slider">
                        <input type="range" id="overall_productivity" name="overall_productivity" 
                               min="1" max="10" value="<?= $existing_update['overall_productivity'] ?? 5 ?>" 
                               class="form-control" oninput="updateProductivityDisplay(this.value)">
                        <div class="productivity-labels">
                            <span>Low</span>
                            <span id="productivityValue"><?= $existing_update['overall_productivity'] ?? 5 ?></span>
                            <span>High</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">
            <i class="bi bi-save"></i> Save Evening Update
        </button>
        <a href="/ergon/workflow/daily-planner/<?= date('Y-m-d', strtotime($selected_date . ' +1 day')) ?>" class="btn btn--success">
            <i class="bi bi-arrow-right"></i> Plan Tomorrow
        </a>
    </div>
</form>

<script>
function changeDate(newDate) {
    window.location.href = `/ergon/workflow/evening-update/${newDate}`;
}

function updateProgressDisplay(slider) {
    const display = slider.parentNode.querySelector('.progress-display');
    display.textContent = slider.value + '%';
}

function updateProductivityDisplay(value) {
    document.getElementById('productivityValue').textContent = value;
}

// Auto-save functionality
let autoSaveTimeout;
document.getElementById('eveningUpdateForm').addEventListener('input', function() {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => {
        // Auto-save logic here if needed
        console.log('Auto-saving...');
    }, 2000);
});

// Form submission
document.getElementById('eveningUpdateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.text();
        }
        throw new Error('Network response was not ok');
    })
    .then(data => {
        // Check if it's a redirect
        if (data.includes('success=')) {
            window.location.href = this.action + '?success=Update saved successfully';
        } else {
            alert('Update saved successfully!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving update. Please try again.');
    });
});
</script>

<style>
.update-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-top: 1rem;
}

.task-updates {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.task-update-item {
    padding: 1.5rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: var(--bg-secondary);
}

.task-info {
    margin-bottom: 1rem;
}

.task-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.task-description {
    margin: 0;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.task-update-controls {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.task-update-controls .form-group:last-child {
    grid-column: 1 / -1;
}

.progress-slider {
    margin-bottom: 0.5rem;
}

.progress-display {
    font-weight: 600;
    color: var(--primary);
}

.productivity-slider {
    position: relative;
}

.productivity-labels {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.5rem;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

#productivityValue {
    font-weight: 600;
    font-size: 1.2rem;
    color: var(--primary);
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
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

@media (max-width: 768px) {
    .update-grid {
        grid-template-columns: 1fr;
    }
    
    .task-update-controls {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<?php
$content = ob_get_clean();
$title = 'Evening Update';
$active_page = 'evening-update';
include __DIR__ . '/../layouts/dashboard.php';
?>
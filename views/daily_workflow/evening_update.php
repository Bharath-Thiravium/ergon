<?php
$title = 'Evening Update';
$active_page = 'daily-workflow';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🌆</span> Evening Progress Update</h1>
        <p>Update your task progress and add unplanned work</p>
    </div>
    <div class="page-actions">
        <?php if ($data['canUpdate']): ?>
            <span class="badge badge--warning">⏳ Pending Update</span>
        <?php else: ?>
            <span class="badge badge--success">✅ Updated</span>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($data['message'])): ?>
<div class="alert <?= strpos($data['message'], 'Error') === 0 ? 'alert--danger' : 'alert--success' ?>" style="margin-bottom: 1rem;">
    <?= htmlspecialchars($data['message']) ?>
</div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
        </div>
        <div class="kpi-card__value"><?= count($data['todayPlans']) ?></div>
        <div class="kpi-card__label">Planned Tasks</div>
        <div class="kpi-card__status">Today</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['todayPlans'], function($p) { return $p['status'] === 'completed'; })) ?></div>
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
</div>

<?php if ($data['canUpdate']): ?>
<form method="POST" action="/ergon/evening-update/submit" id="eveningUpdateForm">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>📝</span> Update Planned Tasks
            </h2>
        </div>
        <div class="card__body">
            <?php foreach ($data['todayPlans'] as $plan): ?>
                <div class="task-update-row" style="border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 1rem; margin-bottom: 1rem;">
                    <div class="task-header" style="margin-bottom: 1rem;">
                        <h4 style="margin: 0; color: var(--text-primary);"><?= htmlspecialchars($plan['title']) ?></h4>
                        <p style="margin: 0.25rem 0 0 0; color: var(--text-secondary); font-size: 0.875rem;">
                            <?= htmlspecialchars($plan['description']) ?>
                        </p>
                        <div style="margin-top: 0.5rem;">
                            <span class="badge badge--<?= $plan['priority'] === 'urgent' ? 'danger' : ($plan['priority'] === 'high' ? 'warning' : 'success') ?>">
                                <?= ucfirst($plan['priority']) ?> Priority
                            </span>
                            <span class="badge badge--secondary">Est: <?= $plan['estimated_hours'] ?>h</span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Progress %</label>
                            <input type="range" name="updates[<?= $plan['id'] ?>][progress]" class="form-control" min="0" max="100" value="<?= $plan['progress'] ?>" oninput="updateProgressDisplay(this)">
                            <div class="progress-display" style="text-align: center; font-weight: bold; margin-top: 0.25rem;"><?= $plan['progress'] ?>%</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="updates[<?= $plan['id'] ?>][status]" class="form-control">
                                <option value="pending" <?= $plan['status'] === 'pending' ? 'selected' : '' ?>>📋 Pending</option>
                                <option value="in_progress" <?= $plan['status'] === 'in_progress' ? 'selected' : '' ?>>⏳ In Progress</option>
                                <option value="completed" <?= $plan['status'] === 'completed' ? 'selected' : '' ?>>✅ Completed</option>
                                <option value="blocked" <?= $plan['status'] === 'blocked' ? 'selected' : '' ?>>🚫 Blocked</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Actual Hours</label>
                            <input type="number" name="updates[<?= $plan['id'] ?>][actual_hours]" class="form-control" min="0" max="12" step="0.25" value="<?= $plan['actual_hours'] ?>" placeholder="0.0">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Completion Notes / Blockers</label>
                        <textarea name="updates[<?= $plan['id'] ?>][completion_notes]" class="form-control" rows="2" placeholder="What did you accomplish? Any blockers or issues?"><?= htmlspecialchars($plan['completion_notes'] ?? '') ?></textarea>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>➕</span> Unplanned Tasks
            </h2>
        </div>
        <div class="card__body">
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                Add any tasks you worked on that weren't in your morning plan.
            </p>
            
            <div id="unplannedTasks">
                <div class="unplanned-task-row" style="border: 1px dashed var(--border-color); border-radius: var(--border-radius); padding: 1rem; margin-bottom: 1rem;">
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label class="form-label">Task Title</label>
                            <input type="text" name="unplanned_tasks[0][title]" class="form-control" placeholder="What unplanned task did you work on?">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <select name="unplanned_tasks[0][department_id]" class="form-control department-select" onchange="loadUnplannedCategories(this, 0)">
                                <option value="">Select Dept</option>
                                <?php if (!empty($data['departments'])): ?>
                                    <?php foreach ($data['departments'] as $dept): ?>
                                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="unplanned_tasks[0][task_category]" class="form-control category-select">
                                <option value="">Select Category</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Hours Spent</label>
                            <input type="number" name="unplanned_tasks[0][actual_hours]" class="form-control" min="0" max="8" step="0.25" placeholder="0.0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="unplanned_tasks[0][status]" class="form-control">
                                <option value="completed">✅ Completed</option>
                                <option value="in_progress">⏳ In Progress</option>
                                <option value="blocked">🚫 Blocked</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Action</label>
                            <button type="button" class="btn btn--danger btn--sm" onclick="removeUnplannedTask(this)">🗑️</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="unplanned_tasks[0][description]" class="form-control" rows="2" placeholder="Brief description of the unplanned task"></textarea>
                    </div>
                </div>
            </div>
            
            <button type="button" class="btn btn--secondary" onclick="addUnplannedTask()">
                <span>➕</span> Add Another Unplanned Task
            </button>
        </div>
    </div>
    
    <div class="form-actions" style="margin-top: 2rem; padding: 1.5rem; background: var(--bg-secondary); border-radius: var(--border-radius);">
        <div style="text-align: center;">
            <button type="submit" class="btn btn--primary" style="font-size: 1.1rem; padding: 0.75rem 2rem;">
                <span>📤</span> Submit Evening Update
            </button>
            <p style="margin: 0.5rem 0 0 0; color: var(--text-secondary); font-size: 0.875rem;">
                This will complete your daily workflow and update the progress dashboard.
            </p>
        </div>
    </div>
</form>

<?php else: ?>
<div class="card">
    <div class="card__body">
        <div class="alert alert--success">
            <strong>✅ Evening Update Completed</strong> 
            Your progress was updated at <?= date('g:i A', strtotime($data['workflowStatus']['evening_updated_at'])) ?>.
            Great work today!
        </div>
        
        <div class="dashboard-grid">
            <div class="kpi-card">
                <div class="kpi-card__header">
                    <div class="kpi-card__icon">📊</div>
                </div>
                <div class="kpi-card__value"><?= round($data['workflowStatus']['productivity_score']) ?>%</div>
                <div class="kpi-card__label">Productivity Score</div>
                <div class="kpi-card__status">Today</div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-card__header">
                    <div class="kpi-card__icon">✅</div>
                </div>
                <div class="kpi-card__value"><?= $data['workflowStatus']['total_completed_tasks'] ?></div>
                <div class="kpi-card__label">Tasks Completed</div>
                <div class="kpi-card__status">Done</div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-card__header">
                    <div class="kpi-card__icon">⏱️</div>
                </div>
                <div class="kpi-card__value"><?= $data['workflowStatus']['total_actual_hours'] ?>h</div>
                <div class="kpi-card__label">Hours Worked</div>
                <div class="kpi-card__status">Actual</div>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="/ergon/dashboard" class="btn btn--primary">
                <span>🏠</span> Back to Dashboard
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
let unplannedTaskIndex = 1;

function updateProgressDisplay(slider) {
    const display = slider.parentNode.querySelector('.progress-display');
    display.textContent = slider.value + '%';
    
    // Auto-update status based on progress
    const taskRow = slider.closest('.task-update-row');
    const statusSelect = taskRow.querySelector('select[name*="[status]"]');
    if (slider.value == 100) {
        statusSelect.value = 'completed';
    } else if (slider.value > 0) {
        statusSelect.value = 'in_progress';
    }
}

function addUnplannedTask() {
    const container = document.getElementById('unplannedTasks');
    if (!container) return;
    const newTask = document.createElement('div');
    newTask.className = 'unplanned-task-row';
    newTask.style.cssText = 'border: 1px dashed var(--border-color); border-radius: var(--border-radius); padding: 1rem; margin-bottom: 1rem;';
    newTask.innerHTML = `
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label class="form-label">Task Title</label>
                <input type="text" name="unplanned_tasks[${unplannedTaskIndex}][title]" class="form-control" placeholder="What unplanned task did you work on?">
            </div>
            <div class="form-group">
                <label class="form-label">Department</label>
                <select name="unplanned_tasks[${unplannedTaskIndex}][department_id]" class="form-control department-select" onchange="loadUnplannedCategories(this, ${unplannedTaskIndex})">
                    <option value="">Select Dept</option>
                    <?php if (!empty($data['departments'])): ?>
                        <?php foreach ($data['departments'] as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="unplanned_tasks[${unplannedTaskIndex}][task_category]" class="form-control category-select">
                    <option value="">Select Category</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Hours Spent</label>
                <input type="number" name="unplanned_tasks[${unplannedTaskIndex}][actual_hours]" class="form-control" min="0" max="8" step="0.25" placeholder="0.0">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="unplanned_tasks[${unplannedTaskIndex}][status]" class="form-control">
                    <option value="completed">✅ Completed</option>
                    <option value="in_progress">⏳ In Progress</option>
                    <option value="blocked">🚫 Blocked</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Action</label>
                <button type="button" class="btn btn--danger btn--sm" onclick="removeUnplannedTask(this)">🗑️</button>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="unplanned_tasks[${unplannedTaskIndex}][description]" class="form-control" rows="2" placeholder="Brief description of the unplanned task"></textarea>
        </div>
    `;
    container.appendChild(newTask);
    unplannedTaskIndex++;
}

function removeUnplannedTask(button) {
    const taskRow = button.closest('.unplanned-task-row');
    taskRow.remove();
}

function loadUnplannedCategories(deptSelect, index) {
    const categorySelect = deptSelect.closest('.unplanned-task-row').querySelector('.category-select');
    const deptId = deptSelect.value;
    
    categorySelect.innerHTML = '<option value="">Select Category</option>';
    
    if (!deptId) return;
    
    fetch(`/ergon/api/task-categories?department_id=${deptId}`)
        .then(response => response.json())
        .then(data => {
            if (data.categories) {
                data.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.category_name;
                    option.textContent = category.category_name;
                    categorySelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading categories:', error);
        });
}

// Initialize progress displays
document.addEventListener('DOMContentLoaded', function() {
    const sliders = document.querySelectorAll('input[type="range"]');
    if (sliders.length > 0) {
        sliders.forEach(slider => {
            updateProgressDisplay(slider);
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
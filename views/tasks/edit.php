<?php
$content = ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="bi bi-pencil-square"></i> Edit Task</h1>
        <p>Update task details and tracking information</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/tasks" class="btn btn--secondary">
            <i class="bi bi-arrow-left"></i> Back to Tasks
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title"><i class="bi bi-clipboard-check-fill"></i> Task Details</h2>
    </div>
    <div class="card__body">
        <form id="editTaskForm" method="POST">
            <div class="form-row">
                <div class="form-group col-md-8">
                    <label for="title" class="form-label"><i class="bi bi-card-text"></i> Task Title *</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($task['title'] ?? '') ?>" required placeholder="Enter task title">
                </div>
                <div class="form-group col-md-4">
                    <label for="task_type" class="form-label">
                        <i class="bi bi-tag-fill"></i> Task Type 
                        <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Choose the type of task based on how it should be tracked and managed"></i>
                    </label>
                    <select class="form-control" id="task_type" name="task_type">
                        <option value="ad-hoc" <?= ($task['task_type'] ?? 'ad-hoc') === 'ad-hoc' ? 'selected' : '' ?>>Ad-hoc - General single task</option>
                        <option value="checklist" <?= ($task['task_type'] ?? '') === 'checklist' ? 'selected' : '' ?>>Checklist - Multiple sub-items to complete</option>
                        <option value="milestone" <?= ($task['task_type'] ?? '') === 'milestone' ? 'selected' : '' ?>>Milestone - Important project checkpoint</option>
                        <option value="timed" <?= ($task['task_type'] ?? '') === 'timed' ? 'selected' : '' ?>>Timed - Time-bound with strict deadline</option>
                    </select>
                    <small class="form-text text-muted">
                        <strong>Ad-hoc:</strong> Regular tasks • <strong>Checklist:</strong> Multiple steps • <strong>Milestone:</strong> Key deliverable • <strong>Timed:</strong> Urgent deadline
                    </small>
                </div>
            </div>

            <div class="form-group">
                <label for="description" class="form-label"><i class="bi bi-file-text-fill"></i> Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" placeholder="Provide detailed task description, requirements, and expected outcomes"><?= htmlspecialchars($task['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="department_id" class="form-label">
                        <i class="bi bi-building"></i> Department 
                        <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Select the department responsible for this task"></i>
                    </label>
                    <select class="form-control" id="department_id" name="department_id" onchange="loadTaskCategories()">
                        <option value="">Select Department</option>
                        <?php if (!empty($departments)): ?>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= ($task['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <small class="form-text text-muted">Choose department to filter relevant task categories</small>
                </div>
                <div class="form-group col-md-6">
                    <label for="task_category" class="form-label">
                        <i class="bi bi-tags-fill"></i> Task Category 
                        <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Specific type of work within the selected department"></i>
                    </label>
                    <select class="form-control" id="task_category" name="task_category">
                        <option value="">Select Category</option>
                        <!-- Will be populated based on department -->
                    </select>
                    <small class="form-text text-muted">Categories will load after selecting department</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="assigned_to" class="form-label"><i class="bi bi-person-fill"></i> Assign To *</label>
                    <select class="form-control" id="assigned_to" name="assigned_to" required>
                        <option value="">Select User</option>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= ($task['assigned_to'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="priority" class="form-label">
                        <i class="bi bi-exclamation-triangle-fill"></i> Priority 
                        <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Task urgency level - affects notification and assignment order"></i>
                    </label>
                    <select class="form-control" id="priority" name="priority">
                        <option value="low" <?= ($task['priority'] ?? '') === 'low' ? 'selected' : '' ?>>🟢 Low - Can wait, flexible timing</option>
                        <option value="medium" <?= ($task['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>🟡 Medium - Normal business priority</option>
                        <option value="high" <?= ($task['priority'] ?? '') === 'high' ? 'selected' : '' ?>>🔴 High - Urgent, needs immediate attention</option>
                    </select>
                    <small class="form-text text-muted">Higher priority tasks appear first in lists</small>
                </div>
                <div class="form-group col-md-4">
                    <label for="deadline" class="form-label">
                        <i class="bi bi-calendar-event-fill"></i> Due Date 
                        <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Target completion date - leave empty for no specific deadline"></i>
                    </label>
                    <input type="date" class="form-control" id="deadline" name="deadline" value="<?= $task['deadline'] ?? '' ?>">
                    <small class="form-text text-muted">Optional - when task should be completed</small>
                </div>
                <div class="form-group col-md-4">
                    <label for="sla_hours" class="form-label">
                        <i class="bi bi-clock-fill"></i> SLA Hours 
                        <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Service Level Agreement - maximum hours allowed to complete this task"></i>
                    </label>
                    <input type="number" class="form-control" id="sla_hours" name="sla_hours" value="<?= $task['sla_hours'] ?? '24' ?>" min="1" max="720">
                    <small class="form-text text-muted">Max hours: 1-720 (1 hour to 30 days)</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="status" class="form-label">
                        <i class="bi bi-flag-fill"></i> Status 
                        <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="Current status of the task"></i>
                    </label>
                    <select class="form-control" id="status" name="status">
                        <option value="assigned" <?= ($task['status'] ?? '') === 'assigned' ? 'selected' : '' ?>>📋 Assigned - Ready to start</option>
                        <option value="in_progress" <?= ($task['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>⚡ In Progress - Currently working</option>
                        <option value="completed" <?= ($task['status'] ?? '') === 'completed' ? 'selected' : '' ?>>✅ Completed - Task finished</option>
                        <option value="blocked" <?= ($task['status'] ?? '') === 'blocked' ? 'selected' : '' ?>>🚫 Blocked - Cannot proceed</option>
                        <option value="on_hold" <?= ($task['status'] ?? '') === 'on_hold' ? 'selected' : '' ?>>⏸️ On Hold - Temporarily paused</option>
                    </select>
                    <small class="form-text text-muted">Current task status and progress</small>
                </div>
                <div class="form-group col-md-6">
                    <label for="progress" class="form-label">
                        <i class="bi bi-percent"></i> Progress 
                        <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="How much of the task is completed (0-100%)"></i>
                    </label>
                    <div class="input-group">
                        <input type="range" class="form-control" id="progress" name="progress" min="0" max="100" value="<?= $task['progress'] ?? '0' ?>" oninput="updateProgressValue(this.value)">
                        <div class="input-group-append">
                            <span class="input-group-text" id="progressValue"><?= $task['progress'] ?? '0' ?>%</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">0% = Not started, 100% = Completed</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <i class="bi bi-check-circle-fill"></i> Update Task
                </button>
                <a href="/ergon/tasks" class="btn btn--secondary">
                    <i class="bi bi-x-circle-fill"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function updateProgressValue(value) {
    document.getElementById('progressValue').textContent = value + '%';
}

// Load task categories based on department
function loadTaskCategories() {
    const deptSelect = document.getElementById('department_id');
    const categorySelect = document.getElementById('task_category');
    const deptId = deptSelect.value;
    const currentCategory = '<?= htmlspecialchars($task['task_category'] ?? $task['category'] ?? '') ?>';
    
    // Clear existing options
    categorySelect.innerHTML = '<option value="">Select Category</option>';
    
    if (!deptId) return;
    
    // Fetch categories for selected department
    fetch(`/ergon/api/task-categories?department_id=${deptId}`)
        .then(response => response.json())
        .then(data => {
            if (data.categories) {
                data.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.category_name;
                    option.textContent = category.category_name;
                    if (category.category_name === currentCategory) {
                        option.selected = true;
                    }
                    categorySelect.appendChild(option);
                });
                
                // If current category is not in the list, add it
                if (currentCategory && !data.categories.find(cat => cat.category_name === currentCategory)) {
                    const option = document.createElement('option');
                    option.value = currentCategory;
                    option.textContent = currentCategory;
                    option.selected = true;
                    categorySelect.appendChild(option);
                }
            }
        })
        .catch(error => console.error('Error loading categories:', error));
}

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Form validation and initialization
document.addEventListener('DOMContentLoaded', function() {
    // Load categories for current department on page load
    const currentDeptId = '<?= $task['department_id'] ?? '' ?>';
    const currentCategory = '<?= htmlspecialchars($task['task_category'] ?? $task['category'] ?? '') ?>';
    
    // Pre-fill category if we have one but no department selected
    if (currentCategory && !currentDeptId) {
        const categorySelect = document.getElementById('task_category');
        const option = document.createElement('option');
        option.value = currentCategory;
        option.textContent = currentCategory;
        option.selected = true;
        categorySelect.appendChild(option);
    }
    if (currentDeptId) {
        loadTaskCategories();
    }
    
    document.getElementById('editTaskForm').addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const assignedTo = document.getElementById('assigned_to').value;
        
        if (!title) {
            e.preventDefault();
            alert('Please enter a task title');
            return;
        }
        
        if (!assignedTo) {
            e.preventDefault();
            alert('Please select a user to assign the task to');
            return;
        }
    });
});
</script>

<style>
.form-row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -0.5rem;
    margin-left: -0.5rem;
}

.form-row .form-group {
    padding-right: 0.5rem;
    padding-left: 0.5rem;
}

.col-md-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
}

.col-md-8 {
    flex: 0 0 66.666667%;
    max-width: 66.666667%;
}

.input-group {
    display: flex;
    align-items: center;
}

.input-group-append {
    margin-left: 0.5rem;
}

.input-group-text {
    padding: 0.375rem 0.75rem;
    background-color: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-secondary);
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 1rem;
}

@media (max-width: 768px) {
    .form-row .form-group {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<?php
$content = ob_get_clean();
$title = 'Edit Task';
$active_page = 'tasks';
include __DIR__ . '/../layouts/dashboard.php';
?>
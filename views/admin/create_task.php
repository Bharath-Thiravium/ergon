<?php
require_once __DIR__ . '/../../app/helpers/Security.php';
$content = ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="bi bi-person-plus-fill"></i> Create Personal Task</h1>
        <p>Create a task for yourself with detailed tracking and management</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/admin/manage-tasks" class="btn btn--secondary">
            <i class="bi bi-arrow-left"></i> Back to Tasks
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title"><i class="bi bi-person-check-fill"></i> Personal Task Details</h2>
    </div>
    <div class="card__body">
        <form id="createTaskForm" method="POST" action="/ergon/admin/create-task">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
            <fieldset>
                <legend>Basic Information</legend>
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="title" class="form-label"><i class="bi bi-card-text"></i> Task Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required placeholder="Enter task title">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="task_type" class="form-label">
                            <i class="bi bi-tag-fill"></i> Task Type
                        </label>
                        <select class="form-control" id="task_type" name="task_type">
                            <option value="ad-hoc" selected>Ad-hoc - General single task</option>
                            <option value="checklist">Checklist - Multiple sub-items to complete</option>
                            <option value="milestone">Milestone - Important project checkpoint</option>
                            <option value="timed">Timed - Time-bound with strict deadline</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label"><i class="bi bi-file-text-fill"></i> Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Provide detailed task description, requirements, and expected outcomes"></textarea>
                </div>
            </fieldset>

            <fieldset>
                <legend>Assignment & Category</legend>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="department_id" class="form-label">
                            <i class="bi bi-building"></i> Department
                        </label>
                        <select class="form-control" id="department_id" name="department_id" onchange="loadTaskCategories()">
                            <option value="">Select Department</option>
                            <?php if (!empty($departments)): ?>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="task_category" class="form-label">
                            <i class="bi bi-tags-fill"></i> Task Category
                        </label>
                        <select class="form-control" id="task_category" name="task_category" onchange="handleCategoryChange()">
                            <option value="">Select Category</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="assigned_to" class="form-label"><i class="bi bi-person-check-fill"></i> Assigned To</label>
                        <select class="form-control form-control--readonly" id="assigned_to" name="assigned_to" required readonly>
                            <option value="<?= $_SESSION['user_id'] ?? '' ?>" selected>
                                <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Current User') ?> (Personal Task)
                            </option>
                        </select>
                        <small class="text-muted"><i class="bi bi-info-circle"></i> This task will be assigned to you automatically.</small>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Settings</legend>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="priority" class="form-label">
                            <i class="bi bi-exclamation-triangle-fill"></i> Priority
                        </label>
                        <select class="form-control" id="priority" name="priority">
                            <option value="low">🟢 Low - Can wait, flexible timing</option>
                            <option value="medium" selected>🟡 Medium - Normal business priority</option>
                            <option value="high">🔴 High - Urgent, needs immediate attention</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="deadline" class="form-label">
                            <i class="bi bi-calendar-event-fill"></i> Due Date
                        </label>
                        <input type="date" class="form-control" id="deadline" name="deadline" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="sla_hours" class="form-label">
                            <i class="bi bi-clock-fill"></i> SLA Hours
                        </label>
                        <input type="number" class="form-control" id="sla_hours" name="sla_hours" value="24" min="1" max="720">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Status & Progress</legend>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="status" class="form-label">
                            <i class="bi bi-flag-fill"></i> Initial Status
                        </label>
                        <select class="form-control" id="status" name="status">
                            <option value="assigned" selected>📋 Assigned - Ready to start</option>
                            <option value="in_progress">⚡ In Progress - Currently working</option>
                            <option value="blocked">🚫 Blocked - Cannot proceed</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="progress" class="form-label">
                            <i class="bi bi-percent"></i> Initial Progress
                        </label>
                        <div class="input-group">
                            <input type="range" class="form-control" id="progress" name="progress" min="0" max="100" value="0" oninput="updateProgressValue(this.value)">
                            <div class="input-group-append">
                                <span class="input-group-text" id="progressValue">0%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- Follow-up Fields (Hidden by default) -->
            <div id="followupFields" class="followup-section" style="display: none;">
                <h3><i class="bi bi-telephone-fill"></i> Follow-up Details</h3>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="company_name" class="form-label">Company</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Company name">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="contact_person" class="form-label">Contact Person</label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person" placeholder="Contact person name">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="contact_phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="contact_phone" name="contact_phone" placeholder="Phone number">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="project_name" class="form-label">Project</label>
                        <input type="text" class="form-control" id="project_name" name="project_name" placeholder="Project name">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="followup_date" class="form-label">Follow-up Date</label>
                        <input type="date" class="form-control" id="followup_date" name="followup_date">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="followup_time" class="form-label">Follow-up Time</label>
                        <input type="time" class="form-control" id="followup_time" name="followup_time" value="09:00">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <i class="bi bi-person-plus-fill"></i> Create Personal Task
                </button>
                <a href="/ergon/admin/manage-tasks" class="btn btn--secondary">
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
                    categorySelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading categories:', error));
}

// Handle category change to show/hide follow-up fields
function handleCategoryChange() {
    const category = document.getElementById('task_category').value.toLowerCase();
    const followupFields = document.getElementById('followupFields');
    
    if (category.includes('follow')) {
        followupFields.style.display = 'block';
        // Set default follow-up date to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('followup_date').value = tomorrow.toISOString().split('T')[0];
    } else {
        followupFields.style.display = 'none';
    }
}

// Form initialization
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date to today
    const deadlineInput = document.getElementById('deadline');
    const today = new Date().toISOString().split('T')[0];
    deadlineInput.min = today;
    
    // Form validation
    document.getElementById('createTaskForm').addEventListener('submit', function(e) {
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

.followup-section {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid var(--border-color);
    background: var(--bg-secondary);
    padding: 1.5rem;
    border-radius: var(--border-radius);
}

.followup-section h3 {
    margin-bottom: 1.5rem;
    color: var(--primary);
}

.form-control--readonly {
    background: #f8f9fa;
    cursor: not-allowed;
}

.form-actions {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 1rem;
}

fieldset {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

legend {
    font-weight: 600;
    color: var(--primary);
    padding: 0 0.5rem;
    font-size: 1.1rem;
}

.card__title {
    color: var(--primary);
}

.text-muted {
    color: #6c757d;
    font-size: 0.875rem;
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
include __DIR__ . '/../layouts/dashboard.php';
?>
<?php
$title = 'Morning Planner';
$active_page = 'tasks';
ob_start();
?>

<div class="header-actions">
    <?php if ($data['canSubmit']): ?>
        <button class="btn btn--primary" onclick="addPlanRow()">
            <span>➕</span> Add Task
        </button>
    <?php else: ?>
        <span class="badge badge--success">✅ Plan Submitted</span>
    <?php endif; ?>
</div>

<?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'no_morning_plan'): ?>
        <div class="alert alert--warning">
            <strong>⚠️ Morning Plan Required</strong> Please submit your morning plan before updating progress.
        </div>
    <?php elseif ($_GET['error'] === '1'): ?>
        <div class="alert alert--danger">
            <strong>❌ Error</strong> Failed to save your morning plan. 
            <?php if (isset($_GET['msg'])): ?>
                <br>Details: <?= htmlspecialchars($_GET['msg']) ?>
            <?php endif; ?>
            Please try again.
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success'] === '1'): ?>
    <div class="alert alert--success">
        <strong>✅ Success</strong> Your morning plan has been saved successfully!
    </div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— Today</div>
        </div>
        <div class="kpi-card__value"><?= date('d') ?></div>
        <div class="kpi-card__label"><?= date('M Y') ?></div>
        <div class="kpi-card__status kpi-card__status--info">Current Date</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏰</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ Live</div>
        </div>
        <div class="kpi-card__value"><?= date('H:i') ?></div>
        <div class="kpi-card__label">Current Time</div>
        <div class="kpi-card__status kpi-card__status--active">Real-time</div>
    </div>
    
    <div class="kpi-card <?= $data['canSubmit'] ? 'kpi-card--warning' : 'kpi-card--success' ?>">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
            <div class="kpi-card__trend <?= $data['canSubmit'] ? 'kpi-card__trend--neutral' : 'kpi-card__trend--up' ?>">
                <?= $data['canSubmit'] ? '— Draft' : '↗ Done' ?>
            </div>
        </div>
        <div class="kpi-card__value"><?= count($data['todayPlans']) ?></div>
        <div class="kpi-card__label">Planned Tasks</div>
        <div class="kpi-card__status <?= $data['canSubmit'] ? 'kpi-card__status--pending' : 'kpi-card__status--active' ?>">
            <?= $data['canSubmit'] ? 'In Progress' : 'Submitted' ?>
        </div>
    </div>
</div>

<!-- Debug Info -->
<?php if (isset($_GET['debug'])): ?>
<div style="background: #f0f0f0; padding: 15px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px;">
    <strong>🔍 Debug Information:</strong><br><br>
    <strong>Session Data:</strong><br>
    User ID: <?= $_SESSION['user_id'] ?? 'Not set' ?><br>
    Username: <?= $_SESSION['username'] ?? 'Not set' ?><br>
    Role: <?= $_SESSION['role'] ?? 'Not set' ?><br><br>
    
    <strong>Query Parameters:</strong><br>
    Today's Date: <?= date('Y-m-d') ?><br>
    Today's Plans Count: <?= count($data['todayPlans'] ?? []) ?><br>
    Can Submit: <?= $data['canSubmit'] ? 'Yes' : 'No' ?><br>
    Departments Count: <?= count($data['departments'] ?? []) ?><br><br>
    
    <?php if (isset($data['debug_info'])): ?>
        <strong>Controller Debug:</strong><br>
        Task Count from Controller: <?= $data['debug_info']['task_count'] ?><br>
        User ID from Controller: <?= $data['debug_info']['user_id'] ?><br>
        Date from Controller: <?= $data['debug_info']['today'] ?><br><br>
    <?php endif; ?>
    
    <?php if (!empty($data['todayPlans'])): ?>
        <strong>Found Tasks:</strong><br>
        <?php foreach ($data['todayPlans'] as $i => $plan): ?>
            <?= $i + 1 ?>. <?= htmlspecialchars($plan['title']) ?> (ID: <?= $plan['id'] ?>, Priority: <?= $plan['priority'] ?>, Created: <?= $plan['created_at'] ?>)<br>
        <?php endforeach; ?>
    <?php else: ?>
        <strong style="color: red;">❌ NO TASKS FOUND!</strong><br>
        This could mean:<br>
        - No tasks have been added for today<br>
        - User is not logged in properly<br>
        - Database connection issue<br>
        - Query is not returning results<br><br>
        <a href="/ergon/check_session_debug.php" style="background: #007cba; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">Check Session Status</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Existing Tasks Display -->
<?php if (!empty($data['todayPlans'])): ?>
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📋</span> Today's Planned Tasks
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Priority</th>
                        <th>Est. Hours</th>
                        <th>Actual Hours</th>
                        <th>Status</th>
                        <th>Department</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['todayPlans'] as $plan): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($plan['title']) ?></strong></td>
                        <td><?= htmlspecialchars(substr($plan['description'] ?? '', 0, 50)) ?><?= strlen($plan['description'] ?? '') > 50 ? '...' : '' ?></td>
                        <td><span class="badge badge--<?= $plan['priority'] === 'urgent' ? 'danger' : ($plan['priority'] === 'high' ? 'warning' : ($plan['priority'] === 'low' ? 'secondary' : 'info')) ?>"><?= ucfirst($plan['priority']) ?></span></td>
                        <td><?= $plan['estimated_hours'] ?>h</td>
                        <td><?= ($plan['actual_hours'] ?? 0) ?>h</td>
                        <td><span class="badge badge--<?= $plan['status'] === 'completed' ? 'success' : ($plan['status'] === 'in_progress' ? 'info' : 'secondary') ?>"><?= ucfirst(str_replace('_', ' ', $plan['status'])) ?></span></td>
                        <td><?= htmlspecialchars($plan['department_name'] ?? 'N/A') ?></td>
                        <td><?= date('M d, H:i', strtotime($plan['created_at'])) ?></td>
                        <td>
                            <div class="btn-group">
                                <button onclick="editTask(<?= $plan['id'] ?>)" class="btn btn--sm btn--secondary" title="Edit Task">
                                    <span>✏️</span>
                                </button>
                                <button onclick="deleteTask(<?= $plan['id'] ?>)" class="btn btn--sm btn--danger" title="Delete Task">
                                    <span>🗑️</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>➕</span> Add New Task
        </h2>
    </div>
    <div class="card__body">
        <?php if ($data['canSubmit']): ?>
            <form method="POST" action="/ergon/planner/submit" id="morningPlanForm">
                <div id="planRows">
                    <div class="plan-row">
                        <div class="form-row">
                            <div class="form-group form-group--flex-2">
                                <label class="form-label">Task Title *</label>
                                <input type="text" name="plans[0][title]" class="form-control" required placeholder="What will you work on today?">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Department</label>
                                <select name="plans[0][department_id]" class="form-control department-select" onchange="loadPlannerCategories(this, 0)">
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
                                <select name="plans[0][task_category]" class="form-control category-select" onchange="checkFollowupCategory(this, 0)">
                                    <option value="">Select Category</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Priority</label>
                                <select name="plans[0][priority]" class="form-control">
                                    <option value="low">🟢 Low</option>
                                    <option value="medium" selected>🟡 Medium</option>
                                    <option value="high">🟠 High</option>
                                    <option value="urgent">🔴 Urgent</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Est. Hours</label>
                                <input type="number" name="plans[0][estimated_hours]" class="form-control" min="0.5" max="8" step="0.5" value="1.0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Action</label>
                                <button type="button" class="btn btn--danger btn--sm" onclick="removePlanRow(this)">
                                    <span>🗑️</span>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="plans[0][description]" class="form-control" rows="2" placeholder="Brief description of the task"></textarea>
                        </div>
                        <div id="followup-fields-0" class="followup-fields" style="display: none;">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" name="plans[0][company_name]" class="form-control" placeholder="Company name">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contact Person</label>
                                    <input type="text" name="plans[0][contact_person]" class="form-control" placeholder="Contact person">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contact Phone</label>
                                    <input type="tel" name="plans[0][contact_phone]" class="form-control" placeholder="Phone number">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn--secondary" onclick="addPlanRow()">
                        <span>➕</span> Add Another Task
                    </button>
                    <button type="submit" class="btn btn--primary" id="submitBtn">
                        <span>📤</span> Submit Morning Plan
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert--success">
                <strong>✅ Morning Plan Submitted</strong> 
                Your daily plan was submitted at <?= date('g:i A', strtotime($data['workflowStatus']['morning_submitted_at'])) ?>.
                You can update progress in the evening.
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Priority</th>
                            <th>Est. Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['todayPlans'] as $plan): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($plan['title']) ?></strong>
                                    <?php if ($plan['description']): ?>
                                        <br><small style="color: var(--text-muted);"><?= htmlspecialchars($plan['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge--<?= $plan['priority'] === 'urgent' ? 'danger' : ($plan['priority'] === 'high' ? 'warning' : 'success') ?>">
                                        <?= ucfirst($plan['priority']) ?>
                                    </span>
                                </td>
                                <td><?= $plan['estimated_hours'] ?>h</td>
                                <td>
                                    <span class="badge badge--<?= $plan['status'] === 'completed' ? 'success' : ($plan['status'] === 'in_progress' ? 'warning' : 'info') ?>">
                                        <?= ucfirst(str_replace('_', ' ', $plan['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="form-actions">
                <a href="/ergon/evening-update" class="btn btn--primary">
                    <span>🌅</span> Go to Evening Update
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let planRowIndex = 1;

// Add event listeners for auto-save and form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('morningPlanForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(e.target)) {
                e.preventDefault();
                return false;
            }
        });
    }
});



function validateForm(form) {
    const planRows = form.querySelectorAll('.plan-row');
    let isValid = true;
    
    if (planRows.length === 0) {
        showNotification('Please add at least one task to your plan', 'error');
        return false;
    }
    
    planRows.forEach((row, index) => {
        const titleInput = row.querySelector('input[name*="[title]"]');
        if (!titleInput || !titleInput.value.trim()) {
            showNotification(`Task ${index + 1}: Title is required`, 'error');
            titleInput?.focus();
            isValid = false;
        }
    });
    
    return isValid;
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert--${type} notification`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}



function loadPlannerCategories(deptSelect, index) {
    const categorySelect = deptSelect.closest('.plan-row').querySelector('.category-select');
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
            showNotification('Failed to load categories', 'error');
        });
}

function checkFollowupCategory(categorySelect, index) {
    const followupFields = document.getElementById(`followup-fields-${index}`);
    const category = categorySelect.value.toLowerCase();
    
    if (category.includes('follow')) {
        followupFields.style.display = 'block';
    } else {
        followupFields.style.display = 'none';
    }
}

function addPlanRow() {
    const planRows = document.getElementById('planRows');
    const newRow = document.createElement('div');
    newRow.className = 'plan-row';
    newRow.innerHTML = `
        <div class="form-row">
            <div class="form-group form-group--flex-2">
                <label class="form-label">Task Title *</label>
                <input type="text" name="plans[${planRowIndex}][title]" class="form-control" required placeholder="What will you work on today?">
            </div>
            <div class="form-group">
                <label class="form-label">Department</label>
                <select name="plans[${planRowIndex}][department_id]" class="form-control department-select" onchange="loadPlannerCategories(this, ${planRowIndex})">
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
                <select name="plans[${planRowIndex}][task_category]" class="form-control category-select" onchange="checkFollowupCategory(this, ${planRowIndex})">
                    <option value="">Select Category</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Priority</label>
                <select name="plans[${planRowIndex}][priority]" class="form-control">
                    <option value="low">🟢 Low</option>
                    <option value="medium" selected>🟡 Medium</option>
                    <option value="high">🟠 High</option>
                    <option value="urgent">🔴 Urgent</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Est. Hours</label>
                <input type="number" name="plans[${planRowIndex}][estimated_hours]" class="form-control" min="0.5" max="8" step="0.5" value="1.0">
            </div>
            <div class="form-group">
                <label class="form-label">Action</label>
                <button type="button" class="btn btn--danger btn--sm" onclick="removePlanRow(this)">
                    <span>🗑️</span>
                </button>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="plans[${planRowIndex}][description]" class="form-control" rows="2" placeholder="Brief description of the task"></textarea>
        </div>
        <div id="followup-fields-${planRowIndex}" class="followup-fields" style="display: none;">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="plans[${planRowIndex}][company_name]" class="form-control" placeholder="Company name">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="plans[${planRowIndex}][contact_person]" class="form-control" placeholder="Contact person">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Phone</label>
                    <input type="tel" name="plans[${planRowIndex}][contact_phone]" class="form-control" placeholder="Phone number">
                </div>
            </div>
        </div>
    `;
    planRows.appendChild(newRow);
    planRowIndex++;
}

function removePlanRow(button) {
    const planRow = button.closest('.plan-row');
    if (document.querySelectorAll('.plan-row').length > 1) {
        planRow.remove();
    } else {
        showNotification('At least one task is required for your daily plan.', 'warning');
    }
}

// Simple page reload approach for displaying updated tasks

function resetFormToInitialState() {
    const planRows = document.getElementById('planRows');
    planRows.innerHTML = `
        <div class="plan-row">
            <div class="form-row">
                <div class="form-group form-group--flex-2">
                    <label class="form-label">Task Title *</label>
                    <input type="text" name="plans[0][title]" class="form-control" required placeholder="What will you work on today?">
                </div>
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="plans[0][department_id]" class="form-control department-select" onchange="loadPlannerCategories(this, 0)">
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
                    <select name="plans[0][task_category]" class="form-control category-select" onchange="checkFollowupCategory(this, 0)">
                        <option value="">Select Category</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select name="plans[0][priority]" class="form-control">
                        <option value="low">🟢 Low</option>
                        <option value="medium" selected>🟡 Medium</option>
                        <option value="high">🟠 High</option>
                        <option value="urgent">🔴 Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Est. Hours</label>
                    <input type="number" name="plans[0][estimated_hours]" class="form-control" min="0.5" max="8" step="0.5" value="1.0">
                </div>
                <div class="form-group">
                    <label class="form-label">Action</label>
                    <button type="button" class="btn btn--danger btn--sm" onclick="removePlanRow(this)">
                        <span>🗑️</span>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="plans[0][description]" class="form-control" rows="2" placeholder="Brief description of the task"></textarea>
            </div>
            <div id="followup-fields-0" class="followup-fields" style="display: none;">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="plans[0][company_name]" class="form-control" placeholder="Company name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="plans[0][contact_person]" class="form-control" placeholder="Contact person">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Phone</label>
                        <input type="tel" name="plans[0][contact_phone]" class="form-control" placeholder="Phone number">
                    </div>
                </div>
            </div>
        </div>
    `;
    planRowIndex = 1;
}

function editTask(taskId) {
    // Get task data
    fetch(`/ergon/daily-workflow/get-task?id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showEditModal(data.task);
            } else {
                showNotification('Failed to load task data', 'error');
            }
        })
        .catch(error => {
            showNotification('Error loading task', 'error');
        });
}

function showEditModal(task) {
    const modal = document.createElement('div');
    modal.className = 'edit-modal';
    modal.innerHTML = `
        <div class="edit-modal-content">
            <div class="edit-modal-header">
                <h3>Edit Task</h3>
                <button onclick="closeEditModal()" class="close-btn">&times;</button>
            </div>
            <form id="editTaskForm" onsubmit="updateTask(event, ${task.id})">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" value="${task.title}" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description">${task.description || ''}</textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department_id" id="editDepartmentSelect" onchange="loadEditCategories(this)">
                            <option value="">Select Dept</option>
                            <?php if (!empty($data['departments'])): ?>
                                <?php foreach ($data['departments'] as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="task_category" id="editCategorySelect">
                            <option value="">Select Category</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority">
                            <option value="low" ${task.priority === 'low' ? 'selected' : ''}>🟢 Low</option>
                            <option value="medium" ${task.priority === 'medium' ? 'selected' : ''}>🟡 Medium</option>
                            <option value="high" ${task.priority === 'high' ? 'selected' : ''}>🟠 High</option>
                            <option value="urgent" ${task.priority === 'urgent' ? 'selected' : ''}>🔴 Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Est. Hours</label>
                        <input type="number" name="estimated_hours" value="${task.estimated_hours}" min="0.5" max="8" step="0.5">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeEditModal()" class="btn btn--secondary">Cancel</button>
                    <button type="submit" class="btn btn--primary">Update Task</button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Set current values
    if (task.department_id) {
        document.getElementById('editDepartmentSelect').value = task.department_id;
        loadEditCategories(document.getElementById('editDepartmentSelect'), task.task_category);
    }
}

function closeEditModal() {
    const modal = document.querySelector('.edit-modal');
    if (modal) {
        modal.remove();
    }
}

function updateTask(event, taskId) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('task_id', taskId);
    
    fetch('/ergon/daily-workflow/update-task', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Task updated successfully', 'success');
            closeEditModal();
            location.reload();
        } else {
            showNotification('Failed to update task', 'error');
        }
    })
    .catch(error => {
        showNotification('Error updating task', 'error');
    });
}

function loadEditCategories(deptSelect, selectedCategory = null) {
    const categorySelect = document.getElementById('editCategorySelect');
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
                    if (selectedCategory && category.category_name === selectedCategory) {
                        option.selected = true;
                    }
                    categorySelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading categories:', error);
        });
}

function deleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task?')) {
        fetch('/ergon/daily-workflow/delete-task', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'task_id=' + taskId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Task deleted successfully', 'success');
                location.reload(); // Refresh page to update display
            } else {
                showNotification('Failed to delete task', 'error');
            }
        })
        .catch(error => {
            showNotification('Error deleting task', 'error');
        });
    }
}

// Page initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('Morning planner loaded');
});
</script>

<style>
.plan-row {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    background: #fafafa;
    transition: all 0.3s ease;
}

.plan-row:hover {
    border-color: #007cba;
    box-shadow: 0 2px 8px rgba(0, 124, 186, 0.1);
}

.followup-fields {
    background: #f0f8ff;
    border: 1px solid #b3d9ff;
    border-radius: 6px;
    padding: 15px;
    margin-top: 15px;
}

.form-control:focus {
    border-color: #007cba;
    box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.2);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
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

.alert--error, .alert--danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.alert--warning {
    background-color: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    padding: 12px 16px;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

.auto-save-indicator {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.auto-save-indicator.show {
    opacity: 1;
}

.saved-task-item {
    transition: all 0.2s ease;
}

.saved-task-item:hover {
    border-color: #007cba !important;
    box-shadow: 0 2px 8px rgba(0, 124, 186, 0.1);
}

#savedTasksSection {
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 1.5rem;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.badge--info {
    background-color: #d1ecf1;
    color: #0c5460;
}

.badge--warning {
    background-color: #fff3cd;
    color: #856404;
}

.badge--danger {
    background-color: #f8d7da;
    color: #721c24;
}

.badge--secondary {
    background-color: #e2e3e5;
    color: #383d41;
}

.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
}

.table th,
.table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: middle;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
    border-top: 1px solid #e0e0e0;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.btn-group {
    display: flex;
    gap: 5px;
}

.btn--sm {
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 4px;
}

.edit-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.edit-modal-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    overflow-y: auto;
}

.edit-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.close-btn:hover {
    color: #333;
}
</style>



<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
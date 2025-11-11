<?php
require_once __DIR__ . '/../../app/helpers/Security.php';
$content = ob_start();
?>

<div class="compact-header">
    <h1><i class="bi bi-plus-circle"></i> Create Task</h1>
    <a href="/ergon/tasks" class="btn-back">← Back</a>
</div>

<div class="compact-form">
        <form id="createTaskForm" method="POST" action="/ergon/tasks/create">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
            
            <!-- Main Task Info -->
            <div class="form-section">
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label for="title">📝 Task Title *</label>
                        <input type="text" id="title" name="title" required placeholder="What needs to be done?">
                    </div>
                    <div class="form-group">
                        <label for="task_type">🏷️ Type</label>
                        <select id="task_type" name="task_type">
                            <option value="ad-hoc">📋 Task</option>
                            <option value="checklist">✅ Checklist</option>
                            <option value="milestone">🎯 Milestone</option>
                            <option value="timed">⏰ Urgent</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">📄 Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Describe the task details, requirements, and expected outcome..."></textarea>
                </div>
            </div>

            <!-- Assignment & Scheduling -->
            <div class="form-section">
                <h3>👥 Assignment & Schedule</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="assigned_for">👤 Assignment Type</label>
                        <select id="assigned_for" name="assigned_for" onchange="handleAssignmentTypeChange()" required>
                            <option value="self">For Myself</option>
                            <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'owner'])): ?>
                                <option value="other">For Others</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="assigned_to">🎯 Assign To *</label>
                        <select id="assigned_to" name="assigned_to" required>
                            <option value="<?= $_SESSION['user_id'] ?>" selected><?= htmlspecialchars($_SESSION['user_name'] ?? 'You') ?></option>
                            <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'owner']) && !empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <option value="<?= $user['id'] ?>" style="display: none;"><?= htmlspecialchars($user['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="planned_date">📅 Planned Date</label>
                        <input type="date" id="planned_date" name="planned_date" min="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </div>

            <!-- Task Details -->
            <div class="form-section">
                <h3>⚙️ Task Configuration</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="department_id">🏢 Department</label>
                        <select id="department_id" name="department_id" onchange="loadTaskCategories()">
                            <option value="">Select Department</option>
                            <?php if (!empty($departments)): ?>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="task_category">🏷️ Category</label>
                        <select id="task_category" name="task_category" onchange="handleCategoryChange()">
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="priority">🚨 Priority</label>
                        <select id="priority" name="priority">
                            <option value="low">🟢 Low</option>
                            <option value="medium" selected>🟡 Medium</option>
                            <option value="high">🔴 High</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Timeline & Status -->
            <div class="form-section">
                <h3>📊 Timeline & Progress</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="deadline">⏰ Due Date</label>
                        <input type="date" id="deadline" name="deadline" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label for="sla_hours">⏱️ SLA Hours</label>
                        <input type="number" id="sla_hours" name="sla_hours" value="24" min="1" max="720" placeholder="24">
                    </div>
                    <div class="form-group">
                        <label for="status">📈 Initial Status</label>
                        <select id="status" name="status">
                            <option value="assigned" selected>📋 Assigned</option>
                            <option value="in_progress">⚡ In Progress</option>
                            <option value="blocked">🚫 Blocked</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="progress">📊 Initial Progress: <span id="progressValue">0%</span></label>
                    <input type="range" id="progress" name="progress" min="0" max="100" value="0" oninput="updateProgressValue(this.value)" class="progress-slider">
                </div>
            </div>

            <!-- Additional Options -->
            <div class="form-section">
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="followup_required" name="followup_required" onchange="toggleFollowupFields()">
                        <span class="checkmark"></span>
                        🔄 This task requires follow-up
                    </label>
                </div>
            </div>

            <!-- Follow-up Fields (Hidden by default) -->
            <div id="followupFields" class="form-section followup-section" style="display: none;">
                <h3>📞 Follow-up Details</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="company_name">🏢 Company</label>
                        <input type="text" id="company_name" name="company_name" placeholder="Company name">
                    </div>
                    <div class="form-group">
                        <label for="contact_person">👤 Contact Person</label>
                        <input type="text" id="contact_person" name="contact_person" placeholder="Contact person name">
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">📱 Phone</label>
                        <input type="tel" id="contact_phone" name="contact_phone" placeholder="Phone number">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="project_name">📁 Project</label>
                        <input type="text" id="project_name" name="project_name" placeholder="Project name">
                    </div>
                    <div class="form-group">
                        <label for="followup_date">📅 Follow-up Date</label>
                        <input type="date" id="followup_date" name="followup_date">
                    </div>
                    <div class="form-group">
                        <label for="followup_time">⏰ Follow-up Time</label>
                        <input type="time" id="followup_time" name="followup_time" value="09:00">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    ✨ Create Task
                </button>
                <a href="/ergon/tasks" class="btn-secondary">
                    ❌ Cancel
                </a>
            </div>
        </form>
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
            console.log('Categories data:', data);
            if (data.categories) {
                console.log('Found categories:', data.categories.length);
                data.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.category_name;
                    option.textContent = category.category_name;
                    categorySelect.appendChild(option);
                });
            } else {
                console.log('No categories found in response');
            }
        })
        .catch(error => console.error('Error loading categories:', error));
}

// Handle assignment type change
function handleAssignmentTypeChange() {
    const assignmentType = document.getElementById('assigned_for').value;
    const assignedToSelect = document.getElementById('assigned_to');
    const options = assignedToSelect.querySelectorAll('option');
    
    if (assignmentType === 'self') {
        // Show only current user
        options.forEach(option => {
            if (option.value === '<?= $_SESSION['user_id'] ?>') {
                option.style.display = 'block';
                option.selected = true;
            } else {
                option.style.display = 'none';
                option.selected = false;
            }
        });
    } else {
        // Show all users
        options.forEach(option => {
            option.style.display = 'block';
        });
        assignedToSelect.value = '';
    }
}

// Toggle follow-up fields
function toggleFollowupFields() {
    const checkbox = document.getElementById('followup_required');
    const followupFields = document.getElementById('followupFields');
    
    if (checkbox.checked) {
        followupFields.style.display = 'block';
        followupFields.style.animation = 'slideDown 0.3s ease';
        
        // Set default follow-up date to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('followup_date').value = tomorrow.toISOString().split('T')[0];
        
        loadFollowupDetails();
    } else {
        followupFields.style.display = 'none';
    }
}

// Handle category change to show/hide follow-up fields
function handleCategoryChange() {
    const category = document.getElementById('task_category').value.toLowerCase();
    const followupCheckbox = document.getElementById('followup_required');
    
    if (category.includes('follow')) {
        followupCheckbox.checked = true;
        toggleFollowupFields();
    }
}

// Load follow-up details for auto-population
function loadFollowupDetails() {
    fetch('/ergon/api/followup-details')
        .then(response => {
            if (!response.ok) return { followups: [] };
            return response.json();
        })
        .then(data => {
            if (data.followups && data.followups.length > 0) {
                populateFollowupDropdowns(data.followups);
                // Auto-populate title and description for follow-up tasks
                const titleField = document.getElementById('title');
                const descField = document.getElementById('description');
                
                if (!titleField.value) {
                    titleField.value = 'Follow-up Call';
                }
                if (!descField.value) {
                    descField.value = 'Follow up with client regarding project status and next steps';
                }
            }
        })
        .catch(error => console.log('Failed to load follow-up details:', error));
}

// Populate follow-up dropdowns with existing data
function populateFollowupDropdowns(followups) {
    const companies = [...new Set(followups.map(f => f.company_name).filter(Boolean))];
    const contacts = [...new Set(followups.map(f => f.contact_person).filter(Boolean))];
    const projects = [...new Set(followups.map(f => f.project_name).filter(Boolean))];
    
    // Add datalist for company suggestions
    addDatalist('company_name', companies);
    addDatalist('contact_person', contacts);
    addDatalist('project_name', projects);
}

// Add datalist for input suggestions
function addDatalist(inputId, options) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    let datalist = document.getElementById(inputId + '_list');
    if (!datalist) {
        datalist = document.createElement('datalist');
        datalist.id = inputId + '_list';
        input.setAttribute('list', datalist.id);
        input.parentNode.appendChild(datalist);
    }
    
    datalist.innerHTML = '';
    options.forEach(option => {
        const optionEl = document.createElement('option');
        optionEl.value = option;
        datalist.appendChild(optionEl);
    });
}

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

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
.compact-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.compact-header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--primary);
}

.btn-back {
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.9rem;
}

.compact-form {
    background: var(--bg-primary);
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid var(--border-color);
}

.form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--bg-secondary);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.form-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.span-2 {
    grid-column: span 2;
}

.form-group.span-3 {
    grid-column: span 3;
}

.form-group label {
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
    color: var(--text-primary);
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    background: var(--bg-primary);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.1);
}

.checkbox-group {
    margin: 1rem 0;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
    cursor: pointer;
    padding: 0.75rem;
    border-radius: 6px;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    transition: all 0.2s ease;
}

.checkbox-label:hover {
    background: var(--bg-tertiary);
    border-color: var(--primary);
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin: 0;
    accent-color: var(--primary);
}

.progress-slider {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: var(--bg-secondary);
    outline: none;
    margin-top: 0.5rem;
}

.progress-slider::-webkit-slider-thumb {
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--primary);
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.progress-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--primary);
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.btn-primary,
.btn-secondary {
    padding: 0.75rem 1.5rem;
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.btn-secondary {
    background: var(--bg-secondary);
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
}

.btn-secondary:hover {
    background: var(--bg-tertiary);
}

.followup-section {
    background: linear-gradient(135deg, var(--primary-light), var(--bg-secondary));
    border: 2px dashed var(--primary);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group.span-2,
    .form-group.span-3 {
        grid-column: span 1;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .compact-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

<?php
$content = ob_get_clean();
$title = 'Create Task';
$active_page = 'tasks';
include __DIR__ . '/../layouts/dashboard.php';
?>

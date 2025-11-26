<?php
require_once __DIR__ . '/../../app/helpers/Security.php';
$content = ob_start();
?>

<div class="compact-header">
    <h1><i class="bi bi-plus-circle"></i> Create Task</h1>
    <div class="header-actions">
        <button type="button" class="btn-help" onclick="toggleHelpPanel()" title="Show Help">
            ❓ Help
        </button>
        <a href="/ergon/tasks" class="btn-back">← Back</a>
    </div>
</div>

<!-- Help Panel - Provides user guidance for task creation -->
<div id="helpPanel" class="help-panel" style="display: none;">
    <div class="help-content">
        <h3>📚 Task Creation Guide</h3>
        <div class="help-grid">
            <!-- Basic Information Section -->
            <div class="help-section">
                <h4>📝 Basic Information</h4>
                <ul>
                    <li><strong>Title:</strong> Clear, descriptive name (e.g., "Update website homepage")</li>
                    <li><strong>Type:</strong> Choose based on task nature - Task (general), Checklist (multiple steps), Milestone (important goal), Urgent (time-critical)</li>
                    <li><strong>Description:</strong> Detailed requirements, acceptance criteria, and expected outcomes</li>
                </ul>
            </div>
            <!-- Assignment & Schedule Section -->
            <div class="help-section">
                <h4>👥 Assignment & Schedule</h4>
                <ul>
                    <li><strong>Assignment Type:</strong> "For Myself" creates personal tasks, "For Others" allows delegation (admin only)</li>
                    <li><strong>Assign To:</strong> Select team member responsible for completion</li>
                    <li><strong>Planned Date:</strong> When you plan to work on this task (optional)</li>
                </ul>
            </div>
            <!-- Configuration Section -->
            <div class="help-section">
                <h4>⚙️ Configuration</h4>
                <ul>
                    <li><strong>Department:</strong> Helps categorize and filter tasks by team</li>
                    <li><strong>Category:</strong> Specific task type within department (loads based on department)</li>
                    <li><strong>Priority:</strong> Low (routine), Medium (normal), High (urgent/important)</li>
                </ul>
            </div>
            <!-- Timeline & Progress Section -->
            <div class="help-section">
                <h4>📊 Timeline & Progress</h4>
                <ul>
                    <li><strong>Due Date:</strong> Hard deadline for task completion</li>
                    <li><strong>SLA Hours:</strong> Service Level Agreement - expected completion time (default: 24 hours)</li>
                    <li><strong>Status:</strong> Starting status - Assigned (not started), In Progress (actively working), Blocked (waiting for something)</li>
                    <li><strong>Progress:</strong> Initial completion percentage (usually 0% for new tasks)</li>
                </ul>
            </div>
            <!-- Additional Options Section -->
            <div class="help-section">
                <h4>🔄 Additional Options</h4>
                <ul>
                    <li><strong>Follow-up Required:</strong> Enable if task needs client/stakeholder follow-up</li>
                    <li><strong>Reminder Notifications:</strong> Get alerts before due date</li>
                    <li><strong>Track Time:</strong> Monitor time spent on this task</li>
                </ul>
            </div>
            <!-- Follow-up Details Section -->
            <div class="help-section">
                <h4>📞 Follow-up Details</h4>
                <ul>
                    <li><strong>Company/Contact:</strong> Type 3+ characters to search existing records</li>
                    <li><strong>Auto-fill:</strong> Selecting company/contact automatically fills related fields</li>
                    <li><strong>Follow-up Date/Time:</strong> When to contact client (defaults to next day, 9 AM)</li>
                </ul>
            </div>
        </div>
        <!-- Pro Tips Section -->
        <div class="help-tips">
            <h4>💡 Pro Tips</h4>
            <div class="tips-grid">
                <div class="tip-item">
                    <span class="tip-icon">🎯</span>
                    <span>Use specific, actionable titles like "Fix login bug" instead of "Bug fix"</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">📅</span>
                    <span>Set realistic due dates - consider dependencies and workload</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">🏷️</span>
                    <span>Choose appropriate priority - not everything can be high priority</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">📝</span>
                    <span>Include acceptance criteria in description for clarity</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">🔄</span>
                    <span>Enable follow-up for client-facing tasks or external dependencies</span>
                </div>
                <div class="tip-item">
                    <span class="tip-icon">⏱️</span>
                    <span>Use time tracking for billable work or performance analysis</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="compact-form">
        <form id="createTaskForm" method="POST" action="/ergon/tasks/create">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
            
            <!-- Main Task Info -->
            <div class="form-section">
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label for="title">📝 Task Title * <span class="field-help" title="Clear, specific description of what needs to be done">ℹ️</span></label>
                        <input type="text" id="title" name="title" required placeholder="e.g., Update homepage banner, Fix login bug, Review project proposal">
                        <small class="field-hint">Be specific and actionable. Good: "Update contact form validation", Bad: "Fix form"</small>
                    </div>
                    <div class="form-group">
                        <label for="task_type">🏷️ Type <span class="field-help" title="Choose based on task complexity and importance">ℹ️</span></label>
                        <select id="task_type" name="task_type">
                            <option value="ad-hoc">📋 Task (General work item)</option>
                            <option value="checklist">✅ Checklist (Multiple steps)</option>
                            <option value="milestone">🎯 Milestone (Important goal)</option>
                            <option value="timed">⏰ Urgent (Time-critical)</option>
                        </select>
                        <small class="field-hint">Task: regular work | Checklist: multi-step process | Milestone: major deliverable | Urgent: immediate attention</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">📄 Description <span class="field-help" title="Detailed requirements and acceptance criteria">ℹ️</span></label>
                    <textarea id="description" name="description" rows="3" placeholder="What needs to be done? What are the requirements? What defines completion? Include any relevant links, files, or context..."></textarea>
                    <small class="field-hint">Include: requirements, acceptance criteria, resources needed, expected outcome. Be detailed to avoid confusion.</small>
                </div>
            </div>

            <!-- Assignment & Scheduling -->
            <div class="form-section">
                <h3>👥 Assignment & Schedule</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="assigned_for">👤 Assignment Type <span class="field-help" title="Who will be responsible for this task?">ℹ️</span></label>
                        <select id="assigned_for" name="assigned_for" onchange="handleAssignmentTypeChange()" required>
                            <option value="self">For Myself (I will do this)</option>
                            <option value="other">For Others (Delegate to team member)</option>
                        </select>
                        <small class="field-hint">Choose "For Myself" for personal tasks, "For Others" to delegate to team members</small>
                    </div>
                    <div class="form-group">
                        <label for="assigned_to">🎯 Assign To *</label>
                        <select id="assigned_to" name="assigned_to" required>
                            <option value="<?= $_SESSION['user_id'] ?>" selected><?= htmlspecialchars($_SESSION['user_name'] ?? 'You') ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="planned_date">📅 Planned Date <span class="field-help" title="When do you plan to work on this? (Optional)">ℹ️</span></label>
                        <input type="date" id="planned_date" name="planned_date" min="<?= date('Y-m-d') ?>">
                        <small class="field-hint">Optional: When you plan to start working on this task. Different from due date.</small>
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
                        <label for="project_id">📁 Project</label>
                        <select id="project_id" name="project_id">
                            <option value="">Select Project</option>
                            <?php if (!empty($projects)): ?>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="priority">🚨 Priority <span class="field-help" title="How urgent/important is this task?">ℹ️</span></label>
                        <select id="priority" name="priority">
                            <option value="low">🟢 Low (Routine, can wait)</option>
                            <option value="medium" selected>🟡 Medium (Normal priority)</option>
                            <option value="high">🔴 High (Urgent/Important)</option>
                        </select>
                        <small class="field-hint">Low: routine tasks | Medium: normal work | High: urgent or business-critical</small>
                    </div>
                </div>
            </div>

            <!-- Timeline & Status -->
            <div class="form-section">
                <h3>📊 Timeline & Progress</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="deadline">⏰ Due Date <span class="field-help" title="Hard deadline for task completion">ℹ️</span></label>
                        <input type="date" id="deadline" name="deadline" min="<?= date('Y-m-d') ?>">
                        <small class="field-hint">Hard deadline when task must be completed. Leave empty if no specific deadline.</small>
                    </div>
                    <div class="form-group">
                        <label for="sla_hours">⏱️ SLA Time <span class="field-help" title="Expected completion time">ℹ️</span></label>
                        <div class="sla-time-inputs">
                            <input type="number" id="sla_hours_part" min="0" max="720" value="0" placeholder="0">
                            <span class="sla-separator">h</span>
                            <input type="number" id="sla_minutes_part" min="0" max="59" value="15" placeholder="15">
                            <span class="sla-separator">m</span>
                        </div>
                        <input type="hidden" id="sla_hours" name="sla_hours" value="0.25">
                        <small class="field-hint">Service Level Agreement: Expected time to complete (e.g., 2h 30m = 2.5 hours)</small>
                    </div>
                    <div class="form-group">
                        <label for="status">📈 Initial Status</label>
                        <select id="status" name="status">
                            <option value="assigned" selected>📋 Assigned</option>
                            <option value="in_progress">⚡ In Progress</option>
                            <option value="cancelled">❌ Cancelled</option>
                            <option value="suspended">⏸️ Suspended</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="progress">📊 Initial Progress: <span id="progressValue">0%</span></label>
                    <input type="range" id="progress" name="progress" min="0" max="100" value="0" oninput="updateProgressValue(this.value)" class="progress-slider">
                </div>
            </div>

            <!-- Additional Options -->
            <div class="form-section options-section">
                <h3>⚙️ Additional Options</h3>
                <div class="options-grid">
                    <div class="option-card">
                        <div class="option-header">
                            <div class="option-icon">🔄</div>
                            <div class="option-content">
                                <h4>Follow-up Required</h4>
                                <p>Enable follow-up tracking for this task</p>
                            </div>
                        </div>
                        <div class="option-toggle">
                            <input type="checkbox" id="followup_required" name="followup_required" onchange="toggleFollowupFields()" class="toggle-switch">
                            <label for="followup_required" class="toggle-label">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="option-card">
                        <div class="option-header">
                            <div class="option-icon">🔔</div>
                            <div class="option-content">
                                <h4>Reminder Notifications</h4>
                                <p>Get notified about task deadlines</p>
                            </div>
                        </div>
                        <div class="option-toggle">
                            <input type="checkbox" id="reminder_enabled" name="reminder_enabled" class="toggle-switch" checked>
                            <label for="reminder_enabled" class="toggle-label">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="option-card">
                        <div class="option-header">
                            <div class="option-icon">📊</div>
                            <div class="option-content">
                                <h4>Track Time</h4>
                                <p>Enable time tracking for this task</p>
                            </div>
                        </div>
                        <div class="option-toggle">
                            <input type="checkbox" id="time_tracking" name="time_tracking" class="toggle-switch">
                            <label for="time_tracking" class="toggle-label">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="option-card">
                        <div class="option-header">
                            <div class="option-icon">🔄</div>
                            <div class="option-content">
                                <h4>Recurring Task</h4>
                                <p>Set task to repeat automatically</p>
                            </div>
                        </div>
                        <div class="option-toggle">
                            <input type="checkbox" id="is_recurring" name="is_recurring" class="toggle-switch" onchange="toggleRecurringFields()">
                            <label for="is_recurring" class="toggle-label">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recurring Fields (Hidden by default) -->
            <div id="recurringFields" class="form-section recurring-section" style="display: none;">
                <h3>🔄 Recurring Schedule</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="recurrence_type">Repeat Frequency *</label>
                        <select id="recurrence_type" name="recurrence_type">
                            <option value="weekly">📅 Weekly</option>
                            <option value="monthly">📆 Monthly</option>
                            <option value="quarterly">📅 Quarterly (3 months)</option>
                            <option value="half_yearly">📆 Half Yearly (6 months)</option>
                            <option value="annually">📅 Annually (12 months)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="recurrence_interval">Repeat Every</label>
                        <div class="interval-input">
                            <input type="number" id="recurrence_interval" name="recurrence_interval" min="1" max="12" value="1">
                            <span id="interval_label">week(s)</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="recurrence_end_date">End Recurrence</label>
                        <input type="date" id="recurrence_end_date" name="recurrence_end_date" min="<?= date('Y-m-d', strtotime('+1 week')) ?>">
                        <small class="field-hint">Optional: When to stop creating recurring tasks</small>
                    </div>
                </div>
            </div>

            <!-- Follow-up Fields (Hidden by default) -->
            <div id="followupFields" class="form-section followup-section" style="display: none;">
                <h3>📞 Follow-up Details</h3>
                
                <div class="form-group">
                    <label class="form-label" for="followup_type">Follow-up Type *</label>
                    <select name="followup_type" id="followup_type" class="form-control">
                        <option value="standalone">Standalone Follow-up</option>
                        <option value="task" selected>Task-linked Follow-up</option>
                    </select>
                    <small class="form-help">This follow-up is linked to the current task</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contact_id">Contact</label>
                        <select name="contact_id" id="contact_id" class="form-control">
                        <option value="">-- Select or type to search --</option>
                        </select>
                        <small class="form-help">Select existing contact or leave empty for manual entry</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="follow_up_date">Follow-up Date *</label>
                        <input type="date" name="follow_up_date" id="follow_up_date" class="form-control">
                    <small class="form-help">When should this follow-up be performed?</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="followup_title">Follow-up Title *</label>
                    <input type="text" name="followup_title" id="followup_title" class="form-control" placeholder="e.g., Follow up on proposal discussion">
                    <small class="form-help">Brief description of what this follow-up is about</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="followup_description">Follow-up Description</label>
                    <textarea name="followup_description" id="followup_description" class="form-control" rows="3" placeholder="Additional details about this follow-up..."></textarea>
                    <small class="form-help">Optional: Add more context or notes about this follow-up</small>
                </div>
                
                <!-- Manual Contact Entry -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contact_company">Company</label>
                        <input type="text" id="contact_company" name="contact_company" class="form-control" placeholder="Company name">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contact_name">Contact Person</label>
                        <input type="text" id="contact_name" name="contact_name" class="form-control" placeholder="Contact person name">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contact_phone">Phone</label>
                        <input type="tel" id="contact_phone" name="contact_phone" class="form-control" placeholder="Phone number">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="project_name">Project</label>
                        <input type="text" id="project_name" name="project_name" class="form-control" placeholder="Project name">
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
// Update progress value display
function updateProgressValue(value) {
    document.getElementById('progressValue').textContent = value + '%';
    
    const statusSelect = document.getElementById('status');
    if (value >= 100) {
        statusSelect.value = 'completed';
    } else if (value > 0) {
        statusSelect.value = 'in_progress';
    } else {
        statusSelect.value = 'assigned';
    }
}

// Load task categories based on selected department
function loadTaskCategories() {
    const deptSelect = document.getElementById('department_id');
    const categorySelect = document.getElementById('task_category');
    const deptId = deptSelect.value;

    // Clear existing options
    categorySelect.innerHTML = '<option value="">Select Category</option>';

    if (!deptId) return;

    // Fetch categories for selected department via API
    fetch(`/ergon/api/task-categories?department_id=${deptId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Categories data:', data);
            if (data.categories) {
                console.log('Found categories:', data.categories.length);
                // Populate category dropdown with fetched data
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
    
    if (assignmentType === 'self') {
        // Show only current user
        assignedToSelect.innerHTML = '<option value="<?= $_SESSION['user_id'] ?>" selected><?= htmlspecialchars($_SESSION['user_name'] ?? 'You') ?></option>';
    } else {
        // Load all users for delegation
        loadAllUsers();
    }
}

// Load all users for assignment
function loadAllUsers() {
    const assignedToSelect = document.getElementById('assigned_to');
    
    fetch('/ergon/api/users')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.users) {
                assignedToSelect.innerHTML = '<option value="">Select User</option>';
                data.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name + (user.email ? ' (' + user.email + ')' : '');
                    assignedToSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
            assignedToSelect.innerHTML = '<option value="">Error loading users</option>';
        });
}

// Toggle recurring fields
function toggleRecurringFields(isInitial = false) {
    const checkbox = document.getElementById('is_recurring');
    const recurringFields = document.getElementById('recurringFields');
    
    if (checkbox.checked) {
        recurringFields.style.display = 'block';
        recurringFields.style.animation = 'slideDown 0.3s ease';
        
        if (!isInitial) {
            recurringFields.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        document.getElementById('recurrence_type').setAttribute('required', 'required');
        
        // Update interval label based on recurrence type
        updateIntervalLabel();
    } else {
        recurringFields.style.display = 'none';
        
        // Remove required attribute
        document.getElementById('recurrence_type').removeAttribute('required');
        
        // Clear values
        document.getElementById('recurrence_type').value = 'weekly';
        document.getElementById('recurrence_interval').value = '1';
        document.getElementById('recurrence_end_date').value = '';
    }
}

// Update interval label based on recurrence type
function updateIntervalLabel() {
    const type = document.getElementById('recurrence_type').value;
    const label = document.getElementById('interval_label');
    const intervalInput = document.getElementById('recurrence_interval');
    
    const labels = {
        'weekly': 'week(s)',
        'monthly': 'month(s)',
        'quarterly': 'quarter(s)',
        'half_yearly': 'half-year(s)',
        'annually': 'year(s)'
    };
    
    const maxValues = {
        'weekly': 52,
        'monthly': 12,
        'quarterly': 4,
        'half_yearly': 2,
        'annually': 5
    };
    
    label.textContent = labels[type] || 'period(s)';
    intervalInput.max = maxValues[type] || 12;
    
    if (parseInt(intervalInput.value) > maxValues[type]) {
        intervalInput.value = 1;
    }
}

// Toggle follow-up fields
function toggleFollowupFields(isInitial = false) {
    const checkbox = document.getElementById('followup_required');
    const followupFields = document.getElementById('followupFields');
    
    // Get follow-up form elements that need required validation
    const followupRequiredFields = [
        document.getElementById('follow_up_date'),
        document.getElementById('followup_title'),
        document.getElementById('followup_type')
    ];
    
    if (checkbox.checked) {
        followupFields.style.display = 'block';
        followupFields.style.animation = 'slideDown 0.3s ease';
        if (!isInitial) {
            followupFields.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        // Add required attribute to follow-up fields
        followupRequiredFields.forEach(field => {
            if (field) field.setAttribute('required', 'required');
        });
        
        // Set default follow-up date to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('follow_up_date').value = tomorrow.toISOString().split('T')[0];
        
        // Set default title based on task title
        const taskTitle = document.getElementById('title').value;
        if (taskTitle && !document.getElementById('followup_title').value) {
            document.getElementById('followup_title').value = 'Follow-up: ' + taskTitle;
        }
        
        // Load contacts for dropdown
        loadContacts();
    } else {
        followupFields.style.display = 'none';
        
        // Remove required attribute from follow-up fields
        followupRequiredFields.forEach(field => {
            if (field) field.removeAttribute('required');
        });
        
        // Clear follow-up field values to prevent submission of hidden data
        followupRequiredFields.forEach(field => {
            if (field && field.tagName === 'INPUT') field.value = '';
        });
        
        // Clear other follow-up related fields
        const otherFields = [
            'followup_description', 'contact_company', 'contact_name', 
            'contact_phone', 'project_name', 'contact_id'
        ];
        otherFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                } else {
                    field.value = '';
                }
            }
        });
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
let followupData = [];

// Load contacts for the dropdown
function loadContacts() {
    const contactSelect = document.getElementById('contact_id');
    if (contactSelect.length > 1) return; // Already loaded

    fetch('/ergon/api/contact-persons')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.contacts) {
                data.contacts.forEach(contact => { // ✅ REBUILT: Prevents duplicate options
                    const option = document.createElement('option');
                    option.value = contact.id || '';
                    option.textContent = contact.name + (contact.company ? ' - ' + contact.company : '');
                    contactSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Failed to load contacts:', error);
        });
}

// Handle contact selection to auto-fill fields
document.addEventListener('DOMContentLoaded', function() {
    const contactSelect = document.getElementById('contact_id');
    if (contactSelect) {
        contactSelect.addEventListener('change', function() {
            if (this.value) {
                // Find selected contact and auto-fill fields
                fetch('/ergon/api/contact-persons')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.contacts) {
                            const selectedContact = data.contacts.find(c => c.id == this.value);
                            if (selectedContact) {
                                document.getElementById('contact_company').value = selectedContact.company || '';
                                document.getElementById('contact_name').value = selectedContact.name || '';
                                document.getElementById('contact_phone').value = selectedContact.phone || '';
                            }
                        }
                    })
                    .catch(error => console.error('Error loading contact details:', error));
            }
        });
    }
});

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Help panel toggle
function toggleHelpPanel() {
    const helpPanel = document.getElementById('helpPanel');
    const isVisible = helpPanel.style.display !== 'none';
    
    if (isVisible) {
        helpPanel.style.display = 'none';
    } else {
        helpPanel.style.display = 'block';
        helpPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// SLA Time calculation
function updateSLAHours() {
    const hours = parseInt(document.getElementById('sla_hours_part').value) || 0;
    const minutes = parseInt(document.getElementById('sla_minutes_part').value) || 0;
    const totalHours = hours + (minutes / 60);
    document.getElementById('sla_hours').value = totalHours.toFixed(4);
}

// Form initialization
document.addEventListener('DOMContentLoaded', function() {
    // SLA time inputs event listeners
    document.getElementById('sla_hours_part').addEventListener('input', updateSLAHours);
    document.getElementById('sla_minutes_part').addEventListener('input', updateSLAHours);
    
    // Initial state setup
    toggleFollowupFields(true);
    toggleRecurringFields(true);

    // Initialize SLA time calculation
    updateSLAHours();
    
    // Set minimum date to today
    const deadlineInput = document.getElementById('deadline');
    const today = new Date().toISOString().split('T')[0];
    deadlineInput.min = today;
    
    // Initialize follow-up fields state (ensure they start without required attribute)
    const followupRequiredFields = [
        document.getElementById('follow_up_date'),
        document.getElementById('followup_title'),
        document.getElementById('followup_type')
    ];
    followupRequiredFields.forEach(field => {
        if (field) field.removeAttribute('required');
    });
    
    // Initialize recurring fields
    const recurringTypeSelect = document.getElementById('recurrence_type');
    if (recurringTypeSelect) {
        recurringTypeSelect.addEventListener('change', updateIntervalLabel);
        recurringTypeSelect.removeAttribute('required');
    }
    
    // Set minimum date for follow-up date field
    const followupDateInput = document.getElementById('follow_up_date');
    if (followupDateInput) {
        followupDateInput.min = today;
    }
    
    // Status/Progress sync
    const statusSelect = document.getElementById('status');
    const progressSlider = document.getElementById('progress');
    
    statusSelect.addEventListener('change', function() {
        if (this.value === 'completed') {
            progressSlider.value = 100;
            document.getElementById('progressValue').textContent = '100%';
        } else if (this.value === 'assigned' && progressSlider.value == 100) {
            progressSlider.value = 0;
            document.getElementById('progressValue').textContent = '0%';
        }
    });
    
    // Form validation
    document.getElementById('createTaskForm').addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const assignedTo = document.getElementById('assigned_to').value;
        const followupRequired = document.getElementById('followup_required').checked;
        
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
        
        // Validate follow-up fields only if follow-up is enabled
        if (followupRequired) {
            const followupDate = document.getElementById('follow_up_date').value.trim();
            const followupTitle = document.getElementById('followup_title').value.trim();
            
            if (!followupDate) {
                e.preventDefault();
                alert('Please select a follow-up date');
                document.getElementById('follow_up_date').focus();
                return;
            }
            
            if (!followupTitle) {
                e.preventDefault();
                alert('Please enter a follow-up title');
                document.getElementById('followup_title').focus();
                return;
            }
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
    border-radius: 6px;
    padding: 0.5rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    border: 1px solid var(--border-color);
}

.form-section {
    margin-bottom: 0.5rem;
    padding: 0.6rem;
    background: var(--bg-secondary);
    border-radius: 4px;
    border: 1px solid var(--border-color);
}

.form-section h3 {
    margin: 0 0 0.4rem 0;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
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
    font-size: 0.75rem;
    font-weight: 500;
    margin-bottom: 0.1rem;
    color: var(--text-primary);
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 0.3rem;
    border: 1px solid var(--border-color);
    border-radius: 3px;
    font-size: 0.75rem;
    background: var(--bg-primary);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(var(--primary-rgb), 0.1);
}

.options-section {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
}

.options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.4rem;
    margin-top: 0.4rem;
}

.option-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.6rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 4px;
    transition: all 0.15s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.option-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.option-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: var(--primary-light);
}

.option-card:hover::before {
    transform: scaleX(1);
}

.option-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.option-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-light), var(--primary));
    border-radius: 4px;
    font-size: 0.85rem;
    color: white;
    box-shadow: 0 1px 3px rgba(var(--primary-rgb), 0.15);
}

.option-content h4 {
    margin: 0 0 0.1rem 0;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-primary);
}

.option-content p {
    margin: 0;
    font-size: 0.65rem;
    color: var(--text-secondary);
    line-height: 1.1;
}

.option-toggle {
    position: relative;
}

.toggle-switch {
    display: none;
}

.toggle-label {
    display: block;
    width: 50px;
    height: 26px;
    background: var(--border-color);
    border-radius: 13px;
    cursor: pointer;
    position: relative;
    transition: all 0.3s ease;
}

.toggle-slider {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.toggle-switch:checked + .toggle-label {
    background: var(--primary);
}

.toggle-switch:checked + .toggle-label .toggle-slider {
    transform: translateX(24px);
    box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.4);
}

.toggle-switch:focus + .toggle-label {
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.2);
}

.option-card:has(.toggle-switch:checked) {
    background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.05), var(--bg-secondary));
    border-color: var(--primary);
}

.option-card:has(.toggle-switch:checked) .option-icon {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
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

.btn-icon {
    padding: 0.75rem;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    border-radius: 50%;
    position: relative;
}

.btn-icon::after {
    content: attr(title);
    position: absolute;
    bottom: -35px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
    z-index: 1000;
}

.btn-icon:hover::after {
    opacity: 1;
    visibility: visible;
}

.btn-primary {
    background: #3b82f6 !important;
    color: white !important;
    font-weight: 600;
}

.btn-primary:hover {
    background: #2563eb !important;
    color: white !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
}

[data-theme="dark"] .btn-primary {
    background: #2563eb !important;
    color: white !important;
}

[data-theme="dark"] .btn-primary:hover {
    background: #1d4ed8 !important;
    color: white !important;
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

.recurring-section {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), var(--bg-secondary));
    border: 2px dashed #22c55e;
    animation: slideDown 0.3s ease;
}

.interval-input {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.interval-input input {
    width: 80px;
    text-align: center;
}

.interval-input span {
    font-size: 0.75rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: border-color 0.2s ease;
    background: var(--bg-primary);
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-help {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #6b7280;
    font-style: italic;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
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

.search-input-container {
    position: relative;
}

.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-top: none;
    border-radius: 0 0 var(--border-radius) var(--border-radius);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    z-index: 1000;
    display: none;
    max-height: 200px;
    overflow-y: auto;
}

.suggestion-item {
    padding: 0.75rem;
    cursor: pointer;
    border-bottom: 1px solid var(--border-color);
    transition: background-color 0.2s ease;
}

.suggestion-item:hover {
    background-color: var(--bg-secondary);
}

.suggestion-item:last-child {
    border-bottom: none;
}

.search-input {
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.search-input:focus + .search-suggestions {
    border-color: var(--primary);
}

/* Help Panel Styles */
.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-help {
    background: var(--primary-light);
    color: var(--primary);
    border: 1px solid var(--primary);
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-help:hover {
    background: var(--primary);
    color: white;
}

.help-panel {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border: 1px solid var(--primary-light);
    border-radius: 4px;
    padding: 0.6rem;
    margin-bottom: 0.5rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}

.help-content h3 {
    margin: 0 0 0.6rem 0;
    color: var(--primary);
    font-size: 1rem;
    text-align: center;
}

.help-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.6rem;
    margin-bottom: 0.6rem;
}

.help-section {
    background: white;
    padding: 0.6rem;
    border-radius: 4px;
    border-left: 2px solid var(--primary);
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}

.help-section h4 {
    margin: 0 0 1rem 0;
    color: var(--primary);
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.help-section ul {
    margin: 0;
    padding-left: 1rem;
}

.help-section li {
    margin-bottom: 0.5rem;
    line-height: 1.4;
    font-size: 0.875rem;
}

.help-section li strong {
    color: var(--text-primary);
}

.help-tips {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    border: 2px dashed var(--primary-light);
}

.help-tips h4 {
    margin: 0 0 1rem 0;
    color: var(--primary);
    text-align: center;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.tip-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: 6px;
    border-left: 3px solid var(--primary-light);
}

.tip-icon {
    font-size: 1.2rem;
    flex-shrink: 0;
}

.tip-item span:last-child {
    font-size: 0.875rem;
    line-height: 1.4;
    color: var(--text-secondary);
}

/* Field Help Styles */
.field-help {
    color: var(--primary);
    cursor: help;
    font-size: 0.875rem;
    margin-left: 0.25rem;
}

.field-help:hover {
    color: var(--primary-dark);
}

.field-hint {
    display: block;
    margin-top: 0.1rem;
    font-size: 0.65rem;
    color: var(--text-secondary);
    line-height: 1.1;
    font-style: italic;
}

.form-group input:focus + .field-hint,
.form-group select:focus + .field-hint,
.form-group textarea:focus + .field-hint {
    color: var(--primary);
}

/* Enhanced form labels */
.form-group label {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
    color: var(--text-primary);
}

/* SLA Time Inputs */
.sla-time-inputs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sla-time-inputs input {
    width: 60px;
    text-align: center;
}

.sla-separator {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .help-grid {
        grid-template-columns: 1fr;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
    
    .header-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .help-panel {
        padding: 1rem;
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
    
    .help-grid {
        grid-template-columns: 1fr;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
    
    .header-actions {
        flex-direction: row;
        width: 100%;
        justify-content: space-between;
    }
    
    .help-panel {
        padding: 1rem;
    }
    
    .help-section {
        padding: 1rem;
    }
}
</style>

<?php
$content = ob_get_clean();
$title = 'Create Task';
$active_page = 'tasks';
include __DIR__ . '/../layouts/dashboard.php';
?>

<?php
require_once __DIR__ . '/../../app/helpers/Security.php';
$content = ob_start();
?>

<div class="compact-header">
    <h1><i class="bi bi-pencil-square"></i> Edit Task</h1>
    <div class="header-actions">
        <button type="button" class="btn-help" onclick="toggleHelpPanel()" title="Show Help">
            ❓ Help
        </button>
        <a href="/ergon/tasks" class="btn-back">← Back</a>
    </div>
</div>

<!-- Help Panel - Provides user guidance for task editing -->
<div id="helpPanel" class="help-panel" style="display: none;">
    <div class="help-content">
        <h3>📚 Task Editing Guide</h3>
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
                    <li><strong>Status:</strong> Current status - Assigned, In Progress, Completed, or Blocked</li>
                    <li><strong>Progress:</strong> Current completion percentage</li>
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
                    <li><strong>Follow-up Date/Time:</strong> When to contact client</li>
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
    <form id="editTaskForm" method="POST" action="/ergon/tasks/edit/<?= $task['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Security::generateCSRFToken()) ?>">
        
        <!-- Main Task Info -->
        <div class="form-section">
            <div class="form-grid">
                <div class="form-group span-2">
                    <label for="title">📝 Task Title * <span class="field-help" title="Clear, specific description of what needs to be done">ℹ️</span></label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($task['title'] ?? '') ?>" required placeholder="e.g., Update homepage banner, Fix login bug, Review project proposal">
                    <small class="field-hint">Be specific and actionable. Good: "Update contact form validation", Bad: "Fix form"</small>
                </div>
                <div class="form-group">
                    <label for="task_type">🏷️ Type <span class="field-help" title="Choose based on task complexity and importance">ℹ️</span></label>
                    <select id="task_type" name="task_type">
                        <option value="ad-hoc" <?= ($task['task_type'] ?? 'ad-hoc') === 'ad-hoc' ? 'selected' : '' ?>>📋 Task (General work item)</option>
                        <option value="checklist" <?= ($task['task_type'] ?? '') === 'checklist' ? 'selected' : '' ?>>✅ Checklist (Multiple steps)</option>
                        <option value="milestone" <?= ($task['task_type'] ?? '') === 'milestone' ? 'selected' : '' ?>>🎯 Milestone (Important goal)</option>
                        <option value="timed" <?= ($task['task_type'] ?? '') === 'timed' ? 'selected' : '' ?>>⏰ Urgent (Time-critical)</option>
                    </select>
                    <small class="field-hint">Task: regular work | Checklist: multi-step process | Milestone: major deliverable | Urgent: immediate attention</small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">📄 Description <span class="field-help" title="Detailed requirements and acceptance criteria">ℹ️</span></label>
                <textarea id="description" name="description" rows="3" placeholder="What needs to be done? What are the requirements? What defines completion? Include any relevant links, files, or context..."><?= htmlspecialchars($task['description'] ?? '') ?></textarea>
                <small class="field-hint">Include: requirements, acceptance criteria, resources needed, expected outcome. Be detailed to avoid confusion.</small>
            </div>
        </div>

        <!-- Assignment & Scheduling -->
        <div class="form-section">
            <h3>👥 Assignment & Schedule</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="assigned_to">🎯 Assign To *</label>
                    <select id="assigned_to" name="assigned_to" required>
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
                <div class="form-group">
                    <label for="planned_date">📅 Planned Date <span class="field-help" title="When do you plan to work on this? (Optional)">ℹ️</span></label>
                    <input type="date" id="planned_date" name="planned_date" value="<?= $task['planned_date'] ?? '' ?>" min="<?= date('Y-m-d') ?>">
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
                                <option value="<?= $dept['id'] ?>" <?= ($task['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
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
                    <label for="priority">🚨 Priority <span class="field-help" title="How urgent/important is this task?">ℹ️</span></label>
                    <select id="priority" name="priority">
                        <option value="low" <?= ($task['priority'] ?? '') === 'low' ? 'selected' : '' ?>>🟢 Low (Routine, can wait)</option>
                        <option value="medium" <?= ($task['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>🟡 Medium (Normal priority)</option>
                        <option value="high" <?= ($task['priority'] ?? '') === 'high' ? 'selected' : '' ?>>🔴 High (Urgent/Important)</option>
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
                    <input type="date" id="deadline" name="deadline" value="<?= $task['deadline'] ?? '' ?>" min="<?= date('Y-m-d') ?>">
                    <small class="field-hint">Hard deadline when task must be completed. Leave empty if no specific deadline.</small>
                </div>
                <div class="form-group">
                    <label for="sla_hours">⏱️ SLA Hours <span class="field-help" title="Expected completion time in hours">ℹ️</span></label>
                    <input type="number" id="sla_hours" name="sla_hours" value="<?= $task['sla_hours'] ?? '24' ?>" min="1" max="720" placeholder="24">
                    <small class="field-hint">Service Level Agreement: Expected hours to complete (24h = 1 day, 168h = 1 week)</small>
                </div>
                <div class="form-group">
                    <label for="status">📈 Status</label>
                    <select id="status" name="status">
                        <option value="assigned" <?= ($task['status'] ?? '') === 'assigned' ? 'selected' : '' ?>>📋 Assigned</option>
                        <option value="in_progress" <?= ($task['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>⚡ In Progress</option>
                        <option value="completed" <?= ($task['status'] ?? '') === 'completed' ? 'selected' : '' ?>>✅ Completed</option>
                        <option value="blocked" <?= ($task['status'] ?? '') === 'blocked' ? 'selected' : '' ?>>🚫 Blocked</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="progress">📊 Progress: <span id="progressValue"><?= $task['progress'] ?? '0' ?>%</span></label>
                <input type="range" id="progress" name="progress" min="0" max="100" value="<?= $task['progress'] ?? '0' ?>" oninput="updateProgressValue(this.value)" class="progress-slider">
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
                        <input type="checkbox" id="followup_required" name="followup_required" <?= !empty($task['followup_required']) ? 'checked' : '' ?> onchange="toggleFollowupFields()" class="toggle-switch">
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
                        <input type="checkbox" id="reminder_enabled" name="reminder_enabled" <?= !empty($task['reminder_enabled']) ? 'checked' : 'checked' ?> class="toggle-switch">
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
                        <input type="checkbox" id="time_tracking" name="time_tracking" <?= !empty($task['time_tracking']) ? 'checked' : '' ?> class="toggle-switch">
                        <label for="time_tracking" class="toggle-label">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Follow-up Fields (Hidden by default) -->
        <div id="followupFields" class="form-section followup-section" style="display: <?= !empty($task['followup_required']) ? 'block' : 'none' ?>;">
            <h3>📞 Follow-up Details</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="company_name">🏢 Company</label>
                    <div class="search-input-container">
                        <input type="text" id="company_name" name="company_name" value="<?= htmlspecialchars($task['company_name'] ?? '') ?>" class="search-input" placeholder="Type to search companies..." autocomplete="off">
                        <div class="search-suggestions" id="company_suggestions"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="contact_person">👤 Contact Person</label>
                    <div class="search-input-container">
                        <input type="text" id="contact_person" name="contact_person" value="<?= htmlspecialchars($task['contact_person'] ?? '') ?>" class="search-input" placeholder="Type to search contacts..." autocomplete="off">
                        <div class="search-suggestions" id="contact_suggestions"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="contact_phone">📱 Phone</label>
                    <input type="tel" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($task['contact_phone'] ?? '') ?>" placeholder="Contact phone number">
                </div>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="project_name">📁 Project</label>
                    <div class="search-input-container">
                        <input type="text" id="project_name" name="project_name" value="<?= htmlspecialchars($task['project_name'] ?? '') ?>" class="search-input" placeholder="Type to search projects..." autocomplete="off">
                        <div class="search-suggestions" id="project_suggestions"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="followup_date">📅 Follow-up Date</label>
                    <input type="date" id="followup_date" name="followup_date" value="<?= $task['followup_date'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label for="followup_time">⏰ Follow-up Time</label>
                    <input type="time" id="followup_time" name="followup_time" value="<?= $task['followup_time'] ?? '09:00' ?>">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                ✨ Update Task
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
    const currentCategory = '<?= htmlspecialchars($task['task_category'] ?? '') ?>';

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
            } else {
                console.log('No categories found in response');
            }
        })
        .catch(error => console.error('Error loading categories:', error));
}

// Toggle follow-up fields
function toggleFollowupFields() {
    const checkbox = document.getElementById('followup_required');
    const followupFields = document.getElementById('followupFields');
    
    if (checkbox.checked) {
        followupFields.style.display = 'block';
        followupFields.style.animation = 'slideDown 0.3s ease';
        
        // Set default follow-up date to tomorrow if empty
        const followupDateInput = document.getElementById('followup_date');
        if (!followupDateInput.value) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            followupDateInput.value = tomorrow.toISOString().split('T')[0];
        }
        
        // Load data and setup search inputs
        setTimeout(() => {
            loadFollowupDetails();
        }, 100);
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
let followupData = [];

function loadFollowupDetails() {
    const API_ENDPOINT = '/ergon/direct_followup_test.php';

    fetch(API_ENDPOINT)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json().catch(jsonError => {
                console.error('JSON parsing failed:', jsonError);
                throw new Error('Invalid JSON response from server');
            });
        })
        .then(data => {
            console.log('API Response received successfully:', data);
            followupData = Array.isArray(data.followups) ? data.followups : [];
            if (followupData.length === 0) {
                console.log('No real data available, using dummy data');
                followupData = getDummyFollowupData();
            }
            console.log(`Followup data loaded: ${followupData.length} items`);
            setupFollowupSearchInputs();
        })
        .catch(error => {
            console.error('Failed to load follow-up details:', error.message || error);
            followupData = getDummyFollowupData();
            console.log('Using fallback dummy data due to API error');
            setupFollowupSearchInputs();
        });
}

function getDummyFollowupData() {
    return [
        {company_name: 'Tech Solutions Inc', contact_person: 'John Smith', project_name: 'Website Redesign', contact_phone: '+1-555-0123'},
        {company_name: 'Digital Marketing Co', contact_person: 'Sarah Johnson', project_name: 'SEO Campaign', contact_phone: '+1-555-0124'},
        {company_name: 'Global Enterprises', contact_person: 'Mike Wilson', project_name: 'Mobile App', contact_phone: '+1-555-0125'},
        {company_name: 'StartUp Ventures', contact_person: 'Emily Davis', project_name: 'Brand Identity', contact_phone: '+1-555-0126'},
        {company_name: 'Corporate Systems', contact_person: 'David Brown', project_name: 'Database Migration', contact_phone: '+1-555-0127'}
    ];
}

function setupFollowupSearchInputs() {
    console.log('Setting up followup search inputs with data:', followupData);
    if (followupData.length === 0) {
        followupData = getDummyFollowupData();
    }
    setupFollowupSearchInput('company_name', 'company_suggestions', 'company_name');
    setupFollowupSearchInput('contact_person', 'contact_suggestions', 'contact_person');
    setupFollowupSearchInput('project_name', 'project_suggestions', 'project_name');
}

function setupFollowupSearchInput(inputId, suggestionsId, field) {
    const input = document.getElementById(inputId);
    const suggestions = document.getElementById(suggestionsId);

    if (!input || !suggestions) {
        console.log('Missing elements:', inputId, suggestionsId);
        return;
    }

    input.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        console.log('Search query:', query, 'for field:', field, 'data length:', followupData.length);

        if (query.length < 2) {
            suggestions.style.display = 'none';
            return;
        }

        const matches = [...new Set(
            followupData
                .map(item => item[field])
                .filter(value => value && value.toLowerCase().includes(query))
                .slice(0, 5)
        )];

        console.log('Found matches:', matches);

        if (matches.length > 0) {
            suggestions.innerHTML = matches
                .map(match => `<div class="suggestion-item" onclick="selectFollowupSuggestion('${inputId}', '${match.replace(/'/g, "\\'")}')"><strong>${match}</strong></div>`)
                .join('');
            suggestions.style.display = 'block';
        } else {
            suggestions.innerHTML = '<div class="suggestion-item">No matches found</div>';
            suggestions.style.display = 'block';
        }
    });

    if (inputId === 'contact_person') {
        input.addEventListener('blur', function() {
            const contactName = this.value.trim();
            if (contactName) {
                const contactData = followupData.find(item =>
                    item.contact_person && item.contact_person.toLowerCase() === contactName.toLowerCase()
                );
                if (contactData && contactData.contact_phone) {
                    document.getElementById('contact_phone').value = contactData.contact_phone;
                }
            }
        });
    }

    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !suggestions.contains(e.target)) {
            suggestions.style.display = 'none';
        }
    });
}

function selectFollowupSuggestion(inputId, value) {
    const input = document.getElementById(inputId);
    const suggestions = document.getElementById(inputId.replace('_name', '_suggestions').replace('_person', '_suggestions'));
    
    input.value = value;
    suggestions.style.display = 'none';
    
    if (inputId === 'company_name') {
        const companyData = followupData.find(item => item.company_name === value);
        if (companyData) {
            if (companyData.contact_person && !document.getElementById('contact_person').value) {
                document.getElementById('contact_person').value = companyData.contact_person;
            }
            if (companyData.contact_phone && !document.getElementById('contact_phone').value) {
                document.getElementById('contact_phone').value = companyData.contact_phone;
            }
            if (companyData.project_name && !document.getElementById('project_name').value) {
                document.getElementById('project_name').value = companyData.project_name;
            }
        }
    }
    
    if (inputId === 'contact_person') {
        const contactData = followupData.find(item => item.contact_person === value);
        if (contactData) {
            if (contactData.company_name && !document.getElementById('company_name').value) {
                document.getElementById('company_name').value = contactData.company_name;
            }
            if (contactData.contact_phone) {
                document.getElementById('contact_phone').value = contactData.contact_phone;
            }
            if (contactData.project_name && !document.getElementById('project_name').value) {
                document.getElementById('project_name').value = contactData.project_name;
            }
        }
    }
}

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

// Form initialization
document.addEventListener('DOMContentLoaded', function() {
    const currentDeptId = '<?= $task['department_id'] ?? '' ?>';
    const currentCategory = '<?= htmlspecialchars($task['task_category'] ?? '') ?>';
    
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
    
    const followupCheckbox = document.getElementById('followup_required');
    if (followupCheckbox && followupCheckbox.checked) {
        setTimeout(() => {
            loadFollowupDetails();
        }, 100);
    }
    
    const deadlineInput = document.getElementById('deadline');
    const today = new Date().toISOString().split('T')[0];
    deadlineInput.min = today;
    
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

.form-group label {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
    color: var(--text-primary);
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
$title = 'Edit Task';
$active_page = 'tasks';
include __DIR__ . '/../layouts/dashboard.php';
?>
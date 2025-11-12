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
                </div>
            </div>

            <!-- Follow-up Fields (Hidden by default) -->
            <div id="followupFields" class="form-section followup-section" style="display: none;">
                <h3>📞 Follow-up Details</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="company_name">🏢 Company</label>
                        <div class="search-input-container">
                            <input type="text" id="company_name" name="company_name" class="search-input" placeholder="Type to search companies..." autocomplete="off">
                            <div class="search-suggestions" id="company_suggestions"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="contact_person">👤 Contact Person</label>
                        <div class="search-input-container">
                            <input type="text" id="contact_person" name="contact_person" class="search-input" placeholder="Type to search contacts..." autocomplete="off">
                            <div class="search-suggestions" id="contact_suggestions"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">📱 Phone</label>
                        <input type="tel" id="contact_phone" name="contact_phone" placeholder="Contact phone number">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="project_name">📁 Project</label>
                        <div class="search-input-container">
                            <input type="text" id="project_name" name="project_name" class="search-input" placeholder="Type to search projects..." autocomplete="off">
                            <div class="search-suggestions" id="project_suggestions"></div>
                        </div>
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
    fetch('/ergon/direct_followup_test.php')
        .then(response => response.json())
        .then(data => {
            console.log('API Response:', data);
            followupData = data.followups || [];
            
            // Add dummy data if no real data exists
            if (followupData.length === 0) {
                followupData = [
                    {company_name: 'Tech Solutions Inc', contact_person: 'John Smith', project_name: 'Website Redesign', contact_phone: '+1-555-0123'},
                    {company_name: 'Digital Marketing Co', contact_person: 'Sarah Johnson', project_name: 'SEO Campaign', contact_phone: '+1-555-0124'},
                    {company_name: 'Global Enterprises', contact_person: 'Mike Wilson', project_name: 'Mobile App', contact_phone: '+1-555-0125'},
                    {company_name: 'StartUp Ventures', contact_person: 'Emily Davis', project_name: 'Brand Identity', contact_phone: '+1-555-0126'},
                    {company_name: 'Corporate Systems', contact_person: 'David Brown', project_name: 'Database Migration', contact_phone: '+1-555-0127'}
                ];
            }
            
            console.log('Followup data loaded:', followupData.length, 'items');
            setupFollowupSearchInputs();
            
            // Auto-populate title and description for follow-up tasks
            const titleField = document.getElementById('title');
            const descField = document.getElementById('description');
            
            if (!titleField.value) {
                titleField.value = 'Follow-up Call';
            }
            if (!descField.value) {
                descField.value = 'Follow up with client regarding project status and next steps';
            }
        })
        .catch(error => {
            console.log('Failed to load follow-up details:', error);
            // Use dummy data on error
            followupData = [
                {company_name: 'Tech Solutions Inc', contact_person: 'John Smith', project_name: 'Website Redesign', contact_phone: '+1-555-0123'},
                {company_name: 'Digital Marketing Co', contact_person: 'Sarah Johnson', project_name: 'SEO Campaign', contact_phone: '+1-555-0124'},
                {company_name: 'Global Enterprises', contact_person: 'Mike Wilson', project_name: 'Mobile App', contact_phone: '+1-555-0125'}
            ];
            setupFollowupSearchInputs();
        });
}

function setupFollowupSearchInputs() {
    console.log('Setting up followup search inputs with data:', followupData);
    
    // Ensure we have data before setting up inputs
    if (followupData.length === 0) {
        followupData = [
            {company_name: 'Tech Solutions Inc', contact_person: 'John Smith', project_name: 'Website Redesign', contact_phone: '+1-555-0123'},
            {company_name: 'Digital Marketing Co', contact_person: 'Sarah Johnson', project_name: 'SEO Campaign', contact_phone: '+1-555-0124'},
            {company_name: 'Global Enterprises', contact_person: 'Mike Wilson', project_name: 'Mobile App', contact_phone: '+1-555-0125'},
            {company_name: 'StartUp Ventures', contact_person: 'Emily Davis', project_name: 'Brand Identity', contact_phone: '+1-555-0126'},
            {company_name: 'Corporate Systems', contact_person: 'David Brown', project_name: 'Database Migration', contact_phone: '+1-555-0127'}
        ];
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
        
        const matches = [...new Set(followupData
            .map(item => item[field])
            .filter(value => value && value.toLowerCase().includes(query))
            .slice(0, 5))];
        
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
    
    // Special handling for contact person field to update phone on blur
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
    
    // Hide suggestions when clicking outside
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
    
    // Auto-fill related fields when selecting company
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
    
    // Auto-fill related fields when selecting contact
    if (inputId === 'contact_person') {
        const contactData = followupData.find(item => item.contact_person === value);
        if (contactData) {
            if (contactData.company_name && !document.getElementById('company_name').value) {
                document.getElementById('company_name').value = contactData.company_name;
            }
            // Always update phone number when contact person changes
            if (contactData.contact_phone) {
                document.getElementById('contact_phone').value = contactData.contact_phone;
            }
            if (contactData.project_name && !document.getElementById('project_name').value) {
                document.getElementById('project_name').value = contactData.project_name;
            }
        }
    }
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

.options-section {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
}

.options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.option-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem;
    background: var(--bg-secondary);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-light), var(--primary));
    border-radius: 10px;
    font-size: 1.2rem;
    color: white;
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3);
}

.option-content h4 {
    margin: 0 0 0.25rem 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-primary);
}

.option-content p {
    margin: 0;
    font-size: 0.8rem;
    color: var(--text-secondary);
    line-height: 1.3;
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

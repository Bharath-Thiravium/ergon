<?php
$title = 'Daily Planner Calendar';
$active_page = 'planner';
ob_start();

// Check if tables exist and setup file exists
$showSetupMessage = empty($data['departments']) && file_exists(__DIR__ . '/../../../setup_daily_planner.php');
?>

<?php if (isset($_SESSION['user']['department'])): ?>
<div class="department-badge"><?= $_SESSION['user']['department'] ?> Department</div>
<?php endif; ?>
<div class="header-actions" style="margin-bottom: var(--space-6);">
    <button class="btn btn--primary" onclick="openPlanModal()">+ Add Plan</button>
    <button class="btn btn--secondary" onclick="showTodayPlans()">Today's Plans</button>
</div>

<?php if ($showSetupMessage): ?>
<div class="alert alert--warning">
    <strong>⚠️ Setup Required</strong>
    The daily planner tables need to be created.
    <?php if ($_SESSION['role'] === 'owner'): ?>
        <div class="setup-buttons">
            <a href="/ergon/setup_daily_planner.php" class="btn btn--sm btn--primary">🚀 Run Setup Script</a>
        </div>
    <?php else: ?>
        <br><small>Please contact your administrator to run the setup.</small>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
            <div class="kpi-card__trend kpi-card__trend--up">Today</div>
        </div>
        <div class="kpi-card__value" id="todayPlansCount">0</div>
        <div class="kpi-card__label">Today's Plans</div>
        <div class="kpi-card__status kpi-card__status--active">Active</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend kpi-card__trend--up">Done</div>
        </div>
        <div class="kpi-card__value" id="completedPlansCount">0</div>
        <div class="kpi-card__label">Completed</div>
        <div class="kpi-card__status kpi-card__status--review">Finished</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">Pending</div>
        </div>
        <div class="kpi-card__value" id="pendingPlansCount">0</div>
        <div class="kpi-card__label">Pending</div>
        <div class="kpi-card__status kpi-card__status--pending">Waiting</div>
    </div>
</div>

<?php 
$events = [];
foreach ($data['plans'] as $plan) {
    $events[$plan['plan_date']][] = [
        'type' => 'task',
        'title' => $plan['title'],
        'priority' => $plan['priority']
    ];
}
include __DIR__ . '/../shared/calendar.php';
?>





<script>
let plans = <?= json_encode($data['plans']) ?>;

function updateStats() {
    const today = new Date().toISOString().split('T')[0];
    const todayPlans = plans.filter(plan => plan.plan_date === today);
    const completedPlans = todayPlans.filter(plan => plan.completion_status === 'completed');
    const pendingPlans = todayPlans.filter(plan => plan.completion_status !== 'completed');
    
    document.getElementById('todayPlansCount').textContent = todayPlans.length;
    document.getElementById('completedPlansCount').textContent = completedPlans.length;
    document.getElementById('pendingPlansCount').textContent = pendingPlans.length;
}

// Override calendar date selection to show plans
document.addEventListener('dateSelected', function(e) {
    showDayPlans(e.detail.date);
});

const depts = <?= json_encode($data['departments']) ?>;
const priorityOpts = [{value:'low',text:'Low'},{value:'medium',text:'Medium'},{value:'high',text:'High'},{value:'urgent',text:'Urgent'}];

function openPlanModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>📅 Add Plan</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="planForm" class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Date *</label>
                        <input type="date" class="form-control" name="plan_date" required value="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department *</label>
                        <select class="form-control" name="department_id" required>
                            ${depts.map(d => `<option value="${d.id}">${d.name}</option>`).join('')}
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" class="form-control" name="title" required placeholder="Enter plan title">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Plan description"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Priority *</label>
                        <select class="form-control" name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estimated Hours</label>
                        <input type="number" class="form-control" name="estimated_hours" step="0.5" placeholder="0.0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Reminder Time</label>
                    <input type="time" class="form-control" name="reminder_time">
                </div>
            </form>
            <div class="modal-footer">
                <button class="btn btn--secondary" onclick="closeModal()">Cancel</button>
                <button class="btn btn--primary" onclick="submitPlan()">✓ Create Plan</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeModal() {
    const modal = document.querySelector('.modal-overlay');
    if (modal) modal.remove();
}

function submitPlan() {
    const form = document.getElementById('planForm');
    const formData = new FormData(form);
    
    fetch('/ergon/planner/create', {
        method: 'POST',
        body: formData
    }).then(() => {
        closeModal();
        location.reload();
    });
}

function openProgressModal(plan) {
    showForm('📊 Update Progress', [
        {name:'completion_percentage',label:'Completion %',type:'range',value:plan.completion_percentage},
        {name:'actual_hours',label:'Actual Hours',type:'number',placeholder:'0.0'},
        {name:'notes',label:'Notes',type:'textarea',placeholder:'Progress notes'}
    ], (data) => submitProgressForm(plan.id, data));
}

function createHiddenForm(action, data) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;
    form.style.display = 'none';
    
    Object.entries(data).forEach(([key, value]) => {
        const input = document.createElement('input');
        input.name = key;
        input.value = value;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

function submitPlanForm(data) {
    showLoading('Creating...');
    createHiddenForm('/ergon/planner/create', data);
}

function submitProgressForm(planId, data) {
    showLoading('Updating...');
    createHiddenForm('/ergon/planner/update', {...data, plan_id: planId});
}

function loadDepartmentForm(departmentId, isProgressModal = false) {
    if (!departmentId) return;
    
    fetch(`/ergon/planner/getDepartmentForm?department_id=${departmentId}`)
        .then(response => response.json())
        .then(template => {
            if (template && isProgressModal) {
                renderDepartmentForm(template);
            }
        });
}

function renderDepartmentForm(template) {
    const section = document.getElementById('departmentFormSection');
    document.getElementById('templateId').value = template.id;
    
    let formHtml = `<h4>${template.form_name}</h4>`;
    const fields = JSON.parse(template.form_fields).fields;
    
    fields.forEach(field => {
        formHtml += `<div class="form-group">`;
        formHtml += `<label>${field.label}${field.required ? ' *' : ''}</label>`;
        
        if (field.type === 'textarea') {
            formHtml += `<textarea name="form_data[${field.name}]" ${field.required ? 'required' : ''}></textarea>`;
        } else {
            formHtml += `<input type="${field.type}" name="form_data[${field.name}]" ${field.required ? 'required' : ''}>`;
        }
        
        formHtml += `</div>`;
    });
    
    section.innerHTML = formHtml;
}

function showDayPlans(date) {
    const dayPlans = plans.filter(p => p.plan_date === date);
    if (!dayPlans.length) {
        showAlert('No plans for this day. Click "Add Plan" to create one.', 'info', 'No Plans');
        return;
    }
    
    const html = dayPlans.map(p => 
        `<div class="plan-detail-item">
            <div class="plan-detail-header">
                <strong>${p.title}</strong>
                <span class="badge badge-${p.priority}">${p.priority.toUpperCase()}</span>
            </div>
            <div class="plan-detail-meta">Progress: ${p.completion_percentage}% • ${p.department_name}</div>
            ${p.description ? `<div class="plan-detail-desc">${p.description}</div>` : ''}
        </div>`
    ).join('');
    
    showModal({title: `📅 Plans for ${date}`, body: html, size: 'md', buttons: [{text: 'Close', class: 'btn-primary'}]});
}

function showTodayPlans() {
    const today = new Date().toISOString().split('T')[0];
    const todayPlans = plans.filter(p => p.plan_date === today);
    
    if (!todayPlans.length) {
        alert('No plans for today. Click "Add Plan" to create one.');
        return;
    }
    
    let message = `Today's Plans (${todayPlans.length}):\n\n`;
    todayPlans.forEach((p, i) => {
        message += `${i + 1}. ${p.title} (${p.priority}) - ${p.completion_percentage}%\n`;
        if (p.description) message += `   ${p.description}\n`;
        message += '\n';
    });
    
    alert(message);
}

// Initialize stats
document.addEventListener('DOMContentLoaded', function() {
    updateStats();
});
</script>

<style>
/* Daily Planner Styles - Override dashboard-grid */
.department-badge {
    background: #007bff;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    margin-bottom: 10px;
    display: inline-block;
}

.setup-buttons {
    margin-left: 10px;
    display: inline-flex;
    gap: 5px;
}



.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #e0e0e0;
    border: 1px solid #e0e0e0;
    width: 100%;
    max-width: none;
}

.calendar-day-header {
    background: #f5f5f5;
    padding: 10px;
    text-align: center;
    font-weight: bold;
}

.calendar-day {
    background: white;
    min-height: 140px;
    padding: 10px;
    position: relative;
    cursor: pointer;
}

.calendar-day:hover {
    background: #f8f9fa;
}

.calendar-day.other-month {
    background: #f5f5f5;
    color: #999;
}

.calendar-day.today {
    background: #e3f2fd;
    border: 2px solid #2196f3;
}

.day-number {
    font-weight: bold;
    margin-bottom: 4px;
}

.plan-item {
    font-size: 11px;
    padding: 2px 4px;
    margin: 1px 0;
    border-radius: 3px;
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.plan-item.priority-urgent { background: #ffebee; color: #c62828; }
.plan-item.priority-high { background: #fff3e0; color: #ef6c00; }
.plan-item.priority-medium { background: #e8f5e8; color: #2e7d32; }
.plan-item.priority-low { background: #f3e5f5; color: #7b1fa2; }
.plan-item.completed { opacity: 0.6; text-decoration: line-through; }

.calendar-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.badge {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.badge-urgent { background: #dc3545; color: white; }
.badge-high { background: #fd7e14; color: white; }
.badge-medium { background: #28a745; color: white; }
.badge-low { background: #6f42c1; color: white; }

.plan-detail-item {
    padding: 12px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    margin-bottom: 8px;
}

.plan-detail-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
}

.plan-detail-meta {
    font-size: 14px;
    color: #6c757d;
}

.plan-detail-desc {
    font-size: 14px;
    margin-top: 4px;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-lg);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow: hidden;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-4);
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.modal-header h3 {
    margin: 0;
    color: var(--text-primary);
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--text-secondary);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    background: var(--bg-tertiary);
}

.modal-body {
    padding: var(--space-5);
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-2);
    padding: var(--space-4);
    border-top: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-4);
    margin-bottom: var(--space-4);
}

.form-group {
    margin-bottom: var(--space-4);
}

.form-label {
    display: block;
    margin-bottom: var(--space-2);
    font-weight: 500;
    color: var(--text-primary);
    font-size: var(--font-size-sm);
}

.form-control {
    width: 100%;
    padding: var(--space-3);
    background: var(--bg-primary, #ffffff);
    border: 1px solid var(--border-color, #e0e0e0);
    border-radius: var(--border-radius, 4px);
    color: var(--text-primary, #333333);
    font-size: var(--font-size-base, 14px);
    transition: var(--transition, all 0.2s ease);
}

/* Dark mode specific fixes */
@media (prefers-color-scheme: dark) {
    .form-control {
        background: #2d3748;
        border-color: #4a5568;
        color: #e2e8f0;
    }
    
    .form-control:focus {
        border-color: #63b3ed;
        box-shadow: 0 0 0 3px rgba(99,179,237,0.1);
    }
    
    .form-label {
        color: #e2e8f0;
    }
    
    .modal-content {
        background: #1a202c;
        color: #e2e8f0;
    }
    
    .modal-header {
        background: #2d3748;
        border-bottom-color: #4a5568;
    }
    
    .modal-footer {
        background: #2d3748;
        border-top-color: #4a5568;
    }
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(30,64,175,0.1);
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
        max-width: none;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
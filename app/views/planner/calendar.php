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

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Calendar View</h2>
        <div class="calendar-controls">
            <button class="btn btn--sm" onclick="previousMonth()">‹</button>
            <span id="currentMonth"><?= date('F Y') ?></span>
            <button class="btn btn--sm" onclick="nextMonth()">›</button>
        </div>
    </div>
    <div class="card__body">
        <div id="calendar" class="calendar-grid"></div>
    </div>
</div>





<script>
let currentDate = new Date();
let plans = <?= json_encode($data['plans']) ?>;

function renderCalendar() {
    const calendar = document.getElementById('calendar');
    const monthYear = document.getElementById('currentMonth');
    
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    monthYear.textContent = new Date(year, month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();
    
    calendar.innerHTML = '';
    
    // Add day headers
    const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayHeaders.forEach(day => {
        const header = document.createElement('div');
        header.className = 'calendar-day-header';
        header.textContent = day;
        header.className += ' calendar-day-header';
        calendar.appendChild(header);
    });
    
    // Add empty cells for days before month starts
    for (let i = 0; i < firstDay; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day other-month';
        calendar.appendChild(emptyDay);
    }
    
    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        
        const currentDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        
        if (today.getDate() === day && today.getMonth() === month && today.getFullYear() === year) {
            dayElement.classList.add('today');
        }
        
        dayElement.innerHTML = `<div class="day-number">${day}</div>`;
        dayElement.onclick = () => showDayPlans(currentDateStr);
        
        // Add plans for this day
        const dayPlans = plans.filter(plan => plan.plan_date === currentDateStr);
        dayPlans.forEach(plan => {
            const planElement = document.createElement('div');
            planElement.className = `plan-item priority-${plan.priority}`;
            if (plan.completion_status === 'completed') {
                planElement.classList.add('completed');
            }
            planElement.textContent = plan.title;
            planElement.onclick = (e) => {
                e.stopPropagation();
                openProgressModal(plan);
            };
            dayElement.appendChild(planElement);
        });
        
        calendar.appendChild(dayElement);
    }
    
    updateStats();
}

function updateStats() {
    const today = new Date().toISOString().split('T')[0];
    const todayPlans = plans.filter(plan => plan.plan_date === today);
    const completedPlans = todayPlans.filter(plan => plan.completion_status === 'completed');
    const pendingPlans = todayPlans.filter(plan => plan.completion_status !== 'completed');
    
    document.getElementById('todayPlansCount').textContent = todayPlans.length;
    document.getElementById('completedPlansCount').textContent = completedPlans.length;
    document.getElementById('pendingPlansCount').textContent = pendingPlans.length;
}

function previousMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
}

function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
}

const depts = <?= json_encode($data['departments']) ?>;
const priorityOpts = [{value:'low',text:'Low'},{value:'medium',text:'Medium'},{value:'high',text:'High'},{value:'urgent',text:'Urgent'}];

function openPlanModal() {
    showForm('📅 Add Plan', [
        {name:'plan_date',label:'Date',type:'date',required:true,value:new Date().toISOString().split('T')[0]},
        {name:'department_id',label:'Department',type:'select',required:true,options:depts.map(d=>({value:d.id,text:d.name}))},
        {name:'title',label:'Title',type:'text',required:true,placeholder:'Enter plan title'},
        {name:'description',label:'Description',type:'textarea',placeholder:'Description'},
        {name:'priority',label:'Priority',type:'select',required:true,value:'medium',options:priorityOpts},
        {name:'estimated_hours',label:'Estimated Hours',type:'number',placeholder:'0.0'},
        {name:'reminder_time',label:'Reminder Time',type:'time'}
    ], submitPlanForm);
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
    showDayPlans(today);
}

// Initialize calendar
document.addEventListener('DOMContentLoaded', function() {
    renderCalendar();
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
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
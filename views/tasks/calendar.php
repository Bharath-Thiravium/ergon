<?php
$title = 'Task Calendar';
$active_page = 'tasks';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>✅</span> Task Management</h1>
        <p>Manage and track all project tasks and assignments</p>
    </div>
    <div class="page-actions">
        <div class="view-options">
            <a href="/ergon/tasks" class="view-btn" data-view="list">
                <span>📋</span> List
            </a>
            <a href="/ergon/tasks/kanban" class="view-btn" data-view="kanban">
                <span>📏</span> Kanban
            </a>
            <a href="/ergon/tasks/calendar" class="view-btn view-btn--active" data-view="calendar">
                <span>📆</span> Calendar
            </a>
        </div>
        <a href="/ergon/tasks/create" class="btn btn--primary">
            <span>➕</span> Create Task
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📆</span> Tasks - Calendar View
        </h2>
        <div class="calendar-controls">
            <button class="btn btn--secondary" onclick="previousMonth()">
                <span>◀</span> Previous
            </button>
            <span class="current-month" id="currentMonth"></span>
            <button class="btn btn--secondary" onclick="nextMonth()">
                Next <span>▶</span>
            </button>
        </div>
    </div>
    <div class="card__body">
        <div class="calendar-container">
            <div class="calendar-grid" id="calendar-grid">
                <!-- Calendar will be generated here -->
            </div>
        </div>
    </div>
</div>

<style>
.calendar-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.current-month {
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--text-primary);
    min-width: 150px;
    text-align: center;
}

.calendar-container {
    width: 100%;
    max-height: 600px;
    overflow: hidden;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: var(--border-color);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.calendar-header {
    background: var(--bg-secondary);
    padding: var(--space-2);
    text-align: center;
    font-weight: 600;
    color: var(--text-primary);
    font-size: var(--font-size-xs);
}

.calendar-day {
    background: var(--bg-primary);
    min-height: 80px;
    max-height: 80px;
    padding: var(--space-1);
    position: relative;
    border: none;
    transition: var(--transition);
    cursor: pointer;
    overflow: hidden;
}

.calendar-day:hover {
    background: var(--bg-hover);
}

.calendar-day.other-month {
    background: var(--bg-tertiary);
    color: var(--text-muted);
}

.calendar-day.today {
    background: rgba(59, 130, 246, 0.1);
    border: 2px solid var(--primary);
}

.day-number {
    font-weight: 600;
    margin-bottom: var(--space-1);
    font-size: var(--font-size-xs);
}

.day-tasks {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    max-height: 50px;
    overflow: hidden;
}

.task-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--primary);
    display: inline-block;
}

.task-dot.high-priority { background: var(--error); }
.task-dot.medium-priority { background: var(--warning); }
.task-dot.low-priority { background: var(--success); }
.task-dot.completed { background: var(--gray-400); }

.task-count {
    font-size: 0.6rem;
    color: var(--text-muted);
    text-align: center;
    margin-top: 2px;
}

.task-count.has-tasks {
    color: var(--primary);
    font-weight: 600;
}

.task-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.task-modal-content {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    max-width: 500px;
    width: 90%;
    max-height: 80%;
    overflow: hidden;
}

.task-modal-header {
    padding: var(--space-4);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.task-modal-body {
    padding: var(--space-4);
    max-height: 400px;
    overflow-y: auto;
}

.task-item-detail {
    padding: var(--space-3);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: var(--space-2);
}

@media (max-width: 768px) {
    .calendar-controls {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .calendar-day {
        min-height: 80px;
        padding: 0.25rem;
    }
    
    .task-item {
        font-size: 0.7rem;
        padding: 1px 4px;
    }
}
</style>

<script>
let currentDate = new Date();
let tasks = <?= json_encode($tasks ?? []) ?>;

document.addEventListener('DOMContentLoaded', function() {
    renderCalendar();
});

function loadTasks() {
    fetch('/ergon/api/tasks')
        .then(response => response.json())
        .then(data => {
            tasks = data.tasks || [];
            renderCalendar();
        })
        .catch(error => console.error('Error loading tasks:', error));
}

function renderCalendar() {
    const grid = document.getElementById('calendar-grid');
    const monthElement = document.getElementById('currentMonth');
    
    // Update month display
    monthElement.textContent = currentDate.toLocaleDateString('en-US', { 
        month: 'long', 
        year: 'numeric' 
    });
    
    // Clear grid
    grid.innerHTML = '';
    
    // Add day headers
    const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayHeaders.forEach(day => {
        const header = document.createElement('div');
        header.className = 'calendar-header';
        header.textContent = day;
        grid.appendChild(header);
    });
    
    // Get first day of month and number of days
    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    // Generate calendar days
    for (let i = 0; i < 42; i++) {
        const date = new Date(startDate);
        date.setDate(startDate.getDate() + i);
        
        const dayElement = createDayElement(date);
        grid.appendChild(dayElement);
    }
}

function createDayElement(date) {
    const day = document.createElement('div');
    day.className = 'calendar-day';
    
    const isCurrentMonth = date.getMonth() === currentDate.getMonth();
    const isToday = date.toDateString() === new Date().toDateString();
    
    if (!isCurrentMonth) {
        day.classList.add('other-month');
    }
    if (isToday) {
        day.classList.add('today');
    }
    
    // Day number
    const dayNumber = document.createElement('div');
    dayNumber.className = 'day-number';
    dayNumber.textContent = date.getDate();
    day.appendChild(dayNumber);
    
    // Tasks for this day
    const dayTasks = document.createElement('div');
    dayTasks.className = 'day-tasks';
    
    const dateStr = date.toISOString().split('T')[0];
    const dayTaskList = tasks.filter(task => {
        const taskDate = task.deadline || task.due_date;
        if (!taskDate) return false;
        const taskDateStr = taskDate.split(' ')[0]; // Handle datetime format
        return taskDateStr === dateStr;
    });
    
    // Show task dots for compact view
    dayTaskList.slice(0, 8).forEach(task => {
        const taskDot = document.createElement('div');
        taskDot.className = `task-dot ${task.priority}-priority`;
        if (task.status === 'completed') {
            taskDot.classList.add('completed');
        }
        taskDot.title = `${task.title} - ${task.status} (${task.progress || 0}%)`;
        dayTasks.appendChild(taskDot);
    });
    
    if (dayTaskList.length > 0) {
        const countElement = document.createElement('div');
        countElement.className = 'task-count has-tasks';
        countElement.textContent = `${dayTaskList.length} task${dayTaskList.length > 1 ? 's' : ''}`;
        day.appendChild(countElement);
        
        day.onclick = () => showDayTasks(date, dayTaskList);
    }
    
    day.appendChild(dayTasks);
    return day;
}

function previousMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
}

function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
}

function viewTask(taskId) {
    window.location.href = `/ergon/tasks/view/${taskId}`;
}

function showDayTasks(date, dayTasks) {
    const dateStr = date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    const modal = document.createElement('div');
    modal.className = 'task-modal';
    modal.innerHTML = `
        <div class="task-modal-content">
            <div class="task-modal-header">
                <h3>Tasks for ${dateStr}</h3>
                <button onclick="this.closest('.task-modal').remove()">&times;</button>
            </div>
            <div class="task-modal-body">
                ${dayTasks.map(task => `
                    <div class="task-item-detail">
                        <div class="task-title">${task.title}</div>
                        <div class="task-meta">Status: ${task.status} | Progress: ${task.progress || 0}% | Priority: ${task.priority}</div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
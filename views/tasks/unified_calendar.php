<?php
$content = ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="bi bi-calendar3"></i> Task Calendar</h1>
        <p>Monthly overview of all allocated tasks and planning</p>
    </div>
    <div class="page-actions">
        <div class="calendar-nav">
            <button class="btn btn--secondary" onclick="changeMonth(-1)">
                <i class="bi bi-chevron-left"></i> Previous
            </button>
            <span class="current-month"><?= date('F Y', mktime(0, 0, 0, $current_month, 1, $current_year)) ?></span>
            <button class="btn btn--secondary" onclick="changeMonth(1)">
                Next <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <a href="/ergon/workflow/create-task" class="btn btn--primary">
            <i class="bi bi-plus-circle"></i> Add Task
        </a>
    </div>
</div>

<div class="calendar-wrapper">
    <div class="calendar-container">
        <div class="calendar-main">
            <div class="calendar-grid">
                <!-- Calendar Header -->
                <div class="calendar-header">
                    <div class="day-header">Sun</div>
                    <div class="day-header">Mon</div>
                    <div class="day-header">Tue</div>
                    <div class="day-header">Wed</div>
                    <div class="day-header">Thu</div>
                    <div class="day-header">Fri</div>
                    <div class="day-header">Sat</div>
                </div>

                <!-- Calendar Body -->
                <div class="calendar-body">
            <?php
            $firstDay = mktime(0, 0, 0, $current_month, 1, $current_year);
            $daysInMonth = date('t', $firstDay);
            $startDay = date('w', $firstDay);
            $today = date('Y-m-d');
            
            // Group tasks by date
            $tasksByDate = [];
            foreach ($calendar_tasks as $task) {
                $date = $task['date'];
                if (!isset($tasksByDate[$date])) {
                    $tasksByDate[$date] = [];
                }
                $tasksByDate[$date][] = $task;
            }
            
            // Empty cells for days before month starts
            for ($i = 0; $i < $startDay; $i++) {
                echo '<div class="calendar-day empty"></div>';
            }
            
            // Days of the month
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                $isToday = $currentDate === $today;
                $dayTasks = $tasksByDate[$currentDate] ?? [];
                
                $dayClass = 'calendar-day';
                if ($isToday) $dayClass .= ' today';
                if (!empty($dayTasks)) $dayClass .= ' has-tasks';
                
                echo '<div class="' . $dayClass . '" data-date="' . $currentDate . '">';
                echo '<div class="day-number">' . $day . '</div>';
                
                if (!empty($dayTasks)) {
                    echo '<div class="day-tasks">';
                    $taskCount = 0;
                    foreach ($dayTasks as $task) {
                        if ($taskCount >= 2) {
                            $remaining = count($dayTasks) - 2;
                            echo '<div class="task-item more">+' . $remaining . ' more</div>';
                            break;
                        }
                        
                        $priorityClass = 'priority-' . ($task['priority'] ?? 'medium');
                        $typeClass = 'type-' . ($task['type'] ?? 'task');
                        $statusIcon = $task['status'] === 'completed' ? '✅' : ($task['status'] === 'in_progress' ? '🔄' : '📋');
                        $typeIcon = $task['task_type'] === 'milestone' ? '🏁' : ($task['task_type'] === 'checklist' ? '☑️' : '📋');
                        
                        $tooltipText = $task['title'];
                        if (!empty($task['description'])) {
                            $tooltipText .= '\n' . $task['description'];
                        }
                        if (!empty($task['project_name'])) {
                            $tooltipText .= '\nProject: ' . $task['project_name'];
                        }
                        if (!empty($task['company_name'])) {
                            $tooltipText .= '\nCompany: ' . $task['company_name'];
                        }
                        if (!empty($task['assigned_by_user'])) {
                            $tooltipText .= '\nAssigned by: ' . $task['assigned_by_user'];
                        }
                        if (!empty($task['department_name'])) {
                            $tooltipText .= '\nDepartment: ' . $task['department_name'];
                        }
                        $tooltipText .= '\nStatus: ' . ucfirst($task['status']) . ' (' . ($task['progress'] ?? 0) . '%)';
                        
                        echo '<div class="task-item ' . $priorityClass . ' ' . $typeClass . '" title="' . htmlspecialchars($tooltipText) . '">';
                        echo '<div class="task-header-mini">';
                        echo '<span class="task-icon">' . $typeIcon . '</span>';
                        echo '<span class="task-status">' . $statusIcon . '</span>';
                        if ($task['progress'] > 0) {
                            echo '<span class="task-progress">' . $task['progress'] . '%</span>';
                        }
                        echo '</div>';
                        echo '<span class="task-title">' . htmlspecialchars(substr($task['title'], 0, 15)) . '</span>';
                        
                        // Show project, company, or category
                        if (!empty($task['project_name'])) {
                            echo '<div class="task-meta">📁 ' . htmlspecialchars(substr($task['project_name'], 0, 12)) . '</div>';
                        } elseif (!empty($task['company_name'])) {
                            echo '<div class="task-meta">🏢 ' . htmlspecialchars(substr($task['company_name'], 0, 12)) . '</div>';
                        } elseif (!empty($task['task_category'])) {
                            echo '<div class="task-meta">🏷️ ' . htmlspecialchars(substr($task['task_category'], 0, 12)) . '</div>';
                        }
                        
                        // Show assigned by or department
                        if (!empty($task['assigned_by_user'])) {
                            echo '<div class="task-assignee">👤 ' . htmlspecialchars(substr($task['assigned_by_user'], 0, 10)) . '</div>';
                        } elseif (!empty($task['department_name'])) {
                            echo '<div class="task-assignee">🏢 ' . htmlspecialchars(substr($task['department_name'], 0, 10)) . '</div>';
                        }
                        
                        echo '</div>';
                        $taskCount++;
                    }
                    echo '</div>';
                }
                
                echo '</div>';
            }
            ?>
                </div>
            </div>
        </div>

        <!-- Task Details Sidebar -->
        <div class="calendar-sidebar">
            <div class="task-sidebar" id="taskSidebar" style="display: none;">
                <div class="sidebar-header">
                    <h3 id="sidebarDate">Select a date</h3>
                    <button class="sidebar-close" onclick="closeSidebar()">&times;</button>
                </div>
                <div class="sidebar-content" id="sidebarContent">
                    <!-- Task details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Legend -->
<div class="calendar-legend">
    <h4>Legend</h4>
    <div class="legend-items">
        <div class="legend-item">
            <span class="legend-color priority-high"></span>
            <span>High Priority</span>
        </div>
        <div class="legend-item">
            <span class="legend-color priority-medium"></span>
            <span>Medium Priority</span>
        </div>
        <div class="legend-item">
            <span class="legend-color priority-low"></span>
            <span>Low Priority</span>
        </div>
        <div class="legend-item">
            <span class="legend-color type-task"></span>
            <span>📋 Task</span>
        </div>
        <div class="legend-item">
            <span class="legend-color type-planner"></span>
            <span>📅 Planner Entry</span>
        </div>
        <div class="legend-item">
            <span class="legend-color type-followup"></span>
            <span>📞 Follow-up</span>
        </div>
    </div>
</div>

<script>
let currentMonth = <?= $current_month ?>;
let currentYear = <?= $current_year ?>;

function changeMonth(direction) {
    currentMonth += direction;
    if (currentMonth > 12) {
        currentMonth = 1;
        currentYear++;
    } else if (currentMonth < 1) {
        currentMonth = 12;
        currentYear--;
    }
    
    window.location.href = `/ergon/workflow/calendar?month=${currentMonth}&year=${currentYear}`;
}

// Add click handlers to calendar days
document.addEventListener('DOMContentLoaded', function() {
    const calendarDays = document.querySelectorAll('.calendar-day[data-date]');
    
    calendarDays.forEach(day => {
        day.addEventListener('click', function() {
            const date = this.dataset.date;
            showTasksForDate(date);
        });
    });
});

function showTasksForDate(date) {
    const sidebar = document.getElementById('taskSidebar');
    const sidebarDate = document.getElementById('sidebarDate');
    const sidebarContent = document.getElementById('sidebarContent');
    
    sidebarDate.textContent = formatDate(date);
    sidebar.style.display = 'block';
    
    // Load tasks for the selected date
    fetch(`/ergon/api/tasks-for-date?date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.tasks && data.tasks.length > 0) {
                let html = '<div class="date-tasks">';
                data.tasks.forEach(task => {
                    html += `
                        <div class="sidebar-task priority-${task.priority} type-${task.type}">
                            <div class="task-header">
                                <h4>${task.title}</h4>
                                <span class="task-type">${task.type}</span>
                            </div>
                            <div class="task-meta">
                                <span class="priority">🔥 ${task.priority}</span>
                                <span class="status">📊 ${task.status} (${task.progress || 0}%)</span>
                                <span class="assignee">👤 ${task.assigned_by_user || 'Self-assigned'}</span>
                                <span class="department">🏢 ${task.department_name || 'No department'}</span>
                                <span class="category">🏷️ ${task.task_category || 'General'}</span>
                                <span class="due">📅 ${task.deadline || task.planned_date || 'No due date'}</span>
                            </div>
                            ${task.description ? `<div class="task-description">${task.description}</div>` : ''}
                            ${task.project_name ? `<div class="task-project">📁 Project: ${task.project_name}</div>` : ''}
                            ${task.company_name ? `<div class="task-company">🏢 Company: ${task.company_name}</div>` : ''}
                            ${task.contact_person ? `<div class="task-contact">📞 Contact: ${task.contact_person}</div>` : ''}
                            <div class="task-actions">
                                <a href="/ergon/workflow/daily-planner/${date}" class="btn btn--primary">📅 Day</a>
                                <a href="/ergon/tasks/view/${task.id}" class="btn btn--secondary">👁️ View</a>
                                <a href="/ergon/tasks/edit/${task.id}" class="btn btn--warning">✏️ Edit</a>
                                <button onclick="markComplete(${task.id})" class="btn btn--success">✅ Done</button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                
                html += `
                    <div class="sidebar-actions">
                        <a href="/ergon/workflow/create-task?planned_date=${date}" class="btn btn--primary">
                            <i class="bi bi-plus"></i> Add Task for This Date
                        </a>
                        <a href="/ergon/workflow/daily-planner/${date}" class="btn btn--success">
                            <i class="bi bi-calendar-day"></i> View Daily Planner
                        </a>
                    </div>
                `;
                
                sidebarContent.innerHTML = html;
            } else {
                sidebarContent.innerHTML = `
                    <div class="no-tasks">
                        <i class="bi bi-calendar-x"></i>
                        <h4>No tasks for this date</h4>
                        <p>You don't have any tasks scheduled for ${formatDate(date)}</p>
                        <a href="/ergon/workflow/create-task?planned_date=${date}" class="btn btn--primary">
                            <i class="bi bi-plus"></i> Add Task
                        </a>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading tasks:', error);
            sidebarContent.innerHTML = '<div class="error">Error loading tasks</div>';
        });
}

function closeSidebar() {
    document.getElementById('taskSidebar').style.display = 'none';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}
</script>

<style>
.calendar-wrapper {
    width: 100%;
    max-width: 100%;
    overflow: hidden;
    margin-top: 1rem;
}

.calendar-container {
    display: block;
    width: 100%;
    max-width: calc(100% - 340px);
    box-sizing: border-box;
    margin-right: 340px;
}

.calendar-main {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}

.calendar-sidebar {
    position: fixed;
    top: var(--header-height, 120px);
    right: 0;
    width: 320px;
    height: calc(100vh - var(--header-height, 120px));
    z-index: 1000;
    background: var(--bg-primary);
    border-left: 1px solid var(--border-color);
    overflow-y: auto;
    box-shadow: -2px 0 8px rgba(0,0,0,0.1);
}

.calendar-nav {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.current-month {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--primary);
    min-width: 150px;
    text-align: center;
}

.calendar-grid {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: var(--primary);
    color: white;
    width: 100%;
}

.day-header {
    padding: 0.75rem 0.5rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
    box-sizing: border-box;
    min-width: 0;
}

.calendar-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: var(--border-color);
    width: 100%;
    box-sizing: border-box;
}

.calendar-day {
    background: var(--bg-primary);
    min-height: 130px;
    padding: 0.4rem;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    box-sizing: border-box;
    min-width: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.calendar-day:hover {
    background: var(--bg-secondary);
}

.calendar-day.today {
    background: var(--primary-light);
    border: 2px solid var(--primary);
}

.calendar-day.has-tasks {
    background: var(--success-light);
}

.calendar-day.empty {
    background: var(--bg-disabled);
    cursor: default;
}

.day-number {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    flex-shrink: 0;
    font-size: 0.9rem;
}

.day-tasks {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
    overflow: hidden;
}

.task-item {
    padding: 3px 4px;
    border-radius: 4px;
    font-size: 0.7rem;
    line-height: 1.1;
    overflow: hidden;
    margin-bottom: 1px;
    border-left: 2px solid transparent;
}

.task-header-mini {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1px;
}

.task-icon, .task-status {
    font-size: 0.6rem;
}

.task-title {
    display: block;
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.task-meta {
    font-size: 0.6rem;
    color: var(--text-secondary);
    margin-top: 1px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.task-assignee {
    font-size: 0.6rem;
    color: var(--text-secondary);
    margin-top: 1px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.task-progress {
    font-size: 0.6rem;
    color: var(--success);
    font-weight: 600;
}

.task-item.priority-high {
    background: var(--danger-light);
    color: var(--danger);
    border-left-color: var(--danger);
}

.task-item.priority-medium {
    background: var(--warning-light);
    color: var(--warning);
    border-left-color: var(--warning);
}

.task-item.priority-low {
    background: var(--success-light);
    color: var(--success);
    border-left-color: var(--success);
}

.task-item.type-planner {
    border-left-color: var(--info);
}

.task-item.type-followup {
    border-left-color: var(--primary);
}

.task-item.type-task {
    border-left-color: var(--secondary);
}

.task-item.more {
    background: var(--bg-secondary);
    color: var(--text-secondary);
    text-align: center;
    font-style: italic;
}

.task-sidebar {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: transparent;
}

.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
    font-size: 0.85rem;
}

.sidebar-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.sidebar-content {
    padding: 0.75rem;
    flex: 1;
    overflow-y: auto;
}

.sidebar-task {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    margin-bottom: 0.5rem;
    background: var(--bg-secondary);
    font-size: 0.85rem;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.task-header h4 {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.3;
}

.task-type {
    font-size: 0.8rem;
    padding: 0.2rem 0.5rem;
    background: var(--primary-light);
    color: var(--primary);
    border-radius: 12px;
}

.task-meta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.task-actions {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.task-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    border-radius: 4px;
}

.task-description {
    font-size: 0.8rem;
    color: var(--text-primary);
    margin: 0.5rem 0;
    line-height: 1.3;
}

.task-project, .task-company, .task-contact {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin: 0.25rem 0;
}

.sidebar-actions {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.no-tasks {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--text-secondary);
}

.no-tasks i {
    font-size: 2rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.page-actions {
    margin-right: 340px;
}

.calendar-legend {
    margin-top: 1.5rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.legend-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
}

.legend-color.priority-high {
    background: var(--danger-light);
}

.legend-color.priority-medium {
    background: var(--warning-light);
}

.legend-color.priority-low {
    background: var(--success-light);
}

.legend-color.type-task {
    background: var(--primary-light);
}

.legend-color.type-planner {
    background: var(--info-light);
}

.legend-color.type-followup {
    background: var(--primary-light);
}

@media (max-width: 1200px) {
    .calendar-container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .calendar-main {
        max-width: 100%;
    }
    
    .calendar-sidebar {
        width: 100%;
        max-width: 100%;
    }
    
    .task-sidebar {
        position: fixed;
        top: 0;
        right: 0;
        width: 100%;
        height: 100vh;
        z-index: 1000;
        max-height: none;
    }
}

@media (max-width: 768px) {
    .calendar-day {
        min-height: 80px;
        padding: 0.25rem;
    }
    
    .day-header {
        padding: 0.5rem 0.25rem;
        font-size: 0.8rem;
    }
    
    .day-number {
        font-size: 0.8rem;
        margin-bottom: 0.25rem;
    }
    
    .task-item {
        font-size: 0.7rem;
        padding: 1px 2px;
    }
}
</style>

<?php
$content = ob_get_clean();
$title = 'Task Calendar';
$active_page = 'calendar';
include __DIR__ . '/../layouts/dashboard.php';
?>
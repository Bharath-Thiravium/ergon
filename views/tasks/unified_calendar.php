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
                        if ($taskCount >= 3) {
                            $remaining = count($dayTasks) - 3;
                            echo '<div class="task-item more">+' . $remaining . ' more</div>';
                            break;
                        }
                        
                        $priorityClass = 'priority-' . ($task['priority'] ?? 'medium');
                        $typeClass = 'type-' . ($task['type'] ?? 'task');
                        
                        echo '<div class="task-item ' . $priorityClass . ' ' . $typeClass . '" title="' . htmlspecialchars($task['title']) . '">';
                        echo '<span class="task-title">' . htmlspecialchars(substr($task['title'], 0, 20)) . '</span>';
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
            <span>Task</span>
        </div>
        <div class="legend-item">
            <span class="legend-color type-planner"></span>
            <span>Planner Entry</span>
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
                                <span class="priority">Priority: ${task.priority}</span>
                                <span class="status">Status: ${task.status}</span>
                            </div>
                            <div class="task-actions">
                                <a href="/ergon/workflow/daily-planner/${date}" class="btn btn--sm btn--primary">View Day</a>
                                <a href="/ergon/tasks/view/${task.id}" class="btn btn--sm btn--secondary">Details</a>
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
    display: flex;
    gap: 1.5rem;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.calendar-main {
    flex: 1;
    min-width: 0;
    max-width: calc(100% - 320px);
    overflow: hidden;
}

.calendar-sidebar {
    width: 300px;
    flex-shrink: 0;
    max-width: 300px;
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
    min-height: 120px;
    padding: 0.5rem;
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
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.75rem;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.task-item.priority-high {
    background: var(--danger-light);
    color: var(--danger);
}

.task-item.priority-medium {
    background: var(--warning-light);
    color: var(--warning);
}

.task-item.priority-low {
    background: var(--success-light);
    color: var(--success);
}

.task-item.type-planner {
    border-left: 3px solid var(--info);
}

.task-item.more {
    background: var(--bg-secondary);
    color: var(--text-secondary);
    text-align: center;
    font-style: italic;
}

.task-sidebar {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    height: fit-content;
    max-height: 80vh;
    overflow-y: auto;
}

.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.sidebar-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.sidebar-content {
    padding: 1.5rem;
}

.sidebar-task {
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    background: var(--bg-secondary);
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.task-header h4 {
    margin: 0;
    font-size: 1rem;
}

.task-type {
    font-size: 0.8rem;
    padding: 0.2rem 0.5rem;
    background: var(--primary-light);
    color: var(--primary);
    border-radius: 12px;
}

.task-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.task-actions {
    display: flex;
    gap: 0.5rem;
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
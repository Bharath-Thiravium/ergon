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
                        
                        echo '<div class="task-item ' . $priorityClass . '">';
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
    
    // Get tasks from the calendar data already loaded
    const calendarTasks = <?= json_encode($calendar_tasks) ?>;
    const dateTasks = calendarTasks.filter(task => task.date === date);
    
    if (dateTasks.length > 0) {
        let html = '<div class="date-tasks">';
        dateTasks.forEach(task => {
            html += `
                <div class="sidebar-task">
                    <h4>${task.title}</h4>
                    <p><strong>Priority:</strong> ${task.priority}</p>
                    <p><strong>Status:</strong> ${task.status}</p>
                    ${task.description ? `<p><strong>Description:</strong> ${task.description}</p>` : ''}
                    ${task.project_name ? `<p><strong>Project:</strong> ${task.project_name}</p>` : ''}
                    ${task.company_name ? `<p><strong>Company:</strong> ${task.company_name}</p>` : ''}
                    <div class="task-actions">
                        <a href="/ergon/tasks/view/${task.id}" class="btn btn--secondary">View</a>
                        <a href="/ergon/tasks/edit/${task.id}" class="btn btn--warning">Edit</a>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        sidebarContent.innerHTML = html;
    } else {
        sidebarContent.innerHTML = `
            <div class="no-tasks">
                <h4>No tasks for this date</h4>
                <p>You don't have any tasks scheduled for ${formatDate(date)}</p>
                <a href="/ergon/tasks/create" class="btn btn--primary">Add Task</a>
            </div>
        `;
    }
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
    box-shadow: var(--shadow-lg);
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
    padding: var(--space-2);
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    box-sizing: border-box;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
}

.calendar-day:hover {
    background: var(--bg-secondary);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.calendar-day.today {
    background: var(--primary-light);
    border-color: var(--primary);
    color: var(--primary);
}

.calendar-day.has-tasks {
    background: var(--success-light);
    border-color: var(--success);
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
    padding: var(--space-1) var(--space-2);
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    line-height: 1.2;
    overflow: hidden;
    margin-bottom: var(--space-1);
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    transition: var(--transition);
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
    border-left: 3px solid var(--danger);
    background: var(--danger-light);
    color: var(--danger);
}

.task-item.priority-medium {
    border-left: 3px solid var(--warning);
    background: var(--warning-light);
    color: var(--warning);
}

.task-item.priority-low {
    border-left: 3px solid var(--success);
    background: var(--success-light);
    color: var(--success);
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
    padding: var(--space-4);
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
    font-size: var(--font-size-base);
    font-weight: 600;
}

.sidebar-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
    padding: var(--space-1);
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.sidebar-close:hover {
    background: var(--bg-primary);
    color: var(--text-primary);
}

.sidebar-content {
    padding: 0.75rem;
    flex: 1;
    overflow-y: auto;
}

.sidebar-task {
    padding: var(--space-4);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: var(--space-3);
    background: var(--bg-secondary);
    font-size: var(--font-size-sm);
    transition: var(--transition);
}

.sidebar-task:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary);
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
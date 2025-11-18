<?php
$content = ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="bi bi-calendar-check"></i> Task Schedule</h1>
        <p>Visual overview of your task timeline and deadlines</p>
    </div>
    <div class="page-actions">
        <div class="view-controls">
            <button class="btn btn--sm <?= $view_type === 'calendar' ? 'btn--primary' : 'btn--secondary' ?>" onclick="switchView('calendar')">
                <i class="bi bi-calendar3"></i> Calendar
            </button>
        </div>
        <div class="calendar-nav">
            <button class="btn btn--secondary" onclick="changeMonth(-1)">
                <i class="bi bi-chevron-left"></i> Prev
            </button>
            <span class="current-month"><?= date('F Y', mktime(0, 0, 0, $current_month, 1, $current_year)) ?></span>
            <button class="btn btn--secondary" onclick="changeMonth(1)">
                Next <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <a href="/ergon/tasks/create" class="btn btn--primary">
            <i class="bi bi-plus-circle"></i> Add Task
        </a>
    </div>
</div>

<div class="task-visualizer" data-view="<?= $view_type ?>">
    <!-- Task Details Modal -->
    <div class="task-modal" id="taskModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalDate">Select a date</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Task details will be loaded here -->
            </div>
        </div>
    </div>

    <?php if ($view_type === 'calendar'): ?>
        <!-- Calendar View -->
        <div class="calendar-grid">
            <div class="calendar-header">
                <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day): ?>
                    <div class="day-header"><?= $day ?></div>
                <?php endforeach; ?>
            </div>
            
            <div class="calendar-body">
                <?php
                $firstDay = mktime(0, 0, 0, $current_month, 1, $current_year);
                $daysInMonth = date('t', $firstDay);
                $startDay = date('w', $firstDay);
                $today = date('Y-m-d');
                
                // Group tasks by date
                $tasksByDate = [];
                foreach ($tasks as $task) {
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
                            
                            $statusClass = 'status-' . $task['status'];
                            $priorityClass = 'priority-' . $task['priority'];
                            
                            echo '<div class="task-item ' . $statusClass . ' ' . $priorityClass . '" data-task-id="' . $task['id'] . '">';
                            echo '<span class="task-title">' . htmlspecialchars(substr($task['title'], 0, 20)) . '</span>';
                            echo '<span class="task-progress">' . $task['progress'] . '%</span>';
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
    <?php endif; ?>
</div>

<script>
let currentMonth = <?= $current_month ?>;
let currentYear = <?= $current_year ?>;
let currentView = '<?= $view_type ?>';

function changeMonth(direction) {
    currentMonth += direction;
    if (currentMonth > 12) {
        currentMonth = 1;
        currentYear++;
    } else if (currentMonth < 1) {
        currentMonth = 12;
        currentYear--;
    }
    
    window.location.href = `/ergon/tasks/schedule?month=${currentMonth}&year=${currentYear}&view=${currentView}`;
}

// Timeline view removed - calendar is the primary view

// Add click handlers to calendar days
document.addEventListener('DOMContentLoaded', function() {
    const calendarDays = document.querySelectorAll('.calendar-day[data-date]');
    
    calendarDays.forEach(day => {
        day.addEventListener('click', function() {
            const date = this.dataset.date;
            showTasksForDate(date);
        });
    });
    
    // Task item click handlers
    const taskItems = document.querySelectorAll('.task-item[data-task-id]');
    taskItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            const taskId = this.dataset.taskId;
            window.location.href = `/ergon/tasks/view/${taskId}`;
        });
    });
    
    // Modal click outside to close
    const modal = document.getElementById('taskModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
});

function showTasksForDate(date) {
    const modal = document.getElementById('taskModal');
    const modalDate = document.getElementById('modalDate');
    const modalContent = document.getElementById('modalContent');
    
    modalDate.textContent = formatDate(date);
    modal.style.display = 'block';
    
    const tasks = <?= json_encode($tasks) ?>;
    const dateTasks = tasks.filter(task => task.date === date);
    
    if (dateTasks.length > 0) {
        let html = '<div class="date-tasks">';
        dateTasks.forEach(task => {
            html += `
                <div class="modal-task">
                    <div class="task-header">
                        <h4>${task.title}</h4>
                        <div class="task-badges">
                            <span class="badge badge--${task.status}">${task.status}</span>
                            <span class="badge badge--${task.priority}">${task.priority}</span>
                        </div>
                    </div>
                    <p class="task-description">${task.description || 'No description'}</p>
                    <div class="task-meta">
                        <span class="task-progress">Progress: ${task.progress}%</span>
                        ${task.project_name ? `<span class="task-project">Project: ${task.project_name}</span>` : ''}
                    </div>
                    <div class="task-actions">
                        <a href="/ergon/tasks/view/${task.id}" class="btn btn--sm btn--secondary">View</a>
                        <a href="/ergon/tasks/edit/${task.id}" class="btn btn--sm btn--warning">Edit</a>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        modalContent.innerHTML = html;
    } else {
        modalContent.innerHTML = `
            <div class="no-tasks">
                <i class="bi bi-calendar-x"></i>
                <h4>No tasks for this date</h4>
                <p>You don't have any tasks scheduled for ${formatDate(date)}</p>
                <a href="/ergon/tasks/create" class="btn btn--primary">Add Task</a>
            </div>
        `;
    }
}

function closeModal() {
    document.getElementById('taskModal').style.display = 'none';
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
.task-visualizer {
    margin-top: 1rem;
}

.view-controls {
    display: flex;
    gap: 0.5rem;
    margin-right: 1rem;
}

.calendar-nav {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-right: 1rem;
}

.current-month {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary);
    min-width: 140px;
    text-align: center;
}

/* Calendar View Styles */
.calendar-grid {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow);
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: var(--primary);
    color: white;
}

.day-header {
    padding: 0.75rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
}

.calendar-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: var(--border-color);
}

.calendar-day {
    background: var(--bg-primary);
    min-height: 100px;
    padding: 0.5rem;
    cursor: pointer;
    transition: var(--transition);
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
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.day-tasks {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}

.task-item {
    padding: 2px 4px;
    border-radius: var(--border-radius-sm);
    font-size: 0.7rem;
    line-height: 1.2;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.task-item:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.task-item.status-completed {
    opacity: 0.7;
    text-decoration: line-through;
}

.task-item.priority-high {
    border-left: 3px solid var(--danger);
    background: var(--danger-light);
}

.task-item.priority-medium {
    border-left: 3px solid var(--warning);
    background: var(--warning-light);
}

.task-item.priority-low {
    border-left: 3px solid var(--success);
    background: var(--success-light);
}

.task-item.more {
    background: var(--bg-secondary);
    color: var(--text-secondary);
    text-align: center;
    font-style: italic;
    justify-content: center;
}

.task-progress {
    font-size: 0.6rem;
    font-weight: 600;
    color: var(--primary);
}

/* Timeline View Styles */
.timeline-view {
    max-width: 800px;
    margin: 0 auto;
}

.timeline-item {
    display: flex;
    margin-bottom: 1.5rem;
    position: relative;
}

.timeline-marker {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--primary);
    margin-right: 1rem;
    margin-top: 0.5rem;
    flex-shrink: 0;
}

.timeline-item.priority-high .timeline-marker {
    background: var(--danger);
}

.timeline-item.priority-medium .timeline-marker {
    background: var(--warning);
}

.timeline-item.priority-low .timeline-marker {
    background: var(--success);
}

.timeline-content {
    flex: 1;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    box-shadow: var(--shadow-sm);
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.task-header h4 {
    margin: 0;
    color: var(--text-primary);
}

.task-meta {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.task-details {
    margin-bottom: 1rem;
}

.task-description {
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.task-info {
    display: flex;
    gap: 1rem;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.task-info i {
    margin-right: 0.25rem;
}

.task-actions {
    display: flex;
    gap: 0.5rem;
}

.no-tasks {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary);
}

.no-tasks i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Modal Styles */
.task-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: var(--shadow-lg);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.modal-header h3 {
    margin: 0;
    color: var(--text-primary);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
    padding: 0.25rem;
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.modal-close:hover {
    background: var(--bg-primary);
    color: var(--text-primary);
}

.modal-body {
    padding: 1rem;
}

.modal-task {
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    background: var(--bg-secondary);
}

.modal-task:last-child {
    margin-bottom: 0;
}

.modal-task .task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.modal-task h4 {
    margin: 0;
    color: var(--text-primary);
}

.task-badges {
    display: flex;
    gap: 0.25rem;
}

.task-description {
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    line-height: 1.4;
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

@media (max-width: 768px) {
    .page-actions {
        flex-direction: column;
        gap: 1rem;
    }
    
    .view-controls, .calendar-nav {
        margin-right: 0;
    }
    
    .calendar-day {
        min-height: 80px;
        padding: 0.25rem;
    }
    
    .task-item {
        font-size: 0.6rem;
        padding: 1px 2px;
    }
    
    .timeline-item {
        flex-direction: column;
    }
    
    .timeline-marker {
        margin-right: 0;
        margin-bottom: 0.5rem;
    }
}
</style>

<?php
$content = ob_get_clean();
$title = 'Task Schedule';
$active_page = 'tasks';
include __DIR__ . '/../layouts/dashboard.php';
?>
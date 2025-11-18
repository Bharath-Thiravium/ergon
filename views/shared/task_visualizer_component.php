<?php
/**
 * Reusable Task Visualizer Component
 * Usage: include this file and call renderTaskVisualizer($tasks, $options)
 */

function renderTaskVisualizer($tasks, $options = []) {
    $view = $options['view'] ?? 'calendar';
    $month = $options['month'] ?? date('m');
    $year = $options['year'] ?? date('Y');
    $showControls = $options['show_controls'] ?? true;
    $compact = $options['compact'] ?? false;
    
    ?>
    <div class="task-visualizer-component <?= $compact ? 'compact' : '' ?>" data-view="<?= $view ?>">
        <?php if ($showControls): ?>
            <div class="visualizer-controls">
                <div class="view-toggle">
                    <button class="btn btn--sm <?= $view === 'calendar' ? 'btn--primary' : 'btn--secondary' ?>" 
                            onclick="switchVisualizerView('calendar')">
                        <i class="bi bi-calendar3"></i> Calendar
                    </button>
                    <button class="btn btn--sm <?= $view === 'timeline' ? 'btn--primary' : 'btn--secondary' ?>" 
                            onclick="switchVisualizerView('timeline')">
                        <i class="bi bi-list-task"></i> Timeline
                    </button>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($view === 'calendar'): ?>
            <div class="mini-calendar">
                <?php renderMiniCalendar($tasks, $month, $year, $compact); ?>
            </div>
        <?php else: ?>
            <div class="mini-timeline">
                <?php renderMiniTimeline($tasks, $compact); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <style>
    .task-visualizer-component {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        overflow: hidden;
    }
    
    .visualizer-controls {
        padding: 0.5rem;
        background: var(--bg-secondary);
        border-bottom: 1px solid var(--border-color);
    }
    
    .view-toggle {
        display: flex;
        gap: 0.25rem;
    }
    
    .mini-calendar {
        padding: 0.5rem;
    }
    
    .mini-calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        font-size: 0.8rem;
    }
    
    .mini-day {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        cursor: pointer;
        position: relative;
    }
    
    .mini-day.has-tasks::after {
        content: '';
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 4px;
        height: 4px;
        background: var(--primary);
        border-radius: 50%;
    }
    
    .mini-day.today {
        background: var(--primary-light);
        color: var(--primary);
        font-weight: 600;
    }
    
    .mini-timeline {
        max-height: 300px;
        overflow-y: auto;
        padding: 0.5rem;
    }
    
    .mini-timeline-item {
        display: flex;
        align-items: center;
        padding: 0.25rem;
        margin-bottom: 0.25rem;
        border-left: 3px solid var(--primary);
        background: var(--bg-secondary);
        border-radius: var(--border-radius-sm);
        font-size: 0.8rem;
    }
    
    .mini-timeline-item.priority-high {
        border-left-color: var(--danger);
    }
    
    .mini-timeline-item.priority-medium {
        border-left-color: var(--warning);
    }
    
    .mini-timeline-item.priority-low {
        border-left-color: var(--success);
    }
    
    .task-visualizer-component.compact .mini-calendar-grid {
        font-size: 0.7rem;
    }
    
    .task-visualizer-component.compact .mini-day {
        min-height: 20px;
    }
    
    .task-visualizer-component.compact .mini-timeline-item {
        font-size: 0.7rem;
        padding: 0.125rem;
    }
    </style>
    
    <script>
    function switchVisualizerView(view) {
        const component = document.querySelector('.task-visualizer-component');
        component.dataset.view = view;
        
        // Toggle visibility
        const calendar = component.querySelector('.mini-calendar');
        const timeline = component.querySelector('.mini-timeline');
        
        if (view === 'calendar') {
            calendar.style.display = 'block';
            timeline.style.display = 'none';
        } else {
            calendar.style.display = 'none';
            timeline.style.display = 'block';
        }
        
        // Update button states
        const buttons = component.querySelectorAll('.view-toggle button');
        buttons.forEach(btn => {
            btn.className = btn.onclick.toString().includes(view) ? 
                'btn btn--sm btn--primary' : 'btn btn--sm btn--secondary';
        });
    }
    </script>
    <?php
}

function renderMiniCalendar($tasks, $month, $year, $compact = false) {
    $firstDay = mktime(0, 0, 0, $month, 1, $year);
    $daysInMonth = date('t', $firstDay);
    $startDay = date('w', $firstDay);
    $today = date('Y-m-d');
    
    // Group tasks by date
    $tasksByDate = [];
    foreach ($tasks as $task) {
        $date = $task['date'] ?? date('Y-m-d');
        $tasksByDate[$date] = ($tasksByDate[$date] ?? 0) + 1;
    }
    
    echo '<div class="mini-calendar-grid">';
    
    // Day headers
    if (!$compact) {
        foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $day) {
            echo '<div class="mini-day-header">' . $day . '</div>';
        }
    }
    
    // Empty cells for days before month starts
    for ($i = 0; $i < $startDay; $i++) {
        echo '<div class="mini-day empty"></div>';
    }
    
    // Days of the month
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $isToday = $currentDate === $today;
        $hasTasks = isset($tasksByDate[$currentDate]);
        
        $dayClass = 'mini-day';
        if ($isToday) $dayClass .= ' today';
        if ($hasTasks) $dayClass .= ' has-tasks';
        
        echo '<div class="' . $dayClass . '" title="' . ($hasTasks ? $tasksByDate[$currentDate] . ' tasks' : 'No tasks') . '">';
        echo $day;
        echo '</div>';
    }
    
    echo '</div>';
}

function renderMiniTimeline($tasks, $compact = false) {
    if (empty($tasks)) {
        echo '<div class="no-tasks-mini">No tasks to display</div>';
        return;
    }
    
    $displayTasks = $compact ? array_slice($tasks, 0, 5) : $tasks;
    
    foreach ($displayTasks as $task) {
        $priorityClass = 'priority-' . ($task['priority'] ?? 'medium');
        $statusClass = 'status-' . ($task['status'] ?? 'assigned');
        
        echo '<div class="mini-timeline-item ' . $priorityClass . ' ' . $statusClass . '">';
        echo '<span class="task-title">' . htmlspecialchars(substr($task['title'], 0, 30)) . '</span>';
        if (!$compact) {
            echo '<span class="task-progress">' . ($task['progress'] ?? 0) . '%</span>';
        }
        echo '</div>';
    }
    
    if ($compact && count($tasks) > 5) {
        $remaining = count($tasks) - 5;
        echo '<div class="mini-timeline-item more">+' . $remaining . ' more tasks</div>';
    }
}
?>
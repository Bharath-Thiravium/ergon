<?php
// Calendar component for displaying events
$currentMonth = date('n');
$currentYear = date('Y');
$today = date('Y-m-d');

// Get first day of month and number of days
$firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$firstDayOfWeek = date('w', $firstDay);
$daysInMonth = date('t', $firstDay);

// Previous month days to show
$prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
$prevYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;
$daysInPrevMonth = date('t', mktime(0, 0, 0, $prevMonth, 1, $prevYear));
?>

<div class="calendar-container">
    <div class="calendar-header">
        <div class="calendar-controls">
            <button class="btn btn--sm btn--secondary" onclick="changeMonth(-1)">‹ Prev</button>
            <h3 id="currentMonthYear"><?= date('F Y', $firstDay) ?></h3>
            <button class="btn btn--sm btn--secondary" onclick="changeMonth(1)">Next ›</button>
        </div>
    </div>
    
    <div class="calendar-grid">
        <div class="calendar-day-header">Sun</div>
        <div class="calendar-day-header">Mon</div>
        <div class="calendar-day-header">Tue</div>
        <div class="calendar-day-header">Wed</div>
        <div class="calendar-day-header">Thu</div>
        <div class="calendar-day-header">Fri</div>
        <div class="calendar-day-header">Sat</div>
        
        <?php
        // Previous month days
        for ($i = $firstDayOfWeek - 1; $i >= 0; $i--) {
            $day = $daysInPrevMonth - $i;
            $date = sprintf('%04d-%02d-%02d', $prevYear, $prevMonth, $day);
            echo "<div class='calendar-day other-month' data-date='$date'>";
            echo "<div class='day-number'>$day</div>";
            echo "</div>";
        }
        
        // Current month days
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
            $isToday = $date === $today ? 'today' : '';
            
            echo "<div class='calendar-day $isToday' data-date='$date' onclick='selectDate(\"$date\")'>";
            echo "<div class='day-number'>$day</div>";
            
            // Show events for this date
            if (isset($events[$date])) {
                foreach ($events[$date] as $event) {
                    $priorityClass = isset($event['priority']) ? 'priority-' . $event['priority'] : '';
                    $completedClass = isset($event['completed']) && $event['completed'] ? 'completed' : '';
                    echo "<div class='plan-item $priorityClass $completedClass' title='" . htmlspecialchars($event['title']) . "'>";
                    echo htmlspecialchars(substr($event['title'], 0, 20));
                    echo "</div>";
                }
            }
            
            echo "</div>";
        }
        
        // Next month days to fill the grid
        $totalCells = 42; // 6 rows × 7 days
        $cellsUsed = $firstDayOfWeek + $daysInMonth;
        $nextMonth = $currentMonth == 12 ? 1 : $currentMonth + 1;
        $nextYear = $currentMonth == 12 ? $currentYear + 1 : $currentYear;
        
        for ($day = 1; $cellsUsed < $totalCells; $day++, $cellsUsed++) {
            $date = sprintf('%04d-%02d-%02d', $nextYear, $nextMonth, $day);
            echo "<div class='calendar-day other-month' data-date='$date'>";
            echo "<div class='day-number'>$day</div>";
            echo "</div>";
        }
        ?>
    </div>
</div>

<script>
function selectDate(date) {
    // Dispatch custom event for date selection
    const event = new CustomEvent('dateSelected', { detail: { date: date } });
    document.dispatchEvent(event);
}

function changeMonth(direction) {
    // This would typically reload the page with new month parameters
    const currentDate = new Date();
    currentDate.setMonth(currentDate.getMonth() + direction);
    
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth() + 1;
    
    // Reload page with new month/year parameters
    const url = new URL(window.location);
    url.searchParams.set('month', month);
    url.searchParams.set('year', year);
    window.location.href = url.toString();
}
</script>

<style>
.calendar-container {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    overflow: hidden;
    margin-bottom: var(--space-6);
}

.calendar-header {
    padding: var(--space-4);
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border-color);
}

.calendar-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.calendar-controls h3 {
    margin: 0;
    color: var(--text-primary);
    font-weight: 600;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: var(--border-color);
}

.calendar-day-header {
    background: var(--bg-secondary);
    padding: var(--space-3);
    text-align: center;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: var(--font-size-sm);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.calendar-day {
    background: var(--bg-primary);
    min-height: 120px;
    padding: var(--space-2);
    position: relative;
    cursor: pointer;
    transition: var(--transition);
}

.calendar-day:hover {
    background: var(--bg-secondary);
}

.calendar-day.other-month {
    background: var(--bg-tertiary);
    color: var(--text-tertiary);
}

.calendar-day.today {
    background: rgba(59, 130, 246, 0.1);
    border: 2px solid var(--primary);
}

.day-number {
    font-weight: 600;
    margin-bottom: var(--space-1);
    color: var(--text-primary);
    font-size: var(--font-size-sm);
}

.calendar-day.other-month .day-number {
    color: var(--text-tertiary);
}

.plan-item {
    font-size: var(--font-size-xs);
    padding: 2px 4px;
    margin: 1px 0;
    border-radius: var(--border-radius-sm);
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    transition: var(--transition);
}

.plan-item:hover {
    opacity: 0.8;
    transform: translateY(-1px);
}

.plan-item.priority-urgent { 
    background: rgba(239, 68, 68, 0.1); 
    color: var(--error);
    border-left: 3px solid var(--error);
}

.plan-item.priority-high { 
    background: rgba(251, 191, 36, 0.1); 
    color: var(--warning);
    border-left: 3px solid var(--warning);
}

.plan-item.priority-medium { 
    background: rgba(34, 197, 94, 0.1); 
    color: var(--success);
    border-left: 3px solid var(--success);
}

.plan-item.priority-low { 
    background: rgba(139, 92, 246, 0.1); 
    color: var(--info);
    border-left: 3px solid var(--info);
}

.plan-item.completed { 
    opacity: 0.6; 
    text-decoration: line-through; 
}

@media (max-width: 768px) {
    .calendar-day {
        min-height: 80px;
        padding: var(--space-1);
    }
    
    .day-number {
        font-size: var(--font-size-xs);
    }
    
    .plan-item {
        font-size: 10px;
        padding: 1px 2px;
    }
    
    .calendar-controls {
        flex-direction: column;
        gap: var(--space-2);
    }
    
    .calendar-controls h3 {
        font-size: var(--font-size-lg);
    }
}

@media (max-width: 480px) {
    .calendar-day {
        min-height: 60px;
    }
    
    .calendar-day-header {
        padding: var(--space-2);
        font-size: 10px;
    }
}
</style>
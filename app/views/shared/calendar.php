<?php
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$today = date('Y-n-j');
$firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$monthName = date('F Y', $firstDay);
$daysInMonth = date('t', $firstDay);
$startDay = date('w', $firstDay);
?>

<div class="ergon-calendar">
    <div class="ergon-calendar__header">
        <button class="btn btn--sm" onclick="navigateMonth(-1)">‹</button>
        <h3 class="ergon-calendar__title"><?= $monthName ?></h3>
        <button class="btn btn--sm" onclick="navigateMonth(1)">›</button>
    </div>
    
    <div class="ergon-calendar__body">
        <div class="ergon-calendar__weekdays">
            <div class="ergon-calendar__weekday">Sun</div>
            <div class="ergon-calendar__weekday">Mon</div>
            <div class="ergon-calendar__weekday">Tue</div>
            <div class="ergon-calendar__weekday">Wed</div>
            <div class="ergon-calendar__weekday">Thu</div>
            <div class="ergon-calendar__weekday">Fri</div>
            <div class="ergon-calendar__weekday">Sat</div>
        </div>
        
        <div class="ergon-calendar__days">
            <?php
            for ($i = 0; $i < $startDay; $i++) {
                echo '<div class="ergon-calendar__day ergon-calendar__day--empty"></div>';
            }
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateStr = "$currentYear-$currentMonth-$day";
                $isToday = ($dateStr === $today);
                $hasEvents = isset($events[$dateStr]) && !empty($events[$dateStr]);
                
                $classes = ['ergon-calendar__day'];
                if ($isToday) $classes[] = 'ergon-calendar__day--today';
                if ($hasEvents) $classes[] = 'ergon-calendar__day--has-events';
                
                echo '<div class="' . implode(' ', $classes) . '" data-date="' . $dateStr . '" onclick="selectDate(\'' . $dateStr . '\')">';
                echo '<span class="ergon-calendar__day-number">' . $day . '</span>';
                
                if ($hasEvents) {
                    echo '<div class="ergon-calendar__events">';
                    $eventCount = count($events[$dateStr]);
                    if ($eventCount <= 3) {
                        foreach ($events[$dateStr] as $event) {
                            echo '<div class="ergon-calendar__event-dot ergon-calendar__event-dot--' . ($event['type'] ?? 'default') . '"></div>';
                        }
                    } else {
                        echo '<div class="ergon-calendar__event-count">+' . $eventCount . '</div>';
                    }
                    echo '</div>';
                }
                
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

<script>
function navigateMonth(direction) {
    const currentUrl = new URL(window.location);
    const currentMonth = parseInt(currentUrl.searchParams.get('month')) || <?= date('n') ?>;
    const currentYear = parseInt(currentUrl.searchParams.get('year')) || <?= date('Y') ?>;
    
    let newMonth = currentMonth + direction;
    let newYear = currentYear;
    
    if (newMonth > 12) {
        newMonth = 1;
        newYear++;
    } else if (newMonth < 1) {
        newMonth = 12;
        newYear--;
    }
    
    currentUrl.searchParams.set('month', newMonth);
    currentUrl.searchParams.set('year', newYear);
    window.location.href = currentUrl.toString();
}

function selectDate(dateStr) {
    const event = new CustomEvent('dateSelected', { detail: { date: dateStr } });
    document.dispatchEvent(event);
    
    document.querySelectorAll('.ergon-calendar__day').forEach(day => {
        day.classList.remove('ergon-calendar__day--selected');
    });
    
    const selectedDay = document.querySelector(`[data-date="${dateStr}"]`);
    if (selectedDay) {
        selectedDay.classList.add('ergon-calendar__day--selected');
    }
}
</script>
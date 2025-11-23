<?php
// Verify the fix by showing the SQL that would be executed
echo "<h2>Verify Planned Date Fix</h2>\n";

$userId = 1;
$date = '2025-11-25';

echo "For future date {$date} and user {$userId}, the following SQL would be executed:<br>\n";

$sql = "
    SELECT 
        t.id, t.title, t.description, t.priority, t.status,
        t.deadline, t.estimated_duration, t.sla_hours, t.assigned_to, t.created_by,
        'planned_date' as source_field
    FROM tasks t
    WHERE t.assigned_to = ? 
    AND t.status NOT IN ('completed', 'cancelled', 'deleted')
    AND t.planned_date = ?
    
    UNION ALL
    
    SELECT 
        t.id, t.title, t.description, t.priority, t.status,
        t.deadline, t.estimated_duration, t.sla_hours, t.assigned_to, t.created_by,
        'deadline' as source_field
    FROM tasks t
    WHERE t.assigned_to = ? 
    AND t.status NOT IN ('completed', 'cancelled', 'deleted')
    AND DATE(t.deadline) = ?
    AND (t.planned_date IS NULL OR t.planned_date = '' OR t.planned_date = '0000-00-00')
";

echo "<pre>" . htmlspecialchars($sql) . "</pre>\n";

echo "Parameters:<br>\n";
echo "1. userId: {$userId}<br>\n";
echo "2. date: {$date}<br>\n";
echo "3. userId: {$userId}<br>\n";
echo "4. date: {$date}<br>\n";

echo "<br><strong>Key improvements:</strong><br>\n";
echo "1. Direct comparison: t.planned_date = ? (no DATE() function)<br>\n";
echo "2. UNION query separates planned_date and deadline logic<br>\n";
echo "3. More explicit null/empty checks for planned_date<br>\n";
echo "4. Added 'deleted' to excluded statuses<br>\n";

echo "<br><strong>Expected behavior:</strong><br>\n";
echo "- Tasks with planned_date = '{$date}' should be found in the first part of the UNION<br>\n";
echo "- Tasks with deadline = '{$date}' and no planned_date should be found in the second part<br>\n";
echo "- Tasks will be inserted into daily_tasks with appropriate source_field values<br>\n";
?>
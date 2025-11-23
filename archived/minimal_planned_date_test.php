<?php
// Minimal test to check SQL query logic
echo "<h2>Minimal Planned Date Test</h2>\n";

$testDate = '2025-11-25';
$userId = 1;

// Simulate the SQL query that should be executed for future dates
$sql = "
    SELECT 
        t.id, t.title, t.description, t.priority, t.status,
        t.deadline, t.estimated_duration, t.sla_hours, t.assigned_to, t.created_by,
        CASE 
            WHEN DATE(t.planned_date) = ? THEN 'planned_date'
            WHEN DATE(t.deadline) = ? THEN 'deadline'
            ELSE 'other'
        END as source_field
    FROM tasks t
    WHERE t.assigned_to = ? 
    AND t.status NOT IN ('completed', 'cancelled')
    AND (
        -- PRIORITY 1: Tasks specifically planned for this future date
        DATE(t.planned_date) = ? OR
        -- PRIORITY 2: Tasks with deadline on this future date but no planned_date
        (DATE(t.deadline) = ? AND (t.planned_date IS NULL OR t.planned_date = ''))
    )
";

echo "SQL Query for future date ({$testDate}):<br>\n";
echo "<pre>" . htmlspecialchars($sql) . "</pre>\n";

echo "Parameters:<br>\n";
echo "1. date (for CASE): {$testDate}<br>\n";
echo "2. date (for CASE): {$testDate}<br>\n";
echo "3. userId: {$userId}<br>\n";
echo "4. date (for planned_date): {$testDate}<br>\n";
echo "5. date (for deadline): {$testDate}<br>\n";

echo "<br>This query should find tasks where:<br>\n";
echo "- assigned_to = {$userId}<br>\n";
echo "- status NOT IN ('completed', 'cancelled')<br>\n";
echo "- AND either:<br>\n";
echo "  - DATE(planned_date) = '{$testDate}' OR<br>\n";
echo "  - (DATE(deadline) = '{$testDate}' AND planned_date IS NULL/empty)<br>\n";

echo "<br>If a task exists with planned_date = '{$testDate}' and assigned_to = {$userId}, it should be found.<br>\n";
?>
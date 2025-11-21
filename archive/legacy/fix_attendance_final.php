<?php
// Final fix for attendance controller and view

// Fix controller
$controllerPath = __DIR__ . '/app/controllers/UnifiedAttendanceController.php';
$content = file_get_contents($controllerPath);

// Replace all WHERE clauses that filter by status
$content = preg_replace(
    '/WHERE u\.status = \'active\' \$userCondition/',
    'WHERE 1=1 $userCondition',
    $content
);

file_put_contents($controllerPath, $content);

// Fix view to handle empty data properly
$viewPath = __DIR__ . '/views/attendance/index.php';
$viewContent = file_get_contents($viewPath);

// Replace the foreach loop to add safety checks
$viewContent = str_replace(
    '<?php foreach ($attendance as $record): ?>',
    '<?php foreach (($attendance ?? []) as $record): 
        if (empty($record) || !isset($record["name"])) continue; ?>',
    $viewContent
);

file_put_contents($viewPath, $viewContent);

echo "Fixed attendance controller and view";
?>
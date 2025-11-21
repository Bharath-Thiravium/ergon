<?php
// Quick fix for UnifiedAttendanceController to exclude removed users

$controllerPath = __DIR__ . '/app/controllers/UnifiedAttendanceController.php';
$content = file_get_contents($controllerPath);

// Replace the WHERE clause to exclude removed users
$content = str_replace(
    "WHERE u.status = 'active' \$userCondition",
    "WHERE u.status != 'removed' \$userCondition",
    $content
);

// Also fix the other occurrence
$content = str_replace(
    "WHERE (u.status = 'active' OR u.status IS NULL) \$userCondition",
    "WHERE u.status != 'removed' \$userCondition", 
    $content
);

file_put_contents($controllerPath, $content);
echo "Fixed UnifiedAttendanceController to exclude removed users";
?>
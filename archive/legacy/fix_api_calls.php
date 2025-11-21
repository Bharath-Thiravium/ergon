<?php
// Fix all API calls in attendance index
$viewPath = __DIR__ . '/views/attendance/index.php';
$content = file_get_contents($viewPath);

// Replace all occurrences of the old API endpoint
$content = str_replace('/ergon/api/attendance_admin.php', '/ergon/api/simple_attendance.php', $content);

file_put_contents($viewPath, $content);
echo "Fixed all API calls to use simple_attendance.php";
?>
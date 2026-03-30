<?php
// Clear all caches
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Touch all relevant files
touch(__DIR__ . '/app/controllers/AttendanceController.php');
touch(__DIR__ . '/views/attendance/index.php');

// Test the exact query that should work now
require_once __DIR__ . '/app/config/database.php';
$db = Database::connect();

$stmt = $db->prepare("SELECT COALESCE(p.place, 'Sivaganga') as location_display, COALESCE(p.name, 'Employee Training Program') as project_name FROM attendance a LEFT JOIN projects p ON a.project_id = p.id WHERE a.id = 25");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Nelson's data should be: Location='{$result['location_display']}' Project='{$result['project_name']}'<br>";
echo "✅ Cache cleared and files touched. Refresh attendance page now!";
?>

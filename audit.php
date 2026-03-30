<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

echo "<h3>Line-by-Line Audit</h3>";

// 1. Check what controller actually passes
$_SESSION['role'] = 'admin'; // Simulate admin
$_SESSION['user_id'] = 1;

$db = Database::connect();
$filterDate = '2025-12-15';

$stmt = $db->prepare("SELECT name, place FROM projects WHERE status = 'active' ORDER BY name ASC LIMIT 1");
$stmt->execute();
$defaultProject = $stmt->fetch(PDO::FETCH_ASSOC);
$defaultProjectName = $defaultProject['name'] ?? '----';
$defaultLocation = $defaultProject['place'] ?? '---';

$stmt = $db->prepare("
    SELECT 
        u.id, u.name, u.role,
        COALESCE(p.place, ?) as location_display,
        COALESCE(p.name, ?) as project_name,
        a.check_in,
        CASE WHEN a.check_in IS NOT NULL THEN 'Present' ELSE 'Absent' END as status
    FROM users u
    LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
    LEFT JOIN projects p ON a.project_id = p.id
    WHERE u.role = 'user' AND u.status = 'active'
");
$stmt->execute([$defaultLocation, $defaultProjectName, $filterDate]);
$employeeAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "1. Controller Data:<br>";
foreach ($employeeAttendance as $emp) {
    echo "User: {$emp['name']} | location_display: '{$emp['location_display']}' | project_name: '{$emp['project_name']}'<br>";
}

// 2. Check view template content
$viewContent = file_get_contents(__DIR__ . '/views/attendance/index.php');
echo "<br>2. View Template Issues:<br>";

if (strpos($viewContent, "(\$record['check_in'] ? '---' : '---')") !== false) {
    echo "❌ Still has hardcoded '---' fallback<br>";
}
if (strpos($viewContent, "(\$record['check_in'] ? '----' : '----')") !== false) {
    echo "❌ Still has hardcoded '----' fallback<br>";
}

// 3. Simulate exact view logic
echo "<br>3. View Logic Test:<br>";
$attendance = ['user' => $employeeAttendance];
$is_grouped = true;

foreach ($attendance['user'] as $record) {
    $locationOutput = $record['location_display'] ?? ($record['check_in'] ? '---' : '---');
    $projectOutput = $record['project_name'] ?? ($record['check_in'] ? '----' : '----');
    echo "User: {$record['name']} | View Output: Location='$locationOutput' Project='$projectOutput'<br>";
}
?>

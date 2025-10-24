<?php
session_start();
require_once __DIR__ . '/app/models/Attendance.php';

if (!isset($_SESSION['user_id'])) {
    echo "Not logged in";
    exit;
}

$attendanceModel = new Attendance();
$attendance = $attendanceModel->getUserAttendance($_SESSION['user_id']);

echo "<h2>Attendance Data for Page</h2>";
echo "<pre>";
print_r($attendance);
echo "</pre>";

echo "<h3>Today's Records</h3>";
$todayAttendance = array_filter($attendance, fn($a) => date('Y-m-d', strtotime($a['check_in'])) === date('Y-m-d'));
echo "<pre>";
print_r($todayAttendance);
echo "</pre>";
?>
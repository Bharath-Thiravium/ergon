<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/TimezoneHelper.php';

$db = Database::connect();

// Check what's actually in the database
echo "<h3>Raw Database Data:</h3>";

// First check if table exists and has any data
$stmt = $db->prepare("SELECT COUNT(*) as total FROM attendance");
$stmt->execute();
$count = $stmt->fetch();
echo "Total attendance records: " . $count['total'] . "<br><br>";

// Get all records (not just today)
$stmt = $db->prepare("SELECT u.name, a.* FROM attendance a JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 10");
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($records) . " records:<br>";

// Also check table structure
echo "<h3>Table Structure:</h3>";
$stmt = $db->prepare("DESCRIBE attendance");
$stmt->execute();
$structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($structure);
echo "</pre>";

echo "<pre>";
foreach ($records as $record) {
    echo "User: " . $record['name'] . "\n";
    echo "Check In: " . var_export($record['check_in'], true) . "\n";
    echo "Check Out: " . var_export($record['check_out'], true) . "\n";
    echo "Created At: " . var_export($record['created_at'], true) . "\n";
    echo "---\n";
}
echo "</pre>";

// Test TimezoneHelper
echo "<h3>TimezoneHelper Test:</h3>";
$testTime = TimezoneHelper::nowIst();
echo "Current IST: " . $testTime . "<br>";

// Check users and their roles
echo "<h3>Users in Database:</h3>";
$stmt = $db->prepare("SELECT id, name, email, role, status FROM users ORDER BY role, name");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
foreach ($users as $user) {
    echo "ID: {$user['id']}, Name: {$user['name']}, Role: {$user['role']}, Status: {$user['status']}\n";
}
echo "</pre>";

// Test TimeHelper
require_once __DIR__ . '/app/helpers/TimeHelper.php';
if (!empty($records)) {
    $firstRecord = $records[0];
    echo "TimeHelper format check_in: " . TimeHelper::formatToIST($firstRecord['check_in']) . "<br>";
    if ($firstRecord['check_out']) {
        echo "TimeHelper format check_out: " . TimeHelper::formatToIST($firstRecord['check_out']) . "<br>";
    }
}
?>
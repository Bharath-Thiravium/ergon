<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo "Not logged in";
    exit;
}

$database = new Database();
$conn = $database->getConnection();

echo "<h2>Attendance Check for User ID: " . $_SESSION['user_id'] . "</h2>";

// Check recent attendance records
$stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($records)) {
    echo "<p>No attendance records found.</p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Check In</th><th>Check Out</th><th>Location</th><th>Created</th></tr>";
    foreach ($records as $record) {
        echo "<tr>";
        echo "<td>" . $record['id'] . "</td>";
        echo "<td>" . ($record['check_in'] ?? 'NULL') . "</td>";
        echo "<td>" . ($record['check_out'] ?? 'NULL') . "</td>";
        echo "<td>" . ($record['location_name'] ?? 'NULL') . "</td>";
        echo "<td>" . ($record['created_at'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test clock in
echo "<h3>Test Clock In</h3>";
echo "<form method='post'>";
echo "<button type='submit' name='test_clock_in'>Test Clock In</button>";
echo "</form>";

if (isset($_POST['test_clock_in'])) {
    $stmt = $conn->prepare("INSERT INTO attendance (user_id, check_in, latitude, longitude, location_name) VALUES (?, NOW(), 0, 0, 'Test Location')");
    $result = $stmt->execute([$_SESSION['user_id']]);
    echo "<p>Clock in test: " . ($result ? "SUCCESS" : "FAILED") . "</p>";
    echo "<script>location.reload();</script>";
}
?>
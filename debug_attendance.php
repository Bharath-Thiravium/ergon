<?php
// Debug script for attendance issues
session_start();

echo "<h1>Attendance Debug Information</h1>";

// Check session
echo "<h2>Session Status</h2>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User logged in: ID = " . $_SESSION['user_id'] . ", Role = " . ($_SESSION['role'] ?? 'unknown') . "<br>";
} else {
    echo "❌ No user session found<br>";
}

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    echo "✅ Database connected successfully<br>";
    
    // Check if attendance table exists
    $stmt = $db->query("SHOW TABLES LIKE 'attendance'");
    if ($stmt->fetch()) {
        echo "✅ Attendance table exists<br>";
        
        // Check table structure
        $stmt = $db->query("DESCRIBE attendance");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📋 Table columns:<br>";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        }
        
        // Check if user has attendance today
        if (isset($_SESSION['user_id'])) {
            $stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE()");
            $stmt->execute([$_SESSION['user_id']]);
            $todayAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($todayAttendance) {
                echo "📅 Today's attendance found:<br>";
                echo "- Check In: " . ($todayAttendance['check_in'] ?? 'NULL') . "<br>";
                echo "- Check Out: " . ($todayAttendance['check_out'] ?? 'NULL') . "<br>";
            } else {
                echo "📅 No attendance record for today<br>";
            }
        }
        
    } else {
        echo "❌ Attendance table does not exist<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test POST endpoint
echo "<h2>Test Clock In Endpoint</h2>";
echo "<button onclick='testClockIn()'>Test Clock In</button>";
echo "<div id='testResult'></div>";

?>

<script>
function testClockIn() {
    const formData = new FormData();
    formData.append('type', 'in');
    formData.append('latitude', 0);
    formData.append('longitude', 0);
    
    fetch('/ergon/attendance/clock', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text();
    })
    .then(data => {
        console.log('Response data:', data);
        document.getElementById('testResult').innerHTML = '<pre>' + data + '</pre>';
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('testResult').innerHTML = '<pre>Error: ' + error.message + '</pre>';
    });
}
</script>
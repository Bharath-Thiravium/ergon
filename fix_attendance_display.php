<?php
// Fix attendance display to convert UTC records to IST
session_start();
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>🔧 Fixing Attendance Display</h2>";
    
    // Get all attendance records
    $stmt = $db->query("SELECT id, user_id, check_in, check_out FROM attendance ORDER BY id DESC LIMIT 10");
    $records = $stmt->fetchAll();
    
    echo "<h3>Current Records (UTC stored, IST display needed):</h3>";
    
    foreach ($records as $record) {
        $checkInUTC = $record['check_in'];
        $checkOutUTC = $record['check_out'];
        
        // Convert UTC to IST for display
        $checkInIST = date('Y-m-d H:i:s', strtotime($checkInUTC . ' +5 hours 30 minutes'));
        $checkOutIST = $checkOutUTC ? date('Y-m-d H:i:s', strtotime($checkOutUTC . ' +5 hours 30 minutes')) : null;
        
        echo "ID {$record['id']}: ";
        echo "UTC({$checkInUTC}) → IST(" . date('H:i', strtotime($checkInIST)) . ")";
        if ($checkOutIST) {
            echo " | Out: IST(" . date('H:i', strtotime($checkOutIST)) . ")";
        }
        echo "<br>";
    }
    
    echo "<h3>✅ Solution: Update attendance display logic</h3>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
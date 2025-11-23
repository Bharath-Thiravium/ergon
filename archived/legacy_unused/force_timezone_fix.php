<?php
// Force timezone fix for all attendance operations
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please log in first";
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = Database::connect();
    
    // Get user's timezone
    $stmt = $db->prepare("SELECT timezone FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userPrefs = $stmt->fetch();
    $userTimezone = $userPrefs['timezone'] ?? 'Asia/Kolkata';
    
    echo "<h2>Force Timezone Fix Test</h2>";
    echo "User timezone: $userTimezone<br>";
    
    // Force set timezone
    date_default_timezone_set($userTimezone);
    echo "Current time after setting timezone: " . date('Y-m-d H:i:s T') . "<br>";
    
    // Test attendance insert with forced timezone
    $testTime = date('Y-m-d H:i:s');
    $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, status, location_name) VALUES (?, ?, 'force_test', 'Timezone Force Test')");
    $result = $stmt->execute([$userId, $testTime]);
    
    if ($result) {
        $testId = $db->lastInsertId();
        echo "✅ Force test record created with ID: $testId<br>";
        
        // Retrieve and verify
        $stmt = $db->prepare("SELECT check_in FROM attendance WHERE id = ?");
        $stmt->execute([$testId]);
        $retrieved = $stmt->fetch();
        
        echo "Inserted time: $testTime<br>";
        echo "Retrieved time: " . $retrieved['check_in'] . "<br>";
        
        if ($retrieved['check_in'] === $testTime) {
            echo "✅ SUCCESS: Timezone force fix works!<br>";
        } else {
            echo "❌ FAILED: Still not working<br>";
        }
        
        // Clean up
        $stmt = $db->prepare("DELETE FROM attendance WHERE id = ?");
        $stmt->execute([$testId]);
        echo "Test record cleaned up<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
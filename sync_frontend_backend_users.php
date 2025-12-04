<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Frontend vs Backend User Sync Analysis</h2>";
    
    // Check if Nelson ID 37 exists
    $nelson37 = $db->prepare("SELECT * FROM users WHERE id = 37");
    $nelson37->execute();
    $nelson37Data = $nelson37->fetch(PDO::FETCH_ASSOC);
    
    if (!$nelson37Data) {
        echo "<p style='color: red;'>❌ Nelson (ID: 37) does NOT exist in production database</p>";
        echo "<p>Frontend is showing cached/stale data or connected to different database</p>";
        
        // Check if there's a different Nelson
        $otherNelson = $db->prepare("SELECT * FROM users WHERE name LIKE '%Nelson%'");
        $otherNelson->execute();
        $nelsons = $otherNelson->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Actual Nelson users in production:</h3>";
        foreach ($nelsons as $nelson) {
            echo "<p>ID: {$nelson['id']}, Name: {$nelson['name']}, Role: {$nelson['role']}</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Nelson (ID: 37) exists in production</p>";
    }
    
    // Check missing Bharath Thiravium
    $bharath = $db->prepare("SELECT * FROM users WHERE id = 59");
    $bharath->execute();
    $bharathData = $bharath->fetch(PDO::FETCH_ASSOC);
    
    if ($bharath) {
        echo "<p style='color: orange;'>⚠️ Bharath Thiravium (ID: 59) exists in production but NOT shown in frontend</p>";
        echo "<p>Name: {$bharathData['name']}, Role: {$bharathData['role']}</p>";
    }
    
    echo "<h3>Recommendations:</h3>";
    echo "<ul>";
    echo "<li>Clear browser cache completely</li>";
    echo "<li>Check if frontend is connected to correct database</li>";
    echo "<li>Verify database connection in app/config/database.php</li>";
    echo "<li>Frontend may be using cached session data</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
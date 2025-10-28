<?php
require_once 'app/config/database.php';

try {
    $pdo = Database::connect();
    
    // Find a real followup ID
    $stmt = $pdo->query("SELECT id FROM followups LIMIT 1");
    $followup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$followup) {
        echo "No followups found. Creating a test followup first...<br>";
        
        $stmt = $pdo->prepare("INSERT INTO followups (user_id, title, follow_up_date) VALUES (1, 'Test Followup', CURDATE())");
        $stmt->execute();
        $followupId = $pdo->lastInsertId();
        echo "Created test followup with ID: $followupId<br>";
    } else {
        $followupId = $followup['id'];
        echo "Using existing followup ID: $followupId<br>";
    }
    
    // Test history insertion
    echo "<h3>Testing History Insertion</h3>";
    $stmt = $pdo->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (?, 'test', 'old', 'new', 'test note', 1)");
    $result = $stmt->execute([$followupId]);
    
    if ($result) {
        $historyId = $pdo->lastInsertId();
        echo "✅ History insertion successful! ID: $historyId<br>";
        
        // Test retrieval
        $stmt = $pdo->prepare("SELECT * FROM followup_history WHERE followup_id = ?");
        $stmt->execute([$followupId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "History records found: " . count($history) . "<br>";
        foreach ($history as $record) {
            echo "- " . $record['action'] . ": " . $record['notes'] . "<br>";
        }
        
        echo "<h3>✅ History functionality is working!</h3>";
        echo "The issue was that followup_id 2 doesn't exist in the followups table.<br>";
        
    } else {
        echo "❌ History insertion failed<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
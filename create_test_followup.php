<?php
require_once 'app/config/database.php';

try {
    $pdo = Database::connect();
    
    // Check if followup ID 2 exists
    $stmt = $pdo->prepare("SELECT * FROM followups WHERE id = 2");
    $stmt->execute();
    $followup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$followup) {
        echo "Creating followup with ID 2...<br>";
        
        // Insert with specific ID
        $pdo->exec("INSERT INTO followups (id, user_id, title, company_name, follow_up_date, description) VALUES (2, 1, 'Test Follow-up #2', 'Test Company', CURDATE(), 'This is a test followup for history testing')");
        
        echo "✅ Created followup ID 2<br>";
        
        // Add some history
        $pdo->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (2, 'created', NULL, 'Follow-up created', 'Initial creation', 1)")->execute();
        
        $pdo->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (2, 'postponed', '2024-01-15', '2024-01-20', 'Rescheduled due to client request', 1)")->execute();
        
        echo "✅ Added test history records<br>";
        
    } else {
        echo "Followup ID 2 already exists<br>";
        
        // Add history if none exists
        $historyCount = $pdo->prepare("SELECT COUNT(*) FROM followup_history WHERE followup_id = 2");
        $historyCount->execute();
        $count = $historyCount->fetchColumn();
        
        if ($count == 0) {
            $pdo->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (2, 'created', NULL, 'Follow-up created', 'Initial creation', 1)")->execute();
            $pdo->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (2, 'postponed', '2024-01-15', '2024-01-20', 'Rescheduled due to client request', 1)")->execute();
            echo "✅ Added history records<br>";
        } else {
            echo "History already exists ($count records)<br>";
        }
    }
    
    // Verify
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followup_history WHERE followup_id = 2");
    $stmt->execute();
    $historyCount = $stmt->fetchColumn();
    
    echo "<h3>✅ Setup Complete!</h3>";
    echo "Followup ID 2 now has $historyCount history records.<br>";
    echo "You can now test the history functionality.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
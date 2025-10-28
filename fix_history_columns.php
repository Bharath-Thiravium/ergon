<?php
require_once 'app/config/database.php';

try {
    $pdo = Database::connect();
    
    echo "<h3>Fixing History Table Column Mismatch</h3>";
    
    // Add missing columns
    $pdo->exec("ALTER TABLE followup_history ADD COLUMN old_value TEXT AFTER action");
    $pdo->exec("ALTER TABLE followup_history ADD COLUMN new_value TEXT AFTER old_value");
    
    echo "✅ Added old_value and new_value columns<br>";
    
    // Test insertion
    $stmt = $pdo->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (2, 'test', 'old', 'new', 'test note', 1)");
    $result = $stmt->execute();
    
    if ($result) {
        $id = $pdo->lastInsertId();
        echo "✅ Test insertion successful! ID: $id<br>";
        
        // Clean up
        $pdo->prepare("DELETE FROM followup_history WHERE id = ?")->execute([$id]);
        echo "✅ Test record cleaned up<br>";
    }
    
    echo "<h4>✅ History table fixed!</h4>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
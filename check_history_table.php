<?php
require_once 'app/config/database.php';

try {
    $pdo = Database::connect();
    
    echo "<h3>Current followup_history Table Structure</h3>";
    
    $stmt = $pdo->query("DESCRIBE followup_history");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "Column: " . $column['Field'] . " - Type: " . $column['Type'] . "<br>";
    }
    
    // Test insertion with correct columns
    echo "<h3>Testing Insertion</h3>";
    $stmt = $pdo->prepare("INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) VALUES (2, 'test', 'old', 'new', 'test note', 1)");
    $result = $stmt->execute();
    
    if ($result) {
        $id = $pdo->lastInsertId();
        echo "✅ Insertion successful! ID: $id<br>";
        
        // Verify
        $verify = $pdo->prepare("SELECT * FROM followup_history WHERE id = ?");
        $verify->execute([$id]);
        $record = $verify->fetch(PDO::FETCH_ASSOC);
        echo "Record: " . json_encode($record) . "<br>";
        
        // Clean up
        $pdo->prepare("DELETE FROM followup_history WHERE id = ?")->execute([$id]);
        echo "✅ Cleaned up<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
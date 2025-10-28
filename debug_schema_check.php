<?php
// Check if schema fix was applied
require_once 'app/config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check current table structure
    $stmt = $pdo->query("DESCRIBE followup_history");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current followup_history table structure:</h3>";
    foreach ($columns as $column) {
        echo "Column: " . $column['Field'] . " - Type: " . $column['Type'] . "<br>";
    }
    
    // Check if we have action or action_type column
    $hasAction = false;
    $hasActionType = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'action') $hasAction = true;
        if ($column['Field'] === 'action_type') $hasActionType = true;
    }
    
    echo "<br><strong>Schema Status:</strong><br>";
    echo "Has 'action' column: " . ($hasAction ? "YES" : "NO") . "<br>";
    echo "Has 'action_type' column: " . ($hasActionType ? "YES" : "NO") . "<br>";
    
    if ($hasActionType && !$hasAction) {
        echo "<br><strong>ISSUE FOUND:</strong> Table still has 'action_type' instead of 'action'<br>";
        echo "Run the SQL fix: ALTER TABLE followup_history CHANGE action_type action varchar(50) NOT NULL;<br>";
    } else if ($hasAction) {
        echo "<br><strong>Schema is correct!</strong> Issue might be elsewhere.<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
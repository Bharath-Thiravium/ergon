<?php
// Apply schema fix and test history insertion
require_once 'app/config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>Step 1: Checking current schema</h3>";
    $stmt = $pdo->query("DESCRIBE followup_history");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasAction = false;
    $hasActionType = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'action') $hasAction = true;
        if ($column['Field'] === 'action_type') $hasActionType = true;
    }
    
    echo "Has 'action' column: " . ($hasAction ? "YES" : "NO") . "<br>";
    echo "Has 'action_type' column: " . ($hasActionType ? "YES" : "NO") . "<br>";
    
    // Apply fix if needed
    if ($hasActionType && !$hasAction) {
        echo "<h3>Step 2: Applying schema fix</h3>";
        $pdo->exec("ALTER TABLE followup_history CHANGE action_type action varchar(50) NOT NULL");
        echo "Schema fix applied successfully!<br>";
    } else if ($hasAction) {
        echo "<h3>Step 2: Schema already correct</h3>";
    }
    
    // Test insertion
    echo "<h3>Step 3: Testing history insertion</h3>";
    $testData = [
        'followup_id' => 2,
        'action' => 'test_action',
        'old_value' => 'test_old',
        'new_value' => 'test_new',
        'changed_by' => 1,
        'changed_at' => date('Y-m-d H:i:s')
    ];
    
    $sql = "INSERT INTO followup_history (followup_id, action, old_value, new_value, changed_by, changed_at) 
            VALUES (:followup_id, :action, :old_value, :new_value, :changed_by, :changed_at)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($testData);
    
    if ($result) {
        echo "✅ Test insertion successful!<br>";
        
        // Check if record was inserted
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM followup_history WHERE followup_id = ?");
        $checkStmt->execute([2]);
        $count = $checkStmt->fetchColumn();
        echo "Total history records for followup_id 2: " . $count . "<br>";
        
        // Clean up test record
        $pdo->prepare("DELETE FROM followup_history WHERE followup_id = 2 AND action = 'test_action'")->execute();
        echo "Test record cleaned up.<br>";
    } else {
        echo "❌ Test insertion failed!<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString();
}
?>
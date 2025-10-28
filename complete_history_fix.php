<?php
// Complete fix for followup history issues
require_once 'app/config/database.php';

try {
    $pdo = Database::connect();
    
    echo "<h3>Followup History Complete Fix</h3>";
    
    // Step 1: Check current table structure
    echo "<h4>Step 1: Current table structure</h4>";
    $stmt = $pdo->query("DESCRIBE followup_history");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $columnNames = [];
    foreach ($columns as $column) {
        $columnNames[] = $column['Field'];
        echo "Column: " . $column['Field'] . " - Type: " . $column['Type'] . "<br>";
    }
    
    // Step 2: Apply all necessary fixes
    echo "<h4>Step 2: Applying fixes</h4>";
    
    // Fix action_type -> action
    if (in_array('action_type', $columnNames) && !in_array('action', $columnNames)) {
        $pdo->exec("ALTER TABLE followup_history CHANGE action_type action varchar(50) NOT NULL");
        echo "✅ Fixed: action_type -> action<br>";
    } else if (in_array('action', $columnNames)) {
        echo "✅ Column 'action' already exists<br>";
    }
    
    // Fix changed_by -> created_by
    if (in_array('changed_by', $columnNames) && !in_array('created_by', $columnNames)) {
        $pdo->exec("ALTER TABLE followup_history CHANGE changed_by created_by int NOT NULL");
        echo "✅ Fixed: changed_by -> created_by<br>";
    } else if (in_array('created_by', $columnNames)) {
        echo "✅ Column 'created_by' already exists<br>";
    }
    
    // Fix changed_at -> created_at
    if (in_array('changed_at', $columnNames) && !in_array('created_at', $columnNames)) {
        $pdo->exec("ALTER TABLE followup_history CHANGE changed_at created_at timestamp DEFAULT CURRENT_TIMESTAMP");
        echo "✅ Fixed: changed_at -> created_at<br>";
    } else if (in_array('created_at', $columnNames)) {
        echo "✅ Column 'created_at' already exists<br>";
    }
    
    // Step 3: Test insertion
    echo "<h4>Step 3: Testing insertion</h4>";
    
    $testData = [
        'followup_id' => 2,
        'action' => 'test_fix',
        'old_value' => 'test_old',
        'new_value' => 'test_new',
        'notes' => 'Test insertion after fix',
        'created_by' => 1
    ];
    
    $sql = "INSERT INTO followup_history (followup_id, action, old_value, new_value, notes, created_by) 
            VALUES (:followup_id, :action, :old_value, :new_value, :notes, :created_by)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($testData);
    
    if ($result) {
        $insertId = $pdo->lastInsertId();
        echo "✅ Test insertion successful! ID: $insertId<br>";
        
        // Verify the record
        $verify = $pdo->prepare("SELECT * FROM followup_history WHERE id = ?");
        $verify->execute([$insertId]);
        $record = $verify->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            echo "✅ Record verified: " . json_encode($record) . "<br>";
        }
        
        // Clean up
        $pdo->prepare("DELETE FROM followup_history WHERE id = ?")->execute([$insertId]);
        echo "✅ Test record cleaned up<br>";
        
    } else {
        echo "❌ Test insertion failed<br>";
        $errorInfo = $stmt->errorInfo();
        echo "Error: " . implode(', ', $errorInfo) . "<br>";
    }
    
    // Step 4: Check final structure
    echo "<h4>Step 4: Final table structure</h4>";
    $stmt = $pdo->query("DESCRIBE followup_history");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($finalColumns as $column) {
        echo "Column: " . $column['Field'] . " - Type: " . $column['Type'] . "<br>";
    }
    
    echo "<h4>✅ Fix Complete!</h4>";
    echo "You can now test the followup history functionality.<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString();
}
?>
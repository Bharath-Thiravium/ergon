<?php
// Fix attendance status column to include 'on_leave'
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>🔧 Fixing Attendance Status Column</h2>";
    
    // Check current status column definition
    $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "<h3>Current Status Column:</h3>";
        echo "<p>Type: " . $column['Type'] . "</p>";
        
        // Check if 'on_leave' is already in the ENUM
        if (strpos($column['Type'], 'on_leave') === false) {
            echo "<h3>Adding 'on_leave' to status ENUM...</h3>";
            
            // Alter the column to include 'on_leave'
            $db->exec("ALTER TABLE attendance MODIFY COLUMN status ENUM('present', 'absent', 'late', 'on_leave') DEFAULT 'present'");
            
            echo "<p>✅ Successfully added 'on_leave' to attendance status column</p>";
        } else {
            echo "<p>✅ 'on_leave' already exists in status column</p>";
        }
    } else {
        echo "<p>❌ Status column not found in attendance table</p>";
        
        // Add status column if it doesn't exist
        $db->exec("ALTER TABLE attendance ADD COLUMN status ENUM('present', 'absent', 'late', 'on_leave') DEFAULT 'present'");
        echo "<p>✅ Added status column with 'on_leave' option</p>";
    }
    
    // Verify the change
    $stmt = $db->query("SHOW COLUMNS FROM attendance LIKE 'status'");
    $updatedColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Updated Status Column:</h3>";
    echo "<p>Type: " . $updatedColumn['Type'] . "</p>";
    
    echo "<h3>✅ Fix Complete</h3>";
    echo "<p>You can now approve leaves without database errors.</p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
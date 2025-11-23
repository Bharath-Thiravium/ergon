<?php
/**
 * Fix Advance Repayment Date Column
 * This script adds the repayment_date column to the advances table if it doesn't exist
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $pdo = Database::connect();
    
    echo "<h2>Fixing Advance Repayment Date Column</h2>";
    
    // Check if repayment_date column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM advances LIKE 'repayment_date'");
    
    if ($stmt->rowCount() == 0) {
        echo "<p>Adding repayment_date column to advances table...</p>";
        $pdo->exec("ALTER TABLE advances ADD COLUMN repayment_date DATE NULL AFTER reason");
        echo "<p style='color: green;'>✅ repayment_date column added successfully!</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ repayment_date column already exists.</p>";
    }
    
    // Test the column
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM advances");
    $result = $stmt->fetch();
    
    echo "<p>✅ Advances table has " . $result['count'] . " records</p>";
    echo "<p style='color: green;'>🎉 Advance repayment date fix completed successfully!</p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li>Visit <a href='/ergon/advances/create' target='_blank'>/ergon/advances/create</a> to test creating an advance with repayment date</li>";
    echo "<li>Visit <a href='/ergon/advances' target='_blank'>/ergon/advances</a> to view advances with repayment date column</li>";
    echo "<li>Edit existing advances to add repayment dates</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<?php
/**
 * Fix Advances Table - Ensure all required columns exist
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Checking advances table structure...\n";
    
    // Check if table exists and create if not
    $db->exec("CREATE TABLE IF NOT EXISTS advances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) DEFAULT 'General Advance',
        amount DECIMAL(10,2) NOT NULL,
        reason TEXT NOT NULL,
        requested_date DATE NULL,
        repayment_months INT DEFAULT 1,
        status VARCHAR(20) DEFAULT 'pending',
        approved_by INT NULL,
        approved_at DATETIME NULL,
        rejection_reason TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        rejected_by INT NULL,
        rejected_at TIMESTAMP NULL
    )");
    
    // Check for missing columns and add them
    $columns = $db->query("DESCRIBE advances")->fetchAll(PDO::FETCH_ASSOC);
    $existingColumns = array_column($columns, 'Field');
    
    $requiredColumns = [
        'rejected_by' => 'INT NULL',
        'rejected_at' => 'TIMESTAMP NULL',
        'rejection_reason' => 'TEXT NULL'
    ];
    
    foreach ($requiredColumns as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            echo "Adding missing column: $column\n";
            $db->exec("ALTER TABLE advances ADD COLUMN $column $definition");
        } else {
            echo "Column $column already exists\n";
        }
    }
    
    echo "Advances table structure verified successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
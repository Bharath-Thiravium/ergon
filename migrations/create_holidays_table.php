<?php
/**
 * Migration: Create Holidays Table
 * This migration creates the holidays table for the holiday management feature
 */

require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::connect();
    
    // Check if table already exists
    $checkTable = $db->prepare("SHOW TABLES LIKE 'holidays'");
    $checkTable->execute();
    
    if ($checkTable->fetch()) {
        echo "✓ Holidays table already exists.\n";
        exit(0);
    }
    
    // Create holidays table
    $sql = "CREATE TABLE IF NOT EXISTS holidays (
        id INT PRIMARY KEY AUTO_INCREMENT,
        holiday_date DATE NOT NULL UNIQUE,
        holiday_name VARCHAR(255) NOT NULL,
        holiday_type VARCHAR(50) DEFAULT 'Company',
        description LONGTEXT,
        applies_to VARCHAR(50) DEFAULT 'All',
        department_id INT,
        repeat_yearly BOOLEAN DEFAULT 0,
        created_by INT,
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_holiday_date (holiday_date),
        KEY idx_applies_to (applies_to),
        KEY idx_department_id (department_id),
        KEY idx_created_by (created_by),
        KEY idx_is_active (is_active),
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    
    echo "✓ Holidays table created successfully!\n";
    
    // Verify table was created
    $verify = $db->prepare("SHOW TABLES LIKE 'holidays'");
    $verify->execute();
    if ($verify->fetch()) {
        echo "✓ Table verified - ready to use.\n";
        
        // Show table structure
        echo "\nTable Structure:\n";
        $describe = $db->prepare("DESCRIBE holidays");
        $describe->execute();
        $columns = $describe->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  - {$col['Field']}: {$col['Type']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Error creating holidays table: " . $e->getMessage() . "\n";
    exit(1);
}
?>

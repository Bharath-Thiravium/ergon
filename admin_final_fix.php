<?php
/**
 * Final Admin Panel Fix - All Issues #16-25
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "FINAL ADMIN PANEL FIX\n";
    echo "====================\n\n";
    
    // Create daily_tasks table for workflow
    echo "1. Creating daily_tasks table...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'daily_tasks'");
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE daily_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            department_id INT,
            project_id INT,
            category_id INT,
            assigned_to INT NOT NULL,
            planned_date DATE NOT NULL,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            estimated_hours DECIMAL(4,2) DEFAULT 1.00,
            actual_hours DECIMAL(4,2),
            status ENUM('planned', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
            progress INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_assigned_to (assigned_to),
            INDEX idx_planned_date (planned_date),
            INDEX idx_department (department_id)
        )";
        $db->exec($sql);
        echo "   ✅ Created daily_tasks table\n";
    }
    
    // Fix attendance table structure completely
    echo "2. Fixing attendance table structure...\n";
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    // Ensure all required columns exist
    $requiredColumns = [
        'date' => 'DATE DEFAULT (CURRENT_DATE)',
        'clock_in' => 'TIMESTAMP NULL',
        'clock_out' => 'TIMESTAMP NULL',
        'latitude' => 'DECIMAL(10,8)',
        'longitude' => 'DECIMAL(11,8)',
        'location' => 'VARCHAR(255) DEFAULT "Office"',
        'status' => 'ENUM("present", "absent", "late", "half_day") DEFAULT "present"'
    ];
    
    foreach ($requiredColumns as $column => $definition) {
        if (!in_array($column, $columnNames)) {
            $db->exec("ALTER TABLE attendance ADD COLUMN $column $definition");
            echo "   ✅ Added $column column\n";
        }
    }
    
    // Fix leaves table for proper day calculation
    echo "3. Fixing leaves table...\n";
    $stmt = $db->query("DESCRIBE leaves");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('total_days', $columns)) {
        $db->exec("ALTER TABLE leaves ADD COLUMN total_days INT AFTER end_date");
        echo "   ✅ Added total_days column to leaves\n";
    }
    
    // Create leave calculation trigger
    $db->exec("DROP TRIGGER IF EXISTS calculate_leave_days");
    $trigger = "CREATE TRIGGER calculate_leave_days 
                BEFORE INSERT ON leaves 
                FOR EACH ROW 
                SET NEW.total_days = DATEDIFF(NEW.end_date, NEW.start_date) + 1";
    $db->exec($trigger);
    echo "   ✅ Created leave days calculation trigger\n";
    
    echo "\n✅ ALL ADMIN PANEL FIXES COMPLETED!\n";
    echo "System should now handle all admin panel functionality correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
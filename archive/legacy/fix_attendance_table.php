<?php
/**
 * Fix Attendance Table - Ensure all required columns exist for smart clock button
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Checking attendance table structure...\n";
    
    // Create attendance table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        check_in DATETIME NOT NULL,
        check_out DATETIME NULL,
        latitude DECIMAL(10, 8) NULL,
        longitude DECIMAL(11, 8) NULL,
        location_name VARCHAR(255) DEFAULT 'Office',
        status VARCHAR(20) DEFAULT 'present',
        shift_id INT NULL,
        total_hours DECIMAL(5,2) NULL,
        ip_address VARCHAR(45) NULL,
        device_info TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_check_in_date (check_in)
    )");
    
    // Check for missing columns and add them
    $columns = $db->query("DESCRIBE attendance")->fetchAll(PDO::FETCH_ASSOC);
    $existingColumns = array_column($columns, 'Field');
    
    $requiredColumns = [
        'check_out' => 'DATETIME NULL',
        'latitude' => 'DECIMAL(10, 8) NULL',
        'longitude' => 'DECIMAL(11, 8) NULL',
        'location_name' => 'VARCHAR(255) DEFAULT "Office"',
        'status' => 'VARCHAR(20) DEFAULT "present"',
        'total_hours' => 'DECIMAL(5,2) NULL',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    foreach ($requiredColumns as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            echo "Adding missing column: $column\n";
            $db->exec("ALTER TABLE attendance ADD COLUMN $column $definition");
        } else {
            echo "Column $column already exists\n";
        }
    }
    
    echo "Attendance table structure verified successfully!\n";
    
    // Test query to ensure everything works
    $stmt = $db->query("SELECT COUNT(*) as count FROM attendance");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current attendance records: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
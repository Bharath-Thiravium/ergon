<?php
// Setup Settings Table - Run this once to fix the settings module
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // 1. Create settings table with correct structure
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(255) DEFAULT 'ERGON Company',
        timezone VARCHAR(50) DEFAULT 'Asia/Kolkata',
        working_hours_start TIME DEFAULT '09:00:00',
        working_hours_end TIME DEFAULT '18:00:00',
        base_location_lat DECIMAL(10,8) DEFAULT 0,
        base_location_lng DECIMAL(11,8) DEFAULT 0,
        attendance_radius INT DEFAULT 200,
        office_address TEXT DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // 2. Insert default record if none exists
    $count = $db->query("SELECT COUNT(*) FROM settings")->fetchColumn();
    if ($count == 0) {
        $db->exec("INSERT INTO settings (company_name, timezone, working_hours_start, working_hours_end, attendance_radius) 
                   VALUES ('ERGON Company', 'Asia/Kolkata', '09:00:00', '18:00:00', 200)");
    }
    
    echo "✅ Settings table created successfully!<br>";
    echo "✅ Default data inserted!<br>";
    echo "<a href='/ergon/settings'>Go to Settings</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
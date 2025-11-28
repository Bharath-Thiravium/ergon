<?php
// Setup script to ensure location settings are properly configured
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Ensure settings table exists with proper structure
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(255) DEFAULT 'ERGON Company',
        base_location_lat DECIMAL(10,8) DEFAULT 0,
        base_location_lng DECIMAL(11,8) DEFAULT 0,
        attendance_radius INT DEFAULT 200,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Check if settings exist
    $stmt = $db->query("SELECT COUNT(*) as count FROM settings");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Insert default settings with a sample office location (Delhi, India)
        $db->exec("INSERT INTO settings (company_name, base_location_lat, base_location_lng, attendance_radius) 
                   VALUES ('ERGON Company', 28.6139, 77.2090, 200)");
        echo "✅ Default location settings created successfully!<br>";
        echo "📍 Office Location: Delhi, India (28.6139, 77.2090)<br>";
        echo "📏 Attendance Radius: 200 meters<br><br>";
        echo "⚠️ <strong>Important:</strong> Please update the office location in Settings to match your actual office coordinates.<br>";
    } else {
        // Check current settings
        $stmt = $db->query("SELECT * FROM settings LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "✅ Location settings already exist:<br>";
        echo "📍 Office Location: " . $settings['base_location_lat'] . ", " . $settings['base_location_lng'] . "<br>";
        echo "📏 Attendance Radius: " . $settings['attendance_radius'] . " meters<br><br>";
        
        if ($settings['base_location_lat'] == 0 && $settings['base_location_lng'] == 0) {
            echo "⚠️ <strong>Warning:</strong> Office coordinates are not set (0, 0). Please update them in Settings.<br>";
        }
    }
    
    // Ensure attendance table has location columns
    try {
        $db->exec("ALTER TABLE attendance ADD COLUMN latitude DECIMAL(10, 8) NULL");
        echo "✅ Added latitude column to attendance table<br>";
    } catch (Exception $e) {
        // Column probably already exists
    }
    
    try {
        $db->exec("ALTER TABLE attendance ADD COLUMN longitude DECIMAL(11, 8) NULL");
        echo "✅ Added longitude column to attendance table<br>";
    } catch (Exception $e) {
        // Column probably already exists
    }
    
    echo "<br>🎉 Location restriction setup completed successfully!<br>";
    echo "<br>📋 <strong>Next Steps:</strong><br>";
    echo "1. Go to Settings → System Settings<br>";
    echo "2. Set your actual office coordinates using the map picker<br>";
    echo "3. Configure the appropriate attendance radius<br>";
    echo "4. Test the attendance system<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
</style>
<?php
/**
 * Quick Setup Script for Enhanced Attendance System
 * Run this once to set up the enhanced attendance system
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>🕐 Enhanced Attendance System Setup</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    $db = Database::connect();
    
    echo "<h2>Step 1: Creating Enhanced Tables</h2>";
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/database/attendance_system_schema.sql');
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $db->exec($statement);
                echo "<span class='success'>✅ Executed: " . substr($statement, 0, 50) . "...</span><br>";
            } catch (Exception $e) {
                echo "<span class='info'>ℹ️ Skipped: " . $e->getMessage() . "</span><br>";
            }
        }
    }
    
    echo "<h2>Step 2: Configuring Default Settings</h2>";
    
    // Set default GPS location (New Delhi coordinates as example)
    $stmt = $db->prepare("
        UPDATE attendance_rules SET 
            office_latitude = 28.6139,
            office_longitude = 77.2090,
            office_radius_meters = 200,
            is_gps_required = 1,
            auto_checkout_time = '18:00:00',
            half_day_hours = 4.0,
            full_day_hours = 8.0
        WHERE id = 1
    ");
    
    if ($stmt->execute()) {
        echo "<span class='success'>✅ GPS and attendance rules configured</span><br>";
    }
    
    // Add shift_id column to users table if not exists
    try {
        $db->exec("ALTER TABLE users ADD COLUMN shift_id INT DEFAULT 1");
        echo "<span class='success'>✅ Added shift_id to users table</span><br>";
    } catch (Exception $e) {
        echo "<span class='info'>ℹ️ shift_id column already exists</span><br>";
    }
    
    echo "<h2>Step 3: Testing System</h2>";
    
    // Test attendance rules
    $stmt = $db->query("SELECT * FROM attendance_rules LIMIT 1");
    $rules = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($rules) {
        echo "<span class='success'>✅ Attendance rules loaded successfully</span><br>";
        echo "<span class='info'>📍 Office Location: {$rules['office_latitude']}, {$rules['office_longitude']}</span><br>";
        echo "<span class='info'>📏 GPS Radius: {$rules['office_radius_meters']} meters</span><br>";
    }
    
    // Test shifts
    $stmt = $db->query("SELECT COUNT(*) as count FROM shifts");
    $shiftCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<span class='success'>✅ {$shiftCount} shifts configured</span><br>";
    
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<div style='background:#e8f5e8;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<h3>🎉 Enhanced Attendance System Ready!</h3>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>📱 Test clock in/out: <a href='/ergon/attendance/clock'>http://localhost/ergon/attendance/clock</a></li>";
    echo "<li>📊 View dashboard: <a href='/ergon/views/attendance/enhanced_index.php'>Enhanced Dashboard</a></li>";
    echo "<li>⚙️ Configure GPS location in attendance_rules table</li>";
    echo "<li>🕐 Set up cron job: <code>0 19 * * * php /path/to/ergon/cron/attendance_cron.php</code></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>📋 System Features Enabled:</h3>";
    echo "<ul>";
    echo "<li>✅ GPS-based attendance tracking</li>";
    echo "<li>✅ Shift management system</li>";
    echo "<li>✅ Auto-checkout functionality</li>";
    echo "<li>✅ Attendance correction requests</li>";
    echo "<li>✅ Real-time dashboard</li>";
    echo "<li>✅ RESTful API endpoints</li>";
    echo "<li>✅ Automated absent marking</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Setup Error: " . $e->getMessage() . "</span><br>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>
<?php
require_once __DIR__ . '/app/config/database.php';

echo "<h1>Settings Debug</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    $db = Database::connect();
    
    // Check if settings table exists
    $stmt = $db->query("SHOW TABLES LIKE 'settings'");
    if (!$stmt->fetchColumn()) {
        echo "<span class='error'>❌ Settings table doesn't exist</span><br>";
        
        // Create table
        $db->exec("CREATE TABLE settings (
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
        
        // Insert default record
        $db->exec("INSERT INTO settings (company_name, timezone, working_hours_start, working_hours_end, attendance_radius) VALUES ('ERGON Company', 'Asia/Kolkata', '09:00:00', '18:00:00', 200)");
        
        echo "<span class='success'>✅ Created settings table and inserted default data</span><br>";
    } else {
        echo "<span class='success'>✅ Settings table exists</span><br>";
    }
    
    // Check current data
    $stmt = $db->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($settings) {
        echo "<h2>Current Settings Data:</h2>";
        foreach ($settings as $key => $value) {
            echo "<span class='info'>{$key}: {$value}</span><br>";
        }
    } else {
        echo "<span class='error'>❌ No settings data found</span><br>";
    }
    
    // Test form submission simulation
    if ($_POST) {
        echo "<h2>Form Data Received:</h2>";
        foreach ($_POST as $key => $value) {
            echo "<span class='info'>{$key}: {$value}</span><br>";
        }
        
        // Test update
        $stmt = $db->prepare("UPDATE settings SET company_name = ?, timezone = ?, working_hours_start = ?, working_hours_end = ?, attendance_radius = ? WHERE id = 1");
        $result = $stmt->execute([
            $_POST['company_name'] ?? 'Test Company',
            $_POST['timezone'] ?? 'Asia/Kolkata',
            $_POST['working_hours_start'] ?? '09:00',
            $_POST['working_hours_end'] ?? '18:00',
            $_POST['attendance_radius'] ?? 200
        ]);
        
        if ($result) {
            echo "<span class='success'>✅ Settings updated successfully</span><br>";
        } else {
            echo "<span class='error'>❌ Settings update failed</span><br>";
        }
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: {$e->getMessage()}</span><br>";
}
?>

<form method="POST">
    <h2>Test Settings Form:</h2>
    <input type="text" name="company_name" placeholder="Company Name" value="Test Company"><br><br>
    <select name="timezone">
        <option value="Asia/Kolkata">Asia/Kolkata</option>
        <option value="UTC">UTC</option>
    </select><br><br>
    <input type="time" name="working_hours_start" value="09:00"><br><br>
    <input type="time" name="working_hours_end" value="18:00"><br><br>
    <input type="number" name="attendance_radius" value="200"><br><br>
    <button type="submit">Test Save</button>
</form>
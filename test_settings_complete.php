<?php
// Complete Settings Test - Verify everything works
require_once __DIR__ . '/app/config/database.php';

echo "<h1>Settings Module Test</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    $db = Database::connect();
    
    // 1. Check table structure
    $stmt = $db->query("DESCRIBE settings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>✅ Table Structure:</h2>";
    foreach ($columns as $col) {
        echo "<span class='info'>{$col['Field']}: {$col['Type']}</span><br>";
    }
    
    // 2. Test data retrieval
    $stmt = $db->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h2>✅ Current Data:</h2>";
    if ($settings) {
        foreach ($settings as $key => $value) {
            echo "<span class='success'>{$key}: {$value}</span><br>";
        }
    } else {
        echo "<span class='error'>No data found</span><br>";
    }
    
    // 3. Test form submission
    if ($_POST) {
        echo "<h2>✅ Testing Save:</h2>";
        $stmt = $db->prepare("UPDATE settings SET company_name=?, timezone=?, working_hours_start=?, working_hours_end=?, attendance_radius=? WHERE id=1");
        $result = $stmt->execute([
            $_POST['company_name'],
            $_POST['timezone'], 
            $_POST['working_hours_start'],
            $_POST['working_hours_end'],
            $_POST['attendance_radius']
        ]);
        
        if ($result) {
            echo "<span class='success'>✅ Data saved successfully!</span><br>";
            echo "<a href='?'>Refresh to see changes</a><br>";
        } else {
            echo "<span class='error'>❌ Save failed</span><br>";
        }
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: {$e->getMessage()}</span><br>";
}
?>

<h2>Test Form:</h2>
<form method="POST">
    <input type="text" name="company_name" placeholder="Company Name" value="<?= $settings['company_name'] ?? 'Test Company' ?>"><br><br>
    <select name="timezone">
        <option value="Asia/Kolkata" <?= ($settings['timezone'] ?? '') === 'Asia/Kolkata' ? 'selected' : '' ?>>Asia/Kolkata</option>
        <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
    </select><br><br>
    <input type="time" name="working_hours_start" value="<?= substr($settings['working_hours_start'] ?? '09:00:00', 0, 5) ?>"><br><br>
    <input type="time" name="working_hours_end" value="<?= substr($settings['working_hours_end'] ?? '18:00:00', 0, 5) ?>"><br><br>
    <input type="number" name="attendance_radius" value="<?= $settings['attendance_radius'] ?? 200 ?>"><br><br>
    <button type="submit">Save Settings</button>
</form>

<p><a href="/ergon/settings">Go to Actual Settings Page</a></p>
<?php
require_once 'app/config/database.php';
require_once 'app/helpers/TimezoneHelper.php';

try {
    $db = Database::connect();
    
    echo "<h2>Time Reference Debug Report</h2>";
    
    // 1. Check attendance table structure
    echo "<h3>1. Attendance Table Structure</h3>";
    $stmt = $db->query("SHOW COLUMNS FROM attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>Column</th><th>Type</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
    
    // 2. Check recent attendance records with raw data
    echo "<h3>2. Recent Attendance Records (Raw Database Values)</h3>";
    $stmt = $db->query("SELECT id, user_id, check_in, check_out, created_at FROM attendance ORDER BY id DESC LIMIT 5");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>ID</th><th>User ID</th><th>Check In (Raw)</th><th>Check Out (Raw)</th><th>Created At</th></tr>";
    foreach ($records as $record) {
        echo "<tr>";
        echo "<td>{$record['id']}</td>";
        echo "<td>{$record['user_id']}</td>";
        echo "<td>{$record['check_in']}</td>";
        echo "<td>{$record['check_out']}</td>";
        echo "<td>{$record['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Test TimezoneHelper conversion
    echo "<h3>3. TimezoneHelper Conversion Test</h3>";
    $testUtc = '2025-01-02 10:17:00';
    echo "<p><strong>Test UTC Time:</strong> $testUtc</p>";
    echo "<p><strong>Owner Timezone:</strong> " . TimezoneHelper::getOwnerTimezone() . "</p>";
    echo "<p><strong>Converted to Owner Time:</strong> " . TimezoneHelper::utcToOwner($testUtc) . "</p>";
    echo "<p><strong>Display Time (H:i format):</strong> " . TimezoneHelper::displayTime($testUtc) . "</p>";
    
    // 4. Check if there are multiple time references in the view
    echo "<h3>4. Check for Multiple Time References</h3>";
    $viewFile = 'views/attendance/admin_index.php';
    if (file_exists($viewFile)) {
        $content = file_get_contents($viewFile);
        
        // Look for time-related patterns
        $patterns = [
            'TimezoneHelper::displayTime' => substr_count($content, 'TimezoneHelper::displayTime'),
            'check_in' => substr_count($content, 'check_in'),
            'check_out' => substr_count($content, 'check_out'),
            'date(' => substr_count($content, 'date('),
            'time(' => substr_count($content, 'time('),
            'strtotime(' => substr_count($content, 'strtotime(')
        ];
        
        echo "<table border='1'><tr><th>Pattern</th><th>Count</th></tr>";
        foreach ($patterns as $pattern => $count) {
            echo "<tr><td>$pattern</td><td>$count</td></tr>";
        }
        echo "</table>";
    }
    
    // 5. Check current server time vs expected display
    echo "<h3>5. Current Time Analysis</h3>";
    echo "<p><strong>Server Time (UTC):</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>PHP Timezone:</strong> " . date_default_timezone_get() . "</p>";
    echo "<p><strong>Current UTC (TimezoneHelper):</strong> " . TimezoneHelper::nowUtc() . "</p>";
    echo "<p><strong>Current Owner Time:</strong> " . TimezoneHelper::utcToOwner(TimezoneHelper::nowUtc()) . "</p>";
    
    // 6. Check for duplicate time handling in controller
    echo "<h3>6. Controller Time Handling Check</h3>";
    $controllerFile = 'app/controllers/AttendanceController.php';
    if (file_exists($controllerFile)) {
        $content = file_get_contents($controllerFile);
        
        // Look for time-related method calls
        $timePatterns = [
            'date(' => substr_count($content, 'date('),
            'time(' => substr_count($content, 'time('),
            'TimezoneHelper::' => substr_count($content, 'TimezoneHelper::'),
            'date_default_timezone_set' => substr_count($content, 'date_default_timezone_set'),
            'getCurrentTime' => substr_count($content, 'getCurrentTime'),
            'nowUtc' => substr_count($content, 'nowUtc'),
            'displayTime' => substr_count($content, 'displayTime')
        ];
        
        echo "<table border='1'><tr><th>Time Method</th><th>Count in Controller</th></tr>";
        foreach ($timePatterns as $pattern => $count) {
            echo "<tr><td>$pattern</td><td>$count</td></tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
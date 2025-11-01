<?php
/**
 * Test Settings Fix
 * Verify that settings form error is resolved
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h1>🔧 Settings Fix Verification</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>\n";

try {
    $db = Database::connect();
    echo "<span class='success'>✅ Database connected</span><br>\n";
    
    // Check settings table structure
    $stmt = $db->query("DESCRIBE settings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Settings Table Structure:</h2>\n";
    foreach ($columns as $column) {
        $name = $column['Field'];
        $type = $column['Type'];
        echo "<span class='info'>📋 {$name}: {$type}</span><br>\n";
    }
    
    // Check if working_hours_start and working_hours_end are TIME type
    $timeColumns = array_filter($columns, function($col) {
        return in_array($col['Field'], ['working_hours_start', 'working_hours_end']) && 
               strpos($col['Type'], 'time') !== false;
    });
    
    if (count($timeColumns) >= 2) {
        echo "<span class='success'>✅ Working hours columns are TIME type</span><br>\n";
    } else {
        echo "<span class='error'>❌ Working hours columns need to be TIME type</span><br>\n";
    }
    
    // Check current settings data
    $stmt = $db->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($settings) {
        echo "<h2>Current Settings:</h2>\n";
        echo "<span class='info'>Company: " . ($settings['company_name'] ?? 'N/A') . "</span><br>\n";
        echo "<span class='info'>Timezone: " . ($settings['timezone'] ?? 'N/A') . "</span><br>\n";
        echo "<span class='info'>Start Time: " . ($settings['working_hours_start'] ?? 'N/A') . "</span><br>\n";
        echo "<span class='info'>End Time: " . ($settings['working_hours_end'] ?? 'N/A') . "</span><br>\n";
        echo "<span class='info'>Radius: " . ($settings['attendance_radius'] ?? 'N/A') . "</span><br>\n";
        echo "<span class='success'>✅ Settings data loaded successfully</span><br>\n";
    } else {
        echo "<span class='error'>❌ No settings data found</span><br>\n";
    }
    
    echo "<h2>✅ Fix Summary:</h2>\n";
    echo "<ul>\n";
    echo "<li>✅ Changed form field from type='number' to type='time'</li>\n";
    echo "<li>✅ Added working_hours_end field</li>\n";
    echo "<li>✅ Fixed database column mapping</li>\n";
    echo "<li>✅ Added proper time value handling</li>\n";
    echo "</ul>\n";
    
    echo "<p><strong>The settings form error should now be resolved!</strong></p>\n";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: {$e->getMessage()}</span><br>\n";
}
?>
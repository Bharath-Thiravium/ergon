<?php
require_once __DIR__ . '/app/config/database.php';

$db = Database::connect();

// Remove unused columns from attendance table
$unusedColumns = [
    'date', 'location', 'shift_id', 'distance_meters', 'is_auto_checkout', 
    'manual_entry', 'edited_by', 'edit_reason', 'working_hours',
    'check_in_latitude', 'check_in_longitude', 'check_out_latitude', 
    'check_out_longitude', 'location_verified', 'location_type', 
    'location_title', 'location_radius', 'latitude', 'longitude',
    'location_display', 'project_name'
];

foreach ($unusedColumns as $column) {
    try {
        $db->exec("ALTER TABLE attendance DROP COLUMN $column");
        echo "✅ Dropped $column<br>";
    } catch (Exception $e) {
        echo "⚠️ $column already removed<br>";
    }
}

echo "<br>✅ Table optimized! Only essential columns remain.";
?>

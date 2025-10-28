<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "GPS COLUMN FIX\n==============\n";
    
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('latitude', $columns)) {
        $db->exec("ALTER TABLE attendance ADD COLUMN latitude DECIMAL(10,8) AFTER clock_out");
        echo "✅ Added latitude column\n";
    }
    
    if (!in_array('longitude', $columns)) {
        $db->exec("ALTER TABLE attendance ADD COLUMN longitude DECIMAL(11,8) AFTER latitude");
        echo "✅ Added longitude column\n";
    }
    
    echo "🎉 100% SYSTEM OPERATIONAL\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
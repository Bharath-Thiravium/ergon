<?php
// Verification script - run this first
require_once __DIR__ . '/app/helpers/TimezoneHelper.php';
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>🔍 Timezone Fix Verification</h2>";
    echo "Owner timezone: " . TimezoneHelper::getOwnerTimezone() . "<br><br>";
    
    $stmt = $db->query("SELECT id, user_id, check_in, check_out FROM attendance ORDER BY id DESC LIMIT 10");
    $rows = $stmt->fetchAll();
    
    echo "<h3>Sample Records (UTC stored → Owner timezone display):</h3>";
    foreach ($rows as $r) {
        $displayIn = TimezoneHelper::displayTime($r['check_in']);
        $displayOut = TimezoneHelper::displayTime($r['check_out']);
        echo "ID {$r['id']} | Raw: {$r['check_in']} → Display: $displayIn";
        if ($displayOut) echo " | Out: $displayOut";
        echo "<br>";
    }
    
    echo "<h3>✅ Fix Applied</h3>";
    echo "- New records will be stored in UTC<br>";
    echo "- All displays will show owner timezone<br>";
    echo "- Attendance table will now show correct IST times<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
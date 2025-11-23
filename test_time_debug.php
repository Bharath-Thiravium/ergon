<?php
try {
    require_once __DIR__ . '/app/helpers/TimezoneHelper.php';
    require_once __DIR__ . '/app/config/database.php';
    
    echo "<pre>";
    echo "Owner Timezone: " . TimezoneHelper::getOwnerTimezone() . "\n";
    echo "UTC now: " . TimezoneHelper::nowUtc() . "\n";
    
    $dt = new DateTime('now', new DateTimeZone(TimezoneHelper::getOwnerTimezone()));
    echo "Owner now: " . $dt->format('Y-m-d H:i:s') . "\n\n";
    
    $sample = "2025-01-20 10:00:00";
    echo "Sample UTC: $sample\n";
    echo "Converted to Owner: " . TimezoneHelper::utcToOwner($sample) . "\n";
    echo "Display time: " . TimezoneHelper::displayTime($sample) . "\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine();
}
?>
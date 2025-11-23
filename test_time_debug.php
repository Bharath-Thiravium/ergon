<?php
require_once __DIR__ . '/app/helpers/TimezoneHelper.php';

echo "<pre>";
echo "Owner Timezone: " . TimezoneHelper::getOwnerTimezone() . "\n";
echo "UTC now: " . TimezoneHelper::nowUtc() . "\n";

// Add nowOwner method to TimezoneHelper
$dt = new DateTime('now', new DateTimeZone(TimezoneHelper::getOwnerTimezone()));
echo "Owner now: " . $dt->format('Y-m-d H:i:s') . "\n\n";

$sample = "2025-01-20 10:00:00";
echo "Sample UTC: $sample\n";
echo "Converted to Owner: " . TimezoneHelper::utcToOwner($sample) . "\n";
echo "Display time: " . TimezoneHelper::displayTime($sample) . "\n";
echo "</pre>";
?>
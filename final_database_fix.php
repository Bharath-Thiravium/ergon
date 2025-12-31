<?php
require_once __DIR__ . '/app/config/database.php';

$db = Database::connect();

// 1. Fix record 28 (Nelson's latest attendance)
$stmt = $db->prepare("UPDATE attendance SET project_id = 14 WHERE id = 28");
$stmt->execute();
echo "✅ Fixed record 28<br>";

// 2. Fix view template
$viewFile = __DIR__ . '/views/attendance/index.php';
$content = file_get_contents($viewFile);

$content = preg_replace(
    '/\?\= \$record\[\'location_display\'\] \?\? \(\$record\[\'check_in\'\] \? \'---\' : \'---\'\)/',
    '?= $record[\'location_display\'] ?? \'No Location\'',
    $content
);

$content = preg_replace(
    '/\?\= \$record\[\'project_name\'\] \?\? \(\$record\[\'check_in\'\] \? \'----\' : \'----\'\)/',
    '?= $record[\'project_name\'] ?? \'No Project\'',
    $content
);

file_put_contents($viewFile, $content);
echo "✅ Fixed view template<br>";

echo "<br>✅ Complete fix applied!";
?>

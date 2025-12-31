<?php
$viewFile = __DIR__ . '/views/attendance/index.php';
$content = file_get_contents($viewFile);

// Replace all instances of hardcoded fallbacks
$content = preg_replace(
    '/\$record\[\'location_display\'\] \?\? \(\$record\[\'check_in\'\] \? \'---\' : \'---\'\)/',
    '$record[\'location_display\']',
    $content
);

$content = preg_replace(
    '/\$record\[\'project_name\'\] \?\? \(\$record\[\'check_in\'\] \? \'----\' : \'----\'\)/',
    '$record[\'project_name\']',
    $content
);

file_put_contents($viewFile, $content);
echo "✅ Forcefully removed all hardcoded fallbacks";
?>

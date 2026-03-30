<?php
$viewFile = __DIR__ . '/views/attendance/index.php';
$content = file_get_contents($viewFile);

$content = str_replace(
    "(\$record['check_in'] ? '---' : '---')",
    "'No Location'",
    $content
);

$content = str_replace(
    "(\$record['check_in'] ? '----' : '----')",
    "'No Project'",
    $content
);

file_put_contents($viewFile, $content);
echo "✅ Fixed view template hardcoded values";
?>

<?php
// Quick verification: confirm SLA stopwatch fix is in unified_daily_planner.php
$file = __DIR__ . '/../views/daily_workflow/unified_daily_planner.php';
$lines = file($file);

echo "<pre><b>Lines 135-155 of unified_daily_planner.php:</b>\n";
for ($i = 134; $i <= 154; $i++) {
    echo str_pad($i + 1, 3) . ': ' . htmlspecialchars($lines[$i] ?? '');
}
echo "</pre>";

// Key checks
$content = implode('', $lines);
$checks = [
    'stopwatch comment'        => 'Stopwatch: count up from 0',
    '$elapsedSeconds defined'  => '$elapsedSeconds  = $activeSeconds',
    'no old remainingTime'     => !str_contains($content, '$remainingTime = $slaDuration;'),
    'no Solution 2 comment'    => !str_contains($content, 'Solution 2: Fix PHP Initial Display'),
    'Elapsed label'            => "'Elapsed'",
];
echo "<b>Checks:</b><ul>";
foreach ($checks as $label => $result) {
    $ok = is_bool($result) ? $result : str_contains($content, $result);
    $icon = $ok ? '✅' : '❌';
    echo "<li>$icon $label</li>";
}
echo "</ul>";

// OPcache status
if (function_exists('opcache_get_status')) {
    $s = opcache_get_status(false);
    echo "<b>OPcache:</b> " . ($s['opcache_enabled'] ? 'Enabled' : 'Disabled') . "<br>";
} else {
    echo "<b>OPcache:</b> extension not loaded<br>";
}
?>

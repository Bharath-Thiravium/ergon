<?php
$f = __DIR__ . '/../views/daily_workflow/unified_daily_planner.php';
$c = file_get_contents($f);
$lines = explode("\n", $c);

echo "Line 139: " . trim($lines[138]) . "\n";
echo "Line 140: " . trim($lines[139]) . "\n";
echo "Line 141: " . trim($lines[140]) . "\n";
echo "Line 142: " . trim($lines[141]) . "\n";
echo "\n";
echo "Check stopwatch comment: "  . (str_contains($c, 'Stopwatch: count up from 0') ? 'FOUND' : 'MISSING') . "\n";
echo "Check old remainingTime:  "  . (str_contains($c, '$remainingTime = $slaDuration;') ? 'STILL PRESENT (BAD)' : 'REMOVED (GOOD)') . "\n";
echo "Check elapsedSeconds def: "  . (str_contains($c, 'elapsedSeconds') ? 'FOUND' : 'MISSING') . "\n";
echo "Check Elapsed label:      "  . (str_contains($c, "'Elapsed'") ? 'FOUND' : 'MISSING') . "\n";

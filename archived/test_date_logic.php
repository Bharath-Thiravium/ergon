<?php
// Test date comparison logic
echo "<h2>Date Logic Test</h2>\n";

$testDate = '2025-11-25';
$currentDate = date('Y-m-d');

echo "Current date: {$currentDate}<br>\n";
echo "Test date: {$testDate}<br>\n";
echo "Test date > current date: " . ($testDate > $currentDate ? 'TRUE' : 'FALSE') . "<br>\n";
echo "Test date < current date: " . ($testDate < $currentDate ? 'TRUE' : 'FALSE') . "<br>\n";
echo "Test date == current date: " . ($testDate === $currentDate ? 'TRUE' : 'FALSE') . "<br>\n";

$isCurrentDate = ($testDate === $currentDate);
$isPastDate = ($testDate < $currentDate);
$isFutureDate = ($testDate > $currentDate);

echo "<br>Classification:<br>\n";
echo "Is current date: " . ($isCurrentDate ? 'YES' : 'NO') . "<br>\n";
echo "Is past date: " . ($isPastDate ? 'YES' : 'NO') . "<br>\n";
echo "Is future date: " . ($isFutureDate ? 'YES' : 'NO') . "<br>\n";

// Test with different dates
$dates = [
    '2024-01-01',
    '2024-12-01', 
    '2025-01-01',
    '2025-11-25',
    '2026-01-01'
];

echo "<br>Testing multiple dates:<br>\n";
foreach ($dates as $date) {
    $isFuture = ($date > $currentDate);
    echo "Date {$date}: " . ($isFuture ? 'FUTURE' : 'PAST/CURRENT') . "<br>\n";
}
?>
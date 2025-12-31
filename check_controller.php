<?php
$file = __DIR__ . '/app/controllers/AttendanceController.php';
$content = file_get_contents($file);

if (strpos($content, 'COALESCE(p.place, ?) as location_display') !== false) {
    echo "✅ Controller has new COALESCE queries";
} else {
    echo "❌ Controller still has old CASE queries";
}

if (strpos($content, 'CASE WHEN a.location_display IS NOT NULL') !== false) {
    echo "<br>❌ Old CASE statements still present";
} else {
    echo "<br>✅ Old CASE statements removed";
}
?>

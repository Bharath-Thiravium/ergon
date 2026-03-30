<?php
$viewFile = __DIR__ . '/views/attendance/index.php';
$content = file_get_contents($viewFile);

// Check for hardcoded values
if (strpos($content, "---") !== false) {
    echo "❌ View contains hardcoded '---'<br>";
    
    // Find the lines with hardcoded values
    $lines = explode("\n", $content);
    foreach ($lines as $num => $line) {
        if (strpos($line, "---") !== false || strpos($line, "----") !== false) {
            echo "Line " . ($num + 1) . ": " . htmlspecialchars(trim($line)) . "<br>";
        }
    }
} else {
    echo "✅ No hardcoded '---' found in view<br>";
}

// Check if view uses the correct variables
if (strpos($content, '$record[\'location_display\']') !== false) {
    echo "✅ View uses location_display variable<br>";
} else {
    echo "❌ View doesn't use location_display variable<br>";
}

if (strpos($content, '$record[\'project_name\']') !== false) {
    echo "✅ View uses project_name variable<br>";
} else {
    echo "❌ View doesn't use project_name variable<br>";
}
?>

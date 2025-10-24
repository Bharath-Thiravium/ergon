<?php
/**
 * Hostinger Compatibility Check
 * Test CSS/JS file loading
 */

echo "<h2>🔧 Hostinger Compatibility Check</h2>";

$baseUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
$ergonPath = '/ergon/public/assets';

$files = [
    'CSS Main' => $baseUrl . $ergonPath . '/css/ergon.css',
    'CSS Dark' => $baseUrl . $ergonPath . '/css/dark-theme.css',
    'JS Activity' => $baseUrl . $ergonPath . '/js/activity-tracker.js',
    'Favicon' => $baseUrl . '/ergon/public/favicon.ico'
];

echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
echo "<tr><th>File</th><th>URL</th><th>Status</th></tr>";

foreach ($files as $name => $url) {
    $headers = @get_headers($url);
    $status = $headers && strpos($headers[0], '200') ? '✅ OK' : '❌ Missing';
    $color = $headers && strpos($headers[0], '200') ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>$name</td>";
    echo "<td><a href='$url' target='_blank'>$url</a></td>";
    echo "<td style='color:$color'>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Environment Info:</h3>";
echo "<p><strong>Server:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p><strong>Scheme:</strong> " . $_SERVER['REQUEST_SCHEME'] . "</p>";
echo "<p><strong>Base URL:</strong> $baseUrl</p>";

echo "<p><a href='/ergon/login'>🚀 Go to Login</a></p>";
?>
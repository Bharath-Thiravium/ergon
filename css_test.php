<?php
/**
 * CSS Accessibility Test
 * Direct test for CSS file access
 */

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>CSS Test</title></head><body>";

echo "<h1>🎨 CSS Accessibility Test</h1>";

// Test CSS file existence and content
$cssFiles = [
    'public/assets/css/ergon.css',
    'public/assets/css/components.css'
];

foreach ($cssFiles as $file) {
    echo "<h2>Testing: $file</h2>";
    
    if (file_exists($file)) {
        $size = filesize($file);
        $content = file_get_contents($file);
        $lines = substr_count($content, "\n");
        
        echo "<p>✅ File exists: {$size} bytes, {$lines} lines</p>";
        
        // Test key CSS classes
        $hasKpiCard = strpos($content, '.kpi-card') !== false;
        $hasDashboardGrid = strpos($content, '.dashboard-grid') !== false;
        
        echo "<p>KPI Card class: " . ($hasKpiCard ? "✅ Found" : "❌ Missing") . "</p>";
        echo "<p>Dashboard Grid class: " . ($hasDashboardGrid ? "✅ Found" : "❌ Missing") . "</p>";
        
        // Show first 500 characters
        echo "<h3>Content Preview:</h3>";
        echo "<pre style='background:#f0f0f0;padding:10px;overflow:auto;max-height:200px;'>";
        echo htmlspecialchars(substr($content, 0, 500)) . "...";
        echo "</pre>";
        
    } else {
        echo "<p>❌ File not found</p>";
    }
}

// Test URL access
echo "<h2>🔗 URL Access Test</h2>";
$baseUrl = "http://" . $_SERVER['HTTP_HOST'];
$cssUrls = [
    '/ergon/public/assets/css/ergon.css',
    '/ergon/public/assets/css/components.css'
];

foreach ($cssUrls as $url) {
    $fullUrl = $baseUrl . $url;
    echo "<p><a href='$fullUrl' target='_blank'>$url</a></p>";
    
    // Test with cURL if available
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p>HTTP Status: " . ($httpCode == 200 ? "✅ $httpCode" : "❌ $httpCode") . "</p>";
    }
}

echo "</body></html>";
?>
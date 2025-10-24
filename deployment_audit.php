<?php
/**
 * Deployment Audit Script
 * Checks for differences between localhost and production environment
 */

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Deployment Audit</title>";
echo "<style>body{font-family:monospace;margin:20px;} .error{color:red;} .success{color:green;} .warning{color:orange;} .section{margin:20px 0; padding:10px; border:1px solid #ccc;}</style>";
echo "</head><body>";

echo "<h1>🔍 ERGON Deployment Audit Report</h1>";
echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";

// Environment Detection
$isProduction = (strpos($_SERVER['HTTP_HOST'], 'localhost') === false);
echo "<div class='section'>";
echo "<h2>Environment: " . ($isProduction ? "🌐 PRODUCTION" : "💻 LOCALHOST") . "</h2>";
echo "<p>Host: " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "</div>";

// File Structure Audit
echo "<div class='section'><h2>📁 File Structure Audit</h2>";

$criticalFiles = [
    'public/assets/css/ergon.css',
    'public/assets/css/components.css',
    'app/views/layouts/dashboard.php',
    'app/views/daily_planner/dashboard.php',
    'app/views/daily_planner/delayed_tasks_overview.php',
    'app/views/daily_planner/project_overview.php',
    'config/routes.php',
    '.htaccess'
];

foreach ($criticalFiles as $file) {
    $exists = file_exists($file);
    $size = $exists ? filesize($file) : 0;
    $modified = $exists ? date('Y-m-d H:i:s', filemtime($file)) : 'N/A';
    
    echo "<div style='margin:5px 0;'>";
    echo ($exists ? "✅" : "❌") . " ";
    echo "<strong>$file</strong> ";
    echo $exists ? "({$size} bytes, modified: {$modified})" : "<span class='error'>MISSING</span>";
    echo "</div>";
}
echo "</div>";

// CSS Content Audit
echo "<div class='section'><h2>🎨 CSS Content Audit</h2>";

$cssFiles = ['public/assets/css/ergon.css', 'public/assets/css/components.css'];
foreach ($cssFiles as $cssFile) {
    if (file_exists($cssFile)) {
        $content = file_get_contents($cssFile);
        $lines = substr_count($content, "\n");
        $size = strlen($content);
        
        echo "<h3>$cssFile</h3>";
        echo "<p>Size: {$size} bytes, Lines: {$lines}</p>";
        
        // Check for critical CSS classes
        $criticalClasses = [
            '.kpi-card' => strpos($content, '.kpi-card') !== false,
            '.dashboard-grid' => strpos($content, '.dashboard-grid') !== false,
            '.main-content' => strpos($content, '.main-content') !== false,
            '.sidebar' => strpos($content, '.sidebar') !== false,
            '--primary' => strpos($content, '--primary') !== false
        ];
        
        foreach ($criticalClasses as $class => $found) {
            echo "<div>" . ($found ? "✅" : "❌") . " $class</div>";
        }
    } else {
        echo "<p class='error'>❌ $cssFile not found</p>";
    }
}
echo "</div>";

// Route Audit
echo "<div class='section'><h2>🛣️ Route Audit</h2>";
if (file_exists('config/routes.php')) {
    $routes = file_get_contents('config/routes.php');
    $dailyPlannerRoutes = [
        '/daily-planner/dashboard' => strpos($routes, '/daily-planner/dashboard') !== false,
        '/daily-planner/delayed-tasks-overview' => strpos($routes, '/daily-planner/delayed-tasks-overview') !== false,
        '/daily-planner/project-overview' => strpos($routes, '/daily-planner/project-overview') !== false
    ];
    
    foreach ($dailyPlannerRoutes as $route => $found) {
        echo "<div>" . ($found ? "✅" : "❌") . " $route</div>";
    }
} else {
    echo "<p class='error'>❌ routes.php not found</p>";
}
echo "</div>";

// Permission Audit
echo "<div class='section'><h2>🔐 Permission Audit</h2>";
$directories = ['public/assets', 'public/assets/css', 'storage', 'logs'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir);
        echo "<div>" . ($writable ? "✅" : "⚠️") . " $dir (permissions: $perms)</div>";
    } else {
        echo "<div>❌ $dir not found</div>";
    }
}
echo "</div>";

// Browser Cache Headers
echo "<div class='section'><h2>🌐 HTTP Headers</h2>";
$headers = getallheaders();
echo "<div>User-Agent: " . ($headers['User-Agent'] ?? 'Unknown') . "</div>";
echo "<div>Cache-Control: " . ($headers['Cache-Control'] ?? 'Not set') . "</div>";
echo "</div>";

// CSS URL Test
echo "<div class='section'><h2>🔗 CSS URL Test</h2>";
$cssUrls = [
    '/ergon/public/assets/css/ergon.css',
    '/ergon/public/assets/css/components.css'
];

foreach ($cssUrls as $url) {
    $fullUrl = "http://" . $_SERVER['HTTP_HOST'] . $url;
    echo "<div>";
    echo "<a href='$fullUrl' target='_blank'>$url</a> ";
    
    // Test if URL is accessible
    $headers = @get_headers($fullUrl);
    if ($headers && strpos($headers[0], '200') !== false) {
        echo "<span class='success'>✅ Accessible</span>";
    } else {
        echo "<span class='error'>❌ Not accessible</span>";
    }
    echo "</div>";
}
echo "</div>";

// Git Info (if available)
echo "<div class='section'><h2>📋 Git Information</h2>";
if (is_dir('.git')) {
    $gitHead = file_exists('.git/HEAD') ? trim(file_get_contents('.git/HEAD')) : 'Unknown';
    echo "<div>HEAD: $gitHead</div>";
    
    if (file_exists('.git/logs/HEAD')) {
        $gitLog = file('.git/logs/HEAD');
        $lastCommit = end($gitLog);
        echo "<div>Last commit: " . substr($lastCommit, 0, 100) . "...</div>";
    }
} else {
    echo "<div class='warning'>⚠️ .git directory not found</div>";
}
echo "</div>";

// Recommendations
echo "<div class='section'><h2>💡 Recommendations</h2>";
echo "<ul>";
echo "<li>Clear browser cache and hard refresh (Ctrl+F5)</li>";
echo "<li>Check .htaccess for CSS file blocking</li>";
echo "<li>Verify file permissions are 644 for CSS files</li>";
echo "<li>Add cache-busting parameters to CSS links</li>";
echo "<li>Check webhook deployment logs</li>";
echo "</ul>";
echo "</div>";

echo "<p><em>Audit completed at " . date('Y-m-d H:i:s') . "</em></p>";
echo "</body></html>";
?>
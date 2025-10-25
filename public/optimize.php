<?php
/**
 * Web-based Asset Optimization
 * Access via: yoursite.com/optimize.php
 */

// Security check
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) && !isset($_GET['allow'])) {
    die('Access denied. Add ?allow=1 to URL if you are the admin.');
}

require_once __DIR__ . '/../app/helpers/PerformanceBooster.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>ERGON Asset Optimizer</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; }
        .success { color: #00ff00; }
        .warning { color: #ffaa00; }
        .info { color: #00aaff; }
        pre { background: #000; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🚀 ERGON Asset Optimization</h1>
    <pre>
<?php
if (isset($_POST['optimize'])) {
    echo "==========================\n\n";
    
    $cssDir = __DIR__ . '/assets/css/';
    $jsDir = __DIR__ . '/assets/js/';
    
    // Minify CSS files
    echo "📄 Minifying CSS files...\n";
    $cssFiles = glob($cssDir . '*.css');
    foreach ($cssFiles as $file) {
        if (strpos($file, '.min.css') !== false) continue;
        
        $content = file_get_contents($file);
        $minified = PerformanceBooster::minifyCSS($content);
        
        $minFile = str_replace('.css', '.min.css', $file);
        file_put_contents($minFile, $minified);
        
        $originalSize = filesize($file);
        $minifiedSize = filesize($minFile);
        $savings = round((($originalSize - $minifiedSize) / $originalSize) * 100, 1);
        
        echo "✅ " . basename($file) . " → " . basename($minFile) . " ({$savings}% smaller)\n";
    }
    
    // Minify JS files
    echo "\n📜 Minifying JS files...\n";
    $jsFiles = glob($jsDir . '*.js');
    foreach ($jsFiles as $file) {
        if (strpos($file, '.min.js') !== false) continue;
        
        $content = file_get_contents($file);
        $minified = PerformanceBooster::minifyJS($content);
        
        $minFile = str_replace('.js', '.min.js', $file);
        file_put_contents($minFile, $minified);
        
        $originalSize = filesize($file);
        $minifiedSize = filesize($minFile);
        $savings = round((($originalSize - $minifiedSize) / $originalSize) * 100, 1);
        
        echo "✅ " . basename($file) . " → " . basename($minFile) . " ({$savings}% smaller)\n";
    }
    
    // Create combined CSS
    echo "\n🔗 Creating combined CSS file...\n";
    $combinedCSS = '';
    $cssFiles = [
        $cssDir . 'ergon.css',
        $cssDir . 'components.css',
        $cssDir . 'sidebar-scroll.css'
    ];
    
    foreach ($cssFiles as $file) {
        if (file_exists($file)) {
            $combinedCSS .= file_get_contents($file) . "\n";
        }
    }
    
    if ($combinedCSS) {
        $minifiedCombined = PerformanceBooster::minifyCSS($combinedCSS);
        file_put_contents($cssDir . 'ergon-combined.min.css', $minifiedCombined);
        echo "✅ Combined CSS created: ergon-combined.min.css\n";
    }
    
    // Optimize database
    echo "\n🗄️ Optimizing database indexes...\n";
    if (PerformanceBooster::optimizeDatabase()) {
        echo "✅ Database indexes optimized\n";
    } else {
        echo "⚠️ Database optimization failed\n";
    }
    
    echo "\n🎯 Optimization Complete!\n";
    echo "========================\n";
    
} else {
    echo "Click the button below to optimize all assets:\n";
    echo "- Minify CSS and JS files\n";
    echo "- Create combined CSS file\n";
    echo "- Optimize database indexes\n\n";
}
?>
    </pre>
    
    <?php if (!isset($_POST['optimize'])): ?>
    <form method="post">
        <button type="submit" name="optimize" style="background: #007700; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
            🚀 OPTIMIZE ASSETS
        </button>
    </form>
    <?php else: ?>
    <p><a href="optimize.php" style="color: #00aaff;">Run Again</a> | <a href="../" style="color: #00aaff;">Back to Site</a></p>
    <?php endif; ?>
</body>
</html>
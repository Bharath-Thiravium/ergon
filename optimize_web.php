<?php
if (isset($_POST['optimize'])) {
    $cssDir = __DIR__ . '/public/assets/css/';
    $jsDir = __DIR__ . '/public/assets/js/';
    
    echo "<h1>Asset Optimization Results</h1>";
    echo "<pre>";
    
    // Minify CSS files
    $cssFiles = glob($cssDir . '*.css');
    foreach ($cssFiles as $file) {
        if (strpos($file, '.min.css') !== false) continue;
        
        $content = file_get_contents($file);
        $minified = preg_replace('/\s+/', ' ', $content);
        
        $minFile = str_replace('.css', '.min.css', $file);
        file_put_contents($minFile, $minified);
        
        echo "✅ Minified: " . basename($file) . "\n";
    }
    
    // Minify JS files
    $jsFiles = glob($jsDir . '*.js');
    foreach ($jsFiles as $file) {
        if (strpos($file, '.min.js') !== false) continue;
        
        $content = file_get_contents($file);
        $minified = preg_replace('/\s+/', ' ', $content);
        
        $minFile = str_replace('.js', '.min.js', $file);
        file_put_contents($minFile, $minified);
        
        echo "✅ Minified: " . basename($file) . "\n";
    }
    
    echo "\n🎯 Optimization Complete!\n";
    echo "</pre>";
    echo '<p><a href="optimize_web.php">Run Again</a></p>';
} else {
?>
<!DOCTYPE html>
<html>
<head><title>ERGON Asset Optimizer</title></head>
<body>
    <h1>🚀 ERGON Asset Optimizer</h1>
    <p>This will minify all CSS and JS files in the assets directory.</p>
    <form method="post">
        <button type="submit" name="optimize" style="padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 5px;">
            Optimize Assets
        </button>
    </form>
</body>
</html>
<?php } ?>
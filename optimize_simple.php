<?php
// Simple asset optimization without dependencies
function minifyCSS($css) {
    $css = preg_replace('/\/\*.*?\*\//s', '', $css);
    $css = preg_replace('/\s+/', ' ', $css);
    return trim($css);
}

function minifyJS($js) {
    $js = preg_replace('/\/\*.*?\*\//s', '', $js);
    $js = preg_replace('/\/\/.*$/m', '', $js);
    $js = preg_replace('/\s+/', ' ', $js);
    return trim($js);
}

if (isset($_POST['optimize'])) {
    echo "<!DOCTYPE html><html><head><title>Optimization Results</title></head><body>";
    echo "<h1>🚀 Asset Optimization Results</h1><pre>";
    
    $cssDir = __DIR__ . '/public/assets/css/';
    $jsDir = __DIR__ . '/public/assets/js/';
    
    // Process CSS files
    if (is_dir($cssDir)) {
        $cssFiles = glob($cssDir . '*.css');
        foreach ($cssFiles as $file) {
            if (strpos($file, '.min.css') !== false) continue;
            
            $content = file_get_contents($file);
            $minified = minifyCSS($content);
            
            $minFile = str_replace('.css', '.min.css', $file);
            file_put_contents($minFile, $minified);
            
            $originalSize = filesize($file);
            $minifiedSize = filesize($minFile);
            $savings = round((($originalSize - $minifiedSize) / $originalSize) * 100, 1);
            
            echo "✅ " . basename($file) . " → " . basename($minFile) . " ({$savings}% smaller)\n";
        }
    } else {
        echo "⚠️ CSS directory not found: $cssDir\n";
    }
    
    // Process JS files
    if (is_dir($jsDir)) {
        $jsFiles = glob($jsDir . '*.js');
        foreach ($jsFiles as $file) {
            if (strpos($file, '.min.js') !== false) continue;
            
            $content = file_get_contents($file);
            $minified = minifyJS($content);
            
            $minFile = str_replace('.js', '.min.js', $file);
            file_put_contents($minFile, $minified);
            
            $originalSize = filesize($file);
            $minifiedSize = filesize($minFile);
            $savings = round((($originalSize - $minifiedSize) / $originalSize) * 100, 1);
            
            echo "✅ " . basename($file) . " → " . basename($minFile) . " ({$savings}% smaller)\n";
        }
    } else {
        echo "⚠️ JS directory not found: $jsDir\n";
    }
    
    echo "\n🎯 Optimization Complete!\n";
    echo "</pre>";
    echo '<p><a href="optimize_simple.php">Run Again</a> | <a href="/">Back to Site</a></p>';
    echo "</body></html>";
} else {
?>
<!DOCTYPE html>
<html>
<head>
    <title>ERGON Asset Optimizer</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        button { padding: 15px 30px; background: #007cba; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>🚀 ERGON Asset Optimizer</h1>
    <p>This tool will minify all CSS and JavaScript files in your assets directory.</p>
    <ul>
        <li>Removes comments and whitespace</li>
        <li>Creates .min.css and .min.js versions</li>
        <li>Reduces file sizes by 30-60%</li>
    </ul>
    <form method="post">
        <button type="submit" name="optimize">Optimize Assets Now</button>
    </form>
</body>
</html>
<?php } ?>
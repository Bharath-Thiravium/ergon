<?php
/**
 * Asset Optimization Script
 * Run this once to minify CSS and JS files
 */

require_once __DIR__ . '/app/helpers/PerformanceBooster.php';

echo "🚀 ERGON Asset Optimization\n";
echo "==========================\n\n";

$cssDir = __DIR__ . '/public/assets/css/';
$jsDir = __DIR__ . '/public/assets/js/';

// Minify CSS files
echo "📄 Minifying CSS files...\n";
$cssFiles = glob($cssDir . '*.css');
foreach ($cssFiles as $file) {
    if (strpos($file, '.min.css') !== false) continue; // Skip already minified
    
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
    if (strpos($file, '.min.js') !== false) continue; // Skip already minified
    
    $content = file_get_contents($file);
    $minified = PerformanceBooster::minifyJS($content);
    
    $minFile = str_replace('.js', '.min.js', $file);
    file_put_contents($minFile, $minified);
    
    $originalSize = filesize($file);
    $minifiedSize = filesize($minFile);
    $savings = round((($originalSize - $minifiedSize) / $originalSize) * 100, 1);
    
    echo "✅ " . basename($file) . " → " . basename($minFile) . " ({$savings}% smaller)\n";
}

// Optimize database
echo "\n🗄️ Optimizing database indexes...\n";
if (PerformanceBooster::optimizeDatabase()) {
    echo "✅ Database indexes optimized\n";
} else {
    echo "⚠️ Database optimization failed (check logs)\n";
}

// Create combined CSS file
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

echo "\n🎯 Optimization Complete!\n";
echo "========================\n";
echo "Next steps:\n";
echo "1. Update your HTML to use .min.css and .min.js files\n";
echo "2. Use ergon-combined.min.css for even better performance\n";
echo "3. Enable the PerformanceBooster in your index.php\n";
?>
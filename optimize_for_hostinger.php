<?php
// Run this script once to optimize your project for Hostinger
require_once __DIR__ . '/app/helpers/PerformanceOptimizer.php';

echo "Optimizing project for Hostinger...\n";

// 1. Compress images
echo "Compressing images...\n";
PerformanceOptimizer::optimizeImages(__DIR__ . '/storage/receipts/');
PerformanceOptimizer::optimizeImages(__DIR__ . '/storage/user_documents/');

// 2. Minify CSS
echo "Minifying CSS...\n";
$cssFile = __DIR__ . '/assets/css/ergon.css';
if (file_exists($cssFile)) {
    $css = file_get_contents($cssFile);
    $minifiedCSS = PerformanceOptimizer::minifyCSS($css);
    file_put_contents($cssFile . '.min.css', $minifiedCSS);
    echo "Created minified CSS: ergon.css.min.css\n";
}

// 3. Clear old cache files
echo "Clearing cache...\n";
$cacheDir = __DIR__ . '/storage/cache/';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '*');
    foreach ($files as $file) {
        if (is_file($file) && time() - filemtime($file) > 86400) { // 24 hours
            unlink($file);
        }
    }
}

// 4. Clean old session files
echo "Cleaning sessions...\n";
$sessionDir = __DIR__ . '/storage/sessions/';
if (is_dir($sessionDir)) {
    $files = glob($sessionDir . 'sess_*');
    foreach ($files as $file) {
        if (is_file($file) && time() - filemtime($file) > 86400) { // 24 hours
            unlink($file);
        }
    }
}

echo "Optimization complete!\n";
echo "Upload the optimized files to Hostinger.\n";
?>
<?php
/**
 * CSS Build Script - Combines and minifies CSS files for production
 * Run this script to generate the production CSS file
 */

// CSS files to combine (in order)
$cssFiles = [
    'assets/css/ergon.css',
    'assets/css/theme-enhanced.css',
    'assets/css/utilities-new.css',
    'assets/css/instant-theme.css',
    'assets/css/global-tooltips.css',
    'assets/css/action-button-clean.css',
    'assets/css/responsive-mobile.css',
    'assets/css/mobile-critical-fixes.css',
    'assets/css/nav-simple-fix.css'
];

$combinedCSS = '';

echo "Building production CSS...\n";

// Combine all CSS files
foreach ($cssFiles as $file) {
    if (file_exists($file)) {
        echo "Adding: $file\n";
        $css = file_get_contents($file);
        
        // Remove comments
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        
        // Remove extra whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove whitespace around specific characters
        $css = str_replace(['; ', ' {', '} ', ', ', ': '], [';', '{', '}', ',', ':'], $css);
        
        $combinedCSS .= $css . "\n";
    } else {
        echo "Warning: File not found: $file\n";
    }
}

// Additional minification
$combinedCSS = trim($combinedCSS);

// Write to production file
$outputFile = 'assets/css/ergon.production.min.css';
$bytesWritten = file_put_contents($outputFile, $combinedCSS);

if ($bytesWritten !== false) {
    $originalSize = 0;
    foreach ($cssFiles as $file) {
        if (file_exists($file)) {
            $originalSize += filesize($file);
        }
    }
    
    $newSize = filesize($outputFile);
    $savings = round((($originalSize - $newSize) / $originalSize) * 100, 1);
    
    echo "\nBuild completed successfully!\n";
    echo "Original size: " . number_format($originalSize) . " bytes\n";
    echo "Minified size: " . number_format($newSize) . " bytes\n";
    echo "Savings: {$savings}%\n";
    echo "Output: $outputFile\n";
} else {
    echo "Error: Failed to write production CSS file\n";
    exit(1);
}
?>
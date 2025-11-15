<?php
// Icon Standardization Script
$viewsDir = __DIR__ . '/views';
$standardSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"></svg>';

$iconMappings = [
    'ab-btn--view' => $standardSvg,
    'ab-btn--edit' => $standardSvg,
    'ab-btn--delete' => $standardSvg,
    'ab-btn--reset' => $standardSvg,
    'ab-btn--approve' => $standardSvg,
    'ab-btn--reject' => $standardSvg,
    'ab-btn--progress' => $standardSvg,
    'ab-btn--print' => $standardSvg
];

function standardizeIconsInFile($filePath) {
    global $standardSvg;
    
    $content = file_get_contents($filePath);
    if (!$content) return false;
    
    // Replace all complex SVG icons with standard empty SVG
    $patterns = [
        // View icons (eye or document)
        '/(<button[^>]*ab-btn--view[^>]*>)\s*<svg[^>]*>.*?<\/svg>\s*(<\/button>)/s',
        '/(<a[^>]*ab-btn--view[^>]*>)\s*<svg[^>]*>.*?<\/svg>\s*(<\/a>)/s',
        
        // Edit icons
        '/(<button[^>]*ab-btn--edit[^>]*>)\s*<svg[^>]*>.*?<\/svg>\s*(<\/button>)/s',
        '/(<a[^>]*ab-btn--edit[^>]*>)\s*<svg[^>]*>.*?<\/svg>\s*(<\/a>)/s',
        
        // Delete icons
        '/(<button[^>]*ab-btn--delete[^>]*>)\s*<svg[^>]*>.*?<\/svg>\s*(<\/button>)/s',
        
        // Reset icons
        '/(<button[^>]*ab-btn--reset[^>]*>)\s*<svg[^>]*>.*?<\/svg>\s*(<\/button>)/s',
        
        // Approve icons
        '/(<button[^>]*ab-btn--approve[^>]*>)\s*<svg[^>]*>.*?<\/svg>\s*(<\/button>)/s',
        
        // Reject icons
        '/(<button[^>]*ab-btn--reject[^>]*>)\s*<svg[^>]*>.*?<\/svg>\s*(<\/button>)/s',
        
        // Progress icons
        '/(<button[^>]*ab-btn--progress[^>]*>)\s*<svg[^>]*>.*?<\/svg>\s*(<\/button>)/s',
        
        // Print/History icons
        '/(<button[^>]*ab-btn--print[^>]*>)\s*<svg[^>]*>.*?<\/svg>\s*(<\/button>)/s'
    ];
    
    foreach ($patterns as $pattern) {
        $content = preg_replace($pattern, '$1' . $standardSvg . '$2', $content);
    }
    
    return file_put_contents($filePath, $content);
}

// Find all PHP files in views directory
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
$phpFiles = [];

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $phpFiles[] = $file->getPathname();
    }
}

echo "Standardizing icons in " . count($phpFiles) . " files...\n";

foreach ($phpFiles as $file) {
    if (standardizeIconsInFile($file)) {
        echo "✓ " . basename($file) . "\n";
    } else {
        echo "✗ Failed: " . basename($file) . "\n";
    }
}

echo "\nIcon standardization complete!\n";
echo "All action buttons now use CSS-defined icons.\n";
?>
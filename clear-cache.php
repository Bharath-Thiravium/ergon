<?php
// Simple cache clearing script
header('Content-Type: text/plain');

// Touch CSS file to update modification time
$cssFile = __DIR__ . '/public/assets/css/ergon.css';
if (file_exists($cssFile)) {
    touch($cssFile);
    echo "CSS cache cleared - " . date('Y-m-d H:i:s') . "\n";
    echo "New version: " . filemtime($cssFile) . "\n";
} else {
    echo "CSS file not found\n";
}

// Clear any PHP opcache if available
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "PHP opcache cleared\n";
}

echo "Cache clearing complete\n";
?>
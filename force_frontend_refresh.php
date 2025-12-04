<?php
// Force clear all caches and refresh frontend data
require_once __DIR__ . '/app/config/database.php';

try {
    // Clear PHP opcache if enabled
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "✅ Cleared PHP OPCache\n";
    }
    
    // Clear any session files
    $sessionPath = __DIR__ . '/storage/sessions/';
    if (is_dir($sessionPath)) {
        $files = glob($sessionPath . 'sess_*');
        foreach ($files as $file) {
            unlink($file);
        }
        echo "✅ Cleared " . count($files) . " session files\n";
    }
    
    // Force database connection refresh
    $db = Database::connect();
    $db->exec("FLUSH TABLES");
    echo "✅ Flushed database tables\n";
    
    // Generate cache-busting timestamp
    $timestamp = time();
    echo "✅ Cache-busting timestamp: $timestamp\n";
    
    echo "\n🔄 Frontend Refresh Instructions:\n";
    echo "1. Hard refresh browser (Ctrl+F5)\n";
    echo "2. Clear browser cache completely\n";
    echo "3. Logout and login again\n";
    echo "4. Add ?v=$timestamp to URL for cache busting\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
<?php
echo "🔄 UPDATING ALL DATABASE REFERENCES\n\n";

// Files that need database configuration updates
$filesToUpdate = [
    'deep_database_sync_fix.php',
    'production_sync_tool.php', 
    'manual_user_setup.php',
    'strong_database_fix.php',
    'verify_database_connection.php',
    'src/services/PostgreSQLSyncService.php',
    'src/cli/sync_shipping_addresses.php',
    'src/api/sync-shipping.php'
];

$updatesCount = 0;

foreach ($filesToUpdate as $file) {
    $fullPath = __DIR__ . '/' . $file;
    
    if (!file_exists($fullPath)) {
        echo "⚠️ File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    $originalContent = $content;
    
    // Update database names
    $content = str_replace('ergon-site_db', 'ergon_db', $content);
    $content = str_replace('u494785662_ergon_site', 'u494785662_ergon', $content);
    
    // Update hardcoded connections to use Database class
    $content = preg_replace(
        '/new PDO\([\'"]mysql:host=localhost;dbname=ergon_db[\'"], [\'"]root[\'"], [\'"][\'"]/',
        'Database::connect()',
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($fullPath, $content);
        echo "✅ Updated: $file\n";
        $updatesCount++;
    } else {
        echo "— No changes needed: $file\n";
    }
}

echo "\n🎯 SUMMARY:\n";
echo "Updated $updatesCount files with new database configuration\n";
echo "Local: ergon_db | Production: u494785662_ergon\n";
echo "All files now use environment-aware Database class\n";
?>
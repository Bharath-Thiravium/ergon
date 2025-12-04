<?php
echo "🔄 COMPREHENSIVE DATABASE ENVIRONMENT UPDATE\n\n";

// Files that need database configuration updates
$filesToUpdate = [
    // Legacy files with hardcoded connections
    'archive/legacy/hostinger-optimizations.php',
    'fix_database.php',
    'production_sync_tool.php',
    'strong_database_fix.php',
    
    // Service files
    'app/services/DataSyncService.php',
    'app/services/PrefixFallback.php',
    
    // API files
    'src/api/sync-shipping.php',
    'src/api/sync.php',
    'src/cli/sync_shipping_addresses.php',
    
    // Debug/test files
    'debug_data_flow.php',
    'validate_environment.php',
    
    // Include files
    'inc/functions.php'
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
    
    // Update hardcoded MySQL connections to use Database class where appropriate
    if (strpos($file, 'src/') === 0 || strpos($file, 'app/') === 0) {
        // For core application files, replace with Database::connect()
        $content = preg_replace(
            '/new PDO\s*\(\s*[\'"]mysql:host=localhost;dbname=ergon_db[\'"][^)]*\)/',
            'Database::connect()',
            $content
        );
        
        // Add Database class include if not present
        if (strpos($content, 'Database::connect()') !== false && strpos($content, 'require_once') === false) {
            $content = "<?php\nrequire_once __DIR__ . '/../../app/config/database.php';\n" . substr($content, 5);
        }
    }
    
    // Update production database references
    $content = str_replace(
        'mysql:host=localhost;dbname=u494785662_ergon',
        'mysql:host=localhost;dbname=u494785662_ergon',
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

// Create environment test script
$testScript = '<?php
require_once __DIR__ . \'/app/config/database.php\';

echo "🧪 ENVIRONMENT TEST\\n\\n";

try {
    $db = Database::connect();
    $info = $db->query("SELECT DATABASE() as db_name, USER() as db_user")->fetch();
    
    echo "✅ Connection successful\\n";
    echo "Database: " . $info[\'db_name\'] . "\\n";
    echo "User: " . $info[\'db_user\'] . "\\n";
    echo "Environment: " . (Environment::isDevelopment() ? \'Development\' : \'Production\') . "\\n";
    
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\\n";
}
?>';

file_put_contents(__DIR__ . '/test_environment.php', $testScript);

echo "\n🎯 COMPREHENSIVE UPDATE SUMMARY:\n";
echo "Updated $updatesCount files with environment-aware database configuration\n";
echo "✅ Created test_environment.php for verification\n";
echo "🔧 Local Development: ergon_db (localhost)\n";
echo "🌐 Production: u494785662_ergon (Hostinger)\n";
echo "🚀 Auto-detection based on HTTP_HOST\n";
?>
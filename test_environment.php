<?php
require_once __DIR__ . '/app/config/database.php';

echo "🧪 ENVIRONMENT TEST\n\n";

try {
    $db = Database::connect();
    $info = $db->query("SELECT DATABASE() as db_name, USER() as db_user")->fetch();
    
    echo "✅ Connection successful\n";
    echo "Database: " . $info['db_name'] . "\n";
    echo "User: " . $info['db_user'] . "\n";
    echo "Environment: " . (Environment::isDevelopment() ? 'Development' : 'Production') . "\n";
    
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
?>
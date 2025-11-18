<?php
require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::connect();
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/test_data_harini.sql');
    
    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $db->beginTransaction();
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $db->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (Exception $e) {
                echo "⚠ Warning: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    $db->commit();
    echo "\n🎉 Test data insertion completed successfully!\n";
    echo "User harini@athenas.co.in now has comprehensive test data.\n";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
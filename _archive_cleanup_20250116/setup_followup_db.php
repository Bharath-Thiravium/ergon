<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/create_followup_tables.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\n✅ Database setup completed successfully!\n";
    echo "Tables created: contacts, followups, followup_history\n";
    echo "Sample data inserted.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

<style>
body { font-family: monospace; white-space: pre-line; padding: 20px; }
</style>
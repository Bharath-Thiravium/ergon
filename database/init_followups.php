<?php
/**
 * Database Initialization Script for Followups
 * Run this script to ensure proper table structure
 */

require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::connect();
    echo "Connected to database successfully.\n";
    
    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/followups.sql');
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $db->exec($statement);
                echo "Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (Exception $e) {
                echo "Error executing statement: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Verify tables exist
    $tables = $db->query("SHOW TABLES LIKE 'followup%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTables created:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
        
        // Show table structure
        $columns = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "  * {$column['Field']} ({$column['Type']})\n";
        }
        echo "\n";
    }
    
    echo "Database initialization completed successfully!\n";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
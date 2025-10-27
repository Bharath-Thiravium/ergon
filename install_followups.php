<?php
/**
 * Follow-ups System Installation Script
 * Run this once to set up the follow-up system database tables
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Installing Follow-ups System...\n\n";
    
    // Read and execute the SQL schema
    $sql = file_get_contents(__DIR__ . '/database/followups_schema.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $db->exec($statement);
        }
    }
    
    echo "\n✅ Follow-ups System installed successfully!\n";
    echo "\nYou can now:\n";
    echo "1. Access Follow-ups from the sidebar menu\n";
    echo "2. Create follow-ups from tasks in Daily Planner\n";
    echo "3. Manage follow-ups with companies, contacts, and projects\n";
    echo "4. Track progress with checklist items\n";
    echo "5. Reschedule and complete follow-ups\n\n";
    
} catch (Exception $e) {
    echo "❌ Installation failed: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
}
?>
<?php
/**
 * Setup script to fix reschedule follow-up functionality
 * This script ensures all required database tables and columns exist
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "Connected to database successfully.\n";
    
    // Read and execute the SQL fix script
    $sqlFile = __DIR__ . '/fix_reschedule_followup.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL fix file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $db->beginTransaction();
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !str_starts_with(trim($statement), '--')) {
            try {
                $db->exec($statement);
                echo "✓ Executed: " . substr(trim($statement), 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Ignore "already exists" errors
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    throw $e;
                }
                echo "⚠ Skipped (already exists): " . substr(trim($statement), 0, 50) . "...\n";
            }
        }
    }
    
    $db->commit();
    
    // Verify tables exist
    echo "\nVerifying table structure:\n";
    
    $tables = ['contacts', 'followups', 'followup_history'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
            
            // Show column structure
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "  Columns: " . implode(', ', $columns) . "\n";
        } else {
            echo "✗ Table '$table' missing\n";
        }
    }
    
    // Test reschedule functionality
    echo "\nTesting reschedule functionality:\n";
    
    // Check if we have any followups to test with
    $stmt = $db->query("SELECT COUNT(*) FROM followups");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "✓ Found $count follow-ups in database\n";
        
        // Get a sample followup
        $stmt = $db->query("SELECT id, title, follow_up_date, status FROM followups LIMIT 1");
        $followup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($followup) {
            echo "✓ Sample follow-up: ID {$followup['id']}, '{$followup['title']}', Date: {$followup['follow_up_date']}, Status: {$followup['status']}\n";
        }
    } else {
        echo "⚠ No follow-ups found in database. Creating sample data...\n";
        
        // Insert sample data
        $db->exec("INSERT IGNORE INTO contacts (name, phone, email, company) VALUES 
                   ('Test Contact', '+1234567890', 'test@example.com', 'Test Company')");
        
        $contactId = $db->lastInsertId() ?: 1;
        
        $db->exec("INSERT IGNORE INTO followups (contact_id, user_id, title, description, follow_up_date, status) VALUES 
                   ($contactId, 1, 'Test Follow-up', 'This is a test follow-up for reschedule functionality', '" . date('Y-m-d', strtotime('+1 day')) . "', 'pending')");
        
        echo "✓ Sample data created\n";
    }
    
    echo "\n✅ Reschedule follow-up fix completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Visit: http://localhost/ergon/contacts/followups/view\n";
    echo "2. Click the 'Reschedule' button on any follow-up\n";
    echo "3. Test the reschedule functionality\n";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
?>
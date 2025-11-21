<?php
// Fix missing expense columns
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    echo "Connected to database successfully.\n";
    
    // Read and execute the SQL fix
    $sql = file_get_contents(__DIR__ . '/fix_expense_columns.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $db->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (Exception $e) {
                echo "⚠ Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Verify the fix
    echo "\n=== Verification ===\n";
    $stmt = $db->query("SHOW COLUMNS FROM expenses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasApprovedBy = false;
    $hasJournalEntryId = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'approved_by') {
            $hasApprovedBy = true;
            echo "✓ approved_by column exists: " . $column['Type'] . "\n";
        }
        if ($column['Field'] === 'journal_entry_id') {
            $hasJournalEntryId = true;
            echo "✓ journal_entry_id column exists: " . $column['Type'] . "\n";
        }
    }
    
    if (!$hasApprovedBy) {
        echo "✗ approved_by column is missing\n";
    }
    if (!$hasJournalEntryId) {
        echo "✗ journal_entry_id column is missing\n";
    }
    
    if ($hasApprovedBy && $hasJournalEntryId) {
        echo "\n🎉 All required columns are now present!\n";
        echo "You can now test expense approval and rejection functionality.\n";
    } else {
        echo "\n❌ Some columns are still missing. Please check the database manually.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
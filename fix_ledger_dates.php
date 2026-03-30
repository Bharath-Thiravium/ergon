<?php
// Fix ledger dates to use actual expense/advance dates instead of creation dates
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Starting ledger date fix...\n";
    
    // Fix expense ledger entries
    $stmt = $db->prepare("
        UPDATE user_ledgers ul 
        JOIN expenses e ON ul.reference_type = 'expense' AND ul.reference_id = e.id 
        SET ul.created_at = e.expense_date
        WHERE ul.reference_type = 'expense'
    ");
    $result1 = $stmt->execute();
    $expenseCount = $stmt->rowCount();
    echo "Updated $expenseCount expense ledger entries\n";
    
    // Fix advance ledger entries
    $stmt = $db->prepare("
        UPDATE user_ledgers ul 
        JOIN advances a ON ul.reference_type = 'advance' AND ul.reference_id = a.id 
        SET ul.created_at = a.requested_date
        WHERE ul.reference_type = 'advance' AND a.requested_date IS NOT NULL
    ");
    $result2 = $stmt->execute();
    $advanceCount = $stmt->rowCount();
    echo "Updated $advanceCount advance ledger entries\n";
    
    echo "Ledger date fix completed successfully!\n";
    echo "Total entries updated: " . ($expenseCount + $advanceCount) . "\n";
    
} catch (Exception $e) {
    echo "Error fixing ledger dates: " . $e->getMessage() . "\n";
}
?>
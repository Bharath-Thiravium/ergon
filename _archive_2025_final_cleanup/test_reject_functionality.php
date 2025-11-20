<?php
/**
 * Test Reject Functionality
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Testing reject functionality...\n";
    
    // Check table structure
    $columns = $db->query("DESCRIBE advances")->fetchAll(PDO::FETCH_ASSOC);
    echo "Table columns:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check for pending advances
    $stmt = $db->query("SELECT id, user_id, amount, reason, status FROM advances WHERE status = 'pending' LIMIT 5");
    $pendingAdvances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nPending advances:\n";
    if (empty($pendingAdvances)) {
        echo "No pending advances found.\n";
    } else {
        foreach ($pendingAdvances as $advance) {
            echo "ID: {$advance['id']}, User: {$advance['user_id']}, Amount: {$advance['amount']}, Status: {$advance['status']}\n";
        }
    }
    
    echo "\nReject functionality test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
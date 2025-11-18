<?php
/**
 * Test script to verify expense approval accounting integration
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/AccountingHelper.php';

try {
    echo "Testing expense approval accounting integration...\n\n";
    
    $db = Database::connect();
    
    // Check if tables exist
    echo "1. Checking database structure...\n";
    $tables = ['accounts', 'journal_entries', 'journal_entry_lines', 'expenses'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ Table '$table' exists\n";
        } else {
            echo "   ❌ Table '$table' missing\n";
        }
    }
    
    // Check if default accounts exist
    echo "\n2. Checking chart of accounts...\n";
    $stmt = $db->query("SELECT account_name, account_code FROM accounts ORDER BY account_code");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($accounts) > 0) {
        foreach ($accounts as $account) {
            echo "   ✓ {$account['account_code']}: {$account['account_name']}\n";
        }
    } else {
        echo "   ❌ No accounts found\n";
    }
    
    // Check for pending expenses
    echo "\n3. Checking for pending expenses...\n";
    $stmt = $db->query("SELECT id, description, amount, status FROM expenses WHERE status = 'pending' LIMIT 3");
    $pendingExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($pendingExpenses) > 0) {
        foreach ($pendingExpenses as $expense) {
            echo "   • Expense #{$expense['id']}: {$expense['description']} - ₹{$expense['amount']} ({$expense['status']})\n";
        }
    } else {
        echo "   ℹ No pending expenses found\n";
    }
    
    // Test accounting helper
    echo "\n4. Testing AccountingHelper...\n";
    try {
        $summary = AccountingHelper::getExpenseAccountingSummary();
        echo "   ✓ AccountingHelper working correctly\n";
        echo "   ✓ Found " . count($summary) . " accounts in summary\n";
    } catch (Exception $e) {
        echo "   ❌ AccountingHelper error: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ Test completed!\n";
    echo "\nTo test the full workflow:\n";
    echo "1. Go to /ergon/expenses\n";
    echo "2. Submit a new expense claim\n";
    echo "3. Approve it as admin/owner\n";
    echo "4. Check that it's recorded in the accounts\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}
?>
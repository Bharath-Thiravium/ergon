<?php
/**
 * FORENSIC DIAGNOSTIC SCRIPT
 * Inspect exactly what's in the ledger for Expense #73
 */

require_once __DIR__ . '/app/config/database.php';

$db = Database::connect();

echo "<h2>🔍 FORENSIC DIAGNOSIS - Expense #73 Ledger Entries</h2>";
echo "<pre>";

// Find Expense #73
echo "\n=== EXPENSE RECORD ===\n";
$stmt = $db->prepare("SELECT id, user_id, amount, approved_amount, status, ledger_synced FROM expenses WHERE id = 73 LIMIT 1");
$stmt->execute();
$expense = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Expense #73 Details:\n";
print_r($expense);

// Find ALL ledger entries for this expense
echo "\n=== ALL LEDGER ENTRIES FOR EXPENSE #73 ===\n";
$stmt = $db->prepare("
    SELECT id, user_id, reference_type, reference_id, entry_type, direction, amount, balance_after, created_at
    FROM user_ledgers
    WHERE reference_type = 'expense' AND reference_id = 73
    ORDER BY created_at ASC
");
$stmt->execute();
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total Ledger Entries Found: " . count($entries) . "\n";
foreach ($entries as $i => $entry) {
    echo "\n--- Ledger Entry " . ($i + 1) . " ---\n";
    print_r($entry);
}

// Count duplicates
echo "\n=== DUPLICATE COUNT ===\n";
$stmt = $db->prepare("
    SELECT reference_type, reference_id, entry_type, COUNT(*) as cnt
    FROM user_ledgers
    WHERE reference_type = 'expense' AND reference_id = 73
    GROUP BY entry_type
");
$stmt->execute();
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($duplicates as $dup) {
    echo "Entry Type: {$dup['entry_type']} = {$dup['cnt']} rows\n";
}

// Find ALL duplicate transactions (same expense_id appearing multiple times)
echo "\n=== ALL TRANSACTIONS WITH DUPLICATES ===\n";
$stmt = $db->query("
    SELECT reference_type, reference_id, entry_type, COUNT(*) as cnt
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type, reference_id, entry_type
    HAVING cnt > 1
");
$allDups = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total Duplicate Groups: " . count($allDups) . "\n";
foreach ($allDups as $dup) {
    echo "- {$dup['reference_type']} #{$dup['reference_id']} ({$dup['entry_type']}): {$dup['cnt']} rows\n";
}

echo "\n=== END OF DIAGNOSIS ===\n";
echo "</pre>";
?>

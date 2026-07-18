<?php
/**
 * DIAGNOSTIC: Owner Ledger Issue
 * Shows exactly what entries are in the database
 */

require_once __DIR__ . '/app/config/database.php';

$db = Database::connect();

echo "=== OWNER LEDGER DIAGNOSTIC REPORT ===\n\n";

// 1. Count entries by type
$all = $db->query("SELECT COUNT(*) FROM user_ledgers WHERE reference_type IN ('expense', 'advance')")->fetchColumn();
echo "[1] Total ledger entries: $all\n\n";

// 2. Show breakdown
$stmt = $db->query("
    SELECT reference_type, COUNT(*) as cnt
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type
");
echo "[2] Breakdown by type:\n";
foreach($stmt as $row) {
    echo "    {$row['reference_type']}: {$row['cnt']}\n";
}

// 3. Show all entries with details
echo "\n[3] All ledger entries (first 50):\n";
echo str_repeat("-", 100) . "\n";
printf("%-5s %-10s %-8s %-15s %-12s %-10s %-12s %-20s\n", 
    "ID", "REF_TYPE", "REF_ID", "ENTRY_TYPE", "DIRECTION", "AMOUNT", "BALANCE", "CREATED_AT");
echo str_repeat("-", 100) . "\n";

$stmt = $db->query("
    SELECT id, reference_type, reference_id, entry_type, direction, amount, balance_after, created_at
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    ORDER BY created_at DESC
    LIMIT 50
");

foreach($stmt as $row) {
    printf("%-5d %-10s %-8d %-15s %-12s %-10.2f %-12.2f %-20s\n",
        $row['id'],
        $row['reference_type'],
        $row['reference_id'],
        $row['entry_type'],
        $row['direction'],
        $row['amount'],
        $row['balance_after'],
        $row['created_at']
    );
}

// 4. Check for duplicates
echo "\n\n[4] Checking for duplicate groups (same reference_id + entry_type):\n";
$dups = $db->query("
    SELECT reference_type, reference_id, entry_type, COUNT(*) as cnt
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type, reference_id, entry_type
    HAVING cnt > 1
")->fetchAll(PDO::FETCH_ASSOC);

if(empty($dups)) {
    echo "✓ No duplicates found - all clean!\n";
} else {
    echo "✗ Found " . count($dups) . " duplicate groups:\n";
    foreach($dups as $d) {
        echo "    {$d['reference_type']} #{$d['reference_id']} ({$d['entry_type']}): {$d['cnt']} entries\n";
    }
}

// 5. Check offset entries
echo "\n[5] Checking for offset entries (reimbursement/settlement):\n";
$offsets = $db->query("
    SELECT id, reference_type, reference_id, entry_type, amount, direction
    FROM user_ledgers
    WHERE entry_type IN ('reimbursement', 'settlement', 'expense_reimbursement', 'advance_settlement')
    ORDER BY created_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

if(empty($offsets)) {
    echo "✓ No offset entries found\n";
} else {
    echo "✗ Found " . count($offsets) . " offset entries:\n";
    foreach($offsets as $o) {
        echo "    ID {$o['id']}: {$o['reference_type']}#{$o['reference_id']} ({$o['entry_type']}) - {$o['direction']} {$o['amount']}\n";
    }
}

// 6. Show what owner ledger query returns
echo "\n[6] What owner ledger query returns:\n";
$stmt = $db->prepare("
    SELECT ul.id, ul.reference_id, ul.reference_type, ul.entry_type, 
           ul.direction, ul.amount, ul.balance_after, ul.created_at,
           u.name as employee_name
    FROM user_ledgers ul
    JOIN users u ON ul.user_id = u.id
    WHERE ul.reference_type IN ('expense', 'advance')
    ORDER BY ul.created_at DESC
    LIMIT 30
");
$stmt->execute();

printf("%-5s %-10s %-8s %-20s %-12s %-15s %-20s\n", 
    "ID", "REF_TYPE", "REF_ID", "EMPLOYEE", "AMOUNT", "BALANCE", "CREATED_AT");
echo str_repeat("-", 90) . "\n";

foreach($stmt as $row) {
    printf("%-5d %-10s %-8d %-20s %-12.2f %-15.2f %-20s\n",
        $row['id'],
        $row['reference_type'],
        $row['reference_id'],
        substr($row['employee_name'], 0, 19),
        $row['amount'],
        $row['balance_after'],
        $row['created_at']
    );
}

echo "\n=== END DIAGNOSTIC ===\n";
?>

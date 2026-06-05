# Owner Ledger Duplicate Issue - Code Fixes

## Overview
This document contains the minimal code changes needed to fix the duplicate ledger entry issue.

## Fix #1: ExpenseController.php - Remove Dual Entry

### Problem
```php
// approve() creates entry
LedgerHelper::recordEntry(..., 'expense_payment', ...);

// markPaid() creates another
if (empty($expense['ledger_synced'])) {
    LedgerHelper::recordEntry(...);  // DUPLICATE!
}
```

### Solution
**Action in `approve()`: Keep as-is** (creates ledger entry at approval)

**Action in `markPaid()`: Remove safety-net entry**

Replace this in markPaid() around line 410:
```php
// OLD CODE (lines 410-417):
if (empty($expense['ledger_synced'])) {
    $ledgerOk = LedgerHelper::recordEntry($expense['user_id'], 'expense_payment', 'expense', $id, $ledgerAmount, 'credit', $expense['expense_date'], $db);
    if (!$ledgerOk) {
        throw new Exception("Ledger safety-net entry failed for expense id=$id");
    }
    error_log("Expense markPaid: safety-net ledger created for id=$id");
}
```

With:
```php
// NEW CODE: Just verify it was synced
if (empty($expense['ledger_synced'])) {
    error_log("WARNING: Expense id=$id marked paid but ledger_synced flag not set (should have been set at approval)");
}
```

---

## Fix #2: AdvanceController.php - Remove Dual Entry + Auto-Expense

### Problem #1: Dual ledger entries
```php
// approve() creates entry
LedgerHelper::recordEntry(..., 'advance_payment', ...);

// markPaid() creates another
if (empty($advance['ledger_synced'])) {
    LedgerHelper::recordEntry(...);  // DUPLICATE!
}
```

### Problem #2: Auto-generates expense entry
```php
// markPaid() creates auto-expense
$expStmt->execute([$paidByOwnerId, $ledgerAmount, $expDesc, ...]);
```
This creates another transaction entirely, which then generates its own ledger entry.

### Solution

**Action in `approve()`: Keep as-is** (creates ledger entry at approval)

**Action in `markPaid()`: Remove safety-net + auto-expense**

Replace markPaid() around line 305 to line 330:

OLD:
```php
if (empty($advance['ledger_synced'])) {
    require_once __DIR__ . '/../helpers/LedgerHelper.php';
    $ledgerOk = LedgerHelper::recordEntry($advance['user_id'], 'advance_payment', 'advance', $id, $ledgerAmount, 'credit', $advance['requested_date'], $db);
    if (!$ledgerOk) {
        throw new Exception("Ledger safety-net entry failed for advance id=$id");
    }
    error_log("Advance markPaid: safety-net ledger created for id=$id");
}
$db->commit();
error_log("Advance paid: id=$id user_id={$advance['user_id']} amount=$ledgerAmount");

// Auto-create expense entry for the paying owner
try {
    // Get employee name for description
    $empStmt = $db->prepare("SELECT name FROM users WHERE id = ?");
    $empStmt->execute([$advance['user_id']]);
    $empName = $empStmt->fetchColumn() ?: 'Employee';

    $advType = $advance['type'] ?? 'General Advance';
    $expDesc = "Advance paid to {$empName} ({$advType})";
    if ($paymentRemarks) $expDesc .= ' - ' . $paymentRemarks;

    $expStmt = $db->prepare("INSERT INTO expenses (user_id, category, amount, description, expense_date, status, paid_by, paid_at, paid_to_user_id, source_advance_id, payment_proof, payment_remarks, created_at) VALUES (?, 'work_advance', ?, ?, NOW(), 'paid', ?, NOW(), ?, ?, ?, ?, NOW())");
    $expStmt->execute([$paidByOwnerId, $ledgerAmount, $expDesc, $paidByOwnerId, $advance['user_id'], $id, $proof, $paymentRemarks]);
} catch (Exception $expEx) {
    error_log('Auto-expense creation for advance payment failed: ' . $expEx->getMessage());
}
```

NEW:
```php
if (empty($advance['ledger_synced'])) {
    error_log("WARNING: Advance id=$id marked paid but ledger_synced flag not set (should have been set at approval)");
}
$db->commit();
error_log("Advance paid: id=$id user_id={$advance['user_id']} amount=$ledgerAmount");

// NOTE: Removed auto-expense generation - advances should be tracked in their own ledger entries
```

---

## Fix #3: LedgerHelper.php - Ensure Single Entry Per Transaction

### Current Design
Currently LedgerHelper has duplicate guards via `ledger_synced` flag. This is good but we need to strengthen it.

### Enhancement: Add method to verify entry uniqueness

Add this method to LedgerHelper class (around line 150):

```php
/**
 * Verify no duplicates exist for a reference+entry_type combination
 * Useful for audit and cleanup.
 */
public static function getDuplicateCount($referenceType, $referenceId, $entryType, $db = null): int {
    if (!$db) {
        require_once __DIR__ . '/../config/database.php';
        $db = Database::connect();
    }
    
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM user_ledgers
        WHERE reference_type = ? AND reference_id = ? AND entry_type = ?
    ");
    $stmt->execute([$referenceType, $referenceId, $entryType]);
    return (int)$stmt->fetchColumn();
}
```

---

## Fix #4: OwnerController.php - Fix Ledger Query

### Current Problem
```php
SELECT e.id as reference_id, 'expense' as reference_type, ...
FROM expenses e
WHERE e.status = 'paid'
```

This pulls from expenses table directly, not from user_ledgers, causing:
- Double-counting auto-generated expenses
- Showing raw transaction data instead of ledger entries

### Solution

Replace `fetchOwnerLedgerEntries()` method (lines 340-380) with:

OLD:
```php
private function fetchOwnerLedgerEntries(PDO $db, ?string $fromDate, ?string $toDate, ?string $transactionType, ?int $projectId): array {
    $expenseDateClause  = '';
    $advanceDateClause  = '';
    // ... complex multi-table joins ...
    
    $parts  = [];
    if ($transactionType !== 'advance') {
        $parts[]  = "
            SELECT e.id as reference_id, 'expense' as reference_type, 'debit' as direction,
                   COALESCE(e.approved_amount, e.amount) as amount,
                   e.description, e.category, e.status,
                   COALESCE(e.expense_date, e.created_at) as created_at,
                   u.name as employee_name,
                   COALESCE(p.name, '') as project_name
            FROM expenses e
            JOIN users u ON e.user_id = u.id
            LEFT JOIN projects p ON e.project_id = p.id
            WHERE e.status = 'paid'
              AND (e.source_advance_id IS NULL OR e.source_advance_id = 0)
              {$expenseDateClause}
              {$expenseProjectClause}";
        // ...
    }
    // More complexity...
}
```

NEW:
```php
private function fetchOwnerLedgerEntries(PDO $db, ?string $fromDate, ?string $toDate, ?string $transactionType, ?int $projectId): array {
    $whereClauses = [];
    $params       = [];
    
    // Filter by type
    if ($transactionType) {
        $whereClauses[] = "ul.reference_type = ?";
        $params[] = $transactionType;
    } else {
        $whereClauses[] = "ul.reference_type IN ('expense', 'advance')";
    }
    
    // Filter by date range
    if ($fromDate) {
        $whereClauses[] = "ul.created_at >= ?";
        $params[] = $fromDate . ' 00:00:00';
    }
    if ($toDate) {
        $whereClauses[] = "ul.created_at <= ?";
        $params[] = $toDate . ' 23:59:59';
    }
    
    // Filter by project (from reference table)
    if ($projectId) {
        $whereClauses[] = "(CASE 
            WHEN ul.reference_type = 'expense' THEN (
                SELECT e.project_id FROM expenses e WHERE e.id = ul.reference_id
            ) = ?
            WHEN ul.reference_type = 'advance' THEN (
                SELECT a.project_id FROM advances a WHERE a.id = ul.reference_id
            ) = ?
            ELSE FALSE
        END)";
        $params[] = $projectId;
        $params[] = $projectId;
    }
    
    $whereClause = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";
    
    $sql = "
        SELECT ul.id, ul.reference_id, ul.reference_type, ul.entry_type, 
               ul.direction, ul.amount, ul.balance_after, ul.created_at,
               u.name as employee_name,
               CASE 
                   WHEN ul.reference_type = 'expense' THEN (
                       SELECT COALESCE(p.name, '') FROM expenses e 
                       LEFT JOIN projects p ON e.project_id = p.id 
                       WHERE e.id = ul.reference_id
                   )
                   WHEN ul.reference_type = 'advance' THEN (
                       SELECT COALESCE(p.name, '') FROM advances a 
                       LEFT JOIN projects p ON a.project_id = p.id 
                       WHERE a.id = ul.reference_id
                   )
                   ELSE ''
               END as project_name,
               CASE 
                   WHEN ul.reference_type = 'expense' THEN (
                       SELECT COALESCE(description, category) FROM expenses WHERE id = ul.reference_id
                   )
                   WHEN ul.reference_type = 'advance' THEN (
                       SELECT COALESCE(reason, CONCAT('Advance - ', type)) FROM advances WHERE id = ul.reference_id
                   )
                   ELSE ul.entry_type
               END as description,
               CASE 
                   WHEN ul.reference_type = 'expense' THEN (
                       SELECT category FROM expenses WHERE id = ul.reference_id
                   )
                   WHEN ul.reference_type = 'advance' THEN (
                       SELECT type FROM advances WHERE id = ul.reference_id
                   )
                   ELSE ''
               END as category
        FROM user_ledgers ul
        JOIN users u ON ul.user_id = u.id
        $whereClause
        ORDER BY ul.created_at ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

---

## Fix #5: Database Cleanup Script

Create new file: `e:\ergon\scripts\cleanup_duplicate_ledger_entries.php`

```php
<?php
/**
 * CLEANUP SCRIPT: Remove duplicate ledger entries
 * Run ONCE after deploying code fixes
 * Creates audit trail of deletions
 */

require_once __DIR__ . '/../app/config/database.php';

$db = Database::connect();

echo "Starting duplicate ledger entry cleanup...\n";

// Step 1: Create cleanup audit table
$db->exec("
    CREATE TABLE IF NOT EXISTS ledger_cleanup_audit (
        id INT AUTO_INCREMENT PRIMARY KEY,
        deleted_entry_id INT,
        reference_type VARCHAR(50),
        reference_id INT,
        entry_type VARCHAR(50),
        amount DECIMAL(12,2),
        reason VARCHAR(255),
        deleted_by VARCHAR(100),
        deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Step 2: Find duplicate offsets to delete
// Delete entries where entry_type contains 'reimburs' or 'settlement' 
// AND the original entry exists
$findDuplicates = "
    SELECT id, reference_type, reference_id, entry_type, amount
    FROM user_ledgers
    WHERE entry_type IN ('reimbursement', 'settlement', 'expense_reimbursement', 'advance_settlement')
      AND reference_type IN ('expense', 'advance')
      AND reference_id IN (
        SELECT reference_id 
        FROM user_ledgers ul1
        WHERE ul1.reference_type IN ('expense', 'advance')
          AND ul1.entry_type IN ('expense_payment', 'advance_payment')
      )
";

$stmt = $db->query($findDuplicates);
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($duplicates) . " duplicate offset entries to delete.\n";

// Step 3: Backup and delete
$deleteCount = 0;
foreach ($duplicates as $dup) {
    try {
        // Log to audit
        $auditStmt = $db->prepare("
            INSERT INTO ledger_cleanup_audit 
            (deleted_entry_id, reference_type, reference_id, entry_type, amount, reason, deleted_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $auditStmt->execute([
            $dup['id'],
            $dup['reference_type'],
            $dup['reference_id'],
            $dup['entry_type'],
            $dup['amount'],
            'Duplicate offset entry removed during consolidation fix',
            'SYSTEM_CLEANUP'
        ]);
        
        // Delete the duplicate
        $delStmt = $db->prepare("DELETE FROM user_ledgers WHERE id = ?");
        $delStmt->execute([$dup['id']]);
        $deleteCount++;
        
    } catch (Exception $e) {
        echo "Error processing {$dup['id']}: " . $e->getMessage() . "\n";
    }
}

echo "Deleted $deleteCount duplicate offset entries.\n";

// Step 4: Rebuild balance_after for all remaining entries
echo "Rebuilding balance_after values...\n";

$ledgerStmt = $db->query("
    SELECT id, user_id, direction, amount 
    FROM user_ledgers 
    ORDER BY user_id, created_at ASC
");

$balances = [];
$updateCount = 0;

while ($row = $ledgerStmt->fetch(PDO::FETCH_ASSOC)) {
    $userId = $row['user_id'];
    if (!isset($balances[$userId])) {
        $balances[$userId] = 0;
    }
    
    $balances[$userId] += ($row['direction'] === 'credit' ? $row['amount'] : -$row['amount']);
    
    // Update this entry's balance_after
    $upd = $db->prepare("UPDATE user_ledgers SET balance_after = ? WHERE id = ?");
    $upd->execute([$balances[$userId], $row['id']]);
    $updateCount++;
}

echo "Updated $updateCount balance_after values.\n";

// Step 5: Verify integrity
$verifyStmt = $db->query("
    SELECT reference_id, reference_type, entry_type, COUNT(*) as cnt
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
      AND entry_type IN ('expense_payment', 'advance_payment')
    GROUP BY reference_id, reference_type, entry_type
    HAVING cnt > 1
");

$violations = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($violations)) {
    echo "\n✅ CLEANUP COMPLETE - No integrity violations found.\n";
    echo "All transactions now have single ledger entries.\n";
} else {
    echo "\n❌ WARNING - Integrity violations found:\n";
    foreach ($violations as $v) {
        echo "  {$v['reference_type']} #{$v['reference_id']}: {$v['cnt']} entries\n";
    }
}
?>
```

---

## Implementation Order

1. Update ExpenseController.php (fix #1)
2. Update AdvanceController.php (fix #2)
3. Update LedgerHelper.php (fix #3)
4. Update OwnerController.php (fix #4)
5. Run cleanup script (fix #5)
6. Test owner ledger display

---

## Testing Checklist

After fixes:

- [ ] Create new expense → verify only 1 ledger entry
- [ ] Create new advance → verify only 1 ledger entry
- [ ] Approve expense → verify ledger_synced = 1
- [ ] Mark expense paid → verify no new ledger entry created
- [ ] Owner cash ledger → shows single row per transaction
- [ ] Owner cash ledger total → matches sum of payments
- [ ] Cleanup script → runs without errors
- [ ] Historical data → no duplicate offsets remain

---

## Verification Queries

Run these after implementing fixes:

```sql
-- Check for any remaining duplicates
SELECT reference_id, reference_type, entry_type, COUNT(*) as count
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY reference_id, reference_type, entry_type
HAVING count > 1;
-- Expected: No results

-- Verify ledger_synced flag is set
SELECT COUNT(*) as synced, COUNT(*) as total
FROM expenses e
WHERE status = 'paid'
AND ledger_synced = 1;
-- Expected: synced = total

-- Check ledger integrity
SELECT user_id, COUNT(*) as entry_count, 
       MAX(balance_after) as current_balance
FROM user_ledgers
GROUP BY user_id;
-- Expected: One balance per user = sum of all their transactions
```

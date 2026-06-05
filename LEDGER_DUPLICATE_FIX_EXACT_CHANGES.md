# EXACT CODE CHANGES - Copy & Paste Ready

## File 1: app/controllers/ExpenseController.php

### Change 1: Remove safety-net entry from markPaid()

**FIND THIS** (around line 410):
```php
            if (empty($expense['ledger_synced'])) {
                $ledgerOk = LedgerHelper::recordEntry($expense['user_id'], 'expense_payment', 'expense', $id, $ledgerAmount, 'credit', $expense['expense_date'], $db);
                if (!$ledgerOk) {
                    throw new Exception("Ledger safety-net entry failed for expense id=$id");
                }
                error_log("Expense markPaid: safety-net ledger created for id=$id");
            }
```

**REPLACE WITH**:
```php
            if (empty($expense['ledger_synced'])) {
                error_log("WARNING: Expense id=$id marked paid but ledger_synced flag not set (should have been set at approval)");
            }
```

---

## File 2: app/controllers/AdvanceController.php

### Change 1: Remove safety-net entry from markPaid()

**FIND THIS** (around line 305):
```php
                // Ledger was already created at approval (ledger_synced = 1).
                // Only create it here as a safety net if somehow missed.
                if (empty($advance['ledger_synced'])) {
                    require_once __DIR__ . '/../helpers/LedgerHelper.php';
                    $ledgerOk = LedgerHelper::recordEntry($advance['user_id'], 'advance_payment', 'advance', $id, $ledgerAmount, 'credit', $advance['requested_date'], $db);
                    if (!$ledgerOk) {
                        throw new Exception("Ledger safety-net entry failed for advance id=$id");
                    }
                    error_log("Advance markPaid: safety-net ledger created for id=$id");
                }
```

**REPLACE WITH**:
```php
                // Ledger entry was created at approval (ledger_synced = 1)
                // Status change from approved→paid should not create a new entry
                if (empty($advance['ledger_synced'])) {
                    error_log("WARNING: Advance id=$id marked paid but ledger_synced flag not set (should have been set at approval)");
                }
```

### Change 2: Remove auto-expense generation from markPaid()

**FIND THIS** (around line 315):
```php
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

                // Notify employee that advance was paid
```

**REPLACE WITH**:
```php
                error_log("Advance paid: id=$id user_id={$advance['user_id']} amount=$ledgerAmount");

                // NOTE: Removed auto-expense generation to prevent duplicate cash flow entries
                // Advances and their payments are tracked in the ledger system only
                // Do not create additional expense records that would create duplicate ledger entries

                // Notify employee that advance was paid
```

---

## File 3: app/controllers/OwnerController.php

### Change: Replace fetchOwnerLedgerEntries() method

**FIND THIS METHOD** (entire method from ~line 340 to ~line 380):
```php
    /**
     * Fetch all company-wide paid expenses + advances with optional filters.
     * Returns rows in chronological ASC order with balance_after attached.
     */
    private function fetchOwnerLedgerEntries(PDO $db, ?string $fromDate, ?string $toDate, ?string $transactionType, ?int $projectId): array {
        $expenseDateClause  = '';
        $advanceDateClause  = '';
        $expenseProjectClause = '';
        $advanceProjectClause = '';
        $dateParams         = [];

        if ($fromDate) {
            $expenseDateClause .= " AND COALESCE(e.expense_date, e.created_at) >= ?";
            $advanceDateClause .= " AND COALESCE(a.requested_date, a.paid_at, a.created_at) >= ?";
            $dateParams[]       = $fromDate;
        }
        if ($toDate) {
            $expenseDateClause .= " AND COALESCE(e.expense_date, e.created_at) <= ?";
            $advanceDateClause .= " AND COALESCE(a.requested_date, a.paid_at, a.created_at) <= ?";
            $dateParams[]       = $toDate . ' 23:59:59';
        }
        if ($projectId) {
            $expenseProjectClause = " AND e.project_id = ?";
            $advanceProjectClause = " AND a.project_id = ?";
        }

        $parts  = [];
        $params = [];

        if ($transactionType !== 'advance') {
            $parts[]  = "\r\n                SELECT e.id as reference_id, 'expense' as reference_type, 'debit' as direction,\r\n                       COALESCE(e.approved_amount, e.amount) as amount,\r\n                       e.description, e.category, e.status,\r\n                       COALESCE(e.expense_date, e.created_at) as created_at,\r\n                       u.name as employee_name,\r\n                       COALESCE(p.name, '') as project_name\r\n                FROM expenses e\r\n                JOIN users u ON e.user_id = u.id\r\n                LEFT JOIN projects p ON e.project_id = p.id\r\n                WHERE e.status = 'paid'\r\n                  AND (e.source_advance_id IS NULL OR e.source_advance_id = 0)\r\n                  {$expenseDateClause}\r\n                  {$expenseProjectClause}";\r\n            foreach ($dateParams as $p) $params[] = $p;\r\n            if ($projectId) $params[] = $projectId;\r\n        }\r\n\r\n        if ($transactionType !== 'expense') {\r\n            $parts[]  = "\r\n                SELECT a.id as reference_id, 'advance' as reference_type, 'debit' as direction,\r\n                       COALESCE(a.approved_amount, a.amount) as amount,\r\n                       COALESCE(a.reason, CONCAT('Advance – ', a.type)) as description,\r\n                       a.type as category, a.status,\r\n                       COALESCE(a.requested_date, a.paid_at, a.created_at) as created_at,\r\n                       u.name as employee_name,\r\n                       COALESCE(p.name, '') as project_name\r\n                FROM advances a\r\n                JOIN users u ON a.user_id = u.id\r\n                LEFT JOIN projects p ON a.project_id = p.id\r\n                WHERE a.status = 'paid'\r\n                  {$advanceDateClause}\r\n                  {$advanceProjectClause}";\r\n            foreach ($dateParams as $p) $params[] = $p;\r\n            if ($projectId) $params[] = $projectId;\r\n        }\r\n\r\n        if (empty($parts)) return [];\r\n\r\n        $sql  = implode(" UNION ALL ", $parts) . " ORDER BY created_at ASC";\r\n        $stmt = $db->prepare($sql);\r\n        $stmt->execute($params);\r\n        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);\r\n\r\n        // Attach running balance (all owner entries are debits from company cash)\r\n        $balance = 0;\r\n        foreach ($rows as &$row) {\r\n            $balance -= $row['amount'];   // every paid expense/advance is a cash outflow\r\n            $row['balance_after'] = $balance;\r\n        }\r\n        unset($row);\r\n\r\n        return $rows;\r\n    }
```

**REPLACE WITH**:
```php
    /**
     * Fetch all company-wide ledger entries (single source of truth).
     * Queries user_ledgers table instead of source tables to ensure single-entry-per-transaction model.
     * Returns rows in chronological ASC order with balance_after attached.
     */
    private function fetchOwnerLedgerEntries(PDO $db, ?string $fromDate, ?string $toDate, ?string $transactionType, ?int $projectId): array {
        $whereClauses = [];
        $params       = [];
        
        // Filter by reference type
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
        
        $whereClause = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";
        
        // Get project data for filtering/display
        $projectData = [];
        try {
            $pstmt = $db->query("SELECT id, name FROM projects");
            while ($p = $pstmt->fetch(PDO::FETCH_ASSOC)) {
                $projectData[$p['id']] = $p['name'];
            }
        } catch (Exception $e) {}
        
        $sql = "
            SELECT ul.id, ul.reference_id, ul.reference_type, ul.entry_type, 
                   ul.direction, ul.amount, ul.balance_after, ul.created_at,
                   u.name as employee_name,
                   u.id as user_id
            FROM user_ledgers ul
            JOIN users u ON ul.user_id = u.id
            $whereClause
            ORDER BY ul.created_at ASC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enrich with transaction details
        foreach ($rows as &$row) {
            $row['project_name'] = '';
            $row['description'] = '';
            $row['category'] = '';
            
            // Fetch from source table
            if ($row['reference_type'] === 'expense') {
                try {
                    $estmt = $db->prepare("SELECT category, description, project_id FROM expenses WHERE id = ? LIMIT 1");
                    $estmt->execute([$row['reference_id']]);
                    if ($erow = $estmt->fetch(PDO::FETCH_ASSOC)) {
                        $row['category'] = $erow['category'] ?? '';
                        $row['description'] = $erow['description'] ?? '';
                        if ($erow['project_id'] && isset($projectData[$erow['project_id']])) {
                            $row['project_name'] = $projectData[$erow['project_id']];
                        }
                    }
                } catch (Exception $e) {}
            } elseif ($row['reference_type'] === 'advance') {
                try {
                    $astmt = $db->prepare("SELECT type, reason, project_id FROM advances WHERE id = ? LIMIT 1");
                    $astmt->execute([$row['reference_id']]);
                    if ($arow = $astmt->fetch(PDO::FETCH_ASSOC)) {
                        $row['category'] = $arow['type'] ?? '';
                        $row['description'] = $arow['reason'] ?? '';
                        if ($arow['project_id'] && isset($projectData[$arow['project_id']])) {
                            $row['project_name'] = $projectData[$arow['project_id']];
                        }
                    }
                } catch (Exception $e) {}
            }
            
            // Project filter (if specified)
            if ($projectId && ($row['reference_type'] === 'expense' || $row['reference_type'] === 'advance')) {
                try {
                    if ($row['reference_type'] === 'expense') {
                        $pcheck = $db->prepare("SELECT project_id FROM expenses WHERE id = ? AND project_id = ? LIMIT 1");
                        $pcheck->execute([$row['reference_id'], $projectId]);
                    } else {
                        $pcheck = $db->prepare("SELECT project_id FROM advances WHERE id = ? AND project_id = ? LIMIT 1");
                        $pcheck->execute([$row['reference_id'], $projectId]);
                    }
                    if (!$pcheck->fetch()) {
                        // Doesn't match project filter, skip
                        continue;
                    }
                } catch (Exception $e) {}
            }
        }
        unset($row);
        
        return $rows;
    }
```

---

## File 4: app/helpers/LedgerHelper.php (Optional Enhancement)

### Add this new method to the LedgerHelper class:

**ADD AFTER the reverseEntry() method** (around line 230):

```php
    /**
     * Get count of duplicate entries for a specific reference+entry_type combo.
     * Used for auditing and verification.
     */
    public static function getDuplicateCount($referenceType, $referenceId, $entryType, $db = null): int {
        try {
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
        } catch (Exception $e) {
            error_log('LedgerHelper::getDuplicateCount error: ' . $e->getMessage());
            return 0;
        }
    }
```

---

## File 5: scripts/cleanup_duplicate_ledger_entries.php (NEW FILE)

**CREATE NEW FILE**: `scripts/cleanup_duplicate_ledger_entries.php`

```php
<?php
/**
 * CLEANUP SCRIPT: Remove duplicate ledger entries from before fix
 * 
 * Usage:
 *   php cleanup_duplicate_ledger_entries.php
 * 
 * This script:
 * 1. Creates audit table for tracking deletions
 * 2. Identifies duplicate ledger entries
 * 3. Keeps first (original) entry, deletes subsequent duplicates
 * 4. Rebuilds balance_after values
 * 5. Verifies integrity
 */

require_once __DIR__ . '/../app/config/database.php';

$db = Database::connect();
date_default_timezone_set('Asia/Kolkata');

echo "\n=== LEDGER DUPLICATE CLEANUP SCRIPT ===\n\n";

// STEP 1: Create cleanup audit table
echo "[1/5] Creating audit table...\n";
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS ledger_cleanup_audit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            deleted_entry_id INT,
            reference_type VARCHAR(50),
            reference_id INT,
            entry_type VARCHAR(50),
            amount DECIMAL(12,2),
            direction VARCHAR(10),
            reason VARCHAR(255),
            deleted_by VARCHAR(100),
            deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_ref (reference_type, reference_id)
        )
    ");
    echo "  ✓ Audit table ready\n";
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 2: Find all duplicate groups
echo "[2/5] Finding duplicate entries...\n";
try {
    $findDuplicates = "
        SELECT reference_type, reference_id, entry_type, COUNT(*) as cnt
        FROM user_ledgers
        WHERE reference_type IN ('expense', 'advance')
        GROUP BY reference_type, reference_id, entry_type
        HAVING cnt > 1
        ORDER BY reference_type, reference_id
    ";
    
    $stmt = $db->query($findDuplicates);
    $duplicateGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicateGroups)) {
        echo "  ✓ No duplicates found - database is clean!\n";
        exit(0);
    }
    
    echo "  ✓ Found " . count($duplicateGroups) . " duplicate groups\n";
    foreach ($duplicateGroups as $group) {
        echo "    - {$group['reference_type']} #{$group['reference_id']}: {$group['cnt']} entries\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 3: Delete duplicates keeping first
echo "[3/5] Removing duplicate entries...\n";
try {
    $deletedCount = 0;
    
    foreach ($duplicateGroups as $group) {
        // Find all entries for this group, ordered by creation
        $getEntries = $db->prepare("
            SELECT id FROM user_ledgers
            WHERE reference_type = ? AND reference_id = ? AND entry_type = ?
            ORDER BY created_at ASC
        ");
        $getEntries->execute([
            $group['reference_type'],
            $group['reference_id'],
            $group['entry_type']
        ]);
        
        $entries = $getEntries->fetchAll(PDO::FETCH_COLUMN);
        
        // Keep the first (original), delete the rest
        array_shift($entries);
        
        foreach ($entries as $entryId) {
            // Get entry details for audit
            $getEntry = $db->prepare("
                SELECT * FROM user_ledgers WHERE id = ?
            ");
            $getEntry->execute([$entryId]);
            $entry = $getEntry->fetch(PDO::FETCH_ASSOC);
            
            if ($entry) {
                // Log to audit table
                $audit = $db->prepare("
                    INSERT INTO ledger_cleanup_audit 
                    (deleted_entry_id, reference_type, reference_id, entry_type, amount, direction, reason, deleted_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $audit->execute([
                    $entryId,
                    $entry['reference_type'],
                    $entry['reference_id'],
                    $entry['entry_type'],
                    $entry['amount'],
                    $entry['direction'],
                    'Duplicate entry removed during ledger consolidation fix',
                    'SYSTEM_CLEANUP'
                ]);
                
                // Delete from ledger
                $del = $db->prepare("DELETE FROM user_ledgers WHERE id = ?");
                $del->execute([$entryId]);
                $deletedCount++;
            }
        }
    }
    
    echo "  ✓ Deleted $deletedCount duplicate entries\n";
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 4: Rebuild balance_after values
echo "[4/5] Rebuilding balance values...\n";
try {
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
        
        // Add credit, subtract debit
        $balances[$userId] += ($row['direction'] === 'credit' ? $row['amount'] : -$row['amount']);
        
        // Update balance_after
        $upd = $db->prepare("UPDATE user_ledgers SET balance_after = ? WHERE id = ?");
        $upd->execute([$balances[$userId], $row['id']]);
        $updateCount++;
    }
    
    echo "  ✓ Updated $updateCount balance values\n";
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 5: Verify integrity
echo "[5/5] Verifying integrity...\n";
try {
    $verify = $db->query("
        SELECT reference_type, reference_id, entry_type, COUNT(*) as cnt
        FROM user_ledgers
        WHERE reference_type IN ('expense', 'advance')
        GROUP BY reference_type, reference_id, entry_type
        HAVING cnt > 1
    ");
    
    $violations = $verify->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($violations)) {
        echo "  ✓ No integrity violations - cleanup successful!\n";
        echo "\n✅ CLEANUP COMPLETE\n";
        echo "   - Deleted: $deletedCount duplicate entries\n";
        echo "   - Updated: $updateCount balance values\n";
        echo "   - Status: All transactions now have single ledger entries\n\n";
    } else {
        echo "  ✗ WARNING: " . count($violations) . " integrity violations found:\n";
        foreach ($violations as $v) {
            echo "    - {$v['reference_type']} #{$v['reference_id']}: {$v['cnt']} entries (expected 1)\n";
        }
        exit(1);
    }
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Final status check
try {
    $totalEntries = $db->query("SELECT COUNT(*) FROM user_ledgers WHERE reference_type IN ('expense', 'advance')")->fetchColumn();
    $totalBalance = 0;
    $userCount = 0;
    
    $users = $db->query("SELECT DISTINCT user_id FROM user_ledgers");
    while ($u = $users->fetch(PDO::FETCH_COLUMN)) {
        $userCount++;
        $balance = $db->prepare("SELECT MAX(balance_after) FROM user_ledgers WHERE user_id = ?")->execute([$u])->fetchColumn();
        $totalBalance += $balance;
    }
    
    echo "\n📊 FINAL STATUS:\n";
    echo "   - Total ledger entries: $totalEntries\n";
    echo "   - Total users: $userCount\n";
    echo "   - Combined balance: ₹" . number_format($totalBalance, 2) . "\n";
    
} catch (Exception $e) {
    // Non-critical
}

?>
```

---

## Deployment Order

1. Backup database
2. Deploy ExpenseController.php changes
3. Deploy AdvanceController.php changes  
4. Deploy OwnerController.php changes
5. Deploy LedgerHelper.php changes (optional)
6. Create cleanup_duplicate_ledger_entries.php script
7. Run cleanup script
8. Test in staging first
9. Verify all transactions show 1 ledger entry
10. Deploy to production

---

## Verification After Fix

```sql
-- Should return 0 rows (no duplicates)
SELECT reference_type, reference_id, entry_type, COUNT(*) as cnt
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY reference_type, reference_id, entry_type
HAVING cnt > 1;

-- Should return 1 row per transaction
SELECT reference_id, reference_type, entry_type, COUNT(*) as cnt
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY reference_id, reference_type, entry_type;

-- Check balance is calculated correctly
SELECT user_id, MAX(balance_after) as final_balance
FROM user_ledgers
GROUP BY user_id;
```

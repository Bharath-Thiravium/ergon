<?php
/**
 * CLEANUP SCRIPT: Remove duplicate ledger entries
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
        array_shift($entries);
        
        foreach ($entries as $entryId) {
            $getEntry = $db->prepare("SELECT * FROM user_ledgers WHERE id = ?");
            $getEntry->execute([$entryId]);
            $entry = $getEntry->fetch(PDO::FETCH_ASSOC);
            
            if ($entry) {
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
        
        $balances[$userId] += ($row['direction'] === 'credit' ? $row['amount'] : -$row['amount']);
        
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

?>

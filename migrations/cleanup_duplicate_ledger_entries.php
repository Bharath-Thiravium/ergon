<?php
/**
 * Duplicate Ledger Entry Cleanup
 * 
 * Consolidates duplicate ledger entries where one business transaction
 * created multiple ledger rows. Implements single-entry model.
 * 
 * Run from: /ergon/migrations/cleanup_duplicate_ledger_entries.php
 * Access: Browser or CLI
 */

require_once __DIR__ . '/../app/config/database.php';

class LedgerDuplicateCleanup {
    private $db;
    private $backupTable = 'user_ledgers_backup_before_dedup';
    private $log = [];
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function run() {
        try {
            echo "<h2>Ledger Duplicate Cleanup Process</h2>";
            echo "<pre>";
            
            $this->log("=== STEP 1: AUDIT ===");
            $duplicates = $this->findDuplicates();
            
            if (empty($duplicates)) {
                $this->log("✓ No duplicates found! Ledger is clean.");
                echo "</pre>";
                return true;
            }
            
            $this->log("⚠ Found " . count($duplicates) . " transactions with duplicate entries\n");
            $this->logDuplicateDetails($duplicates);
            
            $this->log("\n=== STEP 2: VALIDATION ===");
            $this->validateIntegrity();
            
            $this->log("\n=== STEP 3: BACKUP ===");
            $this->createBackup();
            
            $this->log("\n=== STEP 4: CLEANUP ===");
            $deletedCount = $this->deleteDuplicates();
            $this->log("✓ Deleted $deletedCount duplicate entries");
            
            $this->log("\n=== STEP 5: VERIFY ===");
            $this->verifyCleanup();
            
            $this->log("\n=== STEP 6: RECONCILIATION ===");
            $this->reconcile();
            
            echo implode("\n", $this->log);
            echo "</pre>";
            echo "<div style='color: green; font-weight: bold;'>✓ CLEANUP COMPLETE</div>";
            
            return true;
        } catch (Exception $e) {
            echo "<div style='color: red; font-weight: bold;'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</div>";
            error_log("LedgerDuplicateCleanup error: " . $e->getMessage());
            return false;
        }
    }
    
    private function findDuplicates() {
        $sql = "
            SELECT 
                reference_type,
                reference_id,
                COUNT(*) as entry_count,
                GROUP_CONCAT(id ORDER BY created_at) as entry_ids,
                GROUP_CONCAT(entry_type ORDER BY created_at) as entry_types,
                GROUP_CONCAT(ROUND(amount, 2) ORDER BY created_at) as amounts,
                MAX(balance_after) as final_balance
            FROM user_ledgers
            WHERE reference_type IN ('expense', 'advance')
            GROUP BY reference_type, reference_id
            HAVING COUNT(*) > 1
            ORDER BY reference_type, reference_id
        ";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function logDuplicateDetails($duplicates) {
        $this->log("\nDuplicate Transactions:");
        foreach ($duplicates as $dup) {
            $this->log(sprintf(
                "  %s #%d: %d entries (ids: %s) | types: %s | amounts: %s | balance: %.2f",
                $dup['reference_type'],
                $dup['reference_id'],
                $dup['entry_count'],
                $dup['entry_ids'],
                $dup['entry_types'],
                $dup['amounts'],
                $dup['final_balance']
            ));
        }
    }
    
    private function validateIntegrity() {
        $checks = [
            "SELECT COUNT(*) FROM user_ledgers WHERE reference_type IN ('expense', 'advance')" => "Total Ledger Entries",
            "SELECT COUNT(*) FROM expenses WHERE status IN ('approved', 'paid')" => "Approved/Paid Expenses",
            "SELECT COUNT(*) FROM advances WHERE status IN ('approved', 'paid')" => "Approved/Paid Advances",
        ];
        
        $this->log("Pre-Cleanup State:");
        foreach ($checks as $sql => $label) {
            $count = (int)$this->db->query($sql)->fetchColumn();
            $this->log("  $label: $count");
        }
    }
    
    private function createBackup() {
        try {
            $this->db->query("DROP TABLE IF EXISTS " . $this->backupTable);
            $this->db->query("CREATE TABLE " . $this->backupTable . " AS SELECT * FROM user_ledgers");
            $count = $this->db->query("SELECT COUNT(*) FROM " . $this->backupTable)->fetchColumn();
            $this->log("✓ Backup created: " . $this->backupTable . " ($count rows)");
        } catch (Exception $e) {
            throw new Exception("Backup failed: " . $e->getMessage());
        }
    }
    
    private function deleteDuplicates() {
        // Delete all entries EXCEPT the first one for each transaction type
        $sql = "
            DELETE FROM user_ledgers
            WHERE reference_type IN ('expense', 'advance')
            AND id NOT IN (
                SELECT MIN(id)
                FROM (
                    SELECT MIN(id) as id
                    FROM user_ledgers
                    WHERE reference_type IN ('expense', 'advance')
                    GROUP BY reference_type, reference_id, entry_type
                ) as first_entries
            )
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    }
    
    private function verifyCleanup() {
        // Check for remaining duplicates
        $sql = "
            SELECT 
                reference_type,
                reference_id,
                entry_type,
                COUNT(*) as count
            FROM user_ledgers
            WHERE reference_type IN ('expense', 'advance')
            GROUP BY reference_type, reference_id, entry_type
            HAVING COUNT(*) > 1
        ";
        
        $stmt = $this->db->query($sql);
        $remaining = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($remaining)) {
            $this->log("✓ No duplicates remaining");
        } else {
            $this->log("⚠ WARNING: Found " . count($remaining) . " remaining duplicates:");
            foreach ($remaining as $dup) {
                $this->log("  {$dup['reference_type']} #{$dup['reference_id']}: {$dup['count']} entries");
            }
        }
    }
    
    private function reconcile() {
        // Verify each approved/paid transaction has exactly 1 ledger entry
        $sql = "
            SELECT 
                'expense' as type,
                e.id as ref_id,
                u.name as employee_name,
                e.status,
                COUNT(ul.id) as ledger_count
            FROM expenses e
            LEFT JOIN users u ON e.user_id = u.id
            LEFT JOIN user_ledgers ul ON ul.reference_type='expense' AND ul.reference_id=e.id
            WHERE e.status IN ('approved', 'paid')
            GROUP BY e.id
            
            UNION ALL
            
            SELECT 
                'advance',
                a.id,
                u.name,
                a.status,
                COUNT(ul.id)
            FROM advances a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN user_ledgers ul ON ul.reference_type='advance' AND ul.reference_id=a.id
            WHERE a.status IN ('approved', 'paid')
            GROUP BY a.id
        ";
        
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $correct = 0;
        $incorrect = 0;
        
        foreach ($results as $row) {
            if ($row['ledger_count'] == 1) {
                $correct++;
            } else {
                $incorrect++;
                $this->log("✗ ISSUE: {$row['type']} #{$row['ref_id']} ({$row['employee_name']}) has {$row['ledger_count']} ledger entries (expected 1)");
            }
        }
        
        $this->log("Reconciliation:");
        $this->log("  ✓ Correct (1 entry): $correct");
        $this->log("  ✗ Incorrect (not 1): $incorrect");
        
        if ($incorrect == 0) {
            $this->log("✓ All transactions properly reconciled");
        }
    }
    
    private function log($message) {
        $this->log[] = $message;
        error_log($message);
    }
}

// Run cleanup
$cleanup = new LedgerDuplicateCleanup();
$success = $cleanup->run();

if (!$success) {
    http_response_code(500);
}
?>

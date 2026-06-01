<?php
require_once __DIR__ . '/DatabaseHelper.php';

class LedgerHelper {
    public static function ensureTable($db = null) {
        if (!$db) {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
        }
        DatabaseHelper::safeExec($db, "CREATE TABLE IF NOT EXISTS user_ledgers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            reference_type VARCHAR(50) NOT NULL,
            reference_id INT NOT NULL,
            entry_type VARCHAR(50) NOT NULL,
            direction VARCHAR(10) NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            balance_after DECIMAL(12,2) NULL,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_user_id (user_id),
            KEY idx_reference (reference_type, reference_id),
            KEY idx_created_at (created_at)
        )");
    }

    /**
     * Record a ledger entry with audit trail and duplicate prevention.
     *
     * Duplicate guard uses the source table's ledger_synced flag AND content matching
     * to prevent double-entry errors. For manual entries, validates uniqueness by
     * reference and entry_type combination.
     *
     * @param int         $userId        Employee user_id (NOT the approver)
     * @param string      $entryType     'advance_payment' | 'expense_payment' | 'expense_reimbursement' | manual type
     * @param string      $referenceType 'advance' | 'expense' | 'manual'
     * @param int         $referenceId   advances.id | expenses.id | 0 for manual
     * @param float       $amount        Approved amount
     * @param string      $direction     'credit' | 'debit'
     * @param string|null $entryDate     Transaction date (defaults to now)
     * @param PDO|null    $db            Shared connection (keeps INSERT inside caller's transaction)
     * @param int|null    $createdBy     User ID who created this entry (for audit trail)
     */
    public static function recordEntry($userId, $entryType, $referenceType, $referenceId, $amount, $direction = 'credit', $entryDate = null, $db = null, $createdBy = null) {
        try {
            if (!$db) {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
            }

            if (func_num_args() < 8) {
                self::ensureTable($db);
            }

            // Duplicate guard: check ledger_synced on the source record.
            // Manual entries have no source table — skip the guard for them.
            if ($referenceType !== 'manual') {
                $sourceTable = ($referenceType === 'advance') ? 'advances' : 'expenses';
                $chk = $db->prepare("SELECT ledger_synced FROM {$sourceTable} WHERE id = ? LIMIT 1");
                $chk->execute([$referenceId]);
                $row = $chk->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['ledger_synced'])) {
                    error_log("LedgerHelper: skipped — ledger_synced=1 on $sourceTable id=$referenceId");
                    return true;
                }

                // Secondary guard: check if entry already exists for this reference+entry_type combo
                $chk2 = $db->prepare("
                    SELECT id FROM user_ledgers
                    WHERE user_id = ? AND reference_type = ? AND reference_id = ? AND entry_type = ?
                    LIMIT 1
                ");
                $chk2->execute([$userId, $referenceType, $referenceId, $entryType]);
                if ($chk2->fetch()) {
                    // Mark as synced to prevent retry
                    $db->prepare("UPDATE {$sourceTable} SET ledger_synced = 1 WHERE id = ?")->execute([$referenceId]);
                    error_log("LedgerHelper: entry exists (skipped, marked synced) — $referenceType/$referenceId type=$entryType");
                    return true;
                }
            } else {
                // For manual entries, check uniqueness by date + type + amount + direction
                $chk3 = $db->prepare("
                    SELECT id FROM user_ledgers
                    WHERE user_id = ? AND reference_type = 'manual' AND entry_type = ?
                      AND amount = ? AND direction = ? AND DATE(created_at) = DATE(?)
                    LIMIT 1
                ");
                $chk3->execute([$userId, $entryType, $amount, $direction, $entryDate ?? date('Y-m-d H:i:s')]);
                if ($chk3->fetch()) {
                    error_log("LedgerHelper: manual entry duplicate detected — user=$userId type=$entryType amount=$amount dir=$direction");
                    return false;
                }
            }

            $dateToUse = $entryDate ? $entryDate : date('Y-m-d H:i:s');

            // Calculate balance before this entry
            $bal = $db->prepare("
                SELECT COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE 0 END) -
                               SUM(CASE WHEN direction='debit' THEN amount ELSE 0 END), 0)
                FROM user_ledgers
                WHERE user_id = ?
            ");
            $bal->execute([$userId]);
            $prev = floatval($bal->fetchColumn());

            $balanceAfter = $prev + ($direction === 'credit' ? $amount : -$amount);

            // Insert with audit trail (created_by)
            $ins = $db->prepare("
                INSERT INTO user_ledgers
                (user_id, reference_type, reference_id, entry_type, direction, amount, balance_after, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $ins->execute([$userId, $referenceType, $referenceId, $entryType, $direction, $amount, $balanceAfter, $createdBy, $dateToUse]);

            if ($result && $referenceType !== 'manual') {
                // Mark source record as synced
                $sourceTable = ($referenceType === 'advance') ? 'advances' : 'expenses';
                $db->prepare("UPDATE {$sourceTable} SET ledger_synced = 1 WHERE id = ?")->execute([$referenceId]);
                error_log("LedgerHelper: entry created user=$userId $referenceType/$referenceId type=$entryType dir=$direction amount=$amount balance=$balanceAfter");

                // Post-insert integrity check
                $verify = $db->prepare("SELECT COUNT(*) FROM user_ledgers WHERE reference_type = ? AND reference_id = ? AND entry_type = ?");
                $verify->execute([$referenceType, $referenceId, $entryType]);
                $count = (int) $verify->fetchColumn();
                if ($count !== 1) {
                    error_log("LedgerHelper: WARNING integrity — found $count rows for $referenceType/$referenceId type=$entryType (expected 1)");
                }
            } elseif ($result) {
                error_log("LedgerHelper: manual entry created user=$userId amount=$amount dir=$direction balance=$balanceAfter created_by=$createdBy");
            }

            return $result;
        } catch (Exception $e) {
            error_log('LedgerHelper::recordEntry error: ' . $e->getMessage());
            return false;
        }
    }

    public static function getUserBalance($userId, $db = null) {
        try {
            if (!$db) {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
            }
            $stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE 0 END) - SUM(CASE WHEN direction='debit' THEN amount ELSE 0 END), 0) FROM user_ledgers WHERE user_id = ?");
            $stmt->execute([$userId]);
            return floatval($stmt->fetchColumn());
        } catch (Exception $e) {
            error_log('LedgerHelper::getUserBalance error: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Create a manual adjustment/correction entry (e.g., write-off, penalty, bonus adjustment)
     * Primarily for admin corrections and adjustments that bypass the normal flow.
     */
    public static function recordManualAdjustment($userId, $amount, $direction, $description, $entryType = 'adjustment', $db = null, $createdBy = null) {
        try {
            if (!$db) {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
            }
            self::ensureTable($db);

            // Validate direction
            if (!in_array($direction, ['credit', 'debit'])) {
                error_log("LedgerHelper::recordManualAdjustment invalid direction: $direction");
                return false;
            }

            return self::recordEntry(
                $userId,
                $entryType,                    // entry_type
                'manual',                       // reference_type
                0,                             // reference_id
                $amount,
                $direction,
                date('Y-m-d H:i:s'),
                $db,
                $createdBy
            );
        } catch (Exception $e) {
            error_log('LedgerHelper::recordManualAdjustment error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reverse a mistaken ledger entry (creates offsetting debit/credit)
     * Better than deleting for audit trail purposes.
     */
    public static function reverseEntry($entryId, $reason = 'Correction', $db = null, $createdBy = null) {
        try {
            if (!$db) {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
            }

            // Fetch original entry
            $stmt = $db->prepare("SELECT user_id, amount, direction, reference_type, entry_type FROM user_ledgers WHERE id = ? LIMIT 1");
            $stmt->execute([$entryId]);
            $entry = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$entry) {
                error_log("LedgerHelper::reverseEntry entry not found: $entryId");
                return false;
            }

            // Create offsetting entry
            $offsetDirection = $entry['direction'] === 'credit' ? 'debit' : 'credit';
            $offsetType = $entry['entry_type'] . '_reversal';

            return self::recordEntry(
                $entry['user_id'],
                $offsetType,
                'manual',                       // Reversals are manual entries
                0,
                $entry['amount'],
                $offsetDirection,
                date('Y-m-d H:i:s'),
                $db,
                $createdBy
            );
        } catch (Exception $e) {
            error_log('LedgerHelper::reverseEntry error: ' . $e->getMessage());
            return false;
        }
    }
}

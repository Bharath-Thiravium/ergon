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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    /**
     * Record a ledger entry.
     *
     * Duplicate guard uses the source table's ledger_synced flag — NOT content
     * matching — so identical amounts/descriptions on the same day are allowed
     * (construction workflow requirement).
     *
     * @param int         $userId        Employee user_id (NOT the approver)
     * @param string      $entryType     'advance_payment' | 'expense_payment'
     * @param string      $referenceType 'advance' | 'expense'
     * @param int         $referenceId   advances.id | expenses.id
     * @param float       $amount        Approved amount
     * @param string      $direction     'credit'
     * @param string|null $entryDate
     * @param PDO|null    $db            Shared connection (keeps INSERT inside caller's transaction)
     */
    public static function recordEntry($userId, $entryType, $referenceType, $referenceId, $amount, $direction = 'credit', $entryDate = null, $db = null) {
        try {
            if (!$db) {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
            }

            // ensureTable() issues DDL which causes an implicit commit in MySQL.
            // It must NOT be called inside an open transaction.
            // Callers are responsible for calling ensureTable() before beginTransaction().
            // We only call it here when no external $db is provided (standalone use).
            if (func_num_args() < 8) {
                self::ensureTable($db);
            }

            // Duplicate guard: check ledger_synced on the source record.
            // This is construction-safe — it does NOT block same amount/description.
            $sourceTable = ($referenceType === 'advance') ? 'advances' : 'expenses';
            $chk = $db->prepare("SELECT ledger_synced FROM {$sourceTable} WHERE id = ? LIMIT 1");
            $chk->execute([$referenceId]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['ledger_synced'])) {
                error_log("LedgerHelper: skipped — ledger_synced=1 on $sourceTable id=$referenceId");
                return true;
            }

            $dateToUse = $entryDate ? $entryDate : date('Y-m-d H:i:s');

            $bal = $db->prepare("SELECT COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE 0 END) - SUM(CASE WHEN direction='debit' THEN amount ELSE 0 END), 0) FROM user_ledgers WHERE user_id = ?");
            $bal->execute([$userId]);
            $prev = floatval($bal->fetchColumn());

            $balanceAfter = $prev + ($direction === 'credit' ? $amount : -$amount);

            $ins = $db->prepare("INSERT INTO user_ledgers (user_id, reference_type, reference_id, entry_type, direction, amount, balance_after, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $ins->execute([$userId, $referenceType, $referenceId, $entryType, $direction, $amount, $balanceAfter, $dateToUse]);

            if ($result) {
                // Mark source record as synced — prevents any re-entry on retry or markPaid
                $db->prepare("UPDATE {$sourceTable} SET ledger_synced = 1 WHERE id = ?")->execute([$referenceId]);
                error_log("LedgerHelper: entry created user_id=$userId $referenceType/$referenceId type=$entryType dir=$direction amount=$amount balance_after=$balanceAfter");

                // Post-insert integrity log
                $verify = $db->prepare("SELECT COUNT(*) FROM user_ledgers WHERE reference_type = ? AND reference_id = ? AND entry_type = ?");
                $verify->execute([$referenceType, $referenceId, $entryType]);
                $count = (int) $verify->fetchColumn();
                if ($count !== 1) {
                    error_log("LedgerHelper: WARNING integrity check found $count rows for $referenceType/$referenceId type=$entryType");
                }
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
}

<?php
require_once __DIR__ . '/DatabaseHelper.php';

class LedgerHelper {
    public static function ensureTable() {
        require_once __DIR__ . '/../config/database.php';
        $db = Database::connect();
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

    public static function recordEntry($userId, $entryType, $referenceType, $referenceId, $amount, $direction = 'credit') {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            self::ensureTable();

            // compute previous balance
            $stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE 0 END) - SUM(CASE WHEN direction='debit' THEN amount ELSE 0 END),0) as bal FROM user_ledgers WHERE user_id = ?");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $prev = $row ? floatval($row['bal']) : 0.0;

            $balanceAfter = $prev + ($direction === 'credit' ? $amount : -$amount);

            $stmt = $db->prepare("INSERT INTO user_ledgers (user_id, reference_type, reference_id, entry_type, direction, amount, balance_after, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            return $stmt->execute([$userId, $referenceType, $referenceId, $entryType, $direction, $amount, $balanceAfter]);
        } catch (Exception $e) {
            error_log('Ledger record error: ' . $e->getMessage());
            return false;
        }
    }

    public static function getUserBalance($userId) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE 0 END) - SUM(CASE WHEN direction='debit' THEN amount ELSE 0 END),0) as bal FROM user_ledgers WHERE user_id = ?");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? floatval($row['bal']) : 0.0;
        } catch (Exception $e) {
            error_log('Ledger balance error: ' . $e->getMessage());
            return 0.0;
        }
    }
}

?>

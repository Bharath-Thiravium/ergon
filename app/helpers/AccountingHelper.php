<?php
require_once __DIR__ . '/../config/database.php';

class AccountingHelper {
    private static $db;
    
    private static function getDb() {
        if (!self::$db) {
            self::$db = Database::connect();
        }
        return self::$db;
    }
    
    /**
     * Record expense approval in accounting system
     */
    public static function recordExpenseApproval($expenseId, $amount, $category, $description, $approvedBy, $db = null) {
        try {
            if (!$db) {
                $db = self::getDb();
                $shouldCommit = true;
                $db->beginTransaction();
            } else {
                $shouldCommit = false;
            }
            
            // Get appropriate expense account based on category
            $accountId = self::getExpenseAccountId($category);
            
            // Create journal entry
            $stmt = $db->prepare("
                INSERT INTO journal_entries (reference_type, reference_id, entry_date, description, total_amount, created_by) 
                VALUES ('expense', ?, CURDATE(), ?, ?, ?)
            ");
            $stmt->execute([$expenseId, $description, $amount, $approvedBy]);
            $journalEntryId = $db->lastInsertId();
            
            // Create debit entry (Expense Account)
            $stmt = $db->prepare("
                INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit_amount, description) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$journalEntryId, $accountId, $amount, "Expense: " . $description]);
            
            // Create credit entry (Accounts Payable)
            $payableAccountId = self::getAccountByCode('L001'); // Accounts Payable
            $stmt = $db->prepare("
                INSERT INTO journal_entry_lines (journal_entry_id, account_id, credit_amount, description) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$journalEntryId, $payableAccountId, $amount, "Payable: " . $description]);
            
            // Update account balances
            self::updateAccountBalance($accountId, $amount, 'debit');
            self::updateAccountBalance($payableAccountId, $amount, 'credit');
            
            // Link journal entry to expense
            $stmt = $db->prepare("UPDATE expenses SET journal_entry_id = ? WHERE id = ?");
            $stmt->execute([$journalEntryId, $expenseId]);
            
            if ($shouldCommit) {
                $db->commit();
            }
            return $journalEntryId;
            
        } catch (Exception $e) {
            if ($shouldCommit && $db->inTransaction()) {
                $db->rollback();
            }
            error_log('Accounting Helper Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get expense account ID based on category
     */
    private static function getExpenseAccountId($category) {
        $accountMap = [
            'travel' => 'E002',
            'office' => 'E003',
            'general' => 'E001',
            'miscellaneous' => 'E004'
        ];
        
        $accountCode = $accountMap[strtolower($category)] ?? 'E001';
        return self::getAccountByCode($accountCode);
    }
    
    /**
     * Get account ID by account code
     */
    private static function getAccountByCode($code) {
        $db = self::getDb();
        $stmt = $db->prepare("SELECT id FROM accounts WHERE account_code = ?");
        $stmt->execute([$code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : 1; // Default to first account if not found
    }
    
    /**
     * Update account balance
     */
    private static function updateAccountBalance($accountId, $amount, $type) {
        $db = self::getDb();
        
        if ($type === 'debit') {
            $stmt = $db->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
        } else {
            $stmt = $db->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
        }
        
        $stmt->execute([$amount, $accountId]);
    }
    
    /**
     * Reverse expense accounting entry (for rejections or cancellations)
     */
    public static function reverseExpenseEntry($expenseId) {
        try {
            $db = self::getDb();
            $db->beginTransaction();
            
            // Get the journal entry
            $stmt = $db->prepare("SELECT journal_entry_id FROM expenses WHERE id = ?");
            $stmt->execute([$expenseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['journal_entry_id']) {
                $journalEntryId = $result['journal_entry_id'];
                
                // Get journal entry lines to reverse
                $stmt = $db->prepare("
                    SELECT account_id, debit_amount, credit_amount 
                    FROM journal_entry_lines 
                    WHERE journal_entry_id = ?
                ");
                $stmt->execute([$journalEntryId]);
                $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Reverse each line
                foreach ($lines as $line) {
                    if ($line['debit_amount'] > 0) {
                        self::updateAccountBalance($line['account_id'], -$line['debit_amount'], 'debit');
                    }
                    if ($line['credit_amount'] > 0) {
                        self::updateAccountBalance($line['account_id'], -$line['credit_amount'], 'credit');
                    }
                }
                
                // Delete journal entry (cascade will delete lines)
                $stmt = $db->prepare("DELETE FROM journal_entries WHERE id = ?");
                $stmt->execute([$journalEntryId]);
                
                // Clear journal entry reference from expense
                $stmt = $db->prepare("UPDATE expenses SET journal_entry_id = NULL WHERE id = ?");
                $stmt->execute([$expenseId]);
            }
            
            $db->commit();
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            error_log('Reverse Expense Entry Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get account balance
     */
    public static function getAccountBalance($accountId) {
        $db = self::getDb();
        $stmt = $db->prepare("SELECT balance FROM accounts WHERE id = ?");
        $stmt->execute([$accountId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['balance'] : 0;
    }
    
    /**
     * Get expense accounting summary
     */
    public static function getExpenseAccountingSummary($startDate = null, $endDate = null) {
        $db = self::getDb();
        
        $whereClause = "";
        $params = [];
        
        if ($startDate && $endDate) {
            $whereClause = "WHERE je.entry_date BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }
        
        $stmt = $db->prepare("
            SELECT 
                a.account_name,
                a.account_code,
                SUM(jel.debit_amount) as total_debits,
                SUM(jel.credit_amount) as total_credits,
                a.balance as current_balance
            FROM accounts a
            LEFT JOIN journal_entry_lines jel ON a.id = jel.account_id
            LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id
            $whereClause
            GROUP BY a.id, a.account_name, a.account_code, a.balance
            ORDER BY a.account_code
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
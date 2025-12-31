<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/helpers/LedgerHelper.php';
require_once __DIR__ . '/../app/config/database.php';

class LedgerHelperTest extends TestCase {
    public function testRecordAndBalance() {
        // Use a test user id
        $userId = 999999;
        // Ensure table exists
        LedgerHelper::ensureTable();

        // Clean up any existing test entries
        $db = Database::connect();
        $db->prepare('DELETE FROM user_ledgers WHERE user_id = ?')->execute([$userId]);

        $res1 = LedgerHelper::recordEntry($userId, 'test', 'expense', 1, 100.00, 'credit');
        $this->assertTrue($res1, 'Record entry should succeed');

        $res2 = LedgerHelper::recordEntry($userId, 'test', 'expense', 2, 30.00, 'debit');
        $this->assertTrue($res2, 'Record entry should succeed');

        $balance = LedgerHelper::getUserBalance($userId);
        $this->assertEquals(70.00, round($balance,2));

        // cleanup
        $db->prepare('DELETE FROM user_ledgers WHERE user_id = ?')->execute([$userId]);
    }
}

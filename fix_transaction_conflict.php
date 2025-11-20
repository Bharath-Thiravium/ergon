<?php
// Fix the transaction conflict in AccountingHelper
require_once 'app/config/database.php';

$filePath = 'app/helpers/AccountingHelper.php';
$content = file_get_contents($filePath);

// Replace the problematic transaction handling
$oldCode = 'public static function recordExpenseApproval($expenseId, $amount, $category, $description, $approvedBy) {
        try {
            $db = self::getDb();
            $db->beginTransaction();';

$newCode = 'public static function recordExpenseApproval($expenseId, $amount, $category, $description, $approvedBy, $db = null) {
        try {
            if (!$db) {
                $db = self::getDb();
                $shouldCommit = true;
                $db->beginTransaction();
            } else {
                $shouldCommit = false;
            }';

$content = str_replace($oldCode, $newCode, $content);

// Fix the commit/rollback logic
$oldCommit = '$db->commit();
            return $journalEntryId;
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }';

$newCommit = 'if ($shouldCommit) {
                $db->commit();
            }
            return $journalEntryId;
            
        } catch (Exception $e) {
            if ($shouldCommit && $db->inTransaction()) {
                $db->rollback();
            }';

$content = str_replace($oldCommit, $newCommit, $content);

// Write the fixed content
file_put_contents($filePath, $content);

echo "✓ Fixed AccountingHelper transaction conflict\n";

// Now fix the ExpenseController to pass the database connection
$controllerPath = 'app/controllers/ExpenseController.php';
$controllerContent = file_get_contents($controllerPath);

$oldCall = 'AccountingHelper::recordExpenseApproval(
                    $id,
                    $expense[\'amount\'],
                    $expense[\'category\'],
                    $expense[\'description\'],
                    $_SESSION[\'user_id\']
                );';

$newCall = 'AccountingHelper::recordExpenseApproval(
                    $id,
                    $expense[\'amount\'],
                    $expense[\'category\'],
                    $expense[\'description\'],
                    $_SESSION[\'user_id\'],
                    $db
                );';

$controllerContent = str_replace($oldCall, $newCall, $controllerContent);
file_put_contents($controllerPath, $controllerContent);

echo "✓ Fixed ExpenseController to pass database connection\n";
echo "🎉 Transaction conflict fixed!\n";
?>
-- ==========================================
-- OWNER LEDGER DUPLICATE CLEANUP SCRIPT
-- ==========================================
-- 
-- Purpose: Identify and consolidate duplicate ledger entries
-- One business transaction = One ledger row
-- 
-- DO NOT RUN without reviewing output first!
-- ==========================================

-- ==========================================
-- STEP 1: AUDIT - Find all duplicates
-- ==========================================

-- List all transactions with multiple ledger entries
SELECT 
    reference_type,
    reference_id,
    COUNT(*) as entry_count,
    GROUP_CONCAT(id ORDER BY created_at) as entry_ids,
    GROUP_CONCAT(entry_type ORDER BY created_at) as entry_types,
    GROUP_CONCAT(ROUND(amount, 2) ORDER BY created_at) as amounts,
    GROUP_CONCAT(direction ORDER BY created_at) as directions,
    MAX(balance_after) as final_balance
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY reference_type, reference_id
HAVING COUNT(*) > 1
ORDER BY reference_type, reference_id;

-- ==========================================
-- STEP 2: DETAILED DUPLICATE REPORT
-- ==========================================

-- Show exact duplicate entries for Expense #73 (example)
SELECT 
    ul.id,
    ul.user_id,
    u.name as employee_name,
    ul.reference_type,
    ul.reference_id,
    ul.entry_type,
    ul.direction,
    ul.amount,
    ul.balance_after,
    ul.created_at
FROM user_ledgers ul
LEFT JOIN users u ON ul.user_id = u.id
WHERE ul.reference_type = 'expense' 
AND ul.reference_id = 73
ORDER BY ul.created_at;

-- ==========================================
-- STEP 3: COUNT DUPLICATES
-- ==========================================

-- Total duplicate entries
SELECT 
    SUM(duplicate_count - 1) as total_duplicate_rows,
    COUNT(*) as transactions_with_duplicates
FROM (
    SELECT 
        reference_type,
        reference_id,
        COUNT(*) as duplicate_count
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type, reference_id
    HAVING COUNT(*) > 1
) as duplicates;

-- ==========================================
-- STEP 4: VALIDATION BEFORE CLEANUP
-- ==========================================

-- Verify ledger integrity before changes
SELECT 
    'Total Ledger Entries' as check_type,
    COUNT(*) as count
FROM user_ledgers
UNION ALL
SELECT 
    'Approved Expenses',
    COUNT(*)
FROM expenses
WHERE status IN ('approved', 'paid')
UNION ALL
SELECT 
    'Approved Advances',
    COUNT(*)
FROM advances
WHERE status IN ('approved', 'paid')
UNION ALL
SELECT 
    'Ledger Entries (Expense)',
    COUNT(*)
FROM user_ledgers
WHERE reference_type = 'expense' AND entry_type = 'expense_payment'
UNION ALL
SELECT 
    'Ledger Entries (Advance)',
    COUNT(*)
FROM user_ledgers
WHERE reference_type = 'advance' AND entry_type = 'advance_payment';

-- ==========================================
-- STEP 5: CLEANUP PROCEDURE
-- ==========================================
-- 
-- Strategy: Keep FIRST entry, DELETE subsequent entries
-- First entry has correct balance calculations
-- 
-- NOTE: Execute each section separately and verify!
-- ==========================================

-- Create backup table (SAFETY FIRST)
CREATE TABLE IF NOT EXISTS user_ledgers_backup_before_dedup AS
SELECT * FROM user_ledgers;

-- Find and mark rows for deletion (don't delete yet!)
SELECT 
    ul.id as row_to_delete,
    ul.reference_type,
    ul.reference_id,
    ul.entry_type,
    ul.amount,
    ul.created_at,
    (SELECT MIN(id) FROM user_ledgers ul2 
     WHERE ul2.reference_type = ul.reference_type 
     AND ul2.reference_id = ul.reference_id
     AND ul2.entry_type = ul.entry_type) as keep_this_id
FROM user_ledgers ul
WHERE ul.reference_type IN ('expense', 'advance')
AND ul.id NOT IN (
    SELECT MIN(id)
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type, reference_id, entry_type
)
ORDER BY ul.reference_type, ul.reference_id, ul.created_at;

-- ==========================================
-- ACTUAL CLEANUP (Use with caution)
-- ==========================================

-- Delete duplicate ledger entries (keep oldest entry per transaction)
DELETE FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
AND id NOT IN (
    SELECT MIN(id)
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type, reference_id, entry_type
);

-- ==========================================
-- STEP 6: POST-CLEANUP VALIDATION
-- ==========================================

-- Verify no more duplicates exist
SELECT 
    reference_type,
    reference_id,
    entry_type,
    COUNT(*) as entry_count
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY reference_type, reference_id, entry_type
HAVING COUNT(*) > 1;

-- Should return: 0 rows (no duplicates)

-- ==========================================
-- STEP 7: RECONCILIATION REPORT
-- ==========================================

-- Final state: one row per business transaction
SELECT 
    'Ledger State After Cleanup' as report,
    COUNT(*) as total_entries,
    SUM(CASE WHEN direction='credit' THEN amount ELSE 0 END) as total_credits,
    SUM(CASE WHEN direction='debit' THEN amount ELSE 0 END) as total_debits
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance');

-- Expense reconciliation
SELECT 
    e.id as expense_id,
    e.user_id,
    u.name as employee_name,
    e.amount as claimed_amount,
    e.approved_amount,
    e.status,
    (SELECT COUNT(*) FROM user_ledgers WHERE reference_type='expense' AND reference_id=e.id) as ledger_entry_count
FROM expenses e
LEFT JOIN users u ON e.user_id = u.id
WHERE e.status IN ('approved', 'paid')
HAVING ledger_entry_count != 1
ORDER BY e.id;

-- Should return: 0 rows (each expense has exactly 1 ledger entry)

-- Advance reconciliation
SELECT 
    a.id as advance_id,
    a.user_id,
    u.name as employee_name,
    a.amount,
    a.approved_amount,
    a.status,
    (SELECT COUNT(*) FROM user_ledgers WHERE reference_type='advance' AND reference_id=a.id) as ledger_entry_count
FROM advances a
LEFT JOIN users u ON a.user_id = u.id
WHERE a.status IN ('approved', 'paid')
HAVING ledger_entry_count != 1
ORDER BY a.id;

-- Should return: 0 rows (each advance has exactly 1 ledger entry)

-- ==========================================
-- STEP 8: RESTORE FROM BACKUP (if needed)
-- ==========================================
-- 
-- If something goes wrong:
-- 
-- DROP TABLE user_ledgers;
-- RENAME TABLE user_ledgers_backup_before_dedup TO user_ledgers;
-- 
-- Then investigate and fix the code before retrying cleanup
-- ==========================================

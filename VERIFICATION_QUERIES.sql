-- OWNER LEDGER DUPLICATE FIX - VERIFICATION QUERIES
-- Run these queries to verify the fix was successful

-- ===== QUERY 1: Check for remaining duplicates =====
-- Expected: 0 rows (no duplicates)
SELECT reference_type, reference_id, entry_type, COUNT(*) as count
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY reference_type, reference_id, entry_type
HAVING count > 1;


-- ===== QUERY 2: Verify ledger_synced flag is set =====
-- Expected: Both counts should be equal
SELECT 
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
    COUNT(CASE WHEN status = 'approved' AND ledger_synced = 1 THEN 1 END) as synced_count
FROM expenses
WHERE status IN ('approved', 'paid');


-- ===== QUERY 3: Check balance calculations =====
-- Expected: Should match manual calculations
SELECT user_id, 
       MAX(balance_after) as final_balance,
       SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END) as calculated_balance
FROM user_ledgers
GROUP BY user_id
ORDER BY user_id;


-- ===== QUERY 4: Verify no auto-generated expenses =====
-- Expected: Minimal or 0 rows (removed auto-expense generation)
SELECT COUNT(*) as auto_expense_count
FROM expenses
WHERE category = 'work_advance' OR source_advance_id IS NOT NULL;


-- ===== QUERY 5: Owner ledger summary =====
-- Expected: One row per transaction, accurate balance
SELECT 
    reference_type,
    COUNT(*) as entry_count,
    SUM(amount) as total_amount,
    MAX(balance_after) as final_balance
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance')
GROUP BY reference_type;


-- ===== QUERY 6: Check cleanup audit trail =====
-- Expected: Shows what was deleted (if cleanup was run)
SELECT COUNT(*) as deleted_count
FROM ledger_cleanup_audit;


-- ===== QUERY 7: Transaction count verification =====
-- Expected: Ledger entries = approved/paid transactions (1:1 ratio)
SELECT 
    'expenses' as entity_type,
    COUNT(CASE WHEN status IN ('approved', 'paid') THEN 1 END) as approved_paid_count
FROM expenses
UNION ALL
SELECT 
    'advances',
    COUNT(CASE WHEN status IN ('approved', 'paid') THEN 1 END)
FROM advances
UNION ALL
SELECT 
    'ledger_entries',
    COUNT(*)
FROM user_ledgers
WHERE reference_type IN ('expense', 'advance');


-- ===== QUERY 8: Top spenders verification =====
-- Expected: Balance should be negative (liabilities)
SELECT u.id, u.name, 
       COUNT(ul.id) as entry_count,
       SUM(CASE WHEN ul.direction = 'credit' THEN ul.amount ELSE 0 END) as total_credits,
       SUM(CASE WHEN ul.direction = 'debit' THEN ul.amount ELSE 0 END) as total_debits,
       MAX(ul.balance_after) as current_balance
FROM users u
LEFT JOIN user_ledgers ul ON u.id = ul.user_id
WHERE u.status = 'active'
GROUP BY u.id, u.name
HAVING entry_count > 0
ORDER BY current_balance DESC;


-- ===== QUERY 9: Date range ledger (sample) =====
-- Expected: Shows accurate ledger entries for date range
SELECT ul.created_at,
       u.name as employee_name,
       ul.reference_type,
       ul.reference_id,
       ul.entry_type,
       ul.direction,
       ul.amount,
       ul.balance_after
FROM user_ledgers ul
JOIN users u ON ul.user_id = u.id
WHERE ul.reference_type IN ('expense', 'advance')
  AND ul.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY ul.created_at DESC
LIMIT 50;


-- ===== QUERY 10: Integrity check =====
-- Expected: 0 rows (no issues)
SELECT * FROM (
    SELECT 'Missing ledger entry' as issue, 
           reference_type, 
           reference_id
    FROM expenses e
    WHERE status IN ('approved', 'paid')
      AND ledger_synced = 0
    UNION ALL
    SELECT 'Duplicate ledger entry',
           reference_type,
           reference_id
    FROM user_ledgers
    WHERE reference_type IN ('expense', 'advance')
    GROUP BY reference_type, reference_id, entry_type
    HAVING COUNT(*) > 1
) issues;

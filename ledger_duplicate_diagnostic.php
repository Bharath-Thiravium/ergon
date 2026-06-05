<?php
/**
 * Owner Ledger Duplicate Detection & Analysis Tool
 * Diagnostic script to identify and verify the duplicate ledger issue
 * 
 * Usage: Visit http://your-domain.com/ergon/ledger_duplicate_diagnostic.php
 * Delete after use (this is a development tool)
 */

require_once __DIR__ . '/app/config/database.php';

// Prevent accidental deployment
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', 'localhost', '::1'])) {\n    http_response_code(403);
    die('Access denied. This tool is only available from localhost.');\n}

$db = Database::connect();

?><!DOCTYPE html>
<html>
<head>
    <title>Owner Ledger Duplicate Diagnostic</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; font-size: 0.95rem; }
        .content {
            padding: 2rem;
        }
        .section {
            margin-bottom: 3rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            background: #f9fafb;
        }
        .section h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #1f2937;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }
        .stat {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            background: white;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            border-left: 4px solid #667eea;
        }
        .stat-label { font-weight: 600; color: #374151; }
        .stat-value { 
            font-weight: bold; 
            font-size: 1.1rem;
            color: #667eea;
        }
        .stat-value.critical { color: #dc2626; }
        .stat-value.success { color: #10b981; }
        .stat-value.warning { color: #f59e0b; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f3f4f6;
            font-weight: 600;
            color: #374151;
        }
        tr:hover { background: #f9fafb; }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-bad {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-ok {
            background: #d1fae5;
            color: #065f46;
        }
        .status-warn {
            background: #fef3c7;
            color: #92400e;
        }
        .action-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            margin-top: 1rem;
        }
        .action-btn:hover { background: #5568d3; }
        .action-btn.danger {
            background: #dc2626;
        }
        .action-btn.danger:hover {
            background: #b91c1c;
        }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .summary h3 { margin-bottom: 1rem; font-size: 1.1rem; }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .summary-item:last-child { border-bottom: none; }
        .code-block {
            background: #1f2937;
            color: #d1d5db;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 0.85rem;
            line-height: 1.5;
            margin-top: 1rem;
        }
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .warning-box strong { color: #92400e; }
        .footer {
            background: #f3f4f6;
            padding: 1.5rem;
            text-align: center;
            color: #6b7280;
            font-size: 0.9rem;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🔍 Owner Ledger Diagnostic Tool</h1>
        <p>Root Cause Analysis: Duplicate Ledger Entry Detection</p>
    </div>

    <div class="content">

        <?php
        
        try {
            
            // ─────────────────────────────────────────────────────────────
            // 1. Database Table Check
            // ─────────────────────────────────────────────────────────────
            
            ?>
            <div class="section">
                <h2>✓ Database Connection</h2>
                <div class="stat">
                    <span class="stat-label">Database Status:</span>
                    <span class="stat-value success">Connected</span>
                </div>
            </div>
            <?php
            
            // ─────────────────────────────────────────────────────────────
            // 2. Find Duplicate Ledger Entries
            // ─────────────────────────────────────────────────────────────
            
            $dupQuery = "
                SELECT 
                    reference_type,
                    reference_id,
                    COUNT(*) as count,
                    GROUP_CONCAT(id) as entry_ids,
                    GROUP_CONCAT(entry_type) as entry_types,
                    SUM(CASE WHEN direction='credit' THEN amount ELSE -amount END) as net_amount
                FROM user_ledgers
                WHERE reference_type IN ('expense', 'advance')
                GROUP BY reference_type, reference_id
                HAVING COUNT(*) > 1
                ORDER BY reference_id DESC
                LIMIT 50
            ";
            
            $stmt = $db->prepare($dupQuery);
            $stmt->execute();
            $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ?>
            <div class="section">
                <h2>⚠️ Duplicate Entry Detection</h2>
                
                <div class="stat">
                    <span class="stat-label">Total Duplicate Sets Found:</span>
                    <span class="stat-value <?= count($duplicates) > 0 ? 'critical' : 'success' ?>">
                        <?= count($duplicates) ?>
                    </span>
                </div>
                
                <?php if (count($duplicates) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Reference ID</th>
                                <th>Entry Count</th>
                                <th>Entry Types</th>
                                <th>Net Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($duplicates as $dup): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars(ucfirst($dup['reference_type'])) ?></strong></td>
                                <td>#<?= (int)$dup['reference_id'] ?></td>
                                <td><?= (int)$dup['count'] ?> entries</td>
                                <td><small><?= htmlspecialchars($dup['entry_types']) ?></small></td>
                                <td><strong>₹<?= number_format($dup['net_amount'], 2) ?></strong></td>
                                <td><span class="status-badge status-bad">DUPLICATE</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="warning-box">
                        <strong>⚠️ Warning:</strong> Duplicate entries detected. These need to be cleaned up using the cleanup script.
                    </div>
                <?php else: ?>
                    <div style="padding: 1rem; background: #d1fae5; border-radius: 4px; text-align: center;">
                        <strong style="color: #065f46;">✓ No duplicates found! Database is clean.</strong>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php
            
            // ─────────────────────────────────────────────────────────────
            // 3. Code File Analysis
            // ─────────────────────────────────────────────────────────────
            
            $fileStatus = [];
            $files = [
                'ExpenseController.php' => __DIR__ . '/app/controllers/ExpenseController.php',
                'ExpenseController_PATCHED.php' => __DIR__ . '/app/controllers/ExpenseController_PATCHED.php',
                'ExpenseController_FIXED.php' => __DIR__ . '/app/controllers/ExpenseController_FIXED.php',
                'AdvanceController.php' => __DIR__ . '/app/controllers/AdvanceController.php',
                'LedgerHelper.php' => __DIR__ . '/app/helpers/LedgerHelper.php',
            ];
            
            foreach ($files as $name => $path) {
                if (file_exists($path)) {
                    $content = file_get_contents($path);
                    $fileStatus[$name] = [
                        'exists' => true,
                        'size' => filesize($path),
                        'has_ledger_call' => strpos($content, 'recordEntry') !== false,
                        'has_dead_code' => strpos($content, '$ledgerAmount') !== false,
                        'has_critical_comment' => strpos($content, 'CRITICAL: Do NOT create') !== false,
                    ];
                } else {
                    $fileStatus[$name] = ['exists' => false];
                }
            }
            
            ?>
            <div class="section">
                <h2>📁 Code File Analysis</h2>
                
                <table>
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Exists</th>
                            <th>Dead Code</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fileStatus as $name => $status): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($name) ?></strong></td>
                            <td><?= $status['exists'] ? '✓' : '✗' ?></td>
                            <td>
                                <?php if ($status['exists']): ?>
                                    <?php if ($status['has_dead_code']): ?>
                                        <span class="status-badge status-warn">HAS $ledgerAmount</span>
                                    <?php else: ?>
                                        <span class="status-badge status-ok">CLEAN</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$status['exists']): ?>
                                    <span class="status-badge status-bad">MISSING</span>
                                <?php elseif (strpos($name, 'PATCHED') !== false && !$status['has_dead_code']): ?>
                                    <span class="status-badge status-ok">CORRECT</span>
                                <?php elseif (strpos($name, 'ExpenseController.php') === 0 && $status['has_dead_code']): ?>
                                    <span class="status-badge status-bad">STALE</span>
                                <?php elseif ($status['has_critical_comment']): ?>
                                    <span class="status-badge status-ok">PATCHED</span>
                                <?php else: ?>
                                    <span class="status-badge status-warn">CHECK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php
            
            // ─────────────────────────────────────────────────────────────
            // 4. Ledger Entry Count Analysis
            // ─────────────────────────────────────────────────────────────
            
            $totalLedgerRows = (int)$db->query("SELECT COUNT(*) FROM user_ledgers")->fetchColumn();
            $expenseLedgers = (int)$db->query("SELECT COUNT(*) FROM user_ledgers WHERE reference_type='expense'")->fetchColumn();
            $advanceLedgers = (int)$db->query("SELECT COUNT(*) FROM user_ledgers WHERE reference_type='advance'")->fetchColumn();
            $totalExpenses = (int)$db->query("SELECT COUNT(*) FROM expenses WHERE status IN ('approved', 'paid')")->fetchColumn();
            $totalAdvances = (int)$db->query("SELECT COUNT(*) FROM advances WHERE status IN ('approved', 'paid')")->fetchColumn();
            
            $expectedLedgerRows = $totalExpenses + $totalAdvances;
            $actualLedgerRows = $expenseLedgers + $advanceLedgers;
            
            ?>
            <div class="section">
                <h2>📊 Ledger Entry Count Analysis</h2>
                
                <div class="stat">
                    <span class="stat-label">Approved/Paid Expenses:</span>
                    <span class="stat-value"><?= $totalExpenses ?></span>
                </div>
                
                <div class="stat">
                    <span class="stat-label">Approved/Paid Advances:</span>
                    <span class="stat-value"><?= $totalAdvances ?></span>
                </div>
                
                <div class="stat">
                    <span class="stat-label">Expected Ledger Rows (1 per transaction):</span>
                    <span class="stat-value"><?= $expectedLedgerRows ?></span>
                </div>
                
                <div class="stat">
                    <span class="stat-label">Actual Ledger Rows (in user_ledgers):</span>
                    <span class="stat-value <?= $actualLedgerRows > $expectedLedgerRows ? 'critical' : 'success' ?>">
                        <?= $actualLedgerRows ?>
                    </span>
                </div>
                
                <div class="stat">
                    <span class="stat-label">Excess Rows (duplicates):</span>
                    <span class="stat-value <?= ($actualLedgerRows - $expectedLedgerRows) > 0 ? 'critical' : 'success' ?>">
                        <?= max(0, $actualLedgerRows - $expectedLedgerRows) ?>
                    </span>
                </div>
            </div>
            
            <?php
            
            // ─────────────────────────────────────────────────────────────
            // 5. Specific Problem Cases
            // ─────────────────────────────────────────────────────────────
            
            $problemQuery = "
                SELECT 
                    ul.reference_type,
                    ul.reference_id,
                    COUNT(*) as count,
                    GROUP_CONCAT(CONCAT(ul.entry_type, '(', ul.direction, '|', ul.amount, ')') SEPARATOR ' + ') as entries,
                    e.category,
                    e.amount as claimed_amount
                FROM user_ledgers ul
                LEFT JOIN expenses e ON ul.reference_type='expense' AND ul.reference_id=e.id
                LEFT JOIN advances a ON ul.reference_type='advance' AND ul.reference_id=a.id
                WHERE ul.reference_type IN ('expense', 'advance')
                GROUP BY ul.reference_type, ul.reference_id
                HAVING COUNT(*) > 1
                ORDER BY ul.reference_id DESC
                LIMIT 10
            ";
            
            try {
                $stmt = $db->prepare($problemQuery);
                $stmt->execute();
                $problems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($problems) > 0) {
                    ?>
                    <div class="section">
                        <h2>🔴 Problem Transactions</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Reference ID</th>
                                    <th>Entries in Ledger</th>
                                    <th>Entry Breakdown</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($problems as $prob): ?>
                                <tr>
                                    <td><?= htmlspecialchars(ucfirst($prob['reference_type'])) ?></td>
                                    <td>#<?= (int)$prob['reference_id'] ?></td>
                                    <td><strong><?= (int)$prob['count'] ?></strong></td>
                                    <td><small><?= htmlspecialchars($prob['entries']) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                }
            } catch (Exception $e) {
                // Problem query failed
            }
            
            // ─────────────────────────────────────────────────────────────
            // 6. Recommendations
            // ─────────────────────────────────────────────────────────────
            
            $hasProblems = count($duplicates) > 0;
            $needsPatching = $fileStatus['ExpenseController.php']['has_dead_code'] ?? false;
            
            ?>
            <div class="section">
                <h2>📋 Recommendations</h2>
                
                <?php if (!$hasProblems && !$needsPatching): ?>
                    <div style="padding: 1rem; background: #d1fae5; border-radius: 4px;">
                        <strong style="color: #065f46;">✓ System Status: HEALTHY</strong><br>
                        No duplicate ledger entries detected and code is clean.
                    </div>
                <?php else: ?>
                    <ol style="margin-left: 1.5rem; line-height: 1.8;">
                        <?php if ($needsPatching): ?>
                        <li>
                            <strong>Replace ExpenseController.php</strong><br>
                            Copy <code>ExpenseController_PATCHED.php</code> to <code>ExpenseController.php</code>
                            to remove dead code.
                        </li>
                        <?php endif; ?>
                        
                        <?php if ($hasProblems): ?>
                        <li>
                            <strong>Clean Duplicate Ledger Entries</strong><br>
                            Run the cleanup script at:<br>
                            <code>/ergon/migrations/cleanup_duplicate_ledger_entries.php</code>
                        </li>
                        <?php endif; ?>
                        
                        <li>
                            <strong>Verify Results</strong><br>
                            After cleanup, check Owner Ledger and verify no duplicates appear.
                        </li>
                        
                        <li>
                            <strong>Delete This Tool</strong><br>
                            Remove this diagnostic script after use.
                        </li>
                    </ol>
                <?php endif; ?>
            </div>
            
            <?php
            
        } catch (Exception $e) {
            ?>
            <div class="section" style="background: #fee2e2; border: 1px solid #fca5a5;">
                <h2>❌ Error</h2>
                <p><strong style="color: #991b1b;"><?= htmlspecialchars($e->getMessage()) ?></strong></p>
            </div>
            <?php
        }
        
        ?>

    </div>

    <div class="footer">
        <p>🔍 Owner Ledger Diagnostic Tool | Use for troubleshooting only | Delete after use</p>
        <p style="margin-top: 0.5rem; font-size: 0.85rem;">
            For complete analysis, see:<br>
            • ROOT_CAUSE_SUMMARY.md<br>
            • FORENSIC_ANALYSIS_FINAL.md<br>
            • LEDGER_DUPLICATE_IMMEDIATE_FIX.md
        </p>
    </div>

</div>

</body>
</html>

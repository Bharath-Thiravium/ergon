<?php
/**
 * Collation Fix Migration
 * Converts all tables to utf8mb4_unicode_ci to fix UNION collation errors.
 * URL: https://yourdomain.com/ergon/debug/fix_collation.php
 * TEMPORARY — delete after running.
 */
require_once __DIR__ . '/../app/config/environment.php';
require_once __DIR__ . '/../app/config/database.php';

$db = Database::connect();
$results = [];

// Tables that need collation fix
$tables = [
    'task_history',
    'task_progress_history',
    'tasks',
    'users',
    'followups',
    'followup_history',
    'contacts',
    'projects',
    'departments',
    'attendance',
    'leaves',
    'expenses',
    'advances',
    'notifications',
    'activity_logs',
    'settings',
];

foreach ($tables as $table) {
    try {
        // Check table exists
        $exists = $db->query("SHOW TABLES LIKE '{$table}'")->fetchColumn();
        if (!$exists) {
            $results[] = ['table' => $table, 'status' => 'skip', 'msg' => 'Table does not exist'];
            continue;
        }

        // Check current collation
        $row = $db->query("SELECT CCSA.character_set_name, CCSA.collation_name
            FROM information_schema.TABLES T
            JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY CCSA
                ON CCSA.collation_name = T.table_collation
            WHERE T.table_schema = DATABASE()
              AND T.table_name = '{$table}'")->fetch(PDO::FETCH_ASSOC);

        $currentCollation = $row['collation_name'] ?? 'unknown';

        if ($currentCollation === 'utf8mb4_unicode_ci') {
            $results[] = ['table' => $table, 'status' => 'ok', 'msg' => 'Already utf8mb4_unicode_ci'];
            continue;
        }

        // Convert
        $db->exec("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $results[] = ['table' => $table, 'status' => 'fixed', 'msg' => "Converted from {$currentCollation} to utf8mb4_unicode_ci"];

    } catch (Exception $e) {
        $results[] = ['table' => $table, 'status' => 'error', 'msg' => $e->getMessage()];
    }
}

// Also set database default collation
try {
    $dbName = $db->query("SELECT DATABASE()")->fetchColumn();
    $db->exec("ALTER DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $dbResult = "Database '{$dbName}' default collation set to utf8mb4_unicode_ci";
} catch (Exception $e) {
    $dbResult = "Database alter failed: " . $e->getMessage();
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Collation Fix</title>
<style>
body{font-family:system-ui;padding:24px;background:#f9fafb;color:#111827}
table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-top:16px}
th{background:#f3f4f6;padding:10px;text-align:left;font-size:.75rem;color:#6b7280;text-transform:uppercase}
td{padding:10px;border-top:1px solid #f3f4f6;font-size:.875rem}
.fixed{color:#059669;font-weight:600}
.ok{color:#6b7280}
.error{color:#dc2626;font-weight:600}
.skip{color:#9ca3af}
.db-box{background:#d1fae5;border:1px solid #6ee7b7;padding:12px 16px;border-radius:8px;margin-bottom:16px}
.err-box{background:#fee2e2;border:1px solid #fca5a5;padding:12px 16px;border-radius:8px;margin-bottom:16px}
</style>
</head><body>
<h2>🔧 Collation Fix — utf8mb4_unicode_ci</h2>

<div class="<?= str_contains($dbResult, 'failed') ? 'err-box' : 'db-box' ?>">
    <?= htmlspecialchars($dbResult) ?>
</div>

<table>
<thead><tr><th>Table</th><th>Status</th><th>Details</th></tr></thead>
<tbody>
<?php foreach ($results as $r): ?>
<tr>
    <td><code><?= $r['table'] ?></code></td>
    <td class="<?= $r['status'] ?>"><?= match($r['status']) {
        'fixed' => '✅ Fixed',
        'ok'    => '✓ OK',
        'error' => '❌ Error',
        'skip'  => '— Skipped',
    } ?></td>
    <td><?= htmlspecialchars($r['msg']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<p style="margin-top:24px;font-size:.75rem;color:#9ca3af">
    ⚠️ Delete this file after use: <code>/ergon/debug/fix_collation.php</code>
</p>
</body></html>

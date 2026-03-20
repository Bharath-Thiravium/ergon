<?php
/**
 * Migration: Add columns required by admin direct entry & bulk upload
 *
 * Tables affected:
 *   advances  — type, project_id, approved_amount, paid_by, paid_at,
 *               admin_approval, admin_approved_by, admin_approved_at, admin_comments
 *   expenses  — project_id, approved_amount, paid_by, paid_at,
 *               admin_approval, admin_approved_by, admin_approved_at, admin_comments
 */

require_once __DIR__ . '/../app/config/database.php';

$migrations = [
    // ── advances ──────────────────────────────────────────────────────────────
    "ALTER TABLE advances ADD COLUMN type VARCHAR(100) NOT NULL DEFAULT 'General Advance' AFTER user_id",
    "ALTER TABLE advances ADD COLUMN project_id INT NULL AFTER type",
    "ALTER TABLE advances ADD COLUMN approved_amount DECIMAL(10,2) NULL AFTER approved_at",
    "ALTER TABLE advances ADD COLUMN paid_by INT NULL",
    "ALTER TABLE advances ADD COLUMN paid_at DATETIME NULL",
    "ALTER TABLE advances ADD COLUMN admin_approval ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'",
    "ALTER TABLE advances ADD COLUMN admin_approved_by INT NULL",
    "ALTER TABLE advances ADD COLUMN admin_approved_at DATETIME NULL",
    "ALTER TABLE advances ADD COLUMN admin_comments TEXT NULL",

    // ── expenses ──────────────────────────────────────────────────────────────
    "ALTER TABLE expenses ADD COLUMN project_id INT NULL AFTER user_id",
    "ALTER TABLE expenses ADD COLUMN approved_amount DECIMAL(10,2) NULL AFTER approved_at",
    "ALTER TABLE expenses ADD COLUMN paid_by INT NULL",
    "ALTER TABLE expenses ADD COLUMN paid_at DATETIME NULL",
    "ALTER TABLE expenses ADD COLUMN admin_approval ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'",
    "ALTER TABLE expenses ADD COLUMN admin_approved_by INT NULL",
    "ALTER TABLE expenses ADD COLUMN admin_approved_at DATETIME NULL",
    "ALTER TABLE expenses ADD COLUMN admin_comments TEXT NULL",
];

try {
    $db = Database::connect();
    echo "Starting migration: add_admin_entry_columns\n\n";

    $applied = 0;
    $skipped = 0;

    foreach ($migrations as $sql) {
        try {
            $db->exec($sql);
            echo "✓ " . substr($sql, 0, 80) . "\n";
            $applied++;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (
                stripos($msg, 'Duplicate column name') !== false ||
                stripos($msg, 'already exists') !== false
            ) {
                echo "– skipped (already exists): " . substr($sql, 0, 80) . "\n";
                $skipped++;
            } else {
                throw $e;
            }
        }
    }

    echo "\n✅ Migration complete — applied: $applied, skipped: $skipped\n";

    // ── Verification ──────────────────────────────────────────────────────────
    echo "\nVerifying columns...\n";

    $checks = [
        'advances' => ['type', 'project_id', 'approved_amount', 'paid_by', 'paid_at',
                       'admin_approval', 'admin_approved_by', 'admin_approved_at', 'admin_comments'],
        'expenses' => ['project_id', 'approved_amount', 'paid_by', 'paid_at',
                       'admin_approval', 'admin_approved_by', 'admin_approved_at', 'admin_comments'],
    ];

    foreach ($checks as $table => $columns) {
        $existing = $db->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
        $missing  = array_diff($columns, $existing);
        if (empty($missing)) {
            echo "✅ $table — all required columns present\n";
        } else {
            echo "❌ $table — missing: " . implode(', ', $missing) . "\n";
        }
    }

} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . " line " . $e->getLine() . "\n";
    exit(1);
}
?>

<?php
/**
 * Holiday Table Migration Runner
 * Run this once to set up the holidays table
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "========================================\n";
echo "Holiday Table Migration\n";
echo "========================================\n\n";

// Check if migration file exists
$migrationFile = __DIR__ . '/migrations/create_holidays_table.php';

if (!file_exists($migrationFile)) {
    echo "✗ Migration file not found: {$migrationFile}\n";
    exit(1);
}

// Run migration
echo "Running migration...\n\n";
require_once $migrationFile;

echo "\n========================================\n";
echo "Migration Complete!\n";
echo "========================================\n";
echo "\nThe holidays table has been created.\n";
echo "You can now use the Mark Holiday feature.\n";
?>

<?php
/**
 * Migration: Add attachment column to client_ledgers table
 * Run this script on the live server to add the attachment field
 */

require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::connect();
    
    // Check if column already exists
    $cols = $db->query("SHOW COLUMNS FROM client_ledgers LIKE 'attachment'")->fetchAll();
    
    if (empty($cols)) {
        $db->exec("ALTER TABLE client_ledgers ADD COLUMN attachment VARCHAR(500) AFTER reference_no");
        echo "✅ Added 'attachment' column to client_ledgers table.\n";
    } else {
        echo "⚠️ Column 'attachment' already exists.\n";
    }
    
    // Create uploads directory if not exists
    $uploadDir = __DIR__ . '/../public/uploads/client_ledger';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "✅ Created uploads directory: public/uploads/client_ledger\n";
    } else {
        echo "⚠️ Uploads directory already exists.\n";
    }
    
    echo "✅ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

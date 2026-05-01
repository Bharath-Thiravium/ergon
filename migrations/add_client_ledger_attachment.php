<?php
/**
 * Migration: Add attachment column to client_ledgers table
 * Run this script on the live server to add the attachment field
 */

require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::connect();
    
    // Create tables if they don't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS clients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            company_name VARCHAR(255),
            email VARCHAR(255),
            phone VARCHAR(50),
            status ENUM('active','inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Created 'clients' table (if not exists).\n";
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS client_ledgers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            entry_type ENUM('payment_received','payment_sent','adjustment') NOT NULL,
            direction ENUM('debit','credit') NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            balance_after DECIMAL(12,2) NOT NULL,
            description TEXT,
            reference_no VARCHAR(100),
            attachment VARCHAR(500),
            transaction_date DATE NOT NULL,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Created 'client_ledgers' table (if not exists).\n";
    
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

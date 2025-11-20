<?php
// Complete fix for expense approval system
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    echo "🔗 Connected to database successfully.\n\n";
    
    echo "=== STEP 1: Adding missing columns to expenses table ===\n";
    
    // Check and add approved_by column
    $stmt = $db->query("SHOW COLUMNS FROM expenses LIKE 'approved_by'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE expenses ADD COLUMN approved_by INT NULL");
        echo "✓ Added approved_by column\n";
    } else {
        echo "✓ approved_by column already exists\n";
    }
    
    // Check and add journal_entry_id column
    $stmt = $db->query("SHOW COLUMNS FROM expenses LIKE 'journal_entry_id'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE expenses ADD COLUMN journal_entry_id INT NULL");
        echo "✓ Added journal_entry_id column\n";
    } else {
        echo "✓ journal_entry_id column already exists\n";
    }
    
    echo "\n=== STEP 2: Creating accounting tables ===\n";
    
    // Create accounts table
    $db->exec("CREATE TABLE IF NOT EXISTS accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_code VARCHAR(10) NOT NULL UNIQUE,
        account_name VARCHAR(100) NOT NULL,
        account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
        balance DECIMAL(15,2) DEFAULT 0.00,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "✓ Created/verified accounts table\n";
    
    // Create journal_entries table
    $db->exec("CREATE TABLE IF NOT EXISTS journal_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reference_type VARCHAR(50) NOT NULL,
        reference_id INT NOT NULL,
        entry_date DATE NOT NULL,
        description TEXT,
        total_amount DECIMAL(15,2) NOT NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "✓ Created/verified journal_entries table\n";
    
    // Create journal_entry_lines table
    $db->exec("CREATE TABLE IF NOT EXISTS journal_entry_lines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        journal_entry_id INT NOT NULL,
        account_id INT NOT NULL,
        debit_amount DECIMAL(15,2) DEFAULT 0.00,
        credit_amount DECIMAL(15,2) DEFAULT 0.00,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_journal_entry (journal_entry_id),
        INDEX idx_account (account_id)
    )");
    echo "✓ Created/verified journal_entry_lines table\n";
    
    echo "\n=== STEP 3: Setting up default accounts ===\n";
    
    // Insert default accounts
    $defaultAccounts = [
        ['E001', 'General Expenses', 'expense'],
        ['E002', 'Travel Expenses', 'expense'],
        ['E003', 'Office Expenses', 'expense'],
        ['E004', 'Miscellaneous Expenses', 'expense'],
        ['L001', 'Accounts Payable', 'liability'],
        ['A001', 'Cash', 'asset'],
        ['A002', 'Bank Account', 'asset']
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO accounts (account_code, account_name, account_type, balance) VALUES (?, ?, ?, 0.00)");
    
    foreach ($defaultAccounts as $account) {
        $stmt->execute($account);
        echo "✓ Account {$account[0]} - {$account[1]}\n";
    }
    
    echo "\n=== STEP 4: Verification ===\n";
    
    // Verify expenses table columns
    $stmt = $db->query("SHOW COLUMNS FROM expenses WHERE Field IN ('approved_by', 'journal_entry_id')");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Expenses table columns:\n";
    foreach ($columns as $column) {
        echo "  ✓ {$column['Field']} ({$column['Type']})\n";
    }
    
    // Verify accounting tables
    $tables = ['accounts', 'journal_entries', 'journal_entry_lines'];
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ $table table: {$result['count']} records\n";
    }
    
    echo "\n🎉 EXPENSE SYSTEM FIX COMPLETED SUCCESSFULLY!\n";
    echo "You can now test expense approval and rejection at: http://localhost/ergon/expenses\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
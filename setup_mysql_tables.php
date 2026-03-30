<?php
require_once 'app/config/database.php';

echo "Checking MySQL Table Structure\n";
echo "==============================\n\n";

try {
    $config = Database::getPostgreSQLConfig();
    $mysql = $config['mysql'];
    
    $pdo = new PDO(
        "mysql:host={$mysql['host']};port={$mysql['port']};dbname={$mysql['database']};charset=utf8mb4",
        $mysql['username'],
        $mysql['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Check if finance tables exist
    $finance_tables = ['finance_customers', 'finance_quotations', 'finance_purchase_orders', 'finance_invoices', 'finance_payments'];
    
    foreach ($finance_tables as $table) {
        echo "Checking table: $table\n";
        echo str_repeat("-", strlen($table) + 16) . "\n";
        
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            
            echo "✅ Table exists with columns:\n";
            foreach ($columns as $col) {
                echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Table does not exist\n";
            echo "Creating table...\n";
            
            // Create table based on name
            switch ($table) {
                case 'finance_customers':
                    $sql = "CREATE TABLE finance_customers (
                        customer_id INT PRIMARY KEY,
                        customer_code VARCHAR(50),
                        customer_name VARCHAR(255) NOT NULL,
                        display_name VARCHAR(255),
                        email VARCHAR(255),
                        phone VARCHAR(20),
                        customer_gstin VARCHAR(15),
                        is_active BOOLEAN DEFAULT TRUE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    break;
                    
                case 'finance_quotations':
                    $sql = "CREATE TABLE finance_quotations (
                        quotation_id INT PRIMARY KEY,
                        quotation_number VARCHAR(50) NOT NULL,
                        customer_id INT,
                        quotation_amount DECIMAL(15,2),
                        quotation_date DATE,
                        status VARCHAR(50),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (customer_id) REFERENCES finance_customers(customer_id)
                    )";
                    break;
                    
                case 'finance_purchase_orders':
                    $sql = "CREATE TABLE finance_purchase_orders (
                        po_id INT PRIMARY KEY,
                        po_number VARCHAR(50) NOT NULL,
                        customer_id INT,
                        po_total_value DECIMAL(15,2),
                        po_date DATE,
                        po_status VARCHAR(50),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (customer_id) REFERENCES finance_customers(customer_id)
                    )";
                    break;
                    
                case 'finance_invoices':
                    $sql = "CREATE TABLE finance_invoices (
                        invoice_id INT PRIMARY KEY,
                        invoice_number VARCHAR(50) NOT NULL,
                        customer_id INT,
                        total_amount DECIMAL(15,2),
                        taxable_amount DECIMAL(15,2),
                        amount_paid DECIMAL(15,2) DEFAULT 0,
                        igst_amount DECIMAL(15,2) DEFAULT 0,
                        cgst_amount DECIMAL(15,2) DEFAULT 0,
                        sgst_amount DECIMAL(15,2) DEFAULT 0,
                        due_date DATE,
                        invoice_date DATE,
                        status VARCHAR(50),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (customer_id) REFERENCES finance_customers(customer_id)
                    )";
                    break;
                    
                case 'finance_payments':
                    $sql = "CREATE TABLE finance_payments (
                        payment_id INT PRIMARY KEY,
                        payment_number VARCHAR(50),
                        customer_id INT,
                        amount DECIMAL(15,2),
                        payment_date DATE,
                        receipt_number VARCHAR(50),
                        status VARCHAR(50),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (customer_id) REFERENCES finance_customers(customer_id)
                    )";
                    break;
            }
            
            $pdo->exec($sql);
            echo "✅ Table created successfully\n";
        }
        
        echo "\n";
    }
    
    // Check if sync_log table exists
    echo "Checking sync_log table:\n";
    echo "------------------------\n";
    try {
        $stmt = $pdo->query("DESCRIBE sync_log");
        echo "✅ sync_log table exists\n";
    } catch (Exception $e) {
        echo "❌ sync_log table does not exist\n";
        echo "Creating sync_log table...\n";
        
        $sql = "CREATE TABLE sync_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(100) NOT NULL,
            records_synced INT DEFAULT 0,
            sync_status VARCHAR(50) NOT NULL,
            error_message TEXT,
            sync_started_at TIMESTAMP,
            sync_completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        echo "✅ sync_log table created successfully\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ MySQL database structure ready for sync!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
<?php
/**
 * Client Ledger Entry Types Migration
 * Expands entry_type enum to include more essential parameters
 */

require_once __DIR__ . '/app/config/database.php';

function runMigration() {
    try {
        $db = Database::connect();
        
        echo "<h2>Client Ledger Entry Types Migration</h2>";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 8px;'>";
        
        // Check if client_ledgers table exists
        $tableCheck = $db->query("SHOW TABLES LIKE 'client_ledgers'")->fetch();
        if (!$tableCheck) {
            echo "<p style='color: orange;'>⚠️ client_ledgers table does not exist. Creating new table...</p>";
            
            // Create table with new enum values
            $createTable = "
                CREATE TABLE client_ledgers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    client_id INT NOT NULL,
                    entry_type ENUM('payment_received','payment_sent','adjustment','invoice_raised','invoice_received','purchase','sale','expense','income','opening_balance','closing_balance') NOT NULL,
                    direction ENUM('debit','credit') NOT NULL,
                    amount DECIMAL(12,2) NOT NULL,
                    balance_after DECIMAL(12,2) NOT NULL,
                    description TEXT,
                    reference_no VARCHAR(100),
                    attachment VARCHAR(500),
                    transaction_date DATE NOT NULL,
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_client_date (client_id, transaction_date),
                    INDEX idx_entry_type (entry_type),
                    INDEX idx_reference (reference_no)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            
            $db->exec($createTable);
            echo "<p style='color: green;'>✅ Created client_ledgers table with expanded entry types</p>";
        } else {
            echo "<p style='color: blue;'>📋 client_ledgers table exists. Updating entry_type enum...</p>";
            
            // Get current enum values
            $columnInfo = $db->query("SHOW COLUMNS FROM client_ledgers LIKE 'entry_type'")->fetch();
            echo "<p>Current enum: " . htmlspecialchars($columnInfo['Type']) . "</p>";
            
            // Update enum to include new values
            $alterTable = "
                ALTER TABLE client_ledgers 
                MODIFY COLUMN entry_type ENUM(
                    'payment_received',
                    'payment_sent',
                    'adjustment',
                    'invoice_raised',
                    'invoice_received',
                    'purchase',
                    'sale',
                    'expense',
                    'income',
                    'opening_balance',
                    'closing_balance',
                    'fees_paid',
                    'penalties_paid'
                ) NOT NULL
            ";
            
            $db->exec($alterTable);
            echo "<p style='color: green;'>✅ Updated entry_type enum with new values</p>";
        }
        
        // Verify the update
        $columnInfo = $db->query("SHOW COLUMNS FROM client_ledgers LIKE 'entry_type'")->fetch();
        echo "<p style='color: green;'>✅ Final enum: " . htmlspecialchars($columnInfo['Type']) . "</p>";
        
        // Show current entry type distribution
        echo "<h3>Current Entry Type Distribution:</h3>";
        $stats = $db->query("
            SELECT entry_type, COUNT(*) as count 
            FROM client_ledgers 
            GROUP BY entry_type 
            ORDER BY count DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($stats)) {
            echo "<p style='color: #666;'>No entries found in the table.</p>";
        } else {
            echo "<table style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #e5e5e5;'><th style='padding: 8px; border: 1px solid #ccc;'>Entry Type</th><th style='padding: 8px; border: 1px solid #ccc;'>Count</th></tr>";
            foreach ($stats as $stat) {
                echo "<tr><td style='padding: 8px; border: 1px solid #ccc;'>" . htmlspecialchars($stat['entry_type']) . "</td>";
                echo "<td style='padding: 8px; border: 1px solid #ccc; text-align: center;'>" . $stat['count'] . "</td></tr>";
            }
            echo "</table>";
        }
        
        echo "<h3>Available Entry Types:</h3>";
        $entryTypes = [
            'payment_received' => 'Payment Received (Credit — money IN)',
            'payment_sent' => 'Payment Sent (Debit — money OUT)',
            'invoice_raised' => 'Invoice Raised (Debit — bill sent to client)',
            'invoice_received' => 'Invoice Received (Credit — bill from supplier)',
            'purchase' => 'Purchase (Debit — goods/services bought)',
            'sale' => 'Sale (Debit — goods/services sold)',
            'expense' => 'Expense (Debit — business expense)',
            'income' => 'Income (Credit — business income)',
            'fees_paid' => 'Fees Paid (Debit — fees/charges paid)',
            'penalties_paid' => 'Penalties Paid (Debit — penalties/fines paid)',
            'opening_balance' => 'Opening Balance (configurable direction)',
            'closing_balance' => 'Closing Balance (configurable direction)',
            'adjustment' => 'Adjustment (configurable direction)'
        ];
        
        echo "<ul style='margin: 10px 0; padding-left: 20px;'>";
        foreach ($entryTypes as $type => $description) {
            echo "<li style='margin: 5px 0;'><strong>" . htmlspecialchars($type) . "</strong>: " . htmlspecialchars($description) . "</li>";
        }
        echo "</ul>";
        
        echo "<p style='color: green; font-weight: bold; margin-top: 20px;'>🎉 Migration completed successfully!</p>";
        echo "<p><a href='/ergon/client-ledger' style='color: #1d4ed8; text-decoration: none;'>→ Go to Client Ledger</a></p>";
        
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='color: red; background: #fee; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "<h3>❌ Migration Failed</h3>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "</div>";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Client Ledger Entry Types Migration</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 40px; background: #f9fafb; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { color: #1f2937; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #1d4ed8; color: white; text-decoration: none; border-radius: 6px; border: none; cursor: pointer; }
        .btn:hover { background: #1e40af; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Client Ledger Entry Types Migration</h1>
        <p>This migration will expand the client ledger entry types to include more essential parameters like invoices, purchases, sales, expenses, and income.</p>
        
        <?php if (isset($_GET['run'])): ?>
            <?php runMigration(); ?>
        <?php else: ?>
            <div style="background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h3 style="margin-top: 0;">⚠️ Before Running Migration</h3>
                <ul>
                    <li>This will modify the database structure</li>
                    <li>Existing data will be preserved</li>
                    <li>New entry types will be available for future entries</li>
                    <li>Make sure you have a database backup</li>
                </ul>
            </div>
            
            <a href="?run=1" class="btn">Run Migration</a>
        <?php endif; ?>
    </div>
</body>
</html>
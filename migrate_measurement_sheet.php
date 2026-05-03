<?php
/**
 * Measurement Sheet Migration Script
 * Run this in browser to create/update measurement sheet tables
 * URL: http://localhost/ergon/migrate_measurement_sheet.php
 */

// Database configuration
require_once __DIR__ . '/app/config/database.php';

// Set execution time limit
set_time_limit(300);

// Start output buffering for better display
ob_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Measurement Sheet Migration</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; }
        .success { background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px 16px; border-radius: 8px; margin: 10px 0; }
        .error { background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin: 10px 0; }
        .info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 12px 16px; border-radius: 8px; margin: 10px 0; }
        .warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 12px 16px; border-radius: 8px; margin: 10px 0; }
        .sql-block { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 12px; margin: 10px 0; overflow-x: auto; }
        .step { margin: 20px 0; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px; }
        .step-title { font-weight: 700; font-size: 16px; color: #111827; margin-bottom: 10px; }
        .btn { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 10px 5px 0 0; }
        .btn:hover { background: #1d4ed8; }
        .btn-success { background: #059669; }
        .btn-success:hover { background: #047857; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #b91c1c; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">📊 Measurement Sheet Migration</h1>
            <p style="margin: 10px 0 0; opacity: 0.9;">Database setup and migration for RA Bills & Measurement Sheets</p>
        </div>
        <div class="content">

<?php

$results = [];
$errors = [];

try {
    $db = Database::connect();
    
    // Migration steps
    $migrations = [
        [
            'name' => 'Create RA Bills Table',
            'sql' => "CREATE TABLE IF NOT EXISTS `ra_bills` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `po_id` int(11) NOT NULL,
                `po_number` varchar(100) DEFAULT NULL,
                `company_id` int(11) DEFAULT NULL,
                `customer_id` int(11) DEFAULT NULL,
                `ra_bill_number` varchar(50) NOT NULL,
                `ra_sequence` int(11) NOT NULL DEFAULT 1,
                `bill_date` date NOT NULL,
                `project` varchar(255) DEFAULT NULL,
                `contractor` varchar(255) DEFAULT NULL,
                `notes` text DEFAULT NULL,
                `total_claimed` decimal(15,2) DEFAULT 0.00,
                `status` enum('draft','submitted','approved','rejected','paid') DEFAULT 'draft',
                `selected_logo` varchar(100) DEFAULT NULL,
                `selected_seal` varchar(100) DEFAULT NULL,
                `selected_client_logo` varchar(100) DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_po_id` (`po_id`),
                KEY `idx_ra_bill_number` (`ra_bill_number`),
                KEY `idx_status` (`status`),
                KEY `idx_bill_date` (`bill_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ],
        [
            'name' => 'Create RA Bill Items Table',
            'sql' => "CREATE TABLE IF NOT EXISTS `ra_bill_items` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ra_bill_id` int(11) NOT NULL,
                `po_item_id` int(11) NOT NULL,
                `line_number` int(11) NOT NULL,
                `product_name` varchar(255) NOT NULL,
                `description` text DEFAULT NULL,
                `unit` varchar(50) DEFAULT NULL,
                `po_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
                `po_unit_price` decimal(15,4) NOT NULL DEFAULT 0.0000,
                `po_line_total` decimal(15,2) NOT NULL DEFAULT 0.00,
                `prev_claimed_qty` decimal(15,4) NOT NULL DEFAULT 0.0000,
                `prev_claimed_pct` decimal(8,4) NOT NULL DEFAULT 0.0000,
                `prev_claimed_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                `claim_type` enum('quantity','percentage') DEFAULT 'quantity',
                `this_qty` decimal(15,4) NOT NULL DEFAULT 0.0000,
                `this_pct` decimal(8,4) NOT NULL DEFAULT 0.0000,
                `this_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                `cumulative_qty` decimal(15,4) NOT NULL DEFAULT 0.0000,
                `cumulative_pct` decimal(8,4) NOT NULL DEFAULT 0.0000,
                `cumulative_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                `remarks` text DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_ra_bill_id` (`ra_bill_id`),
                KEY `idx_po_item_id` (`po_item_id`),
                KEY `idx_line_number` (`line_number`),
                CONSTRAINT `fk_ra_bill_items_ra_bill` FOREIGN KEY (`ra_bill_id`) REFERENCES `ra_bills` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ],
        [
            'name' => 'Add Indexes for Performance',
            'sql' => null,
            'php_action' => function() use ($db) {
                $indexes = [
                    "CREATE INDEX idx_po_sequence ON ra_bills (po_id, ra_sequence)",
                    "CREATE INDEX idx_created_at ON ra_bills (created_at)", 
                    "CREATE INDEX idx_company_customer ON ra_bills (company_id, customer_id)"
                ];
                
                $results = [];
                foreach ($indexes as $sql) {
                    try {
                        $db->exec($sql);
                        $results[] = "Added: " . substr($sql, 13, 30) . "...";
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                            $results[] = "Already exists: " . substr($sql, 13, 30) . "...";
                        } else {
                            throw $e;
                        }
                    }
                }
                return implode(', ', $results);
            }
        ],
        [
            'name' => 'Add Indexes for RA Bill Items',
            'sql' => null,
            'php_action' => function() use ($db) {
                $indexes = [
                    "CREATE INDEX idx_claim_type ON ra_bill_items (claim_type)",
                    "CREATE INDEX idx_cumulative_qty ON ra_bill_items (cumulative_qty)",
                    "CREATE INDEX idx_this_amount ON ra_bill_items (this_amount)"
                ];
                
                $results = [];
                foreach ($indexes as $sql) {
                    try {
                        $db->exec($sql);
                        $results[] = "Added: " . substr($sql, 13, 30) . "...";
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                            $results[] = "Already exists: " . substr($sql, 13, 30) . "...";
                        } else {
                            throw $e;
                        }
                    }
                }
                return implode(', ', $results);
            }
        ],
        [
            'name' => 'Create Storage Directories',
            'sql' => null, // Special case - handled in PHP
            'php_action' => function() {
                $dirs = [
                    __DIR__ . '/storage',
                    __DIR__ . '/storage/company',
                    __DIR__ . '/storage/company/logos',
                    __DIR__ . '/storage/company/seals',
                    __DIR__ . '/storage/client',
                    __DIR__ . '/storage/client/logos'
                ];
                
                $created = [];
                foreach ($dirs as $dir) {
                    if (!is_dir($dir)) {
                        if (mkdir($dir, 0755, true)) {
                            $created[] = $dir;
                        }
                    }
                }
                
                return $created ? "Created directories: " . implode(', ', $created) : "All directories already exist";
            }
        ],
        [
            'name' => 'Update RA Bills - Add Media Columns (if not exists)',
            'sql' => null,
            'php_action' => function() use ($db) {
                $columns = [
                    ['selected_logo', 'varchar(100) DEFAULT NULL'],
                    ['selected_seal', 'varchar(100) DEFAULT NULL'],
                    ['selected_client_logo', 'varchar(100) DEFAULT NULL']
                ];
                
                $results = [];
                foreach ($columns as [$col, $def]) {
                    try {
                        // Check if column exists
                        $stmt = $db->query("SHOW COLUMNS FROM ra_bills LIKE '{$col}'");
                        if ($stmt->rowCount() == 0) {
                            $db->exec("ALTER TABLE ra_bills ADD COLUMN {$col} {$def}");
                            $results[] = "Added column: {$col}";
                        } else {
                            $results[] = "Column exists: {$col}";
                        }
                    } catch (Exception $e) {
                        throw new Exception("Error with column {$col}: " . $e->getMessage());
                    }
                }
                return implode(', ', $results);
            }
        ],
        [
            'name' => 'Update RA Bill Items - Add Claim Type (if not exists)',
            'sql' => null,
            'php_action' => function() use ($db) {
                try {
                    // Check if column exists
                    $stmt = $db->query("SHOW COLUMNS FROM ra_bill_items LIKE 'claim_type'");
                    if ($stmt->rowCount() == 0) {
                        $db->exec("ALTER TABLE ra_bill_items ADD COLUMN claim_type enum('quantity','percentage') DEFAULT 'quantity' AFTER prev_claimed_amount");
                        return "Added claim_type column";
                    } else {
                        return "Column claim_type already exists";
                    }
                } catch (Exception $e) {
                    throw new Exception("Error adding claim_type: " . $e->getMessage());
                }
            }
        ],
        [
            'name' => 'Verify Table Structure',
            'sql' => "SHOW CREATE TABLE ra_bills",
            'verify_only' => true
        ],
        [
            'name' => 'Verify Items Table Structure',
            'sql' => "SHOW CREATE TABLE ra_bill_items",
            'verify_only' => true
        ]
    ];

    echo "<div class='info'><strong>🚀 Starting Migration Process...</strong><br>Total steps: " . count($migrations) . "</div>";

    foreach ($migrations as $index => $migration) {
        $stepNum = $index + 1;
        echo "<div class='step'>";
        echo "<div class='step-title'>Step {$stepNum}: {$migration['name']}</div>";
        
        try {
            if (isset($migration['php_action'])) {
                // Handle PHP actions
                $result = $migration['php_action']();
                echo "<div class='success'>✅ {$result}</div>";
            } else {
                // Handle SQL
                echo "<div class='sql-block'>" . htmlspecialchars($migration['sql']) . "</div>";
                
                if (isset($migration['verify_only']) && $migration['verify_only']) {
                    // Verification query
                    $stmt = $db->query($migration['sql']);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "<div class='success'>✅ Table structure verified</div>";
                    if ($result) {
                        echo "<div class='info'>📋 Structure: " . htmlspecialchars(substr($result['Create Table'], 0, 200)) . "...</div>";
                    }
                } else {
                    // Execute migration
                    $db->exec($migration['sql']);
                    echo "<div class='success'>✅ Executed successfully</div>";
                }
            }
            
            $results[] = "Step {$stepNum}: {$migration['name']} - SUCCESS";
            
        } catch (Exception $e) {
            $error = "Step {$stepNum}: {$migration['name']} - ERROR: " . $e->getMessage();
            $errors[] = $error;
            echo "<div class='error'>❌ {$error}</div>";
        }
        
        echo "</div>";
    }

    // Final verification
    echo "<div class='step'>";
    echo "<div class='step-title'>Final Verification</div>";
    
    try {
        // Check if tables exist and have data structure
        $tables = ['ra_bills', 'ra_bill_items'];
        foreach ($tables as $table) {
            $stmt = $db->query("DESCRIBE {$table}");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<div class='success'>✅ Table '{$table}' exists with " . count($columns) . " columns</div>";
        }
        
        // Check record counts
        $stmt = $db->query("SELECT COUNT(*) as count FROM ra_bills");
        $raBillCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM ra_bill_items");
        $raItemCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<div class='info'>📊 Current data: {$raBillCount} RA Bills, {$raItemCount} RA Bill Items</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Verification failed: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ <strong>Critical Error:</strong> " . $e->getMessage() . "</div>";
    $errors[] = "Critical Error: " . $e->getMessage();
}

// Summary
echo "<div class='step'>";
echo "<div class='step-title'>📋 Migration Summary</div>";

if (empty($errors)) {
    echo "<div class='success'>";
    echo "<strong>🎉 Migration Completed Successfully!</strong><br>";
    echo "All " . count($results) . " steps executed without errors.<br><br>";
    echo "<strong>✅ What was created/updated:</strong><br>";
    echo "• RA Bills table with all required columns<br>";
    echo "• RA Bill Items table with measurement data<br>";
    echo "• Database indexes for performance<br>";
    echo "• Storage directories for company media<br>";
    echo "• Foreign key constraints for data integrity<br>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<strong>🚀 Next Steps:</strong><br>";
    echo "1. The measurement sheet system is now ready to use<br>";
    echo "2. You can create RA bills from the Finance module<br>";
    echo "3. Upload company logos and seals via the media management<br>";
    echo "4. Import opening balances for existing POs if needed<br>";
    echo "</div>";
    
} else {
    echo "<div class='error'>";
    echo "<strong>⚠️ Migration completed with " . count($errors) . " error(s):</strong><br>";
    foreach ($errors as $error) {
        echo "• " . htmlspecialchars($error) . "<br>";
    }
    echo "</div>";
    
    if (!empty($results)) {
        echo "<div class='success'>";
        echo "<strong>✅ Successful steps (" . count($results) . "):</strong><br>";
        foreach ($results as $result) {
            echo "• " . htmlspecialchars($result) . "<br>";
        }
        echo "</div>";
    }
}

echo "</div>";

?>

            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <a href="/ergon/finance/measurement-sheet" class="btn btn-success">🏠 Go to Measurement Sheet</a>
                <a href="/ergon" class="btn">🔙 Back to Dashboard</a>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">🔄 Run Migration Again</a>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 8px; font-size: 12px; color: #6b7280;">
                <strong>Migration Info:</strong><br>
                • Executed at: <?php echo date('Y-m-d H:i:s'); ?><br>
                • Database: <?php echo defined('DB_NAME') ? DB_NAME : 'Unknown'; ?><br>
                • PHP Version: <?php echo PHP_VERSION; ?><br>
                • Memory Usage: <?php echo round(memory_get_peak_usage() / 1024 / 1024, 2); ?> MB
            </div>
        </div>
    </div>
</body>
</html>

<?php
// End output buffering and send to browser
ob_end_flush();
?>
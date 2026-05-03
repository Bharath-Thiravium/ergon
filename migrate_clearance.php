<?php
/**
 * Clearance Items Migration
 * Run this to add clearance functionality to measurement sheets
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h2>📋 Clearance Items Migration</h2>";

try {
    $db = Database::connect();
    
    // Create clearance_items table
    echo "<h3>1. Creating Clearance Items Table</h3>";
    $sql = "CREATE TABLE IF NOT EXISTS `clearance_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `ra_bill_id` int(11) NOT NULL,
        `department` varchar(100) NOT NULL,
        `checklist_items` text DEFAULT NULL,
        `comments` text DEFAULT NULL,
        `incharge` varchar(100) DEFAULT NULL,
        `signature` varchar(255) DEFAULT NULL,
        `status` tinyint(1) DEFAULT 0,
        `cleared_at` timestamp NULL DEFAULT NULL,
        `cleared_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_ra_bill_id` (`ra_bill_id`),
        KEY `idx_department` (`department`),
        KEY `idx_status` (`status`),
        CONSTRAINT `fk_clearance_ra_bill` FOREIGN KEY (`ra_bill_id`) REFERENCES `ra_bills` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "<p style='color: green;'>✅ Clearance items table created</p>";
    
    // Insert default clearance items for existing RA bills
    echo "<h3>2. Adding Default Clearance Items</h3>";
    
    $clearanceItems = [
        [
            'department' => 'Quality Clearance',
            'checklist_items' => '• Cube test\n• Quality in Execution & Violation Debit\n• Material Consumption & Reconciliation'
        ],
        [
            'department' => 'Safety Clearance', 
            'checklist_items' => '• PPE Debit\n• Safety Violation Debit\n• Safety violation closing (NCR)'
        ],
        [
            'department' => 'Store Clearance',
            'checklist_items' => '• Material Issue Debits\n• Material Reconciliation\n• Store No Dues Certificate'
        ],
        [
            'department' => 'HR & Admin Clearance',
            'checklist_items' => '• Payment List of Subcontractor\n• Local labour proof of paid social insurance\n• Proof of paid PIT\n• Submission of ESI and PF\n• Insurance of local staff / labour'
        ],
        [
            'department' => 'Site Engineer',
            'checklist_items' => '• Any Pending Works\n• Any Punch Points'
        ],
        [
            'department' => 'DGM / PM Projects Clearance',
            'checklist_items' => '• No Due Certificate\n• Work Completion Certificate\n• Certificate of Takeover'
        ]
    ];
    
    // Get existing RA bills
    $stmt = $db->query("SELECT id FROM ra_bills WHERE ra_bill_number != 'RA-00'");
    $raBills = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($raBills)) {
        $insertStmt = $db->prepare("INSERT INTO clearance_items (ra_bill_id, department, checklist_items) VALUES (?, ?, ?)");
        
        $totalInserted = 0;
        foreach ($raBills as $raBillId) {
            foreach ($clearanceItems as $item) {
                // Check if already exists
                $checkStmt = $db->prepare("SELECT id FROM clearance_items WHERE ra_bill_id = ? AND department = ?");
                $checkStmt->execute([$raBillId, $item['department']]);
                
                if (!$checkStmt->fetchColumn()) {
                    $insertStmt->execute([$raBillId, $item['department'], $item['checklist_items']]);
                    $totalInserted++;
                }
            }
        }
        
        echo "<p style='color: green;'>✅ Added {$totalInserted} clearance items for existing RA bills</p>";
    } else {
        echo "<p>ℹ️ No existing RA bills found</p>";
    }
    
    // Create clearance management functions
    echo "<h3>3. Testing Clearance Functionality</h3>";
    
    // Test query
    $stmt = $db->query("SELECT COUNT(*) as total FROM clearance_items");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>📊 Total clearance items: {$total}</p>";
    
    // Show sample data
    $stmt = $db->query("SELECT ci.*, rb.ra_bill_number FROM clearance_items ci 
                       JOIN ra_bills rb ON rb.id = ci.ra_bill_id 
                       LIMIT 5");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($samples)) {
        echo "<h4>Sample Clearance Items:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>RA Bill</th><th>Department</th><th>Status</th></tr>";
        foreach ($samples as $sample) {
            $status = $sample['status'] ? '✅ Cleared' : '⏳ Pending';
            echo "<tr><td>{$sample['ra_bill_number']}</td><td>{$sample['department']}</td><td>{$status}</td></tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>4. Success!</h3>";
    echo "<p style='color: green; font-weight: bold;'>✅ Clearance system is now ready!</p>";
    echo "<p><a href='/ergon/finance/measurement-sheet/15/clearance-print'>🔗 Test Clearance Print</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background: #f0f0f0; }
</style>
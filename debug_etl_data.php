<?php
require_once __DIR__ . '/app/config/database.php';

echo "🔍 ETL Data Debug\n";
echo "================\n\n";

try {
    $db = Database::connect();
    
    // Check consolidated table data
    echo "📊 Consolidated Table Data:\n";
    $stmt = $db->query("SELECT record_type, COUNT(*) as count, SUM(amount) as total FROM finance_consolidated GROUP BY record_type");
    while ($row = $stmt->fetch()) {
        echo "- {$row['record_type']}: {$row['count']} records, ₹{$row['total']}\n";
    }
    
    echo "\n📋 Dashboard Stats:\n";
    $stmt = $db->query("SELECT * FROM dashboard_stats ORDER BY generated_at DESC LIMIT 1");
    $stats = $stmt->fetch();
    if ($stats) {
        echo "- Total Revenue: ₹{$stats['total_revenue']}\n";
        echo "- Outstanding: ₹{$stats['outstanding_amount']}\n";
        echo "- PO Commitments: ₹{$stats['po_commitments']}\n";
        echo "- GST Liability: ₹{$stats['gst_liability']}\n";
        echo "- Generated: {$stats['generated_at']}\n";
    }
    
    echo "\n🔍 Sample Records:\n";
    $stmt = $db->query("SELECT record_type, document_number, amount, status FROM finance_consolidated LIMIT 5");
    while ($row = $stmt->fetch()) {
        echo "- {$row['record_type']}: {$row['document_number']} = ₹{$row['amount']} ({$row['status']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
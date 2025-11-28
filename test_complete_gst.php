<?php
echo "Complete GST Liability Test\n";
echo "==========================\n\n";

echo "1. Testing refresh-stats endpoint...\n";
$response = @file_get_contents('http://localhost/ergon/finance/refresh-stats');
if ($response) {
    $result = json_decode($response, true);
    if ($result && $result['success']) {
        echo "   ✓ Refresh successful: " . $result['message'] . "\n\n";
    } else {
        echo "   ✗ Refresh failed: " . ($result['error'] ?? 'Unknown error') . "\n\n";
    }
} else {
    echo "   ✗ Could not connect to refresh endpoint\n\n";
}

echo "2. Testing dashboard API...\n";
$dashboardResponse = @file_get_contents('http://localhost/ergon/finance/dashboard-stats');
if ($dashboardResponse) {
    $data = json_decode($dashboardResponse, true);
    if ($data) {
        echo "   ✓ Dashboard API working\n";
        echo "   GST Liability Fields:\n";
        echo "     IGST Liability: ₹" . number_format($data['igstLiability'] ?? 0, 2) . "\n";
        echo "     CGST+SGST Total: ₹" . number_format($data['cgstSgstTotal'] ?? 0, 2) . "\n";
        echo "     Total GST Liability: ₹" . number_format($data['gstLiability'] ?? 0, 2) . "\n";
        
        if (($data['gstLiability'] ?? 0) > 0) {
            echo "\n✓ Stat Card 4 GST Liability is working correctly!\n";
        } else {
            echo "\n! GST Liability is zero in API response\n";
        }
    } else {
        echo "   ✗ Invalid JSON response\n";
    }
} else {
    echo "   ✗ Could not connect to dashboard API\n";
}
?>
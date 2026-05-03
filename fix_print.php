<?php
// Quick fix for measurement sheet print issues
require_once __DIR__ . '/app/config/database.php';

echo "<h2>🔧 Quick Fix: Measurement Sheet Print Issues</h2>";

try {
    $db = Database::connect();
    
    // 1. Add missing column if it doesn't exist
    echo "<h3>1. Adding Missing Database Column</h3>";
    try {
        $stmt = $db->query("SHOW COLUMNS FROM ra_bills LIKE 'selected_client_logo'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE ra_bills ADD COLUMN selected_client_logo varchar(100) DEFAULT NULL");
            echo "<p style='color: green;'>✅ Added selected_client_logo column</p>";
        } else {
            echo "<p>✅ Column selected_client_logo already exists</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
    // 2. Create directories
    echo "<h3>2. Creating Storage Directories</h3>";
    $dirs = [
        __DIR__ . '/storage/client',
        __DIR__ . '/storage/client/logos'
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<p style='color: green;'>✅ Created: $dir</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create: $dir</p>";
            }
        } else {
            echo "<p>✅ Exists: $dir</p>";
        }
    }
    
    // 3. Test RA Bill 14
    echo "<h3>3. Testing RA Bill 14</h3>";
    $stmt = $db->prepare("SELECT id, ra_bill_number, selected_logo, selected_seal, selected_client_logo FROM ra_bills WHERE id = 14");
    $stmt->execute();
    $ra = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ra) {
        echo "<p>✅ RA Bill found: " . $ra['ra_bill_number'] . "</p>";
        echo "<p>Logo: " . ($ra['selected_logo'] ?: 'None') . "</p>";
        echo "<p>Seal: " . ($ra['selected_seal'] ?: 'None') . "</p>";
        echo "<p>Client Logo: " . ($ra['selected_client_logo'] ?: 'None') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ RA Bill 14 not found</p>";
    }
    
    // 4. Fix the update media method by making it more robust
    echo "<h3>4. Testing Update Query</h3>";
    try {
        // Test if we can update without client logo column
        $stmt = $db->prepare("UPDATE ra_bills SET selected_logo = ?, selected_seal = ? WHERE id = 14");
        $stmt->execute(['test_logo', 'test_seal']);
        echo "<p style='color: green;'>✅ Basic update works</p>";
        
        // Test with client logo column
        $stmt = $db->prepare("UPDATE ra_bills SET selected_logo = ?, selected_seal = ?, selected_client_logo = ? WHERE id = 14");
        $stmt->execute(['test_logo', 'test_seal', 'test_client']);
        echo "<p style='color: green;'>✅ Full update works</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Update error: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h3>5. Test Links</h3>";
echo "<p><a href='/ergon/finance/measurement-sheet/14/print' target='_blank'>🖨️ Test Basic Print</a></p>";
echo "<p><a href='/ergon/finance/measurement-sheet/14/clearance-print' target='_blank'>📋 Test Clearance Print</a></p>";
echo "<p><a href='/ergon/finance/measurement-sheet/14/select-media'>⚙️ Back to Media Selection</a></p>";

echo "<h3>6. Manual Test Form</h3>";
?>
<form method="POST" action="/ergon/finance/measurement-sheet/14/update-media" style="border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
    <h4>Test Media Update</h4>
    <p>
        <label>Print Type:</label><br>
        <input type="radio" name="print_type" value="basic" checked> Basic Print<br>
        <input type="radio" name="print_type" value="clearance"> Clearance Print
    </p>
    <p>
        <input type="hidden" name="selected_logo" value="default">
        <input type="hidden" name="selected_seal" value="default">
        <input type="hidden" name="selected_client_logo" value="default">
        <button type="submit" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px;">
            Test Update & Print
        </button>
    </p>
</form>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 5px 0; }
</style>
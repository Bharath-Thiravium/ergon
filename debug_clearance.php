<?php
// Debug script for clearance print issue
require_once __DIR__ . '/app/config/database.php';

echo "<h2>Debug: Clearance Print Issue</h2>";

try {
    $db = Database::connect();
    
    // Check if selected_client_logo column exists
    echo "<h3>1. Checking Database Structure</h3>";
    $stmt = $db->query("DESCRIBE ra_bills");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasClientLogo = false;
    echo "<table border='1'><tr><th>Column</th><th>Type</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
        if ($col['Field'] === 'selected_client_logo') {
            $hasClientLogo = true;
        }
    }
    echo "</table>";
    
    echo "<p><strong>Has selected_client_logo column:</strong> " . ($hasClientLogo ? "YES" : "NO") . "</p>";
    
    // Check routes
    echo "<h3>2. Testing Routes</h3>";
    echo "<p>Current URL: " . $_SERVER['REQUEST_URI'] . "</p>";
    echo "<p>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
    
    // Check if RA bill 14 exists
    echo "<h3>3. Checking RA Bill 14</h3>";
    $stmt = $db->prepare("SELECT * FROM ra_bills WHERE id = 14");
    $stmt->execute();
    $ra = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ra) {
        echo "<p><strong>RA Bill found:</strong></p>";
        echo "<pre>" . print_r($ra, true) . "</pre>";
    } else {
        echo "<p><strong>RA Bill 14 not found!</strong></p>";
    }
    
    // Add missing column if needed
    if (!$hasClientLogo) {
        echo "<h3>4. Adding Missing Column</h3>";
        try {
            $db->exec("ALTER TABLE ra_bills ADD COLUMN selected_client_logo varchar(100) DEFAULT NULL");
            echo "<p style='color: green;'>✅ Added selected_client_logo column successfully</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error adding column: " . $e->getMessage() . "</p>";
        }
    }
    
    // Create client logos directory
    echo "<h3>5. Creating Client Logos Directory</h3>";
    $clientDir = __DIR__ . '/storage/client/logos';
    if (!is_dir($clientDir)) {
        if (mkdir($clientDir, 0755, true)) {
            echo "<p style='color: green;'>✅ Created client logos directory</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create client logos directory</p>";
        }
    } else {
        echo "<p>✅ Client logos directory already exists</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>6. Test Links</h3>";
echo "<a href='/ergon/finance/measurement-sheet/14/clearance-print'>Test Clearance Print</a><br>";
echo "<a href='/ergon/finance/measurement-sheet/14/print'>Test Regular Print</a><br>";
echo "<a href='/ergon/finance/measurement-sheet/14/select-media'>Back to Media Selection</a>";
?>
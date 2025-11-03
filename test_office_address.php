<?php
require_once __DIR__ . '/app/config/database.php';

echo "<h1>Office Address Field Test</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    $db = Database::connect();
    
    if ($_POST) {
        // Test saving office_address
        $stmt = $db->prepare("UPDATE settings SET office_address = ? WHERE id = 1");
        $result = $stmt->execute([$_POST['office_address']]);
        
        if ($result) {
            echo "<span class='success'>✅ Office address saved: " . htmlspecialchars($_POST['office_address']) . "</span><br>";
        } else {
            echo "<span class='error'>❌ Failed to save office address</span><br>";
        }
    }
    
    // Get current office_address
    $stmt = $db->query("SELECT office_address FROM settings LIMIT 1");
    $current = $stmt->fetchColumn();
    
    echo "<h2>Current office_address in database:</h2>";
    echo "<span class='success'>" . htmlspecialchars($current ?: 'Empty') . "</span><br><br>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: {$e->getMessage()}</span><br>";
}
?>

<form method="POST">
    <label>Test Office Address:</label><br>
    <input type="text" name="office_address" value="<?= htmlspecialchars($current ?? '') ?>" style="width:400px;padding:5px;"><br><br>
    <button type="submit">Save Test</button>
</form>

<p><a href="/ergon/settings">Go to Settings Page</a></p>
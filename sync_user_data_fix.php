<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>User Data Synchronization Fix</h2>";
    
    // Update Harini's role to match what was expected in frontend
    $updateHarini = $db->prepare("UPDATE users SET role = 'admin' WHERE id = 16 AND name LIKE '%Harini%'");
    $result1 = $updateHarini->execute();
    
    if ($result1) {
        echo "<p>✅ Updated Harini (ID: 16) role to admin</p>";
    }
    
    // Verify the current state
    $stmt = $db->query("SELECT id, name, email, role, status FROM users WHERE id IN (16, 37, 57, 58) ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Key Users Status:</h3>";
    foreach ($users as $user) {
        $roleIcon = $user['role'] === 'owner' ? '👑' : ($user['role'] === 'admin' ? '🔑' : '👤');
        echo "<p>{$roleIcon} {$user['name']} (ID: {$user['id']}) - {$user['email']} - " . ucfirst($user['role']) . " - " . ucfirst($user['status']) . "</p>";
    }
    
    echo "<h3>✅ Frontend should now display correctly:</h3>";
    echo "<ul>";
    echo "<li>👑 Athenas Owner (ID: 1) - Owner</li>";
    echo "<li>🔑 Nelson (ID: 37) - Admin</li>";
    echo "<li>🔑 Harini (ID: 16) - Admin</li>";
    echo "<li>👤 Other users as regular users</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
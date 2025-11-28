<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>User Status Check</h2>";
    
    // Check all users and their status
    $stmt = $db->query("SELECT id, name, role, status FROM users ORDER BY role, name");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Role</th><th>Status</th></tr>";
    foreach ($users as $user) {
        $statusColor = ($user['status'] === 'active') ? 'green' : 'red';
        echo "<tr><td>{$user['id']}</td><td>{$user['name']}</td><td>{$user['role']}</td><td style='color:{$statusColor}'>{$user['status']}</td></tr>";
    }
    echo "</table>";
    
    // Check owners specifically
    echo "<h3>Owners with 'active' status:</h3>";
    $stmt = $db->query("SELECT id, name, status FROM users WHERE role = 'owner' AND status = 'active'");
    $activeOwners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($activeOwners)) {
        echo "<p>❌ No active owners found!</p>";
        
        // Check all owners regardless of status
        $stmt = $db->query("SELECT id, name, status FROM users WHERE role = 'owner'");
        $allOwners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($allOwners)) {
            echo "<p>❌ No owners found at all!</p>";
        } else {
            echo "<p>Found owners with different status:</p>";
            foreach ($allOwners as $owner) {
                echo "<p>- {$owner['name']} (Status: {$owner['status']})</p>";
            }
        }
    } else {
        echo "<p>✅ Found " . count($activeOwners) . " active owners:</p>";
        foreach ($activeOwners as $owner) {
            echo "<p>- {$owner['name']} (ID: {$owner['id']})</p>";
        }
    }
    
    // Check admins specifically
    echo "<h3>Admins with 'active' status:</h3>";
    $stmt = $db->query("SELECT id, name, status FROM users WHERE role = 'admin' AND status = 'active'");
    $activeAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($activeAdmins)) {
        echo "<p>❌ No active admins found!</p>";
        
        // Check all admins regardless of status
        $stmt = $db->query("SELECT id, name, status FROM users WHERE role = 'admin'");
        $allAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($allAdmins)) {
            echo "<p>❌ No admins found at all!</p>";
        } else {
            echo "<p>Found admins with different status:</p>";
            foreach ($allAdmins as $admin) {
                echo "<p>- {$admin['name']} (Status: {$admin['status']})</p>";
            }
        }
    } else {
        echo "<p>✅ Found " . count($activeAdmins) . " active admins:</p>";
        foreach ($activeAdmins as $admin) {
            echo "<p>- {$admin['name']} (ID: {$admin['id']})</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
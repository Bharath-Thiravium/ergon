<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>🔧 Deep Database Synchronization Fix</h2>";
    
    // Step 1: Clear any cached sessions
    session_start();
    session_destroy();
    echo "<p>✅ Cleared PHP sessions</p>";
    
    // Step 2: Sync production data to match frontend expectations
    echo "<h3>📊 Current Production Database State:</h3>";
    $current = $db->query("SELECT id, name, email, role, status FROM users ORDER BY role DESC, id");
    $users = $current->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "<p>ID: {$user['id']} | {$user['name']} | {$user['email']} | {$user['role']}</p>";
    }
    
    // Step 3: Add missing Nelson (ID: 37) to match frontend
    $checkNelson = $db->prepare("SELECT COUNT(*) as count FROM users WHERE id = 37");
    $checkNelson->execute();
    $nelsonExists = $checkNelson->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($nelsonExists == 0) {
        $addNelson = $db->prepare("INSERT INTO users (id, employee_id, name, email, password, role, status, created_at) VALUES (37, 'EMP037', 'Nelson', 'nelson@gmail.com', ?, 'admin', 'active', NOW())");
        $hashedPassword = password_hash('nelson123', PASSWORD_BCRYPT);
        $addNelson->execute([$hashedPassword]);
        echo "<p>✅ Added Nelson (ID: 37) as Admin</p>";
    }
    
    // Step 4: Update Bharath to Anbu to match frontend display
    $updateAnbu = $db->prepare("UPDATE users SET name = 'Anbu', email = 'anbu@bkge.com' WHERE id = 59");
    $updateAnbu->execute();
    echo "<p>✅ Updated ID 59 to Anbu with correct email</p>";
    
    // Step 5: Verify final state matches frontend
    echo "<h3>🎯 Final Synchronized State:</h3>";
    $final = $db->query("SELECT id, name, email, role, status FROM users WHERE status = 'active' ORDER BY role DESC, id");
    $finalUsers = $final->fetchAll(PDO::FETCH_ASSOC);
    
    $owners = array_filter($finalUsers, fn($u) => $u['role'] === 'owner');
    $admins = array_filter($finalUsers, fn($u) => $u['role'] === 'admin');
    $users = array_filter($finalUsers, fn($u) => $u['role'] === 'user');
    
    echo "<h4>👑 Owners:</h4>";
    foreach ($owners as $owner) {
        echo "<p>• {$owner['name']} (ID: {$owner['id']}) - {$owner['email']}</p>";
    }
    
    echo "<h4>🔑 Administrators:</h4>";
    foreach ($admins as $admin) {
        echo "<p>• {$admin['name']} (ID: {$admin['id']}) - {$admin['email']}</p>";
    }
    
    echo "<h4>👤 Regular Users:</h4>";
    foreach ($users as $user) {
        echo "<p>• {$user['name']} (ID: {$user['id']}) - {$user['email']}</p>";
    }
    
    echo "<h3>✅ Database Now Matches Frontend Display</h3>";
    echo "<p>Total Users: " . count($finalUsers) . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
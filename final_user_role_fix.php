<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Update Harini M to admin role
    $stmt = $db->prepare("UPDATE users SET role = 'admin' WHERE id = 16");
    $result = $stmt->execute();
    
    if ($result) {
        echo "✅ Harini M (ID: 16) updated to admin role\n";
    }
    
    // Verify final state
    $verify = $db->query("SELECT id, name, role FROM users WHERE id IN (1, 16, 37, 57, 58) ORDER BY role DESC, id");
    $users = $verify->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nFinal User Roles:\n";
    foreach ($users as $user) {
        $icon = $user['role'] === 'owner' ? '👑' : ($user['role'] === 'admin' ? '🔑' : '👤');
        echo "{$icon} {$user['name']} (ID: {$user['id']}) - " . ucfirst($user['role']) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
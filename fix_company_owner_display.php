<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Update company_owner to owner for display consistency
    $stmt = $db->prepare("UPDATE users SET role = 'owner' WHERE role = 'company_owner'");
    $result = $stmt->execute();
    
    if ($result) {
        echo "✅ Updated company_owner role to owner for frontend display\n";
    }
    
    // Verify final state
    $verify = $db->query("SELECT id, name, role FROM users WHERE status = 'active' ORDER BY role DESC, id");
    $users = $verify->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nFinal User Roles for Frontend:\n";
    foreach ($users as $user) {
        $icon = $user['role'] === 'owner' ? '👑' : ($user['role'] === 'admin' ? '🔑' : '👤');
        echo "{$icon} {$user['name']} (ID: {$user['id']}) - " . ucfirst($user['role']) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
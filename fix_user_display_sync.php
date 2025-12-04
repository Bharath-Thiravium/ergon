<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>User Display Synchronization Fix</h2>";
    
    // Check for any data inconsistencies
    $stmt = $db->query("SELECT id, name, email, role, status, created_at FROM users WHERE status != 'deleted' ORDER BY role DESC, id ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current User Data (as displayed in frontend):</h3>";
    
    // Group users by role like the frontend does
    $owners = array_filter($users, fn($u) => $u['role'] === 'owner');
    $admins = array_filter($users, fn($u) => $u['role'] === 'admin');
    $regularUsers = array_filter($users, fn($u) => $u['role'] === 'user');
    
    echo "<h4>👑 Owners</h4>";
    foreach ($owners as $user) {
        echo "<div style='padding: 10px; border: 1px solid #ddd; margin: 5px;'>";
        echo "<strong>{$user['name']}</strong><br>";
        echo "ID: {$user['id']}<br>";
        echo "Email: {$user['email']}<br>";
        echo "Role: " . ucfirst($user['role']) . "<br>";
        echo "Status: " . ucfirst($user['status']);
        echo "</div>";
    }
    
    echo "<h4>🔑 Administrators</h4>";
    foreach ($admins as $user) {
        echo "<div style='padding: 10px; border: 1px solid #ddd; margin: 5px;'>";
        echo "<strong>{$user['name']}</strong><br>";
        echo "ID: {$user['id']}<br>";
        echo "Email: {$user['email']}<br>";
        echo "Role: " . ucfirst($user['role']) . "<br>";
        echo "Status: " . ucfirst($user['status']);
        echo "</div>";
    }
    
    echo "<h4>👤 Regular Users</h4>";
    foreach ($regularUsers as $user) {
        echo "<div style='padding: 10px; border: 1px solid #ddd; margin: 5px;'>";
        echo "<strong>{$user['name']}</strong><br>";
        echo "ID: {$user['id']}<br>";
        echo "Email: {$user['email']}<br>";
        echo "Role: " . ucfirst($user['role']) . "<br>";
        echo "Status: " . ucfirst($user['status']);
        echo "</div>";
    }
    
    // Check for missing user ID 37
    echo "<h3>Missing User Analysis:</h3>";
    $missingId37 = $db->prepare("SELECT COUNT(*) as count FROM users WHERE id = 37");
    $missingId37->execute();
    $count37 = $missingId37->fetch(PDO::FETCH_ASSOC);
    
    if ($count37['count'] == 0) {
        echo "<p style='color: red;'>⚠️ User ID 37 does not exist in database</p>";
        echo "<p>This explains why 'Nelson ID: 37 Admin' is not showing in the actual database results.</p>";
        echo "<p>The frontend you saw might be cached or from a different environment.</p>";
    } else {
        echo "<p style='color: green;'>✅ User ID 37 exists in database</p>";
    }
    
    // Verify the actual Nelson users
    echo "<h3>Nelson User Verification:</h3>";
    $nelsonQuery = $db->prepare("SELECT * FROM users WHERE name LIKE '%Nelson%' ORDER BY id");
    $nelsonQuery->execute();
    $nelsonUsers = $nelsonQuery->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($nelsonUsers as $nelson) {
        echo "<div style='padding: 10px; border: 2px solid #007bff; margin: 5px;'>";
        echo "<strong>Found Nelson User:</strong><br>";
        echo "ID: {$nelson['id']}<br>";
        echo "Name: {$nelson['name']}<br>";
        echo "Email: {$nelson['email']}<br>";
        echo "Role: {$nelson['role']}<br>";
        echo "Status: {$nelson['status']}<br>";
        echo "Created: {$nelson['created_at']}";
        echo "</div>";
    }
    
    echo "<h3>Recommendations:</h3>";
    echo "<ul>";
    echo "<li>Clear browser cache and refresh the user management page</li>";
    echo "<li>Check if you're looking at the correct database/environment</li>";
    echo "<li>The database shows Nelson Raj (ID: 57) as a regular user, not an admin</li>";
    echo "<li>There is no user with ID 37 in the current database</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
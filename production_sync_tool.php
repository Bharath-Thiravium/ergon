<?php
echo "🔄 PRODUCTION SYNC TOOL\n\n";

try {
    // Production database connection
    $prodPdo = new PDO(
        'mysql:host=localhost;dbname=u494785662_ergon;charset=utf8mb4',
        'u494785662_ergon',
        '@Admin@2025@',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Local database connection
    $localPdo = new PDO(
        'mysql:host=localhost;dbname=ergon_db;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Connected to both databases\n";
    
    // Sync users from production to local
    $prodUsers = $prodPdo->query("SELECT * FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    
    // Clear local users
    $localPdo->exec("DELETE FROM users");
    $localPdo->exec("ALTER TABLE users AUTO_INCREMENT = 1");
    
    // Insert production users into local
    foreach ($prodUsers as $user) {
        $columns = array_keys($user);
        $placeholders = array_map(fn($col) => ":$col", $columns);
        
        $sql = "INSERT INTO users (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $localPdo->prepare($sql);
        $stmt->execute($user);
    }
    
    echo "✅ Synced " . count($prodUsers) . " users to local database\n";
    
    // Verify sync
    $localCount = $localPdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $prodCount = $prodPdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    echo "📊 Production users: $prodCount\n";
    echo "📊 Local users: $localCount\n";
    
    if ($localCount == $prodCount) {
        echo "✅ SYNC SUCCESSFUL - Databases match\n";
    } else {
        echo "❌ SYNC FAILED - Count mismatch\n";
    }
    
    // Show current users
    echo "\n👥 CURRENT USERS:\n";
    $users = $localPdo->query("SELECT id, name, email, role FROM users ORDER BY role DESC, id")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $icon = $user['role'] === 'owner' ? '👑' : ($user['role'] === 'admin' ? '🔑' : '👤');
        echo "$icon {$user['name']} (ID: {$user['id']}) - {$user['role']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
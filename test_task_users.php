<?php
require_once __DIR__ . '/app/config/database.php';

echo "<h1>Task Users Test</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    $db = Database::connect();
    
    // Check if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if (!$stmt->fetchColumn()) {
        echo "<span class='error'>❌ Users table doesn't exist</span><br>";
        
        // Create users table with sample data
        $db->exec("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'user',
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("INSERT INTO users (name, email, role, status) VALUES 
            ('System Owner', 'owner@ergon.com', 'owner', 'active'),
            ('Admin User', 'admin@ergon.com', 'admin', 'active'),
            ('Test User', 'user@ergon.com', 'user', 'active')");
        
        echo "<span class='success'>✅ Created users table with sample data</span><br>";
    } else {
        echo "<span class='success'>✅ Users table exists</span><br>";
    }
    
    // Fetch users for dropdown
    $stmt = $db->prepare("SELECT id, name, email, role FROM users ORDER BY name");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Users for Assign To dropdown:</h2>";
    if (!empty($users)) {
        echo "<select style='padding:5px;width:300px;'>";
        echo "<option value=''>Select User</option>";
        foreach ($users as $user) {
            echo "<option value='{$user['id']}'>" . htmlspecialchars($user['name']) . " ({$user['role']})</option>";
        }
        echo "</select><br><br>";
        
        echo "<h3>Raw Data:</h3>";
        foreach ($users as $user) {
            echo "<span class='success'>ID: {$user['id']}, Name: {$user['name']}, Role: {$user['role']}</span><br>";
        }
    } else {
        echo "<span class='error'>❌ No users found</span><br>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: {$e->getMessage()}</span><br>";
}
?>

<p><a href="/ergon/tasks/create">Go to Create Task Page</a></p>
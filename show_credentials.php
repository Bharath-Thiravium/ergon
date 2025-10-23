<?php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h1>🔑 ERGON Login Credentials</h1>";
    echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #f2f2f2; font-weight: bold; }
    .owner { background-color: #ffebee; }
    .admin { background-color: #fff3e0; }
    .user { background-color: #e8f5e8; }
    .temp-pass { background-color: #fff9c4; font-weight: bold; }
    </style>";
    
    $stmt = $conn->prepare("
        SELECT id, name, email, role, temp_password, is_first_login, status, created_at 
        FROM users 
        ORDER BY role DESC, name ASC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($users) {
        echo "<table>";
        echo "<tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Password</th>
                <th>Status</th>
                <th>First Login</th>
                <th>Created</th>
              </tr>";
        
        foreach ($users as $user) {
            $roleClass = $user['role'];
            $password = $user['temp_password'] ?: 'Use default password';
            
            echo "<tr class='$roleClass'>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>" . ucfirst($user['role']) . "</td>";
            echo "<td class='temp-pass'>$password</td>";
            echo "<td>{$user['status']}</td>";
            echo "<td>" . ($user['is_first_login'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . date('M d, Y', strtotime($user['created_at'])) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<div style='margin-top: 30px; padding: 20px; background: #f0f8ff; border-radius: 8px;'>";
        echo "<h3>📋 Default Login Instructions:</h3>";
        echo "<ul>";
        echo "<li><strong>Owner Account:</strong> Use email and temp password shown above</li>";
        echo "<li><strong>Admin Account:</strong> Use email and temp password shown above</li>";
        echo "<li><strong>User Account:</strong> Use email and temp password shown above</li>";
        echo "<li><strong>Default Password:</strong> If no temp password, try 'password123' or 'admin123'</li>";
        echo "</ul>";
        echo "</div>";
        
    } else {
        echo "<p>No users found in database.</p>";
        echo "<p><a href='/ergon/setup-with-data'>Create sample users</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<br><br><a href='/ergon/login'>🔐 Go to Login Page</a>";
echo " | <a href='/ergon/test-session-security'>🧪 Test Session Security</a>";
?>
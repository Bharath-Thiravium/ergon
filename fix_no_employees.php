<?php
// Fix "No Employees Found" issue
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "✅ Database connected successfully<br><br>";
    
    // Check users table
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    echo "Current users in database: $userCount<br><br>";
    
    if ($userCount == 0) {
        echo "Creating sample users...<br>";
        
        // Create sample users
        $users = [
            ['name' => 'System Admin', 'email' => 'admin@ergon.com', 'role' => 'admin'],
            ['name' => 'John Doe', 'email' => 'john@ergon.com', 'role' => 'user'],
            ['name' => 'Jane Smith', 'email' => 'jane@ergon.com', 'role' => 'user'],
            ['name' => 'Mike Johnson', 'email' => 'mike@ergon.com', 'role' => 'user']
        ];
        
        foreach ($users as $user) {
            $password = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, role, password, created_at) VALUES (?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$user['name'], $user['email'], $user['role'], $password]);
            
            if ($result) {
                echo "✅ Created: {$user['name']} ({$user['role']})<br>";
            } else {
                echo "❌ Failed to create: {$user['name']}<br>";
            }
        }
        
        echo "<br>✅ Sample users created successfully!<br>";
        echo "<br><strong>Login credentials:</strong><br>";
        echo "- Admin: admin@ergon.com / password123<br>";
        echo "- Users: john@ergon.com, jane@ergon.com, mike@ergon.com / password123<br>";
        
    } else {
        echo "Users already exist. Checking roles...<br>";
        
        $stmt = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        $roles = $stmt->fetchAll();
        
        foreach ($roles as $role) {
            echo "- {$role['role']}: {$role['count']} users<br>";
        }
        
        // Check if there are any regular users (employees)
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
        $employeeCount = $stmt->fetch()['count'];
        
        if ($employeeCount == 0) {
            echo "<br>❌ No employees (role='user') found. Creating sample employees...<br>";
            
            $employees = [
                ['name' => 'John Doe', 'email' => 'john@ergon.com', 'role' => 'user'],
                ['name' => 'Jane Smith', 'email' => 'jane@ergon.com', 'role' => 'user'],
                ['name' => 'Mike Johnson', 'email' => 'mike@ergon.com', 'role' => 'user']
            ];
            
            foreach ($employees as $employee) {
                $password = password_hash('password123', PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, role, password, created_at) VALUES (?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$employee['name'], $employee['email'], $employee['role'], $password]);
                
                if ($result) {
                    echo "✅ Created employee: {$employee['name']}<br>";
                } else {
                    echo "❌ Failed to create employee: {$employee['name']}<br>";
                }
            }
        } else {
            echo "<br>✅ Found $employeeCount employees in the system<br>";
        }
    }
    
    echo "<br><a href='/ergon/attendance' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Attendance Page</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
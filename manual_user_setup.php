<?php
echo "👥 MANUAL USER SETUP FOR LOCAL DATABASE\n\n";

try {
    // Connect to local ergon_db
    $localPdo = Database::connect());
    $localPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table if not exists
    $createTable = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id VARCHAR(20) UNIQUE,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('owner', 'admin', 'user') DEFAULT 'user',
        is_system_admin TINYINT(1) DEFAULT 0,
        phone VARCHAR(20),
        department TEXT,
        status ENUM('active', 'inactive', 'suspended', 'terminated') DEFAULT 'active',
        is_first_login TINYINT(1) DEFAULT 1,
        temp_password VARCHAR(255),
        password_reset_required TINYINT(1) DEFAULT 0,
        last_login TIMESTAMP NULL,
        last_ip VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        date_of_birth DATE,
        gender ENUM('male', 'female', 'other'),
        address TEXT,
        emergency_contact VARCHAR(255),
        designation VARCHAR(255),
        joining_date DATE,
        salary DECIMAL(10,2),
        total_points INT DEFAULT 0,
        department_id INT,
        shift_id INT DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $localPdo->exec($createTable);
    echo "✅ Created users table structure\n";
    
    // Clear existing users
    $localPdo->exec("DELETE FROM users");
    $localPdo->exec("ALTER TABLE users AUTO_INCREMENT = 1");
    
    // Insert production-matching users
    $users = [
        [
            'id' => 1,
            'employee_id' => 'EMP001',
            'name' => 'Athenas Owner',
            'email' => 'info@athenas.co.in',
            'password' => password_hash('owner123', PASSWORD_BCRYPT),
            'role' => 'owner',
            'status' => 'active',
            'created_at' => '2025-10-23 06:24:06'
        ],
        [
            'id' => 16,
            'employee_id' => 'ATSO003',
            'name' => 'Harini M',
            'email' => 'harini@athenas.co.in',
            'password' => password_hash('harini123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active',
            'created_at' => '2025-10-24 02:34:52'
        ],
        [
            'id' => 37,
            'employee_id' => 'EMP037',
            'name' => 'Nelson',
            'email' => 'nelson@gmail.com',
            'password' => password_hash('nelson123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active',
            'created_at' => '2025-10-30 05:16:49'
        ],
        [
            'id' => 57,
            'employee_id' => 'EMP015',
            'name' => 'Nelson Raj',
            'email' => 'nelson.raj@athenas.co.in',
            'password' => password_hash('nelsonraj123', PASSWORD_BCRYPT),
            'role' => 'user',
            'status' => 'active',
            'created_at' => '2025-12-01 05:28:01'
        ],
        [
            'id' => 58,
            'employee_id' => 'EMP016',
            'name' => 'Yazhini S',
            'email' => 'yazhini@athenas.co.in',
            'password' => password_hash('yazhini123', PASSWORD_BCRYPT),
            'role' => 'user',
            'status' => 'active',
            'created_at' => '2025-12-01 06:13:28'
        ],
        [
            'id' => 59,
            'employee_id' => 'EMP059',
            'name' => 'Anbu',
            'email' => 'anbu@bkge.com',
            'password' => password_hash('anbu123', PASSWORD_BCRYPT),
            'role' => 'owner',
            'status' => 'active',
            'created_at' => '2025-12-04 07:34:40'
        ]
    ];
    
    foreach ($users as $user) {
        $columns = implode(',', array_keys($user));
        $placeholders = ':' . implode(', :', array_keys($user));
        
        $stmt = $localPdo->prepare("INSERT INTO users ($columns) VALUES ($placeholders)");
        $stmt->execute($user);
    }
    
    echo "✅ Inserted " . count($users) . " users into local database\n";
    
    // Verify users
    echo "\n👥 LOCAL DATABASE USERS:\n";
    $result = $localPdo->query("SELECT id, name, email, role FROM users ORDER BY role DESC, id");
    
    while ($user = $result->fetch(PDO::FETCH_ASSOC)) {
        $icon = $user['role'] === 'owner' ? '👑' : ($user['role'] === 'admin' ? '🔑' : '👤');
        echo "$icon {$user['name']} (ID: {$user['id']}) - {$user['role']}\n";
    }
    
    echo "\n✅ LOCAL DATABASE SETUP COMPLETE\n";
    echo "🌐 Frontend will now show correct users based on environment\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
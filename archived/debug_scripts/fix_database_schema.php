<?php
/**
 * Database Schema Fix for Planner Module
 * Ensures all required tables and columns exist with proper structure
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h1>Database Schema Fix</h1>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.success{color:green;}.error{color:red;}.warning{color:orange;}</style>";
    
    // 1. Create/Update tasks table
    echo "<h2>1. Tasks Table</h2>";
    
    $tasksSQL = "
        CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            assigned_by INT,
            assigned_to INT,
            planned_date DATE NULL,
            deadline DATETIME NULL,
            status ENUM('assigned','not_started','in_progress','completed','cancelled','deleted') DEFAULT 'assigned',
            priority ENUM('low','medium','high') DEFAULT 'medium',
            progress INT DEFAULT 0,
            sla_hours DECIMAL(8,4) DEFAULT 0.25,
            estimated_duration INT DEFAULT 60,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_assigned_to (assigned_to),
            INDEX idx_planned_date (planned_date),
            INDEX idx_deadline (deadline),
            INDEX idx_status (status)
        )
    ";
    
    $db->exec($tasksSQL);
    echo "<p class='success'>✅ Tasks table created/verified</p>";
    
    // Add missing columns to tasks table
    $taskColumns = [
        'sla_hours' => 'DECIMAL(8,4) DEFAULT 0.25',
        'estimated_duration' => 'INT DEFAULT 60',
        'planned_date' => 'DATE NULL'
    ];
    
    foreach ($taskColumns as $column => $definition) {
        try {
            $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE ?");
            $stmt->execute([$column]);
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE tasks ADD COLUMN `{$column}` {$definition}");
                echo "<p class='success'>✅ Added column tasks.{$column}</p>";
            }
        } catch (Exception $e) {
            echo "<p class='warning'>⚠️ Could not add tasks.{$column}: " . $e->getMessage() . "</p>";
        }
    }
    
    // 2. Create/Update daily_tasks table
    echo "<h2>2. Daily Tasks Table</h2>";
    
    $dailyTasksSQL = "
        CREATE TABLE IF NOT EXISTS daily_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            task_id INT NULL,
            original_task_id INT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            scheduled_date DATE NOT NULL,
            planned_start_time TIME NULL,
            planned_duration INT DEFAULT 60,
            priority VARCHAR(20) DEFAULT 'medium',
            status VARCHAR(50) DEFAULT 'not_started',
            completed_percentage INT DEFAULT 0,
            start_time TIMESTAMP NULL,
            pause_time TIMESTAMP NULL,
            pause_start_time TIMESTAMP NULL,
            resume_time TIMESTAMP NULL,
            completion_time TIMESTAMP NULL,
            sla_end_time TIMESTAMP NULL,
            active_seconds INT DEFAULT 0,
            pause_duration INT DEFAULT 0,
            postponed_from_date DATE NULL,
            postponed_to_date DATE NULL,
            source_field VARCHAR(50) NULL,
            rollover_source_date DATE NULL,
            rollover_timestamp TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_date (user_id, scheduled_date),
            INDEX idx_task_id (task_id),
            INDEX idx_original_task_id (original_task_id),
            INDEX idx_status (status),
            INDEX idx_rollover_source (rollover_source_date)
        )
    ";
    
    $db->exec($dailyTasksSQL);
    echo "<p class='success'>✅ Daily tasks table created/verified</p>";
    
    // Add missing columns to daily_tasks table
    $dailyTaskColumns = [
        'original_task_id' => 'INT NULL',
        'source_field' => 'VARCHAR(50) NULL',
        'rollover_source_date' => 'DATE NULL',
        'rollover_timestamp' => 'TIMESTAMP NULL',
        'pause_duration' => 'INT DEFAULT 0',
        'pause_start_time' => 'TIMESTAMP NULL',
        'postponed_to_date' => 'DATE NULL',
        'sla_end_time' => 'TIMESTAMP NULL'
    ];
    
    foreach ($dailyTaskColumns as $column => $definition) {
        try {
            $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks LIKE ?");
            $stmt->execute([$column]);
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE daily_tasks ADD COLUMN `{$column}` {$definition}");
                echo "<p class='success'>✅ Added column daily_tasks.{$column}</p>";
            }
        } catch (Exception $e) {
            echo "<p class='warning'>⚠️ Could not add daily_tasks.{$column}: " . $e->getMessage() . "</p>";
        }
    }
    
    // 3. Create/Update followups table
    echo "<h2>3. Followups Table</h2>";
    
    $followupsSQL = "
        CREATE TABLE IF NOT EXISTS followups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NULL,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            followup_type ENUM('standalone','task') DEFAULT 'standalone',
            follow_up_date DATE NOT NULL,
            contact_id INT NULL,
            status ENUM('pending','in_progress','completed','postponed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_task_id (task_id),
            INDEX idx_user_id (user_id),
            INDEX idx_follow_up_date (follow_up_date),
            INDEX idx_status (status)
        )
    ";
    
    $db->exec($followupsSQL);
    echo "<p class='success'>✅ Followups table created/verified</p>";
    
    // 4. Create supporting tables
    echo "<h2>4. Supporting Tables</h2>";
    
    // Users table (basic structure)
    $usersSQL = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('user','admin','owner') DEFAULT 'user',
            status ENUM('active','inactive','suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    
    $db->exec($usersSQL);
    echo "<p class='success'>✅ Users table created/verified</p>";
    
    // Departments table
    $departmentsSQL = "
        CREATE TABLE IF NOT EXISTS departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    
    $db->exec($departmentsSQL);
    echo "<p class='success'>✅ Departments table created/verified</p>";
    
    // 5. Create indexes for performance
    echo "<h2>5. Performance Indexes</h2>";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_tasks_user_date ON tasks (assigned_to, planned_date)",
        "CREATE INDEX IF NOT EXISTS idx_tasks_deadline_date ON tasks (assigned_to, deadline)",
        "CREATE INDEX IF NOT EXISTS idx_daily_tasks_user_date ON daily_tasks (user_id, scheduled_date)",
        "CREATE INDEX IF NOT EXISTS idx_daily_tasks_source ON daily_tasks (source_field)",
        "CREATE INDEX IF NOT EXISTS idx_followups_user_date ON followups (user_id, follow_up_date)"
    ];
    
    foreach ($indexes as $indexSQL) {
        try {
            $db->exec($indexSQL);
            echo "<p class='success'>✅ Index created</p>";
        } catch (Exception $e) {
            echo "<p class='warning'>⚠️ Index may already exist: " . $e->getMessage() . "</p>";
        }
    }
    
    // 6. Insert test data if tables are empty
    echo "<h2>6. Test Data</h2>";
    
    // Check if we have a test user
    $stmt = $db->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    $userCount = $stmt->fetchColumn();
    
    if ($userCount == 0) {
        echo "<p class='warning'>⚠️ No users found. Creating test user...</p>";
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Test User', 'test@example.com', password_hash('password', PASSWORD_DEFAULT), 'user']);
        echo "<p class='success'>✅ Test user created (ID: " . $db->lastInsertId() . ")</p>";
        echo "<p><strong>Login:</strong> test@example.com / password</p>";
    }
    
    // Check if we have test tasks
    $stmt = $db->prepare("SELECT COUNT(*) FROM tasks");
    $stmt->execute();
    $taskCount = $stmt->fetchColumn();
    
    if ($taskCount == 0) {
        echo "<p class='warning'>⚠️ No tasks found. Creating test tasks...</p>";
        
        $testTasks = [
            ['Test Task 1 - Planned for Today', 'This task is planned for today', date('Y-m-d'), null],
            ['Test Task 2 - Deadline Today', 'This task has deadline today', null, date('Y-m-d') . ' 17:00:00'],
            ['Test Task 3 - Created Today', 'This task was created today', null, null],
            ['Test Task 4 - In Progress', 'This task is in progress', date('Y-m-d', strtotime('-1 day')), null]
        ];
        
        foreach ($testTasks as $i => $taskData) {
            $stmt = $db->prepare("INSERT INTO tasks (title, description, planned_date, deadline, assigned_to, assigned_by, status, sla_hours) VALUES (?, ?, ?, ?, 1, 1, ?, 0.25)");
            $status = ($i == 3) ? 'in_progress' : 'assigned';
            $stmt->execute([$taskData[0], $taskData[1], $taskData[2], $taskData[3], $status]);
        }
        
        echo "<p class='success'>✅ Created " . count($testTasks) . " test tasks</p>";
    }
    
    echo "<h2>7. Verification</h2>";
    
    // Verify table structures
    $tables = ['tasks', 'daily_tasks', 'followups', 'users'];
    foreach ($tables as $table) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table}");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        echo "<p class='success'>✅ {$table}: {$count} records</p>";
    }
    
    echo "<div style='padding:20px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin-top:20px;'>";
    echo "<h3 style='color:#155724;margin:0 0 10px 0;'>🎉 Database Schema Fix Complete!</h3>";
    echo "<p style='margin:0;'>All required tables and columns have been created/verified.</p>";
    echo "<p style='margin:10px 0 0 0;'><strong>Next step:</strong> Run <a href='test_final_fix.php' target='_blank'>Final Test</a> to verify the planner is working.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
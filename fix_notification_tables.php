<?php
// Fix Missing Database Tables for Notifications
session_start();

echo "<h1>Fix Notification Tables</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    echo "<div class='success'>✓ Database connected</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
    exit;
}

// Create missing tables
echo "<h2>Creating Missing Tables</h2>";

// 1. Create leaves table
try {
    $db->exec("CREATE TABLE IF NOT EXISTS leaves (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        leave_type VARCHAR(50) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        days_requested INT DEFAULT 1,
        reason TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        rejection_reason TEXT NULL,
        approved_by INT NULL,
        approved_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Leaves table created</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Leaves table error: " . $e->getMessage() . "</div>";
}

// 2. Create expenses table
try {
    $db->exec("CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        category VARCHAR(100) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        description TEXT,
        expense_date DATE NOT NULL,
        attachment VARCHAR(255) NULL,
        status VARCHAR(20) DEFAULT 'pending',
        approved_by INT NULL,
        approved_at TIMESTAMP NULL,
        rejection_reason TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Expenses table created</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Expenses table error: " . $e->getMessage() . "</div>";
}

// 3. Create advances table
try {
    $db->exec("CREATE TABLE IF NOT EXISTS advances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) DEFAULT 'General Advance',
        amount DECIMAL(10,2) NOT NULL,
        reason TEXT NOT NULL,
        requested_date DATE NULL,
        status VARCHAR(20) DEFAULT 'pending',
        approved_by INT NULL,
        approved_at DATETIME NULL,
        rejection_reason TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Advances table created</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Advances table error: " . $e->getMessage() . "</div>";
}

// 4. Create followups table
try {
    $db->exec("CREATE TABLE IF NOT EXISTS followups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        company_name VARCHAR(255),
        contact_person VARCHAR(255),
        contact_phone VARCHAR(20),
        project_name VARCHAR(255),
        follow_up_date DATE NOT NULL,
        original_date DATE,
        reminder_time TIME NULL,
        description TEXT,
        status ENUM('pending','in_progress','completed','postponed','cancelled') DEFAULT 'pending',
        completed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Followups table created</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Followups table error: " . $e->getMessage() . "</div>";
}

// 5. Create tasks table (simplified)
try {
    $db->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        assigned_by INT DEFAULT NULL,
        assigned_to INT DEFAULT NULL,
        priority ENUM('low','medium','high') DEFAULT 'medium',
        status ENUM('assigned','in_progress','completed','blocked') DEFAULT 'assigned',
        due_date DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Tasks table created</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Tasks table error: " . $e->getMessage() . "</div>";
}

// 6. Create attendance table
try {
    $db->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        check_in DATETIME NOT NULL,
        check_out DATETIME NULL,
        status VARCHAR(20) DEFAULT 'present',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Attendance table created</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Attendance table error: " . $e->getMessage() . "</div>";
}

// 7. Create notifications table
try {
    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        module_name VARCHAR(50) NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        reference_id INT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_receiver_read (receiver_id, is_read),
        INDEX idx_created_at (created_at)
    )");
    echo "<div class='success'>✓ Notifications table created</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Notifications table error: " . $e->getMessage() . "</div>";
}

echo "<h2>Tables Created Successfully!</h2>";
echo "<p>Now run the test again: <a href='/ergon/test_complete_notification_system.php'>Run Tests</a></p>";
?>
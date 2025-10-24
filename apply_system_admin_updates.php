<?php
/**
 * Apply System Admin Database Updates
 * ERGON - Employee Tracker & Task Manager
 * Run this script once to update the database schema
 */

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Applying System Admin database updates...\n";
    
    // Add system admin flag to users table
    $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_system_admin BOOLEAN DEFAULT FALSE AFTER role");
    echo "✓ Added is_system_admin column to users table\n";
    
    // Add system admin flag to admin_positions table
    $conn->exec("ALTER TABLE admin_positions ADD COLUMN IF NOT EXISTS is_system_admin BOOLEAN DEFAULT FALSE AFTER assigned_department");
    echo "✓ Added is_system_admin column to admin_positions table\n";
    
    // Create admin_positions table if it doesn't exist
    $conn->exec("
        CREATE TABLE IF NOT EXISTS admin_positions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            assigned_department VARCHAR(100) NULL,
            permissions JSON NULL,
            is_system_admin BOOLEAN DEFAULT FALSE,
            assigned_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_by) REFERENCES users(id)
        )
    ");
    echo "✓ Ensured admin_positions table exists\n";
    
    // Add indexes for better performance
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_users_system_admin ON users(is_system_admin)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_admin_positions_system_admin ON admin_positions(is_system_admin)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_users_role_status ON users(role, status)");
    echo "✓ Added performance indexes\n";
    
    echo "\n✅ System Admin database updates completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Login as Owner\n";
    echo "2. Go to System Admins section\n";
    echo "3. Create your first system admin\n";
    echo "4. The system admin can then create users\n";
    echo "5. You can later promote users to admin roles\n";
    
} catch (Exception $e) {
    echo "❌ Error applying updates: " . $e->getMessage() . "\n";
    exit(1);
}
?>
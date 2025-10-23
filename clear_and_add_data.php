<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Clear existing data
    $conn->exec("DELETE FROM task_updates");
    $conn->exec("DELETE FROM tasks");
    $conn->exec("DELETE FROM expenses");
    $conn->exec("DELETE FROM leaves");
    $conn->exec("DELETE FROM attendance");
    $conn->exec("DELETE FROM circulars");
    $conn->exec("DELETE FROM users WHERE id > 2");
    
    // Ensure owner and admin users exist
    $stmt = $conn->prepare("INSERT IGNORE INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 'Athenas Owner', 'info@athenas.co.in', password_hash('@Athenas2025@', PASSWORD_DEFAULT), 'owner', 'active']);
    $stmt->execute([2, 'Athenas Admin', 'admin@athenas.co.in', password_hash('Admin@2025@', PASSWORD_DEFAULT), 'admin', 'active']);

    // Add sample users
    $stmt = $conn->prepare("INSERT INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([3, 'John Smith', 'john@athenas.co.in', password_hash('user123', PASSWORD_DEFAULT), 'user', 'active']);
    $stmt->execute([4, 'Sarah Johnson', 'sarah@athenas.co.in', password_hash('user123', PASSWORD_DEFAULT), 'user', 'active']);
    $stmt->execute([5, 'Mike Wilson', 'mike@athenas.co.in', password_hash('user123', PASSWORD_DEFAULT), 'user', 'active']);

    // Add sample tasks with proper data
    $stmt = $conn->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, task_type, priority, deadline, progress, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Complete Project Documentation', 'Finalize all project documentation and user manuals', 2, 3, 'milestone', 'high', '2024-12-30 17:00:00', 75, 'in_progress', '2024-12-20 09:00:00', '2024-12-20 09:00:00']);
    $stmt->execute(['Client Meeting Preparation', 'Prepare presentation slides for upcoming client meeting', 2, 4, 'checklist', 'medium', '2024-12-28 10:00:00', 50, 'in_progress', '2024-12-21 10:30:00', '2024-12-21 10:30:00']);
    $stmt->execute(['Database Backup Setup', 'Configure automated database backup system', 2, 5, 'timed', 'high', '2024-12-29 15:00:00', 25, 'assigned', '2024-12-22 11:15:00', '2024-12-22 11:15:00']);
    $stmt->execute(['Website Content Update', 'Update company website with latest information', 2, 3, 'ad-hoc', 'low', '2025-01-05 12:00:00', 0, 'assigned', '2024-12-23 14:20:00', '2024-12-23 14:20:00']);
    $stmt->execute(['Security Audit Review', 'Review and implement security audit recommendations', 2, 4, 'milestone', 'high', '2024-12-31 16:00:00', 90, 'in_progress', '2024-12-24 08:45:00', '2024-12-24 08:45:00']);

    // Add sample leaves
    $stmt = $conn->prepare("INSERT INTO leaves (employee_id, type, start_date, end_date, reason, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([3, 'Annual Leave', '2024-12-25', '2024-12-27', 'Christmas holidays with family', 'Approved', '2024-12-15 10:00:00']);
    $stmt->execute([4, 'Sick Leave', '2024-12-20', '2024-12-21', 'Flu symptoms and recovery', 'Approved', '2024-12-18 14:30:00']);
    $stmt->execute([5, 'Personal Leave', '2025-01-02', '2025-01-03', 'Personal family matters', 'Pending', '2024-12-20 16:45:00']);

    // Add sample expenses
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, category, amount, description, receipt_path, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([3, 'Travel', 250.00, 'Client visit transportation costs', null, 'approved', '2024-12-20 10:30:00']);
    $stmt->execute([4, 'Food', 45.50, 'Business lunch with potential client', null, 'pending', '2024-12-22 14:15:00']);
    $stmt->execute([5, 'Material', 120.00, 'Office supplies and equipment', null, 'approved', '2024-12-21 09:45:00']);
    $stmt->execute([3, 'Travel', 180.00, 'Conference attendance travel expenses', null, 'pending', '2024-12-23 16:20:00']);

    // Add sample attendance
    $stmt = $conn->prepare("INSERT INTO attendance (user_id, check_in, check_out, latitude, longitude, location_name, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([3, '2024-12-27 09:15:00', '2024-12-27 18:30:00', 12.9716, 77.5946, 'Athenas Office', 'present', '2024-12-27 09:15:00']);
    $stmt->execute([4, '2024-12-27 08:45:00', '2024-12-27 17:45:00', 12.9716, 77.5946, 'Athenas Office', 'present', '2024-12-27 08:45:00']);
    $stmt->execute([5, '2024-12-27 09:30:00', null, 12.9716, 77.5946, 'Athenas Office', 'present', '2024-12-27 09:30:00']);

    // Add sample circulars
    $stmt = $conn->prepare("INSERT INTO circulars (title, message, posted_by, visible_to, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Year-End Holiday Schedule', 'Please note the office will be closed from Dec 24-26 for Christmas holidays. Regular operations resume Dec 27.', 1, 'All', '2024-12-15 09:00:00']);
    $stmt->execute(['New Security Protocols', 'Updated security protocols are now in effect. Please ensure all devices are updated and follow new password requirements.', 2, 'All', '2024-12-18 11:30:00']);
    $stmt->execute(['Q4 Performance Reviews', 'Q4 performance reviews will be conducted in the first week of January. Please prepare your self-assessment forms.', 2, 'User', '2024-12-20 15:45:00']);

    echo "Database cleared and fresh mock data added successfully!\n";
    echo "Sample users: john@athenas.co.in, sarah@athenas.co.in, mike@athenas.co.in (password: user123)\n";
    echo "Tasks: 5 tasks with various priorities and deadlines\n";
    echo "All modules now have proper mock data\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
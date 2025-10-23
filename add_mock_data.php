<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Add sample users
    $stmt = $conn->prepare("INSERT IGNORE INTO users (id, name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([3, 'John Smith', 'john@athenas.co.in', password_hash('user123', PASSWORD_DEFAULT), 'user', 'active']);
    $stmt->execute([4, 'Sarah Johnson', 'sarah@athenas.co.in', password_hash('user123', PASSWORD_DEFAULT), 'user', 'active']);
    $stmt->execute([5, 'Mike Wilson', 'mike@athenas.co.in', password_hash('user123', PASSWORD_DEFAULT), 'user', 'active']);

    // Add sample tasks
    $stmt = $conn->prepare("INSERT IGNORE INTO tasks (id, title, description, assigned_by, assigned_to, task_type, priority, deadline, progress, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 'Complete Project Documentation', 'Finalize all project documentation and user manuals', 2, 3, 'milestone', 'high', '2024-12-30 17:00:00', 75, 'in_progress', '2024-12-20 09:00:00']);
    $stmt->execute([2, 'Client Meeting Preparation', 'Prepare presentation slides for upcoming client meeting', 2, 4, 'checklist', 'medium', '2024-12-28 10:00:00', 50, 'in_progress', '2024-12-21 10:30:00']);
    $stmt->execute([3, 'Database Backup Setup', 'Configure automated database backup system', 2, 5, 'timed', 'high', '2024-12-29 15:00:00', 25, 'assigned', '2024-12-22 11:15:00']);
    $stmt->execute([4, 'Website Content Update', 'Update company website with latest information', 2, 3, 'ad-hoc', 'low', '2025-01-05 12:00:00', 0, 'assigned', '2024-12-23 14:20:00']);
    $stmt->execute([5, 'Security Audit Review', 'Review and implement security audit recommendations', 2, 4, 'milestone', 'high', '2024-12-31 16:00:00', 90, 'in_progress', '2024-12-24 08:45:00']);

    // Add sample leaves
    $stmt = $conn->prepare("INSERT IGNORE INTO leaves (id, employee_id, type, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 3, 'Annual Leave', '2024-12-25', '2024-12-27', 'Christmas holidays with family', 'Approved']);
    $stmt->execute([2, 4, 'Sick Leave', '2024-12-20', '2024-12-21', 'Flu symptoms and recovery', 'Approved']);
    $stmt->execute([3, 5, 'Personal Leave', '2025-01-02', '2025-01-03', 'Personal family matters', 'Pending']);

    // Add sample expenses
    $stmt = $conn->prepare("INSERT IGNORE INTO expenses (id, user_id, category, amount, description, receipt_path, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 3, 'Travel', 250.00, 'Client visit transportation costs', null, 'approved', '2024-12-20 10:30:00']);
    $stmt->execute([2, 4, 'Food', 45.50, 'Business lunch with potential client', null, 'pending', '2024-12-22 14:15:00']);
    $stmt->execute([3, 5, 'Material', 120.00, 'Office supplies and equipment', null, 'approved', '2024-12-21 09:45:00']);
    $stmt->execute([4, 3, 'Travel', 180.00, 'Conference attendance travel expenses', null, 'pending', '2024-12-23 16:20:00']);

    // Add sample attendance
    $stmt = $conn->prepare("INSERT IGNORE INTO attendance (id, user_id, check_in, check_out, latitude, longitude, location_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 3, '2024-12-27 09:15:00', '2024-12-27 18:30:00', 12.9716, 77.5946, 'Athenas Office', 'present']);
    $stmt->execute([2, 4, '2024-12-27 08:45:00', '2024-12-27 17:45:00', 12.9716, 77.5946, 'Athenas Office', 'present']);
    $stmt->execute([3, 5, '2024-12-27 09:30:00', null, 12.9716, 77.5946, 'Athenas Office', 'present']);

    // Add sample circulars
    $stmt = $conn->prepare("INSERT IGNORE INTO circulars (id, title, message, posted_by, visible_to) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([1, 'Year-End Holiday Schedule', 'Please note the office will be closed from Dec 24-26 for Christmas holidays. Regular operations resume Dec 27.', 1, 'All']);
    $stmt->execute([2, 'New Security Protocols', 'Updated security protocols are now in effect. Please ensure all devices are updated and follow new password requirements.', 2, 'All']);
    $stmt->execute([3, 'Q4 Performance Reviews', 'Q4 performance reviews will be conducted in the first week of January. Please prepare your self-assessment forms.', 2, 'User']);

    echo "Mock data added successfully!\n";
    echo "Sample users: john@athenas.co.in, sarah@athenas.co.in, mike@athenas.co.in (password: user123)\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
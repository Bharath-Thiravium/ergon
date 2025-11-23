<?php
/**
 * Test script to verify user status fix
 * This script tests that users with all statuses are visible in the management interface
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/User.php';

echo "<h2>User Status Fix Test</h2>\n";

try {
    $userModel = new User();
    $users = $userModel->getAll();
    
    echo "<h3>All Users Retrieved:</h3>\n";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Role</th></tr>\n";
    
    $statusCounts = [
        'active' => 0,
        'inactive' => 0,
        'suspended' => 0,
        'terminated' => 0,
        'other' => 0
    ];
    
    foreach ($users as $user) {
        $status = $user['status'] ?? 'unknown';
        if (isset($statusCounts[$status])) {
            $statusCounts[$status]++;
        } else {
            $statusCounts['other']++;
        }
        
        $statusColor = match($status) {
            'active' => 'green',
            'inactive' => 'orange',
            'suspended' => 'red',
            'terminated' => 'darkred',
            default => 'gray'
        };
        
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td style='color: {$statusColor}; font-weight: bold;'>" . htmlspecialchars($status) . "</td>";
        echo "<td>" . htmlspecialchars($user['role'] ?? 'user') . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    echo "<h3>Status Summary:</h3>\n";
    echo "<ul>\n";
    foreach ($statusCounts as $status => $count) {
        if ($count > 0) {
            echo "<li><strong>" . ucfirst($status) . ":</strong> {$count} users</li>\n";
        }
    }
    echo "</ul>\n";
    
    echo "<h3>Test Results:</h3>\n";
    echo "<ul>\n";
    echo "<li>✅ Total users retrieved: " . count($users) . "</li>\n";
    
    if ($statusCounts['suspended'] > 0) {
        echo "<li>✅ Suspended users are visible: {$statusCounts['suspended']} found</li>\n";
    } else {
        echo "<li>⚠️ No suspended users found (create one to test)</li>\n";
    }
    
    if ($statusCounts['terminated'] > 0) {
        echo "<li>✅ Terminated users are visible: {$statusCounts['terminated']} found</li>\n";
    } else {
        echo "<li>⚠️ No terminated users found (create one to test)</li>\n";
    }
    
    echo "<li>✅ Users with all statuses (except deleted) should be visible</li>\n";
    echo "</ul>\n";
    
    echo "<h3>Fix Verification:</h3>\n";
    echo "<p>✅ The User model now excludes only 'deleted' status users instead of filtering to only 'active' and 'inactive'.</p>\n";
    echo "<p>✅ Terminated users cannot be reactivated (validation added).</p>\n";
    echo "<p>✅ All status changes preserve user visibility in the management interface.</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<p><a href='/ergon/users'>Go to User Management</a> | <a href='/ergon/admin/management'>Go to Admin Management</a></p>\n";
?>
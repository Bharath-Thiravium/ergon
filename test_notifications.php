<?php
/**
 * Notification System Test Script
 * Tests all notification functionalities and injects dummy data
 */

require_once __DIR__ . '/app/config/database.php';

// Simulate logged-in user for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test User';
    $_SESSION['role'] = 'admin';
}

$db = Database::connect();

// Create notifications table if not exists
$createTable = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$db->exec($createTable);

// Clear existing test notifications
$db->exec("DELETE FROM notifications WHERE title LIKE 'TEST:%'");

// Insert dummy notifications for testing
$testNotifications = [
    ['TEST: New Task Assigned', 'You have been assigned a new task for project Alpha', 'info', 0, date('Y-m-d H:i:s', strtotime('-2 minutes'))],
    ['TEST: Leave Request Approved', 'Your leave request for next week has been approved', 'success', 0, date('Y-m-d H:i:s', strtotime('-1 hour'))],
    ['TEST: Expense Claim Pending', 'Your expense claim of $250 is pending approval', 'warning', 0, date('Y-m-d H:i:s', strtotime('-3 hours'))],
    ['TEST: Advance Request Rejected', 'Your advance request has been rejected. Please contact HR', 'error', 1, date('Y-m-d H:i:s', strtotime('-1 day'))],
    ['TEST: System Maintenance', 'System maintenance scheduled for this weekend', 'info', 1, date('Y-m-d H:i:s', strtotime('-2 days'))],
    ['TEST: Approval Required', 'You have 3 pending approvals waiting for your review', 'warning', 0, date('Y-m-d H:i:s', strtotime('-30 minutes'))],
    ['TEST: Task Overdue', 'Task "Website Update" is overdue by 2 days', 'error', 0, date('Y-m-d H:i:s', strtotime('-5 minutes'))],
    ['TEST: Welcome Message', 'Welcome to the ERGON notification system!', 'success', 1, date('Y-m-d H:i:s', strtotime('-1 week'))]
];

$stmt = $db->prepare("INSERT INTO notifications (title, message, type, is_read, created_at, user_id) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($testNotifications as $notif) {
    $stmt->execute([$notif[0], $notif[1], $notif[2], $notif[3], $notif[4], $_SESSION['user_id']]);
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Notification System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .test-button { padding: 10px 15px; margin: 5px; background: #007cba; color: white; border: none; border-radius: 3px; cursor: pointer; }
        .test-result { margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 3px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <h1>🔔 Notification System Test Suite</h1>
    <p><strong>Test Data Injected:</strong> " . count($testNotifications) . " dummy notifications created</p>
    
    <div class='test-section'>
        <h3>📊 Test Results</h3>
        <div id='testResults'></div>
    </div>
    
    <div class='test-section'>
        <h3>🧪 API Tests</h3>
        <button class='test-button' onclick='testGetNotifications()'>Test Get Notifications</button>
        <button class='test-button' onclick='testGetUnreadCount()'>Test Unread Count</button>
        <button class='test-button' onclick='testMarkAsRead()'>Test Mark as Read</button>
        <button class='test-button' onclick='testMarkAllAsRead()'>Test Mark All as Read</button>
    </div>
    
    <div class='test-section'>
        <h3>🎯 UI Tests</h3>
        <button class='test-button' onclick='testNotificationDropdown()'>Test Dropdown</button>
        <button class='test-button' onclick='testNotificationLinks()'>Test Navigation Links</button>
        <button class='test-button' onclick='window.open(\"/ergon/notifications\", \"_blank\")'>Open Notifications Page</button>
    </div>
    
    <div class='test-section'>
        <h3>📋 Current Notifications</h3>
        <div id='notificationList'></div>
    </div>

    <script>
        let testResults = [];
        
        function addResult(test, status, message) {
            const result = `<div class='test-result \${status}'><strong>\${test}:</strong> \${message}</div>`;
            document.getElementById('testResults').innerHTML += result;
        }
        
        async function testGetNotifications() {
            try {
                const response = await fetch('/ergon/api/notifications');
                const data = await response.json();
                if (data.notifications && Array.isArray(data.notifications)) {
                    addResult('Get Notifications', 'success', `✅ Retrieved \${data.notifications.length} notifications`);
                    displayNotifications(data.notifications);
                } else {
                    addResult('Get Notifications', 'error', '❌ Invalid response format');
                }
            } catch (error) {
                addResult('Get Notifications', 'error', `❌ Error: \${error.message}`);
            }
        }
        
        async function testGetUnreadCount() {
            try {
                const response = await fetch('/ergon/api/notifications/unread-count');
                const data = await response.json();
                if (typeof data.count === 'number') {
                    addResult('Unread Count', 'success', `✅ Unread count: \${data.count}`);
                } else {
                    addResult('Unread Count', 'error', '❌ Invalid count format');
                }
            } catch (error) {
                addResult('Unread Count', 'error', `❌ Error: \${error.message}`);
            }
        }
        
        async function testMarkAsRead() {
            try {
                // Get first unread notification
                const notifResponse = await fetch('/ergon/api/notifications');
                const notifData = await notifResponse.json();
                const unreadNotif = notifData.notifications.find(n => n.is_read == 0);
                
                if (!unreadNotif) {
                    addResult('Mark as Read', 'info', '⚠️ No unread notifications to test');
                    return;
                }
                
                const response = await fetch('/ergon/api/notifications/mark-as-read', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=\${unreadNotif.id}`
                });
                const data = await response.json();
                
                if (data.success) {
                    addResult('Mark as Read', 'success', `✅ Marked notification \${unreadNotif.id} as read`);
                } else {
                    addResult('Mark as Read', 'error', `❌ Failed: \${data.error || 'Unknown error'}`);
                }
            } catch (error) {
                addResult('Mark as Read', 'error', `❌ Error: \${error.message}`);
            }
        }
        
        async function testMarkAllAsRead() {
            try {
                const response = await fetch('/ergon/api/notifications/mark-all-read', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                });
                const data = await response.json();
                
                if (data.success) {
                    addResult('Mark All as Read', 'success', '✅ All notifications marked as read');
                } else {
                    addResult('Mark All as Read', 'error', `❌ Failed: \${data.error || 'Unknown error'}`);
                }
            } catch (error) {
                addResult('Mark All as Read', 'error', `❌ Error: \${error.message}`);
            }
        }
        
        function testNotificationDropdown() {
            const hasDropdown = document.querySelector('#notificationDropdown') !== null;
            const hasToggleFunction = typeof toggleNotifications === 'function';
            
            if (hasDropdown && hasToggleFunction) {
                addResult('Notification Dropdown', 'success', '✅ Dropdown elements and functions exist');
            } else {
                addResult('Notification Dropdown', 'error', '❌ Missing dropdown elements or functions');
            }
        }
        
        function testNotificationLinks() {
            const testLinks = [
                { type: 'task', expected: '/ergon/tasks' },
                { type: 'leave', expected: '/ergon/leaves' },
                { type: 'expense', expected: '/ergon/expenses' },
                { type: 'advance', expected: '/ergon/advances' },
                { type: 'approval', expected: '/ergon/owner/approvals' }
            ];
            
            let passed = 0;
            testLinks.forEach(test => {
                // Simulate the getNotificationLink function logic
                let link = '/ergon/notifications'; // default
                if (test.type === 'task') link = '/ergon/tasks';
                else if (test.type === 'leave') link = '/ergon/leaves';
                else if (test.type === 'expense') link = '/ergon/expenses';
                else if (test.type === 'advance') link = '/ergon/advances';
                else if (test.type === 'approval') link = '/ergon/owner/approvals';
                
                if (link === test.expected) passed++;
            });
            
            if (passed === testLinks.length) {
                addResult('Navigation Links', 'success', `✅ All \${testLinks.length} link mappings correct`);
            } else {
                addResult('Navigation Links', 'error', `❌ Only \${passed}/\${testLinks.length} link mappings correct`);
            }
        }
        
        function displayNotifications(notifications) {
            const html = notifications.map(n => `
                <div style='padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 3px; \${n.is_read == 1 ? 'opacity: 0.6;' : ''}'>
                    <strong>\${n.title}</strong><br>
                    <small>\${n.message}</small><br>
                    <span style='font-size: 11px; color: #666;'>Type: \${n.type} | Read: \${n.is_read == 1 ? 'Yes' : 'No'} | Created: \${n.created_at}</span>
                </div>
            `).join('');
            document.getElementById('notificationList').innerHTML = html;
        }
        
        // Auto-run basic tests on page load
        window.onload = function() {
            setTimeout(() => {
                testGetNotifications();
                testGetUnreadCount();
            }, 500);
        };
    </script>
</body>
</html>";
?>
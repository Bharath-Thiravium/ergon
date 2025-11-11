<?php
// Backfill Notifications for Existing Pending Items
session_start();

// Set test session if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'owner';
}

echo "<h1>Backfill Notifications System</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    require_once __DIR__ . '/app/config/database.php';
    require_once __DIR__ . '/app/helpers/NotificationHelper.php';
    $db = Database::connect();
    echo "<div class='success'>✓ Database connected</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
    exit;
}

$totalCreated = 0;

// 1. Backfill Leave Notifications
echo "<h2>1. Processing Leave Requests</h2>";
try {
    $stmt = $db->query("SELECT l.*, u.name as user_name FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.status = 'pending' ORDER BY l.created_at DESC");
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($leaves as $leave) {
        NotificationHelper::notifyOwners(
            $leave['user_id'],
            'leave',
            'request',
            "{$leave['user_name']} has requested leave from {$leave['start_date']} to {$leave['end_date']}",
            $leave['id']
        );
        $totalCreated++;
        echo "<div class='info'>✓ Created leave notification for {$leave['user_name']}</div>";
    }
    echo "<div class='success'>Processed " . count($leaves) . " leave requests</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Leave processing error: " . $e->getMessage() . "</div>";
}

// 2. Backfill Expense Notifications
echo "<h2>2. Processing Expense Claims</h2>";
try {
    $stmt = $db->query("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.status = 'pending' ORDER BY e.created_at DESC");
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($expenses as $expense) {
        NotificationHelper::notifyOwners(
            $expense['user_id'],
            'expense',
            'claim',
            "{$expense['user_name']} submitted expense claim of ₹{$expense['amount']} for {$expense['description']}",
            $expense['id']
        );
        $totalCreated++;
        echo "<div class='info'>✓ Created expense notification for {$expense['user_name']}</div>";
    }
    echo "<div class='success'>Processed " . count($expenses) . " expense claims</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Expense processing error: " . $e->getMessage() . "</div>";
}

// 3. Backfill Task Notifications
echo "<h2>3. Processing Tasks</h2>";
try {
    $stmt = $db->query("SELECT t.*, u.name as assigned_user, ub.name as assigned_by_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id LEFT JOIN users ub ON t.assigned_by = ub.id WHERE t.status IN ('assigned', 'in_progress') ORDER BY t.created_at DESC");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tasks as $task) {
        if ($task['assigned_user']) {
            NotificationHelper::notifyOwners(
                $task['assigned_by'] ?? 1,
                'task',
                'assigned',
                "Task '{$task['title']}' assigned to {$task['assigned_user']}",
                $task['id']
            );
            $totalCreated++;
            echo "<div class='info'>✓ Created task notification for {$task['title']}</div>";
        }
    }
    echo "<div class='success'>Processed " . count($tasks) . " tasks</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Task processing error: " . $e->getMessage() . "</div>";
}

// 4. Backfill Advance Notifications (if table exists)
echo "<h2>4. Processing Advance Requests</h2>";
try {
    $stmt = $db->query("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.status = 'pending' ORDER BY a.created_at DESC");
    $advances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($advances as $advance) {
        NotificationHelper::notifyOwners(
            $advance['user_id'],
            'advance',
            'request',
            "{$advance['user_name']} requested advance of ₹{$advance['amount']}",
            $advance['id']
        );
        $totalCreated++;
        echo "<div class='info'>✓ Created advance notification for {$advance['user_name']}</div>";
    }
    echo "<div class='success'>Processed " . count($advances) . " advance requests</div>";
} catch (Exception $e) {
    echo "<div class='info'>Advances table not found or empty</div>";
}

// 5. Backfill Followup Notifications (if table exists)
echo "<h2>5. Processing Follow-ups</h2>";
try {
    $stmt = $db->query("SELECT f.*, u.name as user_name FROM followups f JOIN users u ON f.user_id = u.id WHERE f.status = 'pending' ORDER BY f.created_at DESC");
    $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($followups as $followup) {
        NotificationHelper::notifyOwners(
            $followup['user_id'],
            'followup',
            'created',
            "{$followup['user_name']} created follow-up: {$followup['title']}",
            $followup['id']
        );
        $totalCreated++;
        echo "<div class='info'>✓ Created followup notification for {$followup['title']}</div>";
    }
    echo "<div class='success'>Processed " . count($followups) . " follow-ups</div>";
} catch (Exception $e) {
    echo "<div class='info'>Followups table not found or empty</div>";
}

// 6. Check Late Arrivals Today
echo "<h2>6. Processing Today's Late Arrivals</h2>";
try {
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM attendance a JOIN users u ON a.user_id = u.id WHERE DATE(a.clock_in) = ? AND TIME(a.clock_in) > '09:30:00' ORDER BY a.clock_in DESC");
    $stmt->execute([$today]);
    $lateArrivals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($lateArrivals as $attendance) {
        NotificationHelper::notifyOwners(
            $attendance['user_id'],
            'attendance',
            'late_arrival',
            "{$attendance['user_name']} arrived late at " . date('H:i', strtotime($attendance['clock_in'])),
            $attendance['id']
        );
        $totalCreated++;
        echo "<div class='info'>✓ Created late arrival notification for {$attendance['user_name']}</div>";
    }
    echo "<div class='success'>Processed " . count($lateArrivals) . " late arrivals</div>";
} catch (Exception $e) {
    echo "<div class='info'>Attendance table not found or empty</div>";
}

echo "<h2>Backfill Complete</h2>";
echo "<div class='success'>Total notifications created: $totalCreated</div>";

// Show final notification count
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM notifications");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<div class='info'>Total notifications in database: $count</div>";
    
    // Show latest notifications
    $stmt = $db->query("SELECT n.*, u.name as sender_name FROM notifications n LEFT JOIN users u ON n.sender_id = u.id ORDER BY n.created_at DESC LIMIT 10");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Latest Notifications:</h3>";
    echo "<ul>";
    foreach ($notifications as $notif) {
        echo "<li>{$notif['module_name']} - {$notif['message']} (from {$notif['sender_name']}) - {$notif['created_at']}</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<div class='error'>Error checking final count: " . $e->getMessage() . "</div>";
}

echo "<p><strong>Next:</strong> Visit <a href='/ergon/notifications' target='_blank'>/ergon/notifications</a> to see the notifications</p>";
?>
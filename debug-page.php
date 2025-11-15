<?php
require_once __DIR__ . '/app/config/database.php';

$logs = [];
$users = [];
$testResults = [];

// Get all users
try {
    $db = Database::connect();
    $stmt = $db->query("SELECT id, name, email, status FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $logs[] = "✅ Retrieved " . count($users) . " users from database";
} catch (Exception $e) {
    $logs[] = "❌ Database error: " . $e->getMessage();
}

// Handle test actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? '';
    
    $logs[] = "🔄 Testing action: $action for user ID: $userId";
    
    if ($action === 'test_delete' && $userId) {
        try {
            // Check current status
            $stmt = $db->prepare("SELECT id, name, status FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $logs[] = "📋 User before: " . json_encode($user);
                
                // Update status
                $stmt = $db->prepare("UPDATE users SET status = 'removed' WHERE id = ?");
                $result = $stmt->execute([$userId]);
                
                if ($result) {
                    // Check after update
                    $stmt = $db->prepare("SELECT id, name, status FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $userAfter = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $logs[] = "📋 User after: " . json_encode($userAfter);
                    $logs[] = "✅ Status update successful";
                    $testResults[] = "SUCCESS: User $userId status changed to 'removed'";
                } else {
                    $logs[] = "❌ Status update failed";
                    $testResults[] = "FAILED: Could not update user $userId status";
                }
            } else {
                $logs[] = "❌ User not found";
                $testResults[] = "FAILED: User $userId not found";
            }
        } catch (Exception $e) {
            $logs[] = "❌ Test error: " . $e->getMessage();
            $testResults[] = "ERROR: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Remove Action</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .log { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .btn { padding: 5px 10px; margin: 2px; cursor: pointer; }
        .btn-test { background: #007cba; color: white; border: none; }
        .btn-reset { background: #28a745; color: white; border: none; }
    </style>
</head>
<body>
    <h1>🔍 Debug Remove Action</h1>
    
    <div class="log">
        <h3>📊 System Status</h3>
        <?php foreach ($logs as $log): ?>
            <div><?= htmlspecialchars($log) ?></div>
        <?php endforeach; ?>
    </div>

    <?php if ($testResults): ?>
    <div class="log">
        <h3>🧪 Test Results</h3>
        <?php foreach ($testResults as $result): ?>
            <div class="<?= strpos($result, 'SUCCESS') === 0 ? 'success' : 'error' ?>"><?= htmlspecialchars($result) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <h3>👥 Users Table</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><strong><?= $user['status'] ?></strong></td>
            <td>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="test_delete">
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                    <button type="submit" class="btn btn-test">Test Remove</button>
                </form>
                <?php if ($user['status'] === 'removed'): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="reset_status">
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                    <button type="submit" class="btn btn-reset">Reset to Active</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="log">
        <h3>🔧 Manual SQL Test</h3>
        <p>Run this in your database to test manually:</p>
        <code>UPDATE users SET status = 'removed' WHERE id = 1;</code><br>
        <code>SELECT id, name, status FROM users WHERE id = 1;</code>
    </div>
</body>
</html>
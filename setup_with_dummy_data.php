<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    die('Access denied. Only owners can run setup.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once 'config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        // Create tables
        $sql = file_get_contents('create_planner_tables.sql');
        $conn->exec($sql);
        
        // Insert dummy data
        $dummyData = file_get_contents('inject_dummy_data.sql');
        $conn->exec($dummyData);
        
        $message = "✅ Tables created and dummy data inserted successfully!";
        $success = true;
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $success = false;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup with Dummy Data - ERGON</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #0056b3; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        ul { text-align: left; }
    </style>
</head>
<body>
    <h1>🧭 ERGON - Setup with Test Data</h1>
    
    <?php if (isset($message)): ?>
        <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>">
            <?= $message ?>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-info">
                <strong>Test Data Created:</strong>
                <ul>
                    <li>5 Users (owner, admin, 3 employees)</li>
                    <li>5 Tasks with different priorities</li>
                    <li>15 Daily planner entries (past, present, future)</li>
                    <li>5 Department form submissions</li>
                    <li>Attendance, leave, and expense records</li>
                </ul>
                <strong>Test Credentials:</strong><br>
                • owner@test.com / password<br>
                • admin@test.com / password<br>
                • user@test.com / password
            </div>
            <p>
                <a href="/ergon/planner/calendar" class="btn">📅 Daily Planner</a>
                <a href="/ergon/tasks" class="btn">✅ Tasks</a>
                <a href="/ergon/dashboard" class="btn">🏠 Dashboard</a>
            </p>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <strong>Setup Complete System with Test Data</strong><br>
            This will create all tables and insert realistic dummy data for testing:
            <ul>
                <li>Database tables (planner, tasks, departments)</li>
                <li>Sample users and departments</li>
                <li>Daily planner entries with different priorities</li>
                <li>Tasks with progress tracking</li>
                <li>Department-specific forms and submissions</li>
                <li>Attendance, leave, and expense records</li>
            </ul>
        </div>
        
        <form method="POST">
            <button type="submit" class="btn">🚀 Setup with Dummy Data</button>
        </form>
    <?php endif; ?>
    
    <p><a href="/ergon/dashboard">← Back to Dashboard</a></p>
</body>
</html>
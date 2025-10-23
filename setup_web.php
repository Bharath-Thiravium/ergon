<?php
session_start();

// Simple authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    die('Access denied. Only owners can run setup.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once 'config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        // Read and execute SQL
        $sql = file_get_contents('create_planner_tables.sql');
        $conn->exec($sql);
        
        $message = "✅ Daily Planner tables created successfully!";
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
    <title>Setup Daily Planner - ERGON</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <h1>🧭 ERGON - Daily Planner Setup</h1>
    
    <?php if (isset($message)): ?>
        <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>">
            <?= $message ?>
        </div>
        
        <?php if ($success): ?>
            <p><a href="/ergon/planner/calendar" class="btn">Go to Daily Planner</a></p>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <strong>Setup Required</strong><br>
            This will create the necessary database tables for the Daily Planner feature:
            <ul>
                <li>daily_planners</li>
                <li>department_form_templates</li>
                <li>department_form_submissions</li>
                <li>departments (if not exists)</li>
            </ul>
        </div>
        
        <form method="POST">
            <button type="submit" class="btn">Create Tables</button>
        </form>
    <?php endif; ?>
    
    <p><a href="/ergon/dashboard">← Back to Dashboard</a></p>
</body>
</html>
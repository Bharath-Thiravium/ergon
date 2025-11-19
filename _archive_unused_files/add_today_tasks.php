<?php
require_once __DIR__ . '/app/config/environment.php';
require_once __DIR__ . '/app/core/Session.php';
require_once __DIR__ . '/app/middlewares/AuthMiddleware.php';

Session::init();
AuthMiddleware::requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/app/config/database.php';
    
    try {
        $db = Database::connect();
        
        $taskData = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'assigned_by' => $_SESSION['user_id'],
            'assigned_to' => intval($_POST['assigned_to'] ?? $_SESSION['user_id']),
            'task_type' => 'ad-hoc',
            'priority' => $_POST['priority'] ?? 'medium',
            'deadline' => date('Y-m-d 23:59:59'),
            'status' => 'assigned',
            'progress' => 0
        ];
        
        if (empty($taskData['title'])) {
            echo json_encode(['success' => false, 'message' => 'Title is required']);
            exit;
        }
        
        $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, task_type, priority, deadline, status, progress, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $result = $stmt->execute([
            $taskData['title'],
            $taskData['description'],
            $taskData['assigned_by'],
            $taskData['assigned_to'],
            $taskData['task_type'],
            $taskData['priority'],
            $taskData['deadline'],
            $taskData['status'],
            $taskData['progress']
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Task added successfully', 'task_id' => $db->lastInsertId()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add task']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    // GET request - show form
    ?>
    <!DOCTYPE html>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Add Today's Task</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body>
        <h2>Add Task for Today</h2>
        <form method="POST">
            <div>
                <label>Title:</label>
                <input type="text" name="title" required>
            </div>
            <div>
                <label>Description:</label>
                <textarea name="description"></textarea>
            </div>
            <div>
                <label>Priority:</label>
                <select name="priority">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <button type="submit">Add Task</button>
        </form>
    </body>
    </html>
    <?php
}
?>
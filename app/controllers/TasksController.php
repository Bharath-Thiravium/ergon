<?php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';

class TasksController {
    private $taskModel;
    private $userModel;
    
    public function __construct() {
        $this->taskModel = new Task();
        $this->userModel = new User();
    }
    
    public function index() {
        $tasks = $this->taskModel->getAllTasks();
        $users = $this->userModel->getAll();
        $stats = $this->taskModel->getTaskStats();
        
        $data = [
            'tasks' => $tasks,
            'users' => $users,
            'stats' => $stats
        ];
        
        include __DIR__ . '/../views/tasks/index.php';
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $taskData = [
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'assigned_by' => $_SESSION['user_id'],
                'assigned_to' => $_POST['assigned_to'],
                'task_type' => $_POST['task_type'],
                'priority' => $_POST['priority'],
                'deadline' => $_POST['deadline']
            ];
            
            $result = $this->taskModel->create($taskData);
            if ($result) {
                header('Location: /ergon/tasks?success=created');
                exit;
            }
        }
        
        $users = $this->userModel->getAll();
        $data = ['users' => $users];
        include __DIR__ . '/../views/tasks/create.php';
    }
    
    public function update($taskId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $progress = $_POST['progress'];
            $comment = $_POST['comment'] ?? null;
            
            $result = $this->taskModel->updateProgress($taskId, $_SESSION['user_id'], $progress, $comment);
            if ($result) {
                header('Location: /ergon/tasks?success=updated');
                exit;
            }
        }
        
        $task = $this->taskModel->getTaskById($taskId);
        $updates = $this->taskModel->getTaskUpdates($taskId);
        
        $data = [
            'task' => $task,
            'updates' => $updates
        ];
        
        include __DIR__ . '/../views/tasks/update.php';
    }
    
    public function calendar() {
        $tasks = $this->taskModel->getTasksForCalendar();
        $data = ['tasks' => $tasks];
        include __DIR__ . '/../views/tasks/calendar.php';
    }
    
    public function overdue() {
        $tasks = $this->taskModel->getOverdueTasks();
        $data = ['tasks' => $tasks];
        include __DIR__ . '/../views/tasks/overdue.php';
    }
    
    public function slaBreaches() {
        $tasks = $this->taskModel->getSLABreaches();
        $data = ['tasks' => $tasks];
        include __DIR__ . '/../views/tasks/sla_breaches.php';
    }
    
    public function getVelocity($userId) {
        header('Content-Type: application/json');
        $velocity = $this->taskModel->getTaskVelocity($userId);
        echo json_encode(['velocity' => $velocity]);
    }
    
    public function getProductivity($userId) {
        header('Content-Type: application/json');
        $productivity = $this->taskModel->getProductivityScore($userId);
        echo json_encode($productivity);
    }
    
    public function bulkCreate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tasks = json_decode($_POST['tasks'], true);
            $result = $this->taskModel->createBulkTasks($tasks);
            echo json_encode(['success' => $result]);
        }
    }
    
    public function getSubtasks($parentId) {
        header('Content-Type: application/json');
        $subtasks = $this->taskModel->getSubtasks($parentId);
        echo json_encode(['subtasks' => $subtasks]);
    }
}
?>
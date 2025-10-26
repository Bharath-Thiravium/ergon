<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/database.php';

class AdminController extends Controller {
    private $taskModel;
    private $userModel;
    
    public function __construct() {
        Session::init();
        $this->taskModel = new Task();
        $this->userModel = new User();
    }
    
    public function dashboard() {
        if (!Session::isLoggedIn() || !in_array(Session::get('role'), ['admin', 'owner'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        try {
            $stats = [
                'my_tasks' => count($this->taskModel->getByUserId(Session::get('user_id')) ?? []),
                'team_members' => count($this->userModel->getAll() ?? [])
            ];
            
            $data = [
                'user_name' => Session::get('user_name'),
                'role' => Session::get('role'),
                'stats' => $stats,
                'active_page' => 'dashboard'
            ];
            
            $this->view('dashboard/admin', $data);
        } catch (Exception $e) {
            error_log('Admin Dashboard Error: ' . $e->getMessage());
            $data = [
                'user_name' => Session::get('user_name'),
                'role' => Session::get('role'),
                'stats' => ['my_tasks' => 0, 'team_members' => 0],
                'active_page' => 'dashboard'
            ];
            $this->view('dashboard/admin', $data);
        }
    }
}
?>

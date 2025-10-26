<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/constants.php';

class AdminController extends Controller {
    private $taskModel;
    private $userModel;
    
    public function __construct() {
        $this->taskModel = new Task();
        $this->userModel = new User();
    }
    
    public function dashboard() {
        $this->requireRole(ROLE_ADMIN);
        
        $stats = [
            'my_tasks' => count($this->taskModel->getByUserId(Session::get('user_id'))),
            'team_members' => count($this->userModel->getAll())
        ];
        
        $data = [
            'user_name' => Session::get('user_name'),
            'role' => Session::get('role'),
            'stats' => $stats
        ];
        
        $this->view('dashboard/admin', $data);
    }
}
?>

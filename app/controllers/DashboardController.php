<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Attendance.php';

class DashboardController {
    private $userModel;
    private $taskModel;
    private $attendanceModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->taskModel = new Task();
        $this->attendanceModel = new Attendance();
    }
    
    public function index() {
        $role = $_SESSION['user_role'];
        
        switch ($role) {
            case 'owner':
                $this->ownerDashboard();
                break;
            case 'admin':
                $this->adminDashboard();
                break;
            case 'user':
                $this->userDashboard();
                break;
        }
    }
    
    private function ownerDashboard() {
        $data = [
            'total_employees' => $this->userModel->getTotalUsers(),
            'attendance_rate' => $this->getAttendanceRate(),
            'tasks_completed' => $this->getTasksCompleted(),
            'monthly_expenses' => $this->getMonthlyExpenses()
        ];
        
        include __DIR__ . '/../views/dashboard/owner.php';
    }
    
    private function adminDashboard() {
        $data = [
            'pending_approvals' => $this->getPendingApprovals(),
            'active_tasks' => $this->getActiveTasks(),
            'absent_today' => $this->getAbsentToday()
        ];
        
        include __DIR__ . '/../views/dashboard/admin.php';
    }
    
    private function userDashboard() {
        $userId = $_SESSION['user_id'];
        $data = [
            'my_tasks' => $this->taskModel->getUserTasks($userId),
            'today_attendance' => $this->attendanceModel->getTodayAttendance($userId)
        ];
        
        include __DIR__ . '/../views/dashboard/user.php';
    }
    
    private function getAttendanceRate() {
        // Calculate attendance rate for current month
        return 85.5; // Placeholder
    }
    
    private function getTasksCompleted() {
        // Get completed tasks count
        return 142; // Placeholder
    }
    
    private function getMonthlyExpenses() {
        // Get current month expenses
        return 25000; // Placeholder
    }
    
    private function getPendingApprovals() {
        // Get pending leave/advance/expense requests
        return 8; // Placeholder
    }
    
    private function getActiveTasks() {
        // Get active tasks count
        return 23; // Placeholder
    }
    
    private function getAbsentToday() {
        // Get today's absent employees
        return 3; // Placeholder
    }
}
?>
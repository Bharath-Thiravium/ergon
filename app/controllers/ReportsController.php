<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class ReportsController {
    private $userModel;
    private $attendanceModel;
    private $taskModel;
    private $activityLogModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->attendanceModel = new Attendance();
        $this->taskModel = new Task();
        $this->activityLogModel = new ActivityLog();
    }
    
    public function index() {
        $data = [
            'attendance_summary' => $this->getAttendanceSummary(),
            'task_summary' => $this->getTaskSummary(),
            'user_performance' => $this->getUserPerformance()
        ];
        
        include __DIR__ . '/../views/reports/index.php';
    }
    
    private function getAttendanceSummary() {
        return [
            'total_present' => 85,
            'total_absent' => 15,
            'average_hours' => 8.2,
            'late_arrivals' => 12
        ];
    }
    
    private function getTaskSummary() {
        return [
            'completed_tasks' => 142,
            'pending_tasks' => 28,
            'overdue_tasks' => 5,
            'completion_rate' => 83.5
        ];
    }
    
    private function getUserPerformance() {
        return [
            ['name' => 'John Doe', 'tasks_completed' => 25, 'attendance_rate' => 95],
            ['name' => 'Jane Smith', 'tasks_completed' => 22, 'attendance_rate' => 98],
            ['name' => 'Mike Johnson', 'tasks_completed' => 18, 'attendance_rate' => 87]
        ];
    }
    
    public function activity() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $data = [
            'activity' => $this->activityLogModel->getRecentActivity(),
            'productivity' => $this->getProductivityData()
        ];
        
        include __DIR__ . '/../views/reports/activity.php';
    }
    
    private function getProductivityData() {
        return [
            'daily_tasks' => 15,
            'completed_today' => 12,
            'efficiency_rate' => 80
        ];
    }
    
    public function export() {
        $data = [
            'attendance_summary' => $this->getAttendanceSummary(),
            'task_summary' => $this->getTaskSummary(),
            'user_performance' => $this->getUserPerformance()
        ];
        
        $csv = "ERGON Reports Export - " . date('Y-m-d H:i:s') . "\n\n";
        
        // Attendance Summary
        $csv .= "ATTENDANCE SUMMARY\n";
        $csv .= "Present Today," . $data['attendance_summary']['total_present'] . "\n";
        $csv .= "Absent Today," . $data['attendance_summary']['total_absent'] . "\n";
        $csv .= "Average Hours," . $data['attendance_summary']['average_hours'] . "\n";
        $csv .= "Late Arrivals," . $data['attendance_summary']['late_arrivals'] . "\n\n";
        
        // Task Summary
        $csv .= "TASK SUMMARY\n";
        $csv .= "Completed Tasks," . $data['task_summary']['completed_tasks'] . "\n";
        $csv .= "Pending Tasks," . $data['task_summary']['pending_tasks'] . "\n";
        $csv .= "Overdue Tasks," . $data['task_summary']['overdue_tasks'] . "\n";
        $csv .= "Completion Rate," . $data['task_summary']['completion_rate'] . "%\n\n";
        
        // User Performance
        $csv .= "USER PERFORMANCE\n";
        $csv .= "Employee,Tasks Completed,Attendance Rate\n";
        foreach ($data['user_performance'] as $user) {
            $csv .= $user['name'] . "," . $user['tasks_completed'] . "," . $user['attendance_rate'] . "%\n";
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="ergon_report_' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit;
    }
}
?>
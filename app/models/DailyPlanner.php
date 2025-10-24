<?php
require_once __DIR__ . '/../../config/database.php';

class DailyPlanner {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function createPlan($data) {
        try {
            $query = "INSERT INTO daily_planner (user_id, department_id, plan_date, title, description, priority, estimated_hours, reminder_time) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                $data['user_id'], $data['department_id'], $data['plan_date'],
                $data['title'], $data['description'], $data['priority'],
                $data['estimated_hours'], $data['reminder_time']
            ]);
        } catch (Exception $e) {
            error_log("DailyPlanner create error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserPlans($userId, $date = null) {
        try {
            $query = "SELECT dp.*, d.name as department_name 
                      FROM daily_planner dp 
                      LEFT JOIN departments d ON dp.department_id = d.id 
                      WHERE dp.user_id = ?";
            $params = [$userId];
            
            if ($date) {
                $query .= " AND dp.plan_date = ?";
                $params[] = $date;
            }
            
            $query .= " ORDER BY dp.plan_date DESC, dp.priority DESC, dp.created_at ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("DailyPlanner getUserPlans error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateProgress($planId, $completionPercentage, $actualHours = null, $notes = null) {
        $status = $completionPercentage >= 100 ? 'completed' : 
                 ($completionPercentage > 0 ? 'in_progress' : 'not_started');
        
        try {
            $query = "UPDATE daily_planner SET completion_percentage = ?, completion_status = ?, actual_hours = ?, notes = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$completionPercentage, $status, $actualHours, $notes, $planId]);
        } catch (Exception $e) {
            error_log("DailyPlanner updateProgress error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCalendarData($userId, $startDate, $endDate) {
        try {
            $query = "SELECT dp.*, d.name as department_name 
                      FROM daily_planner dp 
                      LEFT JOIN departments d ON dp.department_id = d.id 
                      WHERE dp.user_id = ? AND dp.plan_date BETWEEN ? AND ?
                      ORDER BY dp.plan_date, dp.priority DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId, $startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("DailyPlanner getCalendarData error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getDepartmentFormTemplate($departmentId) {
        $query = "SELECT * FROM department_form_templates WHERE department_id = ? AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$departmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function submitDepartmentForm($templateId, $userId, $plannerId, $formData) {
        $query = "INSERT INTO department_form_submissions (template_id, user_id, planner_id, form_data, submission_date, status) 
                  VALUES (?, ?, ?, ?, CURDATE(), 'submitted')";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$templateId, $userId, $plannerId, json_encode($formData)]);
    }
    
    public function getPendingReminders() {
        $query = "SELECT dp.*, u.name as user_name, u.email 
                  FROM daily_planners dp 
                  JOIN users u ON dp.user_id = u.id 
                  WHERE dp.plan_date = CURDATE() 
                  AND dp.reminder_time <= CURTIME() 
                  AND dp.is_reminder_sent = 0 
                  AND dp.completion_status != 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function markReminderSent($planId) {
        $query = "UPDATE daily_planners SET is_reminder_sent = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$planId]);
    }
}
?>
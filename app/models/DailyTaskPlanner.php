<?php

class DailyTaskPlanner {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO daily_task_planner (user_id, project_name, task_description, progress, status, priority, estimated_hours, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            return $stmt->execute([
                $data['user_id'],
                $data['project_name'],
                $data['task_description'],
                $data['progress'] ?? 0,
                $data['status'] ?? 'pending',
                $data['priority'] ?? 'medium',
                $data['estimated_hours'] ?? 1
            ]);
        } catch (Exception $e) {
            error_log("DailyTaskPlanner create error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByUser($userId, $date = null) {
        try {
            if ($date) {
                $stmt = $this->db->prepare("SELECT * FROM daily_task_planner WHERE user_id = ? AND DATE(created_at) = ? ORDER BY created_at DESC");
                $stmt->execute([$userId, $date]);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM daily_task_planner WHERE user_id = ? ORDER BY created_at DESC");
                $stmt->execute([$userId]);
            }
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("DailyTaskPlanner getByUser error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateProgress($id, $progress, $status = null) {
        try {
            if ($status) {
                $stmt = $this->db->prepare("UPDATE daily_task_planner SET progress = ?, status = ?, updated_at = NOW() WHERE id = ?");
                return $stmt->execute([$progress, $status, $id]);
            } else {
                $stmt = $this->db->prepare("UPDATE daily_task_planner SET progress = ?, updated_at = NOW() WHERE id = ?");
                return $stmt->execute([$progress, $id]);
            }
        } catch (Exception $e) {
            error_log("DailyTaskPlanner updateProgress error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getTeamProgress($date = null) {
        try {
            $dateCondition = $date ? "DATE(dtp.created_at) = ?" : "DATE(dtp.created_at) = CURDATE()";
            $stmt = $this->db->prepare("SELECT u.name, u.id as user_id, COUNT(dtp.id) as task_count, AVG(dtp.progress) as avg_progress, 
                                       SUM(CASE WHEN dtp.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
                                       FROM users u 
                                       LEFT JOIN daily_task_planner dtp ON u.id = dtp.user_id AND $dateCondition
                                       WHERE u.role = 'user' AND u.status = 'active'
                                       GROUP BY u.id, u.name");
            
            if ($date) {
                $stmt->execute([$date]);
            } else {
                $stmt->execute();
            }
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("DailyTaskPlanner getTeamProgress error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getProjectStats($days = 7) {
        try {
            $stmt = $this->db->prepare("SELECT project_name, COUNT(*) as total_tasks, 
                                       SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                                       AVG(progress) as avg_progress,
                                       SUM(estimated_hours) as total_hours
                                       FROM daily_task_planner 
                                       WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                                       GROUP BY project_name
                                       ORDER BY total_tasks DESC");
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("DailyTaskPlanner getProjectStats error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getDelayedTasks() {
        try {
            $stmt = $this->db->prepare("SELECT dtp.*, u.name as user_name 
                                       FROM daily_task_planner dtp
                                       JOIN users u ON dtp.user_id = u.id
                                       WHERE dtp.status = 'delayed' OR (dtp.progress < 50 AND dtp.status != 'completed')
                                       ORDER BY dtp.created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("DailyTaskPlanner getDelayedTasks error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUserProductivity($userId, $days = 30) {
        try {
            $stmt = $this->db->prepare("SELECT DATE(created_at) as date, COUNT(*) as tasks, AVG(progress) as avg_progress
                                       FROM daily_task_planner 
                                       WHERE user_id = ? AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                                       GROUP BY DATE(created_at)
                                       ORDER BY date ASC");
            $stmt->execute([$userId, $days]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("DailyTaskPlanner getUserProductivity error: " . $e->getMessage());
            return [];
        }
    }
}

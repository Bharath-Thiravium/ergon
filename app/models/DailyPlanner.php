<?php

class DailyPlanner {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO daily_planner (user_id, plan_date, title, description, department_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            return $stmt->execute([
                $data['user_id'],
                $data['plan_date'],
                $data['title'],
                $data['description'],
                $data['department_id'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("DailyPlanner create error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByUser($userId, $date = null) {
        try {
            if ($date) {
                $stmt = $this->db->prepare("SELECT * FROM daily_planner WHERE user_id = ? AND DATE(plan_date) = ? ORDER BY plan_date ASC");
                $stmt->execute([$userId, $date]);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM daily_planner WHERE user_id = ? ORDER BY plan_date ASC");
                $stmt->execute([$userId]);
            }
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("DailyPlanner getByUser error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getByDate($date) {
        try {
            $stmt = $this->db->prepare("SELECT dp.*, u.name as user_name FROM daily_planner dp JOIN users u ON dp.user_id = u.id WHERE DATE(dp.plan_date) = ? ORDER BY dp.created_at ASC");
            $stmt->execute([$date]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("DailyPlanner getByDate error: " . $e->getMessage());
            return [];
        }
    }
    
    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("UPDATE daily_planner SET title = ?, description = ?, plan_date = ? WHERE id = ?");
            return $stmt->execute([
                $data['title'],
                $data['description'],
                $data['plan_date'],
                $id
            ]);
        } catch (Exception $e) {
            error_log("DailyPlanner update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM daily_planner WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("DailyPlanner delete error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUpcoming($userId, $days = 7) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM daily_planner WHERE user_id = ? AND plan_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY) ORDER BY plan_date ASC");
            $stmt->execute([$userId, $days]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("DailyPlanner getUpcoming error: " . $e->getMessage());
            return [];
        }
    }
}
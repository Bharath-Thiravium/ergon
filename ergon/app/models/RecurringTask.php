<?php
require_once __DIR__ . '/../config/database.php';

class RecurringTask {
    private $db;
    private $table = 'recurring_tasks';
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (title, description, assigned_to, frequency, next_due_date, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['assigned_to'],
            $data['frequency'],
            $data['next_due_date'],
            $data['created_by']
        ]);
    }
    
    public function getDueTasks() {
        $stmt = $this->db->query("
            SELECT rt.*, u.name as assigned_user 
            FROM {$this->table} rt 
            JOIN users u ON rt.assigned_to = u.id 
            WHERE rt.next_due_date <= CURDATE() AND rt.is_active = 1
        ");
        return $stmt->fetchAll();
    }
    
    public function updateNextDueDate($id, $nextDate) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET next_due_date = ?, last_generated = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$nextDate, $id]);
    }
    
    public function getByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE assigned_to = ? AND is_active = 1 
            ORDER BY next_due_date ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
?>
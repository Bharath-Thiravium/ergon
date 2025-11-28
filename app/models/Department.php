<?php
require_once __DIR__ . '/../config/database.php';

class Department {
    private $db;
    private $table = 'departments';
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function getAll() {
        $stmt = $this->db->query("
            SELECT d.*, u.name as head_name 
            FROM {$this->table} d 
            LEFT JOIN users u ON d.head_id = u.id 
            ORDER BY d.name
        ");
        return $stmt->fetchAll();
    }
    
    public function getAllWithStats() {
        $stmt = $this->db->query("
            SELECT d.*, 
                   u.name as head_name,
                   COUNT(emp.id) as employee_count
            FROM {$this->table} d 
            LEFT JOIN users u ON d.head_id = u.id 
            LEFT JOIN users emp ON emp.department_id = d.id AND emp.status = 'active'
            GROUP BY d.id, d.name, d.description, d.head_id, d.status, d.created_at, d.updated_at, u.name
            ORDER BY d.name
        ");
        return $stmt->fetchAll();
    }
    
    public function getStats() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_departments,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_departments,
                (SELECT COUNT(*) FROM users WHERE status = 'active') as total_employees
            FROM {$this->table}
        ");
        return $stmt->fetch();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (name, description, head_id, status) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['head_id'],
            $data['status']
        ]);
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET name = ?, description = ?, head_id = ?, status = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['head_id'],
            $data['status'],
            $id
        ]);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}


<?php
require_once __DIR__ . '/../../config/database.php';

class Department {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getAll() {
        $cacheKey = 'departments_all';
        $cached = Cache::get($cacheKey);
        if ($cached) return $cached;
        
        $query = "SELECT d.*, u.name as head_name, 
                         COUNT(ud.user_id) as employee_count
                  FROM departments d 
                  LEFT JOIN users u ON d.head_id = u.id 
                  LEFT JOIN user_departments ud ON d.id = ud.department_id
                  GROUP BY d.id
                  ORDER BY d.name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        Cache::set($cacheKey, $result);
        return $result;
    }
    
    public function getById($id) {
        $query = "SELECT d.*, u.name as head_name 
                  FROM departments d 
                  LEFT JOIN users u ON d.head_id = u.id 
                  WHERE d.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $query = "INSERT INTO departments (name, description, head_id, status) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['head_id'] ?: null,
            $data['status'] ?? 'active'
        ]);
    }
    
    public function update($id, $data) {
        $query = "UPDATE departments SET name = ?, description = ?, head_id = ?, status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['head_id'] ?: null,
            $data['status'],
            $id
        ]);
    }
    
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total_departments,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_departments,
                    (SELECT COUNT(DISTINCT user_id) FROM user_departments) as total_employees
                  FROM departments";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
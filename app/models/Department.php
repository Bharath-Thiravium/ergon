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
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (name, description, head_id) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['head_id']
        ]);
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
?>

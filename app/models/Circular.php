<?php
require_once __DIR__ . '/../../config/database.php';

class Circular {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function create($title, $message, $posted_by, $visible_to = 'All') {
        $sql = "INSERT INTO circulars (title, message, posted_by, visible_to) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$title, $message, $posted_by, $visible_to]);
    }
    
    public function getVisible($user_role) {
        $sql = "SELECT c.*, u.name as posted_by_name 
                FROM circulars c 
                JOIN users u ON c.posted_by = u.id 
                WHERE visible_to = 'All' OR visible_to = ? 
                ORDER BY c.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([ucfirst($user_role ?? 'user')]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getRecent($user_role, $limit = 5) {
        $sql = "SELECT c.*, u.name as posted_by_name 
                FROM circulars c 
                JOIN users u ON c.posted_by = u.id 
                WHERE visible_to = 'All' OR visible_to = ? 
                ORDER BY c.created_at DESC 
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([ucfirst($user_role ?? 'user'), $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
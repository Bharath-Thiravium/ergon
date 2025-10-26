<?php
require_once __DIR__ . '/../config/database.php';

class UserPreference {
    private $db;
    private $table = 'user_preferences';
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function get($userId, $key) {
        $stmt = $this->db->prepare("
            SELECT value FROM {$this->table} 
            WHERE user_id = ? AND preference_key = ?
        ");
        $stmt->execute([$userId, $key]);
        $result = $stmt->fetch();
        return $result ? $result['value'] : null;
    }
    
    public function set($userId, $key, $value) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (user_id, preference_key, value) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE value = VALUES(value)
        ");
        return $stmt->execute([$userId, $key, $value]);
    }
    
    public function getAll($userId) {
        $stmt = $this->db->prepare("
            SELECT preference_key, value 
            FROM {$this->table} 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $preferences = [];
        while ($row = $stmt->fetch()) {
            $preferences[$row['preference_key']] = $row['value'];
        }
        return $preferences;
    }
    
    public function delete($userId, $key) {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table} 
            WHERE user_id = ? AND preference_key = ?
        ");
        return $stmt->execute([$userId, $key]);
    }
}
?>
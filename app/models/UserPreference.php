<?php
require_once __DIR__ . '/../config/database.php';

class UserPreference {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
        $this->ensureTable();
    }
    
    private function ensureTable() {
        try {
            DatabaseHelper::safeExec($this->db, "CREATE TABLE IF NOT EXISTS user_preferences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                preference_key VARCHAR(100) NOT NULL,
                preference_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_key (user_id, preference_key)
            )", "Model operation");
        } catch (Exception $e) {
            error_log('UserPreference table creation error: ' . $e->getMessage());
        }
    }
    
    public function set($userId, $key, $value) {
        try {
            $stmt = $this->db->prepare("INSERT INTO user_preferences (user_id, preference_key, preference_value) 
                                       VALUES (?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value)");
            return $stmt->execute([$userId, $key, $value]);
        } catch (Exception $e) {
            error_log('UserPreference set error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function get($userId, $key, $default = null) {
        try {
            $stmt = $this->db->prepare("SELECT preference_value FROM user_preferences WHERE user_id = ? AND preference_key = ?");
            $stmt->execute([$userId, $key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['preference_value'] : $default;
        } catch (Exception $e) {
            error_log('UserPreference get error: ' . $e->getMessage());
            return $default;
        }
    }
}
?>

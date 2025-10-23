<?php
require_once __DIR__ . '/../../config/database.php';

class UserPreference {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getUserPreferences($userId) {
        try {
            $this->createTableIfNotExists();
            $stmt = $this->conn->prepare("
                SELECT preference_key, preference_value 
                FROM user_preferences 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $preferences = [];
            foreach ($results as $row) {
                $preferences[$row['preference_key']] = $row['preference_value'];
            }
            
            // Default preferences
            $defaults = [
                'theme' => 'light',
                'language' => 'en',
                'timezone' => 'UTC',
                'notifications_email' => '1',
                'notifications_browser' => '1',
                'dashboard_layout' => 'default'
            ];
            
            return array_merge($defaults, $preferences);
        } catch (Exception $e) {
            error_log("getUserPreferences Error: " . $e->getMessage());
            return [
                'theme' => 'light',
                'language' => 'en',
                'timezone' => 'UTC',
                'notifications_email' => '1',
                'notifications_browser' => '1',
                'dashboard_layout' => 'default'
            ];
        }
    }
    
    public function updatePreference($userId, $key, $value) {
        try {
            $this->createTableIfNotExists();
            $stmt = $this->conn->prepare("
                INSERT INTO user_preferences (user_id, preference_key, preference_value) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value)
            ");
            return $stmt->execute([$userId, $key, $value]);
        } catch (Exception $e) {
            error_log("UserPreference Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function createTableIfNotExists() {
        try {
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS user_preferences (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    preference_key VARCHAR(50) NOT NULL,
                    preference_value TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_user_pref (user_id, preference_key)
                )
            ");
        } catch (Exception $e) {
            error_log("Table creation error: " . $e->getMessage());
        }
    }
    
    public function updateMultiplePreferences($userId, $preferences) {
        try {
            $this->createTableIfNotExists();
            $this->conn->beginTransaction();
            
            foreach ($preferences as $key => $value) {
                $stmt = $this->conn->prepare("
                    INSERT INTO user_preferences (user_id, preference_key, preference_value) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value)
                ");
                $stmt->execute([$userId, $key, $value]);
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("updateMultiplePreferences Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
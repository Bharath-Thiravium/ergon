<?php
require_once __DIR__ . '/../../config/database.php';

class SettingsController {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function index() {
        $settings = $this->getSettings();
        $data = ['settings' => $settings];
        include __DIR__ . '/../views/settings/index.php';
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->updateSettings($_POST);
            if ($result) {
                header('Location: /ergon/settings?success=1');
            } else {
                header('Location: /ergon/settings?error=1');
            }
            exit;
        }
    }
    
    private function getSettings() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM settings LIMIT 1");
            $stmt->execute();
            $settings = $stmt->fetch();
            
            if (!$settings) {
                return [
                    'company_name' => 'ERGON Company',
                    'attendance_radius' => 200,
                    'backup_email' => '',
                    'base_location_lat' => 0,
                    'base_location_lng' => 0
                ];
            }
            
            return $settings;
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function updateSettings($data) {
        try {
            // Check if settings exist
            $stmt = $this->conn->prepare("SELECT id FROM settings LIMIT 1");
            $stmt->execute();
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Update existing settings
                $stmt = $this->conn->prepare("
                    UPDATE settings SET 
                    company_name = ?, 
                    attendance_radius = ?, 
                    backup_email = ?, 
                    base_location_lat = ?, 
                    base_location_lng = ?
                    WHERE id = ?
                ");
                
                return $stmt->execute([
                    $data['company_name'],
                    $data['attendance_radius'],
                    $data['backup_email'],
                    $data['base_location_lat'] ?? 0,
                    $data['base_location_lng'] ?? 0,
                    $exists['id']
                ]);
            } else {
                // Insert new settings
                $stmt = $this->conn->prepare("
                    INSERT INTO settings (company_name, attendance_radius, backup_email, base_location_lat, base_location_lng) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                return $stmt->execute([
                    $data['company_name'],
                    $data['attendance_radius'],
                    $data['backup_email'],
                    $data['base_location_lat'] ?? 0,
                    $data['base_location_lng'] ?? 0
                ]);
            }
        } catch (Exception $e) {
            error_log("Settings update error: " . $e->getMessage());
            return false;
        }
    }
}
?>
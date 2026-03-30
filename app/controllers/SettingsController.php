<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/DatabaseHelper.php';

class SettingsController extends Controller {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        $settings = $this->getSettings();
        
        $data = [
            'settings' => $settings,
            'active_page' => 'settings'
        ];
        
        $this->view('settings/index', $data);
    }
    
    public function update() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $settings = [
                    'company_name' => trim($_POST['company_name'] ?? ''),
                    'office_latitude' => floatval($_POST['office_latitude'] ?? 0),
                    'office_longitude' => floatval($_POST['office_longitude'] ?? 0),
                    'attendance_radius' => max(5, min(1000, intval($_POST['attendance_radius'] ?? 50))),
                    'location_title' => trim($_POST['location_title'] ?? 'Main Office'),
                    'office_address' => trim($_POST['office_address'] ?? '')
                ];
                
                $result = $this->updateSettings($settings);
                
                if ($result) {
                    header('Location: /ergon/settings?success=Settings updated successfully');
                } else {
                    header('Location: /ergon/settings?error=Failed to update settings');
                }
            } catch (Exception $e) {
                error_log('Settings update error: ' . $e->getMessage());
                header('Location: /ergon/settings?error=Database error');
            }
            exit;
        }
        
        $this->index();
    }
    
    public function locationPicker() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        include __DIR__ . '/../../views/settings/location_picker.php';
    }
    
    public function mapPicker() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        $this->view('settings/map_picker');
    }
    
    public function locationDiagnostic() {
        AuthMiddleware::requireAuth();
        
        if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        $this->view('settings/location_diagnostic');
    }
    
    private function getSettings() {
        try {
            // Ensure settings table exists
            DatabaseHelper::safeExec($this->db, "CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_name VARCHAR(255) DEFAULT 'ERGON Company',
                base_location_lat DECIMAL(10,8) DEFAULT 0,
                base_location_lng DECIMAL(11,8) DEFAULT 0,
                attendance_radius INT DEFAULT 5,
                office_address TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )", "Create table");
            
            // Add address column if it doesn't exist
            try {
                DatabaseHelper::safeExec($this->db, "ALTER TABLE settings ADD COLUMN office_address TEXT NULL", "Alter table");
            } catch (Exception $e) {}
            
            $stmt = $this->db->query("SELECT * FROM settings LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                // Insert default settings
                DatabaseHelper::safeExec($this->db, "INSERT INTO settings (company_name, base_location_lat, base_location_lng, attendance_radius) VALUES ('ERGON Company', 0, 0, 5)", "Insert data");
                $stmt = $this->db->query("SELECT * FROM settings LIMIT 1");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return $result ?: [
                'company_name' => 'ERGON Company',
                'base_location_lat' => 0,
                'base_location_lng' => 0,
                'attendance_radius' => 5
            ];
        } catch (Exception $e) {
            error_log('Settings fetch error: ' . $e->getMessage());
            return ['company_name' => 'ERGON Company', 'attendance_radius' => 5];
        }
    }
    
    private function updateSettings($settings) {
        try {
            // Validate location data
            if ($settings['office_latitude'] != 0 && $settings['office_longitude'] != 0) {
                // Validate coordinate ranges
                if ($settings['office_latitude'] < -90 || $settings['office_latitude'] > 90) {
                    throw new Exception('Invalid latitude. Must be between -90 and 90.');
                }
                if ($settings['office_longitude'] < -180 || $settings['office_longitude'] > 180) {
                    throw new Exception('Invalid longitude. Must be between -180 and 180.');
                }
            }
            
            // Validate radius
            if ($settings['attendance_radius'] < 5 || $settings['attendance_radius'] > 1000) {
                throw new Exception('Attendance radius must be between 5 and 1000 meters.');
            }
            
            // Get the first settings record ID
            $stmt = $this->db->query("SELECT id FROM settings LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Update existing record
                $sql = "UPDATE settings SET 
                        company_name = ?, 
                        base_location_lat = ?, 
                        base_location_lng = ?, 
                        attendance_radius = ?,
                        location_title = ?,
                        office_address = ?,
                        updated_at = NOW()
                        WHERE id = ?";
                
                $stmt = $this->db->prepare($sql);
                $success = $stmt->execute([
                    $settings['company_name'],
                    $settings['office_latitude'],
                    $settings['office_longitude'],
                    $settings['attendance_radius'],
                    $settings['location_title'] ?? 'Main Office',
                    $settings['office_address'],
                    $result['id']
                ]);
            } else {
                // Insert new record
                $sql = "INSERT INTO settings (company_name, base_location_lat, base_location_lng, attendance_radius, location_title, office_address) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $success = $stmt->execute([
                    $settings['company_name'],
                    $settings['office_latitude'],
                    $settings['office_longitude'],
                    $settings['attendance_radius'],
                    $settings['location_title'] ?? 'Main Office',
                    $settings['office_address']
                ]);
            }
            
            if ($success) {
                error_log("[SETTINGS_DEBUG] Location updated: ({$settings['office_latitude']}, {$settings['office_longitude']}) with radius {$settings['attendance_radius']}m");
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log('Settings update error: ' . $e->getMessage());
            return false;
        }
    }
    

}
?>

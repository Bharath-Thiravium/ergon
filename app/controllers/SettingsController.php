<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../config/database.php';

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
                    'attendance_radius' => max(5, intval($_POST['attendance_radius'] ?? 5))
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
    
    private function getSettings() {
        try {
            // Ensure settings table exists
            $this->db->exec("CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_name VARCHAR(255) DEFAULT 'ERGON Company',
                base_location_lat DECIMAL(10,8) DEFAULT 0,
                base_location_lng DECIMAL(11,8) DEFAULT 0,
                attendance_radius INT DEFAULT 5,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            $stmt = $this->db->query("SELECT * FROM settings LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                // Insert default settings
                $this->db->exec("INSERT INTO settings (company_name, base_location_lat, base_location_lng, attendance_radius) VALUES ('ERGON Company', 0, 0, 5)");
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
            // Get the first settings record ID
            $stmt = $this->db->query("SELECT id FROM settings LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Update existing record
                $sql = "UPDATE settings SET 
                        company_name = ?, 
                        base_location_lat = ?, 
                        base_location_lng = ?, 
                        attendance_radius = ? 
                        WHERE id = ?";
                
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    $settings['company_name'],
                    $settings['office_latitude'],
                    $settings['office_longitude'],
                    $settings['attendance_radius'],
                    $result['id']
                ]);
            } else {
                // Insert new record
                $sql = "INSERT INTO settings (company_name, base_location_lat, base_location_lng, attendance_radius) VALUES (?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    $settings['company_name'],
                    $settings['office_latitude'],
                    $settings['office_longitude'],
                    $settings['attendance_radius']
                ]);
            }
        } catch (Exception $e) {
            error_log('Settings update error: ' . $e->getMessage());
            return false;
        }
    }
    

}
?>

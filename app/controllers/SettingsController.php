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
                    'company_name' => Security::sanitizeString($_POST['company_name'] ?? ''),
                    'working_hours_start' => $_POST['working_hours_start'] ?? '09:00',
                    'working_hours_end' => $_POST['working_hours_end'] ?? '18:00',
                    'timezone' => Security::sanitizeString($_POST['timezone'] ?? 'Asia/Kolkata'),
                    'office_latitude' => floatval($_POST['office_latitude'] ?? 0),
                    'office_longitude' => floatval($_POST['office_longitude'] ?? 0),
                    'office_address' => Security::sanitizeString($_POST['office_address'] ?? '', 500),
                    'attendance_radius' => intval($_POST['attendance_radius'] ?? 200)
                ];
                
                error_log('Form POST data: ' . json_encode($_POST));
                
                if ($this->updateSettings($settings)) {
                    header('Location: /ergon/settings?success=1');
                } else {
                    header('Location: /ergon/settings?error=1');
                }
            } catch (Exception $e) {
                error_log('Settings update error: ' . $e->getMessage());
                header('Location: /ergon/settings?error=1');
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
    
    private function getSettings() {
        try {
            $stmt = $this->db->query("SELECT * FROM settings LIMIT 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: [
                'company_name' => 'ERGON Company',
                'timezone' => 'Asia/Kolkata',
                'working_hours_start' => '09:00:00',
                'working_hours_end' => '18:00:00',
                'base_location_lat' => 0,
                'base_location_lng' => 0,
                'attendance_radius' => 200,
                'office_address' => ''
            ];
        } catch (Exception $e) {
            return ['company_name' => 'ERGON Company', 'timezone' => 'Asia/Kolkata', 'working_hours_start' => '09:00:00', 'working_hours_end' => '18:00:00', 'attendance_radius' => 200];
        }
    }
    
    private function updateSettings($settings) {
        try {
            // Check if settings record exists
            $stmt = $this->db->query("SELECT id FROM settings LIMIT 1");
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Update existing record
                $sql = "UPDATE settings SET company_name=?, timezone=?, working_hours_start=?, working_hours_end=?, base_location_lat=?, base_location_lng=?, attendance_radius=?, office_address=? WHERE id=?";
                $result = $this->db->prepare($sql)->execute([
                    $settings['company_name'], $settings['timezone'], $settings['working_hours_start'], $settings['working_hours_end'],
                    $settings['office_latitude'], $settings['office_longitude'], $settings['attendance_radius'], $settings['office_address'], $exists['id']
                ]);
            } else {
                // Insert new record
                $sql = "INSERT INTO settings (company_name, timezone, working_hours_start, working_hours_end, base_location_lat, base_location_lng, attendance_radius, office_address) VALUES (?,?,?,?,?,?,?,?)";
                $result = $this->db->prepare($sql)->execute([
                    $settings['company_name'], $settings['timezone'], $settings['working_hours_start'], $settings['working_hours_end'],
                    $settings['office_latitude'], $settings['office_longitude'], $settings['attendance_radius'], $settings['office_address']
                ]);
            }
            return $result;
        } catch (Exception $e) {
            error_log('Settings error: ' . $e->getMessage());
            return false;
        }
    }
    

}
?>

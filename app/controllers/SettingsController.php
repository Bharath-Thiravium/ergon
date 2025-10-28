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
        
        include __DIR__ . '/../../views/settings/index.php';
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
                    'company_email' => Security::validateEmail($_POST['company_email'] ?? ''),
                    'company_phone' => Security::sanitizeString($_POST['company_phone'] ?? ''),
                    'company_address' => Security::sanitizeString($_POST['company_address'] ?? '', 500),
                    'working_hours_start' => $_POST['working_hours_start'] ?? '09:00',
                    'working_hours_end' => $_POST['working_hours_end'] ?? '18:00',
                    'timezone' => Security::sanitizeString($_POST['timezone'] ?? 'UTC'),
                    'office_latitude' => floatval($_POST['office_latitude'] ?? 0),
                    'office_longitude' => floatval($_POST['office_longitude'] ?? 0),
                    'office_address' => Security::sanitizeString($_POST['office_address'] ?? '', 500),
                    'attendance_radius' => intval($_POST['attendance_radius'] ?? 200)
                ];
                
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
            $sql = "SELECT * FROM settings LIMIT 1";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return [
                    'company_name' => 'ERGON',
                    'company_email' => '',
                    'company_phone' => '',
                    'company_address' => '',
                    'working_hours_start' => '09:00',
                    'working_hours_end' => '18:00',
                    'timezone' => 'UTC'
                ];
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('getSettings error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function updateSettings($settings) {
        try {
            $sql = "INSERT INTO settings (company_name, company_email, company_phone, company_address, working_hours_start, working_hours_end, timezone, office_latitude, office_longitude, office_address, attendance_radius, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    company_name = VALUES(company_name),
                    company_email = VALUES(company_email),
                    company_phone = VALUES(company_phone),
                    company_address = VALUES(company_address),
                    working_hours_start = VALUES(working_hours_start),
                    working_hours_end = VALUES(working_hours_end),
                    timezone = VALUES(timezone),
                    office_latitude = VALUES(office_latitude),
                    office_longitude = VALUES(office_longitude),
                    office_address = VALUES(office_address),
                    attendance_radius = VALUES(attendance_radius),
                    updated_at = NOW()";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $settings['company_name'],
                $settings['company_email'],
                $settings['company_phone'],
                $settings['company_address'],
                $settings['working_hours_start'],
                $settings['working_hours_end'],
                $settings['timezone'],
                $settings['office_latitude'],
                $settings['office_longitude'],
                $settings['office_address'],
                $settings['attendance_radius']
            ]);
        } catch (Exception $e) {
            error_log('updateSettings error: ' . $e->getMessage());
            return false;
        }
    }
}
?>

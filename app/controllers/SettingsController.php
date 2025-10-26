<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/Security.php';

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
                    'company_email' => Security::validateEmail($_POST['company_email'] ?? ''),
                    'company_phone' => Security::sanitizeString($_POST['company_phone'] ?? ''),
                    'company_address' => Security::sanitizeString($_POST['company_address'] ?? '', 500),
                    'working_hours_start' => $_POST['working_hours_start'] ?? '09:00',
                    'working_hours_end' => $_POST['working_hours_end'] ?? '18:00',
                    'timezone' => Security::sanitizeString($_POST['timezone'] ?? 'UTC')
                ];
                
                if ($this->updateSettings($settings)) {
                    header('Location: /ergon/public/settings?success=1');
                } else {
                    header('Location: /ergon/public/settings?error=1');
                }
            } catch (Exception $e) {
                error_log('Settings update error: ' . $e->getMessage());
                header('Location: /ergon/public/settings?error=1');
            }
            exit;
        }
        
        $this->index();
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
            $sql = "INSERT INTO settings (company_name, company_email, company_phone, company_address, working_hours_start, working_hours_end, timezone, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    company_name = VALUES(company_name),
                    company_email = VALUES(company_email),
                    company_phone = VALUES(company_phone),
                    company_address = VALUES(company_address),
                    working_hours_start = VALUES(working_hours_start),
                    working_hours_end = VALUES(working_hours_end),
                    timezone = VALUES(timezone),
                    updated_at = NOW()";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $settings['company_name'],
                $settings['company_email'],
                $settings['company_phone'],
                $settings['company_address'],
                $settings['working_hours_start'],
                $settings['working_hours_end'],
                $settings['timezone']
            ]);
        } catch (Exception $e) {
            error_log('updateSettings error: ' . $e->getMessage());
            return false;
        }
    }
}
?>

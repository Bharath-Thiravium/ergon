<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Holiday.php';
require_once __DIR__ . '/../config/database.php';

class HolidayController extends Controller {
    private $holidayModel;
    private $conn;
    
    public function __construct() {
        $this->holidayModel = new Holiday();
        $this->conn = Database::connect();
    }
    
    /**
     * Display holidays management page (admin/owner only)
     */
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'owner']);
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');
            
            $holidays = $this->holidayModel->getAll([
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            // Get departments for filter
            $stmt = $db->prepare("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name");
            $stmt->execute();
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('Holiday index error: ' . $e->getMessage());
            $holidays = [];
            $departments = [];
        }
        
        $this->view('admin/holidays_management', [
            'holidays' => $holidays,
            'departments' => $departments,
            'active_page' => 'holidays',
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
    
    public function create() {
        $this->requireAuth();
        $this->requireRole(['admin', 'owner']);
        
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            // Log incoming request for debugging
            error_log('Holiday create request - POST data: ' . print_r($_POST, true));
            
            $data = [
                'holiday_date' => $_POST['holiday_date'] ?? null,
                'holiday_name' => $_POST['holiday_name'] ?? null,
                'holiday_type' => $_POST['holiday_type'] ?? 'Company',
                'description' => $_POST['description'] ?? null,
                'applies_to' => $_POST['applies_to'] ?? 'All',
                'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : null,
                'repeat_yearly' => isset($_POST['repeat_yearly']) && $_POST['repeat_yearly'] === 'on' ? true : false,
                'created_by' => $_SESSION['user_id']
            ];
            
            error_log('Holiday data prepared: ' . json_encode($data));
            
            // Validation
            if (!$data['holiday_date']) {
                throw new Exception('Holiday date is required');
            }
            if (!$data['holiday_name']) {
                throw new Exception('Holiday name is required');
            }
            
            if (!$this->isValidDate($data['holiday_date'])) {
                throw new Exception('Invalid holiday date format. Use YYYY-MM-DD');
            }
            
            // Check if holiday model has the required methods
            if (!method_exists($this->holidayModel, 'isDuplicate')) {
                error_log('Holiday model missing isDuplicate method');
            } else {
                if ($this->holidayModel->isDuplicate($data['holiday_date'])) {
                    throw new Exception('Holiday already exists for this date');
                }
            }
            
            // Check if create method exists
            if (!method_exists($this->holidayModel, 'create')) {
                throw new Exception('Holiday model missing create method');
            }
            
            $holidayId = $this->holidayModel->create($data);
            
            if (!$holidayId) {
                throw new Exception('Failed to create holiday - no ID returned');
            }
            
            error_log('Holiday created successfully with ID: ' . $holidayId);
            
            // Count affected users
            $affectedCount = 0;
            if ($data['applies_to'] === 'All') {
                $stmt = $this->conn->prepare('SELECT COUNT(*) as count FROM users WHERE status = "active"');
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $affectedCount = $result['count'] ?? 0;
            } elseif ($data['applies_to'] === 'Department' && $data['department_id']) {
                $stmt = $this->conn->prepare('SELECT COUNT(*) as count FROM users WHERE status = "active" AND department_id = ?');
                $stmt->execute([$data['department_id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $affectedCount = $result['count'] ?? 0;
            }
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Holiday created successfully',
                'holiday_id' => $holidayId,
                'affected_users' => $affectedCount,
                'holiday_date' => $data['holiday_date'],
                'holiday_name' => $data['holiday_name']
            ]);
            
        } catch (Exception $e) {
            error_log('Holiday create error: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Update existing holiday (AJAX POST)
     */
    public function update() {
        $this->requireAuth();
        $this->requireRole(['admin', 'owner']);
        
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid holiday ID');
            }
            
            $holiday = $this->holidayModel->getById($id);
            if (!$holiday) {
                throw new Exception('Holiday not found');
            }
            
            $data = [
                'holiday_name' => $_POST['holiday_name'] ?? $holiday['holiday_name'],
                'holiday_type' => $_POST['holiday_type'] ?? $holiday['holiday_type'],
                'description' => $_POST['description'] ?? $holiday['description'],
                'applies_to' => $_POST['applies_to'] ?? $holiday['applies_to'],
                'department_id' => !empty($_POST['department_id']) ? intval($_POST['department_id']) : $holiday['department_id'],
                'repeat_yearly' => isset($_POST['repeat_yearly']) && $_POST['repeat_yearly'] === 'on' ? true : false
            ];
            
            if ($this->holidayModel->update($id, $data)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Holiday updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update holiday');
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Delete holiday (AJAX POST)
     */
    public function delete() {
        $this->requireAuth();
        $this->requireRole(['admin', 'owner']);
        
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid holiday ID');
            }
            
            if ($this->holidayModel->delete($id)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Holiday deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete holiday');
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Get holiday details (AJAX GET)
     */
    public function get() {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid holiday ID');
            }
            
            $holiday = $this->holidayModel->getById($id);
            if (!$holiday) {
                throw new Exception('Holiday not found');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $holiday
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Get upcoming holidays (AJAX GET)
     */
    public function upcoming() {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            $days = intval($_GET['days'] ?? 30);
            $holidays = $this->holidayModel->getUpcoming($days);
            
            echo json_encode([
                'success' => true,
                'data' => $holidays
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Check if today is holiday (API endpoint)
     */
    public function today() {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            $holiday = $this->holidayModel->getTodayHoliday();
            
            echo json_encode([
                'success' => true,
                'is_holiday' => $holiday !== null,
                'holiday' => $holiday
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Get holidays for calendar (AJAX GET)
     */
    public function calendar() {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            $startDate = $_GET['start'] ?? date('Y-m-01');
            $endDate = $_GET['end'] ?? date('Y-m-t');
            
            $holidays = $this->holidayModel->getAll([
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            // Format for calendar display
            $formatted = [];
            foreach ($holidays as $holiday) {
                $formatted[] = [
                    'id' => $holiday['id'],
                    'title' => $holiday['holiday_name'],
                    'date' => $holiday['holiday_date'],
                    'type' => $holiday['holiday_type'],
                    'color' => $this->getHolidayColor($holiday['holiday_type'])
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $formatted
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Verify attendance is correct for holidays
     */
    public function verifyAttendance() {
        $this->requireAuth();
        $this->requireRole(['admin', 'owner']);
        
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');
            
            // Get all holidays in range
            $holidays = $this->holidayModel->getAll([
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            $issues = [];
            
            foreach ($holidays as $holiday) {
                // Check if all employees have correct attendance marking
                $stmt = $db->prepare(
                    "SELECT a.id FROM attendance a 
                     WHERE DATE(a.check_in) = ? 
                     AND a.is_holiday = 0 
                     AND a.user_id IN (SELECT u.id FROM users u WHERE u.status = 'active' 
                     " . ($holiday['applies_to'] === 'Department' ? "AND u.department_id = ?" : "") . ")"
                );
                
                $params = [$holiday['holiday_date']];
                if ($holiday['applies_to'] === 'Department') {
                    $params[] = $holiday['department_id'];
                }
                
                $stmt->execute($params);
                $unmatchedRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($unmatchedRecords) > 0) {
                    $issues[] = [
                        'holiday_id' => $holiday['id'],
                        'holiday_name' => $holiday['holiday_name'],
                        'date' => $holiday['holiday_date'],
                        'unmatched_records' => count($unmatchedRecords)
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'issues' => $issues,
                'total_issues' => count($issues)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Validation helper
     */
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Get color for holiday type
     */
    private function getHolidayColor($type) {
        $colors = [
            'National' => '#0066cc',
            'Festival' => '#ff6600',
            'Company' => '#00cc66',
            'Emergency' => '#cc0000',
            'Other' => '#9933cc'
        ];
        return $colors[$type] ?? '#0066cc';
    }
    
    /**
     * Require specific role
     */
    private function checkRole($roles) {
        if (!in_array($_SESSION['role'] ?? null, $roles)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
    }
}
?>

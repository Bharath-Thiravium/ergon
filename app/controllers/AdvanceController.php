<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Advance.php';

class AdvanceController {
    private $advance;
    
    public function __construct() {
        $this->advance = new Advance();
    }
    
    public function create() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'user_id' => $_SESSION['user_id'],
                'type' => $_POST['type'],
                'amount' => $_POST['amount'],
                'reason' => $_POST['reason'],
                'repayment_date' => $_POST['repayment_date']
            ];
            
            try {
                if ($this->advance->create($data)) {
                    header('Location: /ergon/user/requests?success=1');
                    exit;
                }
            } catch (PDOException $e) {
                error_log('Advance creation error: ' . $e->getMessage());
                header('Location: /ergon/user/requests?error=1');
                exit;
            }
        }
        
        include __DIR__ . '/../views/advances/create.php';
    }
}
?>
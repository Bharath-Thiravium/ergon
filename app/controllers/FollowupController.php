<?php
require_once __DIR__ . '/../core/Controller.php';

class FollowupController extends Controller {
    
    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /ergon/login');
            exit;
        }
        
        $active_page = 'followups';
        $title = 'Follow-ups Management';
        
        include __DIR__ . '/../../views/followups/index.php';
    }
}
?>
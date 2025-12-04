<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../helpers/NotificationHelper.php';

// Enable output compression
if (!ob_get_level()) {
    ob_start('ob_gzhandler');
}

class ContactFollowupController extends Controller {
    
    public function getFollowupHistory($id) {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT f.*, c.name as contact_name FROM followups f LEFT JOIN contacts c ON f.contact_id = c.id WHERE f.id = ?");
            $stmt->execute([$id]);
            $followup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($followup) {
                $stmt = $db->prepare("SELECT h.*, u.name as user_name FROM followup_history h LEFT JOIN users u ON h.created_by = u.id WHERE h.followup_id = ? ORDER BY h.created_at DESC");
                $stmt->execute([$id]);
                $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'followup' => $followup, 'history' => $history]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Follow-up not found']);
            }
        } catch (Exception $e) {
            error_log('History error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}
?>

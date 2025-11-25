<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Notification.php';

Session::init();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = Database::connect();
    $userId = $_SESSION['user_id'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Enhanced GET with cursor pagination
        $limit = min(intval($_GET['limit'] ?? 20), 100);
        $cursor = $_GET['cursor'] ?? null;
        $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
        
        $whereClause = "WHERE n.receiver_id = ?";
        $params = [$userId];
        
        if ($unreadOnly) {
            $whereClause .= " AND n.is_read = 0";
        }
        
        if ($cursor) {
            $whereClause .= " AND n.created_at < ?";
            $params[] = $cursor;
        }
        
        $sql = "
            SELECT n.*, u.name as sender_name 
            FROM notifications n 
            LEFT JOIN users u ON n.sender_id = u.id 
            {$whereClause}
            ORDER BY n.created_at DESC, n.id DESC
            LIMIT ?
        ";
        $params[] = $limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get unread count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ? AND is_read = 0");
        $countStmt->execute([$userId]);
        $unreadCount = $countStmt->fetchColumn();
        
        // Determine next cursor
        $nextCursor = null;
        if (count($notifications) === $limit && !empty($notifications)) {
            $lastNotification = end($notifications);
            $nextCursor = $lastNotification['created_at'];
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'next_cursor' => $nextCursor,
            'has_more' => $nextCursor !== null
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'mark-read':
                $ids = $input['ids'] ?? [];
                if (!is_array($ids) || empty($ids)) {
                    throw new Exception('Invalid notification IDs');
                }
                
                // Verify ownership and mark as read
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $stmt = $db->prepare("
                    UPDATE notifications 
                    SET is_read = 1, updated_at = NOW() 
                    WHERE uuid IN ({$placeholders}) AND receiver_id = ?
                ");
                $params = array_merge($ids, [$userId]);
                $result = $stmt->execute($params);
                $affectedRows = $stmt->rowCount();
                
                echo json_encode([
                    'success' => $result,
                    'marked_count' => $affectedRows
                ]);
                break;
                
            case 'mark-all-read':
                $before = $input['before'] ?? null;
                $whereClause = "WHERE receiver_id = ? AND is_read = 0";
                $params = [$userId];
                
                if ($before) {
                    $whereClause .= " AND created_at <= ?";
                    $params[] = $before;
                }
                
                $stmt = $db->prepare("UPDATE notifications SET is_read = 1, updated_at = NOW() {$whereClause}");
                $result = $stmt->execute($params);
                $affectedRows = $stmt->rowCount();
                
                echo json_encode([
                    'success' => $result,
                    'marked_count' => $affectedRows
                ]);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log('Notifications API v2 error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
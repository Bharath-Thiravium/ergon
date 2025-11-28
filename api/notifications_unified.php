<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    require_once __DIR__ . '/../app/config/database.php';
    require_once __DIR__ . '/../app/models/Notification.php';
    
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'error' => 'Authentication required',
            'code' => 'AUTH_REQUIRED'
        ]);
        exit;
    }
    
    $userId = (int)$_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Initialize notification model
    $notification = new Notification();
    
    if ($method === 'GET') {
        // GET: Fetch notifications
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        $offset = max((int)($_GET['offset'] ?? 0), 0);
        $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
        
        try {
            // Get notifications
            $notifications = $notification->getForUser($userId, $limit + 1); // Get one extra to check if more exist
            
            // Check if there are more notifications
            $hasMore = count($notifications) > $limit;
            if ($hasMore) {
                array_pop($notifications); // Remove the extra notification
            }
            
            // Get unread count
            $unreadCount = $notification->getUnreadCount($userId);
            
            // Filter unread only if requested
            if ($unreadOnly) {
                $notifications = array_filter($notifications, function($notif) {
                    return !($notif['is_read'] ?? false);
                });
                $notifications = array_values($notifications); // Re-index array
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
                'has_more' => $hasMore,
                'total_fetched' => count($notifications)
            ]);
            
        } catch (Exception $e) {
            error_log('Notification fetch error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to fetch notifications',
                'code' => 'FETCH_ERROR'
            ]);
        }
        
    } elseif ($method === 'POST') {
        // POST: Handle actions
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST; // Fallback to form data
        }
        
        $action = $input['action'] ?? '';
        
        // Skip CSRF for now
        
        switch ($action) {
            case 'mark-read':
                try {
                    $notificationId = (int)($input['id'] ?? 0);
                    
                    $result = $notification->markAsRead($notificationId, $userId);
                    
                    if ($result) {
                        // Get updated unread count
                        $unreadCount = $notification->getUnreadCount($userId);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'Notification marked as read',
                            'unread_count' => $unreadCount
                        ]);
                    } else {
                        throw new Exception('Failed to mark notification as read');
                    }
                    
                } catch (Exception $e) {
                    error_log('Mark as read error: ' . $e->getMessage());
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage(),
                        'code' => 'MARK_READ_ERROR'
                    ]);
                }
                break;
                
            case 'mark-all-read':
                try {
                    $before = $input['before'] ?? null;
                    
                    if ($before) {
                        // Mark all notifications before a certain date as read
                        $db = Database::connect();
                        $stmt = $db->prepare("
                            UPDATE notifications 
                            SET is_read = 1, read_at = NOW() 
                            WHERE receiver_id = ? AND is_read = 0 AND created_at <= ?
                        ");
                        $result = $stmt->execute([$userId, $before]);
                        $affectedRows = $stmt->rowCount();
                    } else {
                        // Mark all unread notifications as read
                        $result = $notification->markAllAsRead($userId);
                        $affectedRows = $result ? 'all' : 0;
                    }
                    
                    if ($result) {
                        // Get updated unread count
                        $unreadCount = $notification->getUnreadCount($userId);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'All notifications marked as read',
                            'marked_count' => $affectedRows,
                            'unread_count' => $unreadCount
                        ]);
                    } else {
                        throw new Exception('Failed to mark all notifications as read');
                    }
                    
                } catch (Exception $e) {
                    error_log('Mark all as read error: ' . $e->getMessage());
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage(),
                        'code' => 'MARK_ALL_READ_ERROR'
                    ]);
                }
                break;
                
            case 'mark-selected-read':
                try {
                    $ids = array_map('intval', $input['ids'] ?? []);
                    
                    $db = Database::connect();
                    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                    $stmt = $db->prepare("
                        UPDATE notifications 
                        SET is_read = 1, read_at = NOW() 
                        WHERE id IN ({$placeholders}) AND receiver_id = ?
                    ");
                    
                    $params = array_merge($ids, [$userId]);
                    $result = $stmt->execute($params);
                    $affectedRows = $stmt->rowCount();
                    
                    if ($result) {
                        // Get updated unread count
                        $unreadCount = $notification->getUnreadCount($userId);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => "Marked {$affectedRows} notifications as read",
                            'marked_count' => $affectedRows,
                            'unread_count' => $unreadCount
                        ]);
                    } else {
                        throw new Exception('Failed to mark selected notifications as read');
                    }
                    
                } catch (Exception $e) {
                    error_log('Mark selected as read error: ' . $e->getMessage());
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage(),
                        'code' => 'MARK_SELECTED_READ_ERROR'
                    ]);
                }
                break;
                
            case 'get-unread-count':
                try {
                    $unreadCount = $notification->getUnreadCount($userId);
                    
                    echo json_encode([
                        'success' => true,
                        'unread_count' => $unreadCount
                    ]);
                    
                } catch (Exception $e) {
                    error_log('Get unread count error: ' . $e->getMessage());
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to get unread count',
                        'code' => 'COUNT_ERROR'
                    ]);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid action',
                    'code' => 'INVALID_ACTION',
                    'available_actions' => ['mark-read', 'mark-all-read', 'mark-selected-read', 'get-unread-count']
                ]);
        }
        
    } else {
        // Unsupported method
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed',
            'code' => 'METHOD_NOT_ALLOWED',
            'allowed_methods' => ['GET', 'POST']
        ]);
    }
    
} catch (Exception $e) {
    error_log('Notification API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 'INTERNAL_ERROR'
    ]);
}
?>
<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/config/database.php';

// Check if user is owner or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$db = Database::connect();

// Ensure attendance_logs table exists
try {
    $db->exec("CREATE TABLE IF NOT EXISTS attendance_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        log_action VARCHAR(50) NOT NULL,
        details TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_action (log_action)
    )");
} catch (Exception $e) {
    error_log('Failed to create attendance_logs table: ' . $e->getMessage());
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userId = $_POST['user_id'] ?? null;
        $entryDate = $_POST['entry_date'] ?? null;
        $entryType = $_POST['entry_type'] ?? null;
        $entryTime = $_POST['entry_time'] ?? null;
        $clockInTime = $_POST['clock_in_time'] ?? null;
        $clockOutTime = $_POST['clock_out_time'] ?? null;
        $reason = $_POST['reason'] ?? null;
        $notes = $_POST['notes'] ?? '';
        
        if (!$userId || !$entryDate || !$entryType || !$reason) {
            throw new Exception('Required fields missing');
        }
        
        if ($entryDate > date('Y-m-d')) {
            throw new Exception('Cannot enter future dates');
        }
        
        $db->beginTransaction();
        
        // Check for existing record first
        $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
        $stmt->execute([$userId, $entryDate]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($entryType === 'full_day') {
            $clockInDateTime = $entryDate . ' ' . $clockInTime . ':00';
            $clockOutDateTime = $entryDate . ' ' . $clockOutTime . ':00';
            
            if ($existing) {
                $stmt = $db->prepare("UPDATE attendance SET check_in = ?, check_out = ? WHERE id = ?");
                $stmt->execute([$clockInDateTime, $clockOutDateTime, $existing['id']]);
            } else {
                $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, check_out, status) VALUES (?, ?, ?, 'present')");
                $stmt->execute([$userId, $clockInDateTime, $clockOutDateTime]);
            }
            
        } else {
            $entryDateTime = $entryDate . ' ' . $entryTime . ':00';
            
            if ($entryType === 'clock_in') {
                if ($existing) {
                    $stmt = $db->prepare("UPDATE attendance SET check_in = ? WHERE id = ?");
                    $stmt->execute([$entryDateTime, $existing['id']]);
                } else {
                    $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, status) VALUES (?, ?, 'present')");
                    $stmt->execute([$userId, $entryDateTime]);
                }
            } else {
                if ($existing) {
                    $stmt = $db->prepare("UPDATE attendance SET check_out = ? WHERE id = ?");
                    $stmt->execute([$entryDateTime, $existing['id']]);
                } else {
                    throw new Exception('No clock-in record found');
                }
            }
        }
        
        // Log the manual entry
        $stmt = $db->prepare("
            INSERT INTO attendance_logs (user_id, log_action, details, created_by, created_at)
            VALUES (?, 'manual_entry', ?, ?, NOW())
        ");
        $logDetails = "Manual {$entryType} for {$entryDate}. Reason: {$reason}. Notes: {$notes}";
        $stmt->execute([$userId, $logDetails, $_SESSION['user_id']]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Manual attendance entry created successfully'
        ]);
        
    } else {
        // Get recent entries
        $stmt = $db->prepare("
            SELECT 
                l.*,
                u.name as user_name,
                c.name as created_by_name
            FROM attendance_logs l
            JOIN users u ON l.user_id = u.id
            LEFT JOIN users c ON l.created_by = c.id
            WHERE l.log_action = 'manual_entry'
            ORDER BY l.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'entries' => array_map(function($entry) {
                return [
                    'user_name' => $entry['user_name'],
                    'details' => $entry['details'],
                    'created_by_name' => $entry['created_by_name'],
                    'created_at' => date('M d, Y H:i', strtotime($entry['created_at'])),
                    'entry_type' => 'manual',
                    'entry_type_display' => 'Manual Entry'
                ];
            }, $entries)
        ]);
    }
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

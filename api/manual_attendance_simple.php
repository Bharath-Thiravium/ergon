<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$db = Database::connect();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userId = $_POST['user_id'] ?? null;
        $entryDate = $_POST['entry_date'] ?? null;
        $entryType = $_POST['entry_type'] ?? null;
        $entryTime = $_POST['entry_time'] ?? null;
        $clockInTime = $_POST['clock_in_time'] ?? null;
        $clockOutTime = $_POST['clock_out_time'] ?? null;
        
        if (!$userId || !$entryDate || !$entryType) {
            throw new Exception('Required fields missing');
        }
        
        if ($entryDate > date('Y-m-d')) {
            throw new Exception('Cannot enter future dates');
        }
        
        $db->beginTransaction();
        
        if ($entryType === 'full_day') {
            $clockInDateTime = $entryDate . ' ' . $clockInTime . ':00';
            $clockOutDateTime = $entryDate . ' ' . $clockOutTime . ':00';
            
            $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
            $stmt->execute([$userId, $entryDate]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $db->prepare("UPDATE attendance SET check_in = ?, check_out = ?, status = 'present' WHERE user_id = ? AND DATE(check_in) = ?");
                $stmt->execute([$clockInDateTime, $clockOutDateTime, $userId, $entryDate]);
            } else {
                $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, check_out, status) VALUES (?, ?, ?, 'present')");
                $stmt->execute([$userId, $clockInDateTime, $clockOutDateTime]);
            }
            
        } else {
            $entryDateTime = $entryDate . ' ' . $entryTime . ':00';
            
            if ($entryType === 'clock_in') {
                $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
                $stmt->execute([$userId, $entryDate]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $stmt = $db->prepare("UPDATE attendance SET check_in = ?, status = 'present' WHERE user_id = ? AND DATE(check_in) = ?");
                    $stmt->execute([$entryDateTime, $userId, $entryDate]);
                } else {
                    $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, status) VALUES (?, ?, 'present')");
                    $stmt->execute([$userId, $entryDateTime]);
                }
            } else {
                $stmt = $db->prepare("UPDATE attendance SET check_out = ? WHERE user_id = ? AND DATE(check_in) = ?");
                $stmt->execute([$entryDateTime, $userId, $entryDate]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception('No clock-in record found for this date');
                }
            }
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Manual attendance entry created successfully'
        ]);
        
    } else {
        echo json_encode([
            'success' => true,
            'entries' => []
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

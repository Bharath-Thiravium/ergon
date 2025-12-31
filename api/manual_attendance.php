<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Owner/Admin role required.']);
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
        $reason = $_POST['reason'] ?? null;
        $notes = $_POST['notes'] ?? '';
        
        if (!$userId || !$entryDate || !$entryType || !$reason) {
            throw new Exception('Required fields are missing');
        }
        
        if ($entryDate > date('Y-m-d')) {
            throw new Exception('Cannot enter attendance for future dates');
        }
        
        $stmt = $db->prepare("SELECT name FROM users WHERE id = ? AND status = 'active'");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            throw new Exception('Invalid user selected');
        }
        
        $db->beginTransaction();
        
        // First, always check for existing record for this user and date
        $stmt = $db->prepare("SELECT id, check_in, check_out FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
        $stmt->execute([$userId, $entryDate]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($entryType === 'full_day') {
            if (!$clockInTime || !$clockOutTime) {
                throw new Exception('Clock in and out times required for full day entry');
            }
            $clockInDateTime = $entryDate . ' ' . $clockInTime . ':00';
            $clockOutDateTime = $entryDate . ' ' . $clockOutTime . ':00';
            
            if ($existing) {
                $stmt = $db->prepare("UPDATE attendance SET check_in = ?, check_out = ?, manual_entry = 1, edit_reason = ?, edited_by = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$clockInDateTime, $clockOutDateTime, "$reason: $notes", $_SESSION['user_id'], $existing['id']]);
            } else {
                $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, check_out, status, manual_entry, edit_reason, created_at) VALUES (?, ?, ?, 'present', 1, ?, NOW())");
                $stmt->execute([$userId, $clockInDateTime, $clockOutDateTime, "$reason: $notes"]);
            }
        } else {
            if (!$entryTime) {
                throw new Exception('Entry time is required');
            }
            $entryDateTime = $entryDate . ' ' . $entryTime . ':00';
            
            if ($entryType === 'clock_in') {
                if ($existing) {
                    $stmt = $db->prepare("UPDATE attendance SET check_in = ?, manual_entry = 1, edit_reason = ?, edited_by = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$entryDateTime, "$reason: $notes", $_SESSION['user_id'], $existing['id']]);
                } else {
                    $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, status, manual_entry, edit_reason, created_at) VALUES (?, ?, 'present', 1, ?, NOW())");
                    $stmt->execute([$userId, $entryDateTime, "$reason: $notes"]);
                }
            } else if ($entryType === 'clock_out') {
                if ($existing) {
                    $stmt = $db->prepare("UPDATE attendance SET check_out = ?, manual_entry = 1, edit_reason = ?, edited_by = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$entryDateTime, "$reason: $notes", $_SESSION['user_id'], $existing['id']]);
                } else {
                    throw new Exception('No check-in record found. Please add check-in first.');
                }
            } else {
                throw new Exception('Invalid entry type');
            }
        }
        
        $stmt = $db->prepare("INSERT INTO attendance_logs (user_id, action, details, created_by, created_at) VALUES (?, 'manual_entry', ?, ?, NOW())");
        $logDetails = json_encode(['entry_type' => $entryType, 'date' => $entryDate, 'time' => $entryTime, 'reason' => $reason, 'notes' => $notes]);
        $stmt->execute([$userId, $logDetails, $_SESSION['user_id']]);
        
        $db->commit();
        
        echo json_encode(['success' => true, 'message' => 'Manual attendance entry created successfully']);
        
    } else {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'recent') {
            $stmt = $db->prepare("
                SELECT a.*, u.name as user_name, e.name as created_by_name,
                    CASE 
                        WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 'Full Day'
                        WHEN a.check_in IS NOT NULL THEN 'Clock In'
                        WHEN a.check_out IS NOT NULL THEN 'Clock Out'
                        ELSE 'Unknown'
                    END as entry_type_display,
                    COALESCE(TIME(a.check_in), TIME(a.check_out)) as entry_time,
                    a.edit_reason as reason_display
                FROM attendance a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN users e ON a.edited_by = e.id
                WHERE a.manual_entry = 1
                ORDER BY a.created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'entries' => $entries]);
        } else {
            throw new Exception('Invalid action');
        }
    }
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    error_log('Manual Attendance Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}

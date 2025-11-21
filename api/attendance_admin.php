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
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'clock_in':
            $userId = $input['user_id'] ?? null;
            if (!$userId) throw new Exception('User ID required');
            
            $today = date('Y-m-d');
            $now = date('Y-m-d H:i:s');
            
            // Check if already clocked in today
            $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
            $stmt->execute([$userId, $today]);
            
            if ($stmt->fetch()) {
                throw new Exception('User already has attendance record for today');
            }
            
            // Create clock in record
            $stmt = $db->prepare("
                INSERT INTO attendance (user_id, check_in, status) 
                VALUES (?, ?, 'present')
            ");
            $stmt->execute([$userId, $now]);
            
            // Log the action (optional)
            try {
                $stmt = $db->prepare("
                    INSERT INTO attendance_logs (user_id, action, details, created_by) 
                    VALUES (?, 'admin_clock_in', ?, ?)
                ");
                $stmt->execute([$userId, "Admin clock in at {$now}", $_SESSION['user_id']]);
            } catch (Exception $e) {
                // Ignore logging errors
            }
            
            echo json_encode(['success' => true, 'message' => 'User clocked in successfully']);
            break;
            
        case 'clock_out':
            $userId = $input['user_id'] ?? null;
            if (!$userId) throw new Exception('User ID required');
            
            $today = date('Y-m-d');
            $now = date('Y-m-d H:i:s');
            
            // Update existing record
            $stmt = $db->prepare("
                UPDATE attendance 
                SET check_out = ? 
                WHERE user_id = ? AND DATE(check_in) = ? AND check_out IS NULL
            ");
            $stmt->execute([$now, $userId, $today]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('No active clock-in record found for today');
            }
            
            // Log the action (optional)
            try {
                $stmt = $db->prepare("
                    INSERT INTO attendance_logs (user_id, action, details, created_by) 
                    VALUES (?, 'admin_clock_out', ?, ?)
                ");
                $stmt->execute([$userId, "Admin clock out at {$now}", $_SESSION['user_id']]);
            } catch (Exception $e) {
                // Ignore logging errors
            }
            
            echo json_encode(['success' => true, 'message' => 'User clocked out successfully']);
            break;
            
        case 'delete':
            if ($_SESSION['role'] !== 'owner') {
                throw new Exception('Only owner can delete attendance records');
            }
            
            $id = $input['id'] ?? null;
            if (!$id) throw new Exception('Record ID required');
            
            // Get record details for logging
            $stmt = $db->prepare("SELECT user_id, DATE(check_in) as date FROM attendance WHERE id = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) throw new Exception('Record not found');
            
            // Delete the record
            $stmt = $db->prepare("DELETE FROM attendance WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log the deletion (optional)
            try {
                $stmt = $db->prepare("
                    INSERT INTO attendance_logs (user_id, action, details, created_by) 
                    VALUES (?, 'admin_delete', ?, ?)
                ");
                $stmt->execute([
                    $record['user_id'], 
                    "Attendance record deleted for {$record['date']}", 
                    $_SESSION['user_id']
                ]);
            } catch (Exception $e) {
                // Ignore logging errors
            }
            
            echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
            break;
            
        case 'get_details':
            $id = $input['id'] ?? null;
            if (!$id) throw new Exception('Record ID required');
            
            $stmt = $db->prepare("
                SELECT a.*, u.name as user_name, u.email, DATE(a.check_in) as date
                FROM attendance a
                JOIN users u ON a.user_id = u.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) throw new Exception('Record not found');
            
            // Calculate working hours
            if ($record['check_in'] && $record['check_out']) {
                $clockIn = new DateTime($record['check_in']);
                $clockOut = new DateTime($record['check_out']);
                $diff = $clockIn->diff($clockOut);
                $record['working_hours_calculated'] = $diff->format('%H:%I');
            }
            
            // Format times for display
            $record['check_in_time'] = $record['check_in'] ? date('H:i', strtotime($record['check_in'])) : null;
            $record['check_out_time'] = $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : null;
            
            echo json_encode(['success' => true, 'data' => $record, 'record' => $record});
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
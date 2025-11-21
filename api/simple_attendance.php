<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
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
            $date = $input['date'] ?? date('Y-m-d');
            $time = $input['time'] ?? null;
            if (!$userId || !$time) throw new Exception('User ID and time required');
            
            $datetime = $date . ' ' . $time . ':00';
            
            // Check if record exists for the date
            $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
            $stmt->execute([$userId, $date]);
            
            if ($stmt->fetch()) {
                throw new Exception('User already has attendance record for today');
            }
            
            $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, status) VALUES (?, ?, 'present')");
            $stmt->execute([$userId, $datetime]);
            
            echo json_encode(['success' => true, 'message' => 'User clocked in successfully']);
            break;
            
        case 'clock_out':
            $userId = $input['user_id'] ?? null;
            $date = $input['date'] ?? date('Y-m-d');
            $time = $input['time'] ?? null;
            if (!$userId || !$time) throw new Exception('User ID and time required');
            
            $datetime = $date . ' ' . $time . ':00';
            
            $stmt = $db->prepare("UPDATE attendance SET check_out = ? WHERE user_id = ? AND DATE(check_in) = ? AND check_out IS NULL");
            $stmt->execute([$datetime, $userId, $date]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('No active clock-in record found for today');
            }
            
            echo json_encode(['success' => true, 'message' => 'User clocked out successfully']);
            break;
            
        case 'get_details':
            $id = $input['id'] ?? null;
            if (!$id || $id == 0) {
                echo json_encode(['success' => false, 'message' => 'No attendance record found']);
                break;
            }
            
            $stmt = $db->prepare("SELECT a.*, u.name as user_name, u.email FROM attendance a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
                break;
            }
            
            // Format date and calculate working hours
            $record['date'] = date('Y-m-d', strtotime($record['check_in']));
            
            if ($record['check_in'] && $record['check_out']) {
                $checkIn = new DateTime($record['check_in']);
                $checkOut = new DateTime($record['check_out']);
                $diff = $checkIn->diff($checkOut);
                $record['working_hours_calculated'] = $diff->format('%H:%I');
            } else {
                $record['working_hours_calculated'] = 'N/A';
            }
            
            echo json_encode(['success' => true, 'record' => $record]);
            break;
            
        case 'edit':
            $id = $input['id'] ?? null;
            $userId = $input['user_id'] ?? null;
            $date = $input['date'] ?? null;
            $checkIn = $input['check_in'] ?? null;
            $checkOut = $input['check_out'] ?? null;
            
            if (!$id || !$userId || !$date) {
                throw new Exception('Missing required fields');
            }
            
            // Build update query based on provided times
            $updates = [];
            $params = [];
            
            if ($checkIn) {
                $updates[] = 'check_in = ?';
                $params[] = $date . ' ' . $checkIn . ':00';
            }
            
            if ($checkOut) {
                $updates[] = 'check_out = ?';
                $params[] = $date . ' ' . $checkOut . ':00';
            }
            
            if (empty($updates)) {
                throw new Exception('No time updates provided');
            }
            
            $params[] = $id;
            $sql = "UPDATE attendance SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('No record found to update');
            }
            
            echo json_encode(['success' => true, 'message' => 'Attendance record updated successfully']);
            break;
            
        case 'delete':
            if ($_SESSION['role'] !== 'owner') {
                throw new Exception('Only owner can delete records');
            }
            
            $id = $input['id'] ?? null;
            if (!$id || $id == 0) {
                echo json_encode(['success' => false, 'message' => 'No record to delete']);
                break;
            }
            
            $stmt = $db->prepare("DELETE FROM attendance WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
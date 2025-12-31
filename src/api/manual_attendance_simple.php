<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$user_id = $_POST['user_id'] ?? null;
$entry_date = $_POST['entry_date'] ?? null;
$entry_type = $_POST['entry_type'] ?? null;
$reason = $_POST['reason'] ?? '';
$notes = $_POST['notes'] ?? '';

if (!$user_id || !$entry_date || !$entry_type) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $pdo = Database::connect();
    
    if ($entry_type === 'full_day') {
        $clock_in_time = $_POST['clock_in_time'] ?? '09:00';
        $clock_out_time = $_POST['clock_out_time'] ?? '17:00';
        
        $check_in = $entry_date . ' ' . $clock_in_time . ':00';
        $check_out = $entry_date . ' ' . $clock_out_time . ':00';
        
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
        $stmt->execute([$user_id, $entry_date]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE attendance SET check_in = ?, check_out = ?, status = 'present', location_name = ? WHERE id = ?");
            $stmt->execute([$check_in, $check_out, 'Manual: ' . $reason, $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance (user_id, check_in, check_out, status, location_name) VALUES (?, ?, ?, 'present', ?)");
            $stmt->execute([$user_id, $check_in, $check_out, 'Manual: ' . $reason]);
        }
        
    } elseif ($entry_type === 'clock_in') {
        $entry_time = $_POST['entry_time'] ?? '09:00';
        $check_in = $entry_date . ' ' . $entry_time . ':00';
        
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
        $stmt->execute([$user_id, $entry_date]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE attendance SET check_in = ?, status = 'present', location_name = ? WHERE id = ?");
            $stmt->execute([$check_in, 'Manual: ' . $reason, $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance (user_id, check_in, status, location_name) VALUES (?, ?, 'present', ?)");
            $stmt->execute([$user_id, $check_in, 'Manual: ' . $reason]);
        }
        
    } elseif ($entry_type === 'clock_out') {
        $entry_time = $_POST['entry_time'] ?? '17:00';
        $check_out = $entry_date . ' ' . $entry_time . ':00';
        
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
        $stmt->execute([$user_id, $entry_date]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE attendance SET check_out = ?, location_name = ? WHERE id = ?");
            $stmt->execute([$check_out, 'Manual: ' . $reason, $existing['id']]);
        } else {
            $check_in = $entry_date . ' 09:00:00';
            $stmt = $pdo->prepare("INSERT INTO attendance (user_id, check_in, check_out, status, location_name) VALUES (?, ?, ?, 'present', ?)");
            $stmt->execute([$user_id, $check_in, $check_out, 'Manual: ' . $reason]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Attendance updated successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/config/database.php';

// Check if user is owner or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Owner/Admin role required.']);
    exit;
}

$db = Database::connect();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle manual entry submission
        $userId = $_POST['user_id'] ?? null;
        $entryDate = $_POST['entry_date'] ?? null;
        $entryType = $_POST['entry_type'] ?? null;
        $entryTime = $_POST['entry_time'] ?? null;
        $clockInTime = $_POST['clock_in_time'] ?? null;
        $clockOutTime = $_POST['clock_out_time'] ?? null;
        $reason = $_POST['reason'] ?? null;
        $notes = $_POST['notes'] ?? '';
        
        // Validation
        if (!$userId || !$entryDate || !$entryType || !$reason) {
            throw new Exception('Required fields are missing');
        }
        
        // Validate date (not future)
        if ($entryDate > date('Y-m-d')) {
            throw new Exception('Cannot enter attendance for future dates');
        }
        
        // Check if user exists
        $stmt = $db->prepare("SELECT name FROM users WHERE id = ? AND status = 'active'");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('Invalid user selected');
        }
        
        $db->beginTransaction();
        
        if ($entryType === 'full_day') {
            // Handle full day entry (both clock in and out)
            if (!$clockInTime || !$clockOutTime) {
                throw new Exception('Clock in and out times required for full day entry');
            }
            
            // Insert clock in
            $stmt = $db->prepare("
                INSERT INTO attendance (user_id, clock_in, clock_out, date, status, entry_type, reason, notes, created_by, created_at)
                VALUES (?, ?, ?, ?, 'present', 'manual', ?, ?, ?, NOW())
            ");
            $clockInDateTime = $entryDate . ' ' . $clockInTime . ':00';
            $clockOutDateTime = $entryDate . ' ' . $clockOutTime . ':00';
            
            $stmt->execute([
                $userId,
                $clockInDateTime,
                $clockOutDateTime,
                $entryDate,
                $reason,
                $notes,
                $_SESSION['user_id']
            ]);
            
        } else {
            // Handle single entry (clock in or clock out)
            if (!$entryTime) {
                throw new Exception('Entry time is required');
            }
            
            $entryDateTime = $entryDate . ' ' . $entryTime . ':00';
            
            // Check existing attendance for the date
            $stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
            $stmt->execute([$userId, $entryDate]);
            $existingAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($entryType === 'clock_in') {
                if ($existingAttendance) {
                    // Update existing record
                    $stmt = $db->prepare("
                        UPDATE attendance 
                        SET clock_in = ?, entry_type = 'manual', reason = ?, notes = ?, 
                            updated_by = ?, updated_at = NOW()
                        WHERE user_id = ? AND date = ?
                    ");
                    $stmt->execute([$entryDateTime, $reason, $notes, $_SESSION['user_id'], $userId, $entryDate]);
                } else {
                    // Create new record
                    $stmt = $db->prepare("
                        INSERT INTO attendance (user_id, clock_in, date, status, entry_type, reason, notes, created_by, created_at)
                        VALUES (?, ?, ?, 'present', 'manual', ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$userId, $entryDateTime, $entryDate, $reason, $notes, $_SESSION['user_id']]);
                }
            } else { // clock_out
                if ($existingAttendance) {
                    // Update existing record
                    $stmt = $db->prepare("
                        UPDATE attendance 
                        SET clock_out = ?, entry_type = 'manual', reason = ?, notes = ?, 
                            updated_by = ?, updated_at = NOW()
                        WHERE user_id = ? AND date = ?
                    ");
                    $stmt->execute([$entryDateTime, $reason, $notes, $_SESSION['user_id'], $userId, $entryDate]);
                } else {
                    throw new Exception('No clock-in record found. Please add clock-in first.');
                }
            }
        }
        
        // Log the manual entry
        $stmt = $db->prepare("
            INSERT INTO attendance_logs (user_id, action, details, created_by, created_at)
            VALUES (?, 'manual_entry', ?, ?, NOW())
        ");
        $logDetails = json_encode([
            'entry_type' => $entryType,
            'date' => $entryDate,
            'time' => $entryTime,
            'reason' => $reason,
            'notes' => $notes
        ]);
        $stmt->execute([$userId, $logDetails, $_SESSION['user_id']]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Manual attendance entry created successfully'
        ]);
        
    } else {
        // Handle GET request for recent entries
        $action = $_GET['action'] ?? '';
        
        if ($action === 'recent') {
            $stmt = $db->prepare("
                SELECT 
                    a.*, 
                    u.name as user_name,
                    c.name as created_by_name,
                    CASE 
                        WHEN a.clock_in IS NOT NULL AND a.clock_out IS NOT NULL THEN 'full_day'
                        WHEN a.clock_in IS NOT NULL THEN 'clock_in'
                        WHEN a.clock_out IS NOT NULL THEN 'clock_out'
                        ELSE 'unknown'
                    END as entry_type,
                    CASE 
                        WHEN a.clock_in IS NOT NULL AND a.clock_out IS NOT NULL THEN 'Full Day'
                        WHEN a.clock_in IS NOT NULL THEN 'Clock In'
                        WHEN a.clock_out IS NOT NULL THEN 'Clock Out'
                        ELSE 'Unknown'
                    END as entry_type_display,
                    CASE 
                        WHEN a.clock_in IS NOT NULL AND a.clock_out IS NOT NULL THEN TIME(a.clock_in)
                        WHEN a.clock_in IS NOT NULL THEN TIME(a.clock_in)
                        WHEN a.clock_out IS NOT NULL THEN TIME(a.clock_out)
                        ELSE NULL
                    END as entry_time,
                    CASE a.reason
                        WHEN 'geo_fencing' THEN 'Outside geo-fencing range'
                        WHEN 'technical_issue' THEN 'Technical/App issue'
                        WHEN 'network_problem' THEN 'Network connectivity problem'
                        WHEN 'device_malfunction' THEN 'Device malfunction'
                        WHEN 'emergency' THEN 'Emergency situation'
                        ELSE 'Other'
                    END as reason_display
                FROM attendance a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN users c ON a.created_by = c.id
                WHERE a.entry_type = 'manual'
                ORDER BY a.created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'entries' => $entries
            ]);
        } else {
            throw new Exception('Invalid action');
        }
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
<?php
/**
 * User Controller - Complete User Panel Implementation
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/RoleManager.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Leave.php';
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../models/Advance.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Notification.php';

class UserController extends Controller {
    
    public function dashboard() {
        AuthMiddleware::requireRole('user');
        
        try {
            $db = Database::connect();
            $userId = $_SESSION['user_id'];
            $financialSummary = $this->getDashboardFinancialSummary($db, $userId);
            
            // Get user's personal statistics
            $stats = [
                'my_tasks' => $this->getMyTaskStats($db, $userId),
                'attendance_this_month' => $this->getAttendanceStats($db, $userId),
                'pending_requests' => $this->getPendingRequestsCount($db, $userId),
                'completed_tasks_this_month' => $this->getCompletedTasksCount($db, $userId),
                'leave_balance' => $this->getLeaveBalance($db, $userId),
                'quick_finance' => $this->getQuickFinanceStats($db, $userId),
            ];
            
            // Get today's tasks
            $todayTasks = $this->getTodayTasks($db, $userId);
            
            // Get recent activities
            $recentActivities = $this->getRecentActivities($db, $userId);
            
            // Get notifications
            $notifications = $this->getRecentNotifications($db, $userId);
            
            // Check if user needs to clock in/out
            $attendanceStatus = $this->getTodayAttendanceStatus($db, $userId);
            $smartAlerts = $this->getSmartAlerts($db, $userId, $attendanceStatus);
            
            $this->view('dashboard/user', [
                'stats' => $stats,
                'today_tasks' => $todayTasks,
                'recent_activities' => $recentActivities,
                'notifications' => $notifications,
                'attendance_status' => $attendanceStatus,
                'smart_alerts' => $smartAlerts,
                'expense_total' => $financialSummary['expense_total'],
                'advance_total' => $financialSummary['advance_total'],
                'outstanding_total' => $financialSummary['outstanding_total'],
                'active_page' => 'dashboard'
            ]);
            
        } catch (Exception $e) {
            error_log('User dashboard error: ' . $e->getMessage());
            $this->view('user/dashboard', ['error' => 'Unable to load dashboard data']);
        }
    }
    
    public function myTasks() {
        AuthMiddleware::requireRole('user');
        
        try {
            $db = Database::connect();
            $userId = $_SESSION['user_id'];
            
            // Get all user's tasks with filters
            $filter = $_GET['filter'] ?? 'all';
            $tasks = $this->getUserTasks($db, $userId, $filter);
            
            // Get task statistics
            $taskStats = [
                'total' => count($this->getUserTasks($db, $userId, 'all')),
                'pending' => count($this->getUserTasks($db, $userId, 'pending')),
                'in_progress' => count($this->getUserTasks($db, $userId, 'in_progress')),
                'completed' => count($this->getUserTasks($db, $userId, 'completed')),
                'overdue' => count($this->getUserTasks($db, $userId, 'overdue'))
            ];
            
            $this->view('user/my_tasks', [
                'tasks' => $tasks,
                'task_stats' => $taskStats,
                'current_filter' => $filter,
                'active_page' => 'tasks'
            ]);
            
        } catch (Exception $e) {
            error_log('My tasks error: ' . $e->getMessage());
            $this->view('user/my_tasks', ['error' => 'Unable to load tasks']);
        }
    }
    
    public function updateTaskProgress() {
        AuthMiddleware::requireRole('user');
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        try {
            $taskId = $_POST['task_id'];
            $progress = $_POST['progress'];
            $status = $_POST['status'] ?? null;
            $comments = $_POST['comments'] ?? '';
            
            $db = Database::connect();
            
            // Verify task belongs to user
            $stmt = $db->prepare("SELECT id FROM tasks WHERE id = ? AND assigned_to = ?");
            $stmt->execute([$taskId, $_SESSION['user_id']]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Task not found or access denied');
            }
            
            // Update task progress
            $updateFields = ['progress = ?', 'updated_at = NOW()'];
            $params = [$progress];
            
            if ($status) {
                $updateFields[] = 'status = ?';
                $params[] = $status;
            }
            
            if ($comments) {
                $updateFields[] = 'comments = ?';
                $params[] = $comments;
            }
            
            $params[] = $taskId;
            
            $stmt = $db->prepare("UPDATE tasks SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $result = $stmt->execute($params);
            
            if ($result) {
                // Log activity
                $this->logActivity($db, $_SESSION['user_id'], 'task_updated', "Updated task progress to {$progress}%");
                
                $this->json(['success' => true, 'message' => 'Task updated successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to update task']);
            }
            
        } catch (Exception $e) {
            error_log('Update task progress error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function submitLeaveRequest() {
        AuthMiddleware::requireRole('user');
        
        if ($this->isPost()) {
            try {
                $db = Database::connect();
                
                $leaveData = [
                    'user_id' => $_SESSION['user_id'],
                    'leave_type' => $_POST['leave_type'],
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'reason' => $_POST['reason'],
                    'status' => 'pending',
                    'admin_approval' => 'pending',
                    'owner_approval' => 'pending'
                ];
                
                // Calculate leave days
                $startDate = new DateTime($leaveData['start_date']);
                $endDate = new DateTime($leaveData['end_date']);
                $leaveDays = $startDate->diff($endDate)->days + 1;
                
                // Check leave balance
                if (!$this->checkLeaveBalance($db, $_SESSION['user_id'], $leaveData['leave_type'], $leaveDays)) {
                    throw new Exception('Insufficient leave balance');
                }
                
                $stmt = $db->prepare("
                    INSERT INTO leaves (user_id, leave_type, start_date, end_date, reason, days, status, admin_approval, owner_approval, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $result = $stmt->execute([
                    $leaveData['user_id'],
                    $leaveData['leave_type'],
                    $leaveData['start_date'],
                    $leaveData['end_date'],
                    $leaveData['reason'],
                    $leaveDays,
                    $leaveData['status'],
                    $leaveData['admin_approval'],
                    $leaveData['owner_approval']
                ]);
                
                if ($result) {
                    $this->logActivity($db, $_SESSION['user_id'], 'leave_requested', "Requested {$leaveDays} days leave");
                    $this->json(['success' => true, 'message' => 'Leave request submitted successfully']);
                } else {
                    $this->json(['success' => false, 'message' => 'Failed to submit leave request']);
                }
                
            } catch (Exception $e) {
                error_log('Submit leave request error: ' . $e->getMessage());
                $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $this->view('user/submit_leave', ['active_page' => 'requests']);
        }
    }
    
    public function submitExpenseClaim() {
        AuthMiddleware::requireRole('user');
        
        if ($this->isPost()) {
            try {
                $db = Database::connect();
                
                // Handle file upload
                $receiptPath = null;
                if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
                    $receiptPath = $this->handleReceiptUpload($_FILES['receipt']);
                }
                
                $expenseData = [
                    'user_id' => $_SESSION['user_id'],
                    'category' => $_POST['category'],
                    'amount' => $_POST['amount'],
                    'description' => $_POST['description'],
                    'expense_date' => $_POST['expense_date'],
                    'receipt_path' => $receiptPath,
                    'status' => 'pending',
                    'admin_approval' => 'pending',
                    'owner_approval' => 'pending'
                ];
                
                $stmt = $db->prepare("
                    INSERT INTO expenses (user_id, category, amount, description, expense_date, receipt_path, status, admin_approval, owner_approval, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $result = $stmt->execute([
                    $expenseData['user_id'],
                    $expenseData['category'],
                    $expenseData['amount'],
                    $expenseData['description'],
                    $expenseData['expense_date'],
                    $expenseData['receipt_path'],
                    $expenseData['status'],
                    $expenseData['admin_approval'],
                    $expenseData['owner_approval']
                ]);
                
                if ($result) {
                    $this->logActivity($db, $_SESSION['user_id'], 'expense_claimed', "Claimed expense of ₹{$expenseData['amount']}");
                    $this->json(['success' => true, 'message' => 'Expense claim submitted successfully']);
                } else {
                    $this->json(['success' => false, 'message' => 'Failed to submit expense claim']);
                }
                
            } catch (Exception $e) {
                error_log('Submit expense claim error: ' . $e->getMessage());
                $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $this->view('user/submit_expense', ['active_page' => 'requests']);
        }
    }
    
    public function submitAdvanceRequest() {
        AuthMiddleware::requireRole('user');
        
        if ($this->isPost()) {
            try {
                $db = Database::connect();
                
                $advanceData = [
                    'user_id' => $_SESSION['user_id'],
                    'amount' => $_POST['amount'],
                    'reason' => $_POST['reason'],
                    'repayment_months' => $_POST['repayment_months'] ?? 1,
                    'status' => 'pending',
                    'admin_approval' => 'pending',
                    'owner_approval' => 'pending'
                ];
                
                $stmt = $db->prepare("
                    INSERT INTO advances (user_id, amount, reason, repayment_months, status, admin_approval, owner_approval, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $result = $stmt->execute([
                    $advanceData['user_id'],
                    $advanceData['amount'],
                    $advanceData['reason'],
                    $advanceData['repayment_months'],
                    $advanceData['status'],
                    $advanceData['admin_approval'],
                    $advanceData['owner_approval']
                ]);
                
                if ($result) {
                    $this->logActivity($db, $_SESSION['user_id'], 'advance_requested', "Requested advance of ₹{$advanceData['amount']}");
                    $this->json(['success' => true, 'message' => 'Advance request submitted successfully']);
                } else {
                    $this->json(['success' => false, 'message' => 'Failed to submit advance request']);
                }
                
            } catch (Exception $e) {
                error_log('Submit advance request error: ' . $e->getMessage());
                $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            $this->view('user/submit_advance', ['active_page' => 'requests']);
        }
    }
    
    public function myRequests() {
        AuthMiddleware::requireRole('user');
        
        try {
            $db = Database::connect();
            $userId = $_SESSION['user_id'];
            
            // Get all user's requests
            $leaves = $this->getUserLeaves($db, $userId);
            $expenses = $this->getUserExpenses($db, $userId);
            $advances = $this->getUserAdvances($db, $userId);
            
            // Calculate stats
            $stats = [
                'pending_leaves' => count(array_filter($leaves, function($l) { return $l['status'] === 'pending'; })),
                'pending_expenses' => count(array_filter($expenses, function($e) { return $e['status'] === 'pending'; })),
                'pending_advances' => count(array_filter($advances, function($a) { return $a['status'] === 'pending'; }))
            ];
            
            $this->view('user/requests', [
                'leaves' => $leaves,
                'expenses' => $expenses,
                'advances' => $advances,
                'stats' => $stats,
                'active_page' => 'requests'
            ]);
            
        } catch (Exception $e) {
            error_log('My requests error: ' . $e->getMessage());
            $this->view('user/requests', ['error' => 'Unable to load requests']);
        }
    }
    
    public function myAttendance() {
        AuthMiddleware::requireRole('user');
        
        try {
            $db = Database::connect();
            $userId = $_SESSION['user_id'];
            
            // Get attendance history
            $attendanceHistory = $this->getAttendanceHistory($db, $userId);
            
            // Get attendance statistics
            $attendanceStats = [
                'this_month' => $this->getMonthlyAttendanceStats($db, $userId),
                'today_status' => $this->getTodayAttendanceStatus($db, $userId),
                'weekly_hours' => $this->getWeeklyHours($db, $userId)
            ];
            
            $this->view('user/my_attendance', [
                'attendance_history' => $attendanceHistory,
                'attendance_stats' => $attendanceStats,
                'active_page' => 'attendance'
            ]);
            
        } catch (Exception $e) {
            error_log('My attendance error: ' . $e->getMessage());
            $this->view('user/my_attendance', ['error' => 'Unable to load attendance data']);
        }
    }
    
    public function clockIn() {
        AuthMiddleware::requireRole('user');
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        try {
            $db = Database::connect();
            $userId = $_SESSION['user_id'];
            
            // Check if already clocked in today
            $stmt = $db->prepare("SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL");
            $stmt->execute([$userId]);
            
            if ($stmt->fetch()) {
                throw new Exception('Already clocked in today');
            }
            
            $latitude = $_POST['latitude'] ?? null;
            $longitude = $_POST['longitude'] ?? null;
            
            // Validate GPS coordinates if required
            if (!$this->validateLocation($latitude, $longitude)) {
                throw new Exception('Invalid location. Please ensure you are at the office premises.');
            }
            
            $stmt = $db->prepare("
                INSERT INTO attendance (user_id, check_in, latitude, longitude, status, created_at) 
                VALUES (?, NOW(), ?, ?, 'present', NOW())
            ");
            
            $result = $stmt->execute([$userId, $latitude, $longitude]);
            
            if ($result) {
                $this->logActivity($db, $userId, 'clocked_in', 'Clocked in for the day');
                $this->json(['success' => true, 'message' => 'Clocked in successfully']);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to clock in']);
            }
            
        } catch (Exception $e) {
            error_log('Clock in error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function clockOut() {
        AuthMiddleware::requireRole('user');
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        try {
            $db = Database::connect();
            $userId = $_SESSION['user_id'];
            
            // Find today's attendance record
            $stmt = $db->prepare("SELECT id, check_in FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL");
            $stmt->execute([$userId]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$attendance) {
                throw new Exception('No clock-in record found for today');
            }
            
            $latitude = $_POST['latitude'] ?? null;
            $longitude = $_POST['longitude'] ?? null;
            
            // Calculate work hours
            $clockIn = new DateTime($attendance['check_in']);
            $clockOut = new DateTime();
            $workHours = $clockIn->diff($clockOut)->h + ($clockIn->diff($clockOut)->i / 60);
            
            $stmt = $db->prepare("
                UPDATE attendance 
                SET check_out = NOW(), out_latitude = ?, out_longitude = ?, work_hours = ? 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$latitude, $longitude, $workHours, $attendance['id']]);
            
            if ($result) {
                $this->logActivity($db, $userId, 'clocked_out', "Clocked out after {$workHours} hours");
                $this->json(['success' => true, 'message' => 'Clocked out successfully', 'work_hours' => $workHours]);
            } else {
                $this->json(['success' => false, 'message' => 'Failed to clock out']);
            }
            
        } catch (Exception $e) {
            error_log('Clock out error: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // Helper Methods
    private function getMyTaskStats($db, $userId) {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('assigned', 'pending') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(
                    CASE
                        WHEN COALESCE(deadline, due_date, planned_date) IS NOT NULL
                         AND DATE(COALESCE(deadline, due_date, planned_date)) < CURDATE()
                         AND status NOT IN ('completed', 'cancelled', 'suspended')
                        THEN 1
                        ELSE 0
                    END
                ) as overdue
            FROM tasks WHERE assigned_to = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getAttendanceStats($db, $userId) {
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM attendance
            WHERE user_id = ?
              AND MONTH(check_in) = MONTH(CURDATE())
              AND YEAR(check_in) = YEAR(CURDATE())
              AND check_in IS NOT NULL
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    private function getPendingRequestsCount($db, $userId) {
        $leaves = $db->prepare("SELECT COUNT(*) FROM leaves WHERE user_id = ? AND status = 'pending'");
        $leaves->execute([$userId]);
        $expenses = $db->prepare("SELECT COUNT(*) FROM expenses WHERE user_id = ? AND status = 'pending'");
        $expenses->execute([$userId]);
        $advances = $db->prepare("SELECT COUNT(*) FROM advances WHERE user_id = ? AND status = 'pending'");
        $advances->execute([$userId]);
        
        return $leaves->fetchColumn() + $expenses->fetchColumn() + $advances->fetchColumn();
    }
    
    private function getCompletedTasksCount($db, $userId) {
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM tasks
            WHERE assigned_to = ?
              AND status = 'completed'
              AND MONTH(updated_at) = MONTH(CURDATE())
              AND YEAR(updated_at) = YEAR(CURDATE())
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    private function getLeaveBalance($db, $userId) {
        // Calculate leave balance based on company policy
        $totalLeaves = 24; // Annual leave entitlement
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(days_requested), 0)
            FROM leaves
            WHERE user_id = ?
              AND YEAR(start_date) = YEAR(CURDATE())
              AND status = 'approved'
        ");
        $stmt->execute([$userId]);
        $usedLeaves = $stmt->fetchColumn();
        
        return $totalLeaves - $usedLeaves;
    }
    
    private function getTodayTasks($db, $userId) {
        $stmt = $db->prepare("
            SELECT *
            FROM tasks
            WHERE assigned_to = ?
              AND (
                    planned_date = CURDATE()
                    OR due_date = CURDATE()
                    OR DATE(deadline) = CURDATE()
                    OR status = 'in_progress'
                  )
              AND status NOT IN ('completed', 'cancelled', 'suspended')
            ORDER BY
                CASE priority
                    WHEN 'high' THEN 1
                    WHEN 'medium' THEN 2
                    ELSE 3
                END,
                COALESCE(deadline, due_date, planned_date) ASC,
                created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getRecentActivities($db, $userId) {
        $stmt = $db->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getRecentNotifications($db, $userId) {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE receiver_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getTodayAttendanceStatus($db, $userId) {
        $stmt = $db->prepare("SELECT check_in, check_out FROM attendance WHERE user_id = ? AND DATE(check_in) = CURDATE() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$attendance) {
            return ['status' => 'not_clocked_in', 'can_clock_in' => true, 'can_clock_out' => false];
        } elseif ($attendance['check_out']) {
            return ['status' => 'clocked_out', 'can_clock_in' => false, 'can_clock_out' => false, 'clock_in' => $attendance['check_in'], 'clock_out' => $attendance['check_out']];
        } else {
            return ['status' => 'clocked_in', 'can_clock_in' => false, 'can_clock_out' => true, 'clock_in' => $attendance['check_in']];
        }
    }
    
    private function getUserTasks($db, $userId, $filter) {
        $whereClause = "assigned_to = ?";
        $params = [$userId];
        
        switch ($filter) {
            case 'pending':
                $whereClause .= " AND status IN ('assigned', 'pending')";
                break;
            case 'in_progress':
                $whereClause .= " AND status = 'in_progress'";
                break;
            case 'completed':
                $whereClause .= " AND status = 'completed'";
                break;
            case 'overdue':
                $whereClause .= " AND DATE(COALESCE(deadline, due_date, planned_date)) < CURDATE() AND status NOT IN ('completed', 'cancelled', 'suspended')";
                break;
        }
        
        $stmt = $db->prepare("SELECT * FROM tasks WHERE {$whereClause} ORDER BY COALESCE(deadline, due_date, planned_date, created_at) ASC, priority DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUserLeaves($db, $userId) {
        $stmt = $db->prepare("SELECT * FROM leaves WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUserExpenses($db, $userId) {
        $stmt = $db->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUserAdvances($db, $userId) {
        $stmt = $db->prepare("SELECT * FROM advances WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getAttendanceHistory($db, $userId) {
        $stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? ORDER BY check_in DESC LIMIT 30");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getMonthlyAttendanceStats($db, $userId) {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as days_present,
                AVG(work_hours) as avg_hours,
                SUM(work_hours) as total_hours
            FROM attendance 
            WHERE user_id = ? AND MONTH(check_in) = MONTH(CURDATE())
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getWeeklyHours($db, $userId) {
        $stmt = $db->prepare("SELECT SUM(work_hours) FROM attendance WHERE user_id = ? AND WEEK(check_in) = WEEK(CURDATE())");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 0;
    }
    
    private function checkLeaveBalance($db, $userId, $leaveType, $days) {
        $balance = $this->getLeaveBalance($db, $userId);
        return $balance >= $days;
    }
    
    private function handleReceiptUpload($file) {
        $uploadDir = __DIR__ . '/../../storage/receipts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . $file['name'];
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return 'storage/receipts/' . $fileName;
        }
        
        throw new Exception('Failed to upload receipt');
    }
    
    private function validateLocation($latitude, $longitude) {
        // Implement GPS validation logic
        // For now, return true (can be customized based on office location)
        return true;
    }
    
    private function getQuickFinanceStats($db, $userId): array {
        // Advances
        $advStmt = $db->prepare("
            SELECT
                COALESCE(SUM(amount),0) as total,
                COALESCE(SUM(CASE WHEN status='pending' THEN amount ELSE 0 END),0) as pending,
                COALESCE(SUM(CASE WHEN status IN ('approved','paid') THEN amount ELSE 0 END),0) as approved,
                COALESCE(SUM(CASE WHEN status='paid' THEN amount ELSE 0 END),0) as paid
            FROM advances WHERE user_id = ?
        ");
        $advStmt->execute([$userId]);
        $adv = $advStmt->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'pending'=>0,'approved'=>0,'paid'=>0];

        // Expenses
        $expStmt = $db->prepare("
            SELECT
                COALESCE(SUM(amount),0) as total,
                COALESCE(SUM(CASE WHEN status='pending' THEN amount ELSE 0 END),0) as pending,
                COALESCE(SUM(CASE WHEN status IN ('approved','paid','reimbursed') THEN COALESCE(approved_amount, amount) ELSE 0 END),0) as approved,
                COALESCE(SUM(CASE WHEN status IN ('paid','reimbursed') THEN COALESCE(approved_amount, amount) ELSE 0 END),0) as reimbursed
            FROM expenses WHERE user_id = ?
        ");
        $expStmt->execute([$userId]);
        $exp = $expStmt->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'pending'=>0,'approved'=>0,'reimbursed'=>0];

        $pendingExp = (float)$exp['pending'];
        $pendingAdv = (float)$adv['pending'];
        return [
            'advance'  => $adv,
            'expense'  => $exp,
            'unclaimed' => [
                'pending_expense'  => $pendingExp,
                'pending_advance'  => $pendingAdv,
                'total_unclaimed'  => $pendingExp + $pendingAdv,
            ],
        ];
    }

    private function getDashboardFinancialSummary($db, $userId): array {
        $expenseStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = ?");
        $expenseStmt->execute([$userId]);
        $expenseTotal = (float) $expenseStmt->fetchColumn();

        $advanceStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM advances WHERE user_id = ?");
        $advanceStmt->execute([$userId]);
        $advanceTotal = (float) $advanceStmt->fetchColumn();

        return [
            'expense_total' => $expenseTotal,
            'advance_total' => $advanceTotal,
            'outstanding_total' => $advanceTotal - $expenseTotal,
        ];
    }

    private function getSmartAlerts($db, $userId, array $attendanceStatus): array {
        $alerts = [];

        if (($attendanceStatus['status'] ?? 'not_clocked_in') === 'not_clocked_in' && !$this->isUserOnApprovedLeaveToday($db, $userId)) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Clock-in missing',
                'message' => 'You have not clocked in today.',
                'action_label' => 'Open Attendance',
                'action_url' => '/ergon/attendance',
            ];
        }

        $taskStmt = $db->prepare("
            SELECT COUNT(*) as overdue_count,
                   MAX(DATEDIFF(CURDATE(), DATE(COALESCE(deadline, due_date, planned_date)))) as max_days_overdue
            FROM tasks
            WHERE assigned_to = ?
              AND COALESCE(deadline, due_date, planned_date) IS NOT NULL
              AND DATE(COALESCE(deadline, due_date, planned_date)) < CURDATE()
              AND status NOT IN ('completed', 'cancelled', 'suspended')
        ");
        $taskStmt->execute([$userId]);
        $taskAlert = $taskStmt->fetch(PDO::FETCH_ASSOC) ?: ['overdue_count' => 0, 'max_days_overdue' => 0];

        if ((int) ($taskAlert['overdue_count'] ?? 0) > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Task overdue',
                'message' => (int) $taskAlert['overdue_count'] . ' task(s) are overdue' .
                    ((int) ($taskAlert['max_days_overdue'] ?? 0) > 0 ? ' by up to ' . (int) $taskAlert['max_days_overdue'] . ' day(s).' : '.'),
                'action_label' => 'View Tasks',
                'action_url' => '/ergon/tasks?filter=overdue',
            ];
        }

        try {
            $tableStmt = $db->query("SHOW TABLES LIKE 'site_reports'");
            if ($tableStmt && $tableStmt->rowCount() > 0) {
                $reportStmt = $db->prepare("SELECT COUNT(*) FROM site_reports WHERE submitted_by = ? AND report_date = CURDATE()");
                $reportStmt->execute([$userId]);
                $hasReportToday = (int) $reportStmt->fetchColumn() > 0;

                if (!$hasReportToday) {
                    $alerts[] = [
                        'type' => 'info',
                        'title' => 'Submit today\'s report',
                        'message' => 'Your daily site report has not been submitted yet.',
                        'action_label' => 'Submit Report',
                        'action_url' => '/ergon/site-reports/create',
                    ];
                }
            }
        } catch (Exception $e) {
            error_log('Smart alert report check error: ' . $e->getMessage());
        }

        return $alerts;
    }

    private function isUserOnApprovedLeaveToday($db, $userId): bool {
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*)
                FROM leaves
                WHERE user_id = ?
                  AND status = 'approved'
                  AND CURDATE() BETWEEN start_date AND end_date
            ");
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log('Approved leave check error: ' . $e->getMessage());
            return false;
        }
    }

    private function logActivity($db, $userId, $action, $description) {
        try {
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$userId, $action, $description]);
        } catch (Exception $e) {
            error_log('Activity log error: ' . $e->getMessage());
        }
    }
}
?>

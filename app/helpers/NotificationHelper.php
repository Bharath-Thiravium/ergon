<?php
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../config/environment.php';

class NotificationHelper {
    
    public static function notifyLeaveRequest($leaveId, $userId, $userRole) {
        $notification = new Notification();
        
        // Get leave details
        $db = Database::connect();
        $stmt = $db->prepare("SELECT l.*, u.name as user_name FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.id = ?");
        $stmt->execute([$leaveId]);
        $leave = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$leave) return false;
        
        // Notify admins and owners about new leave request
        $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('admin', 'owner') AND status = 'active'");
        $stmt->execute();
        $approvers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($approvers as $approver) {
            $notification->create([
                'sender_id' => $userId,
                'receiver_id' => $approver['id'],
                'type' => 'info',
                'category' => 'approval',
                'title' => 'New Leave Request',
                'message' => "Leave request from {$leave['user_name']} for {$leave['leave_type']} ({$leave['start_date']} to {$leave['end_date']})",
                'reference_type' => 'leave',
                'reference_id' => $leaveId,
                'action_url' => Environment::getBaseUrl() . "/leaves/view/{$leaveId}"
            ]);
        }
        
        return true;
    }
    
    public static function notifyLeaveStatusChange($leaveId, $status, $approverId) {
        $notification = new Notification();
        
        // Get leave details
        $db = Database::connect();
        $stmt = $db->prepare("SELECT l.*, u.name as user_name, a.name as approver_name FROM leaves l JOIN users u ON l.user_id = u.id JOIN users a ON a.id = ? WHERE l.id = ?");
        $stmt->execute([$approverId, $leaveId]);
        $leave = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$leave) return false;
        
        $type = $status === 'approved' ? 'success' : 'warning';
        $title = "Leave Request " . ucfirst($status);
        $message = "Your leave request ({$leave['leave_type']}) from {$leave['start_date']} to {$leave['end_date']} has been {$status} by {$leave['approver_name']}";
        
        if ($status === 'rejected' && !empty($leave['rejection_reason'])) {
            $message .= ". Reason: {$leave['rejection_reason']}";
        }
        
        $notification->create([
            'sender_id' => $approverId,
            'receiver_id' => $leave['user_id'],
            'type' => $type,
            'category' => 'approval',
            'title' => $title,
            'message' => $message,
            'reference_type' => 'leave',
            'reference_id' => $leaveId,
            'action_url' => Environment::getBaseUrl() . "/leaves/view/{$leaveId}"
        ]);
        
        return true;
    }
    
    public static function notifyExpenseRequest($expenseId, $userId) {
        $notification = new Notification();
        
        // Get expense details
        $db = Database::connect();
        $stmt = $db->prepare("SELECT e.*, u.name as user_name FROM expenses e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
        $stmt->execute([$expenseId]);
        $expense = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$expense) return false;
        
        // Notify admins and owners
        $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('admin', 'owner') AND status = 'active'");
        $stmt->execute();
        $approvers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($approvers as $approver) {
            $notification->create([
                'sender_id' => $userId,
                'receiver_id' => $approver['id'],
                'type' => 'info',
                'category' => 'approval',
                'title' => 'New Expense Request',
                'message' => "Expense request from {$expense['user_name']} - ₹" . number_format($expense['amount'], 2) . " for {$expense['description']}",
                'reference_type' => 'expense',
                'reference_id' => $expenseId,
                'action_url' => Environment::getBaseUrl() . "/expenses/view/{$expenseId}"
            ]);
        }
        
        return true;
    }
    
    public static function notifyExpenseClaim($userId, $userName, $amount, $expenseId = null) {
        require_once __DIR__ . '/../config/database.php';
        $notification = new Notification();
        
        try {
            $db = Database::connect();
            
            // Notify admins and owners
            $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('admin', 'owner') AND status = 'active'");
            $stmt->execute();
            $approvers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($approvers as $approver) {
                $notification->create([
                    'sender_id' => $userId,
                    'receiver_id' => $approver['id'],
                    'type' => 'info',
                    'category' => 'approval',
                    'title' => 'New Expense Claim',
                    'message' => "Expense claim from {$userName} - ₹" . number_format($amount, 2),
                    'reference_type' => 'expense',
                    'reference_id' => $expenseId,
                    'action_url' => $expenseId ? Environment::getBaseUrl() . "/expenses/view/{$expenseId}" : null
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('NotificationHelper::notifyExpenseClaim error: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function notifyExpenseStatusChange($expenseId, $status, $approverId) {
        $notification = new Notification();
        
        // Get expense details
        $db = Database::connect();
        $stmt = $db->prepare("SELECT e.*, u.name as user_name, a.name as approver_name FROM expenses e JOIN users u ON e.user_id = u.id JOIN users a ON a.id = ? WHERE e.id = ?");
        $stmt->execute([$approverId, $expenseId]);
        $expense = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$expense) return false;
        
        $type = $status === 'approved' ? 'success' : 'warning';
        $title = "Expense Request " . ucfirst($status);
        $message = "Your expense request has been {$status} by {$expense['approver_name']} - Amount: ₹" . number_format($expense['amount'], 2);
        
        $notification->create([
            'sender_id' => $approverId,
            'receiver_id' => $expense['user_id'],
            'type' => $type,
            'category' => 'approval',
            'title' => $title,
            'message' => $message,
            'reference_type' => 'expense',
            'reference_id' => $expenseId,
            'action_url' => Environment::getBaseUrl() . "/expenses/view/{$expenseId}"
        ]);
        
        return true;
    }
    
    public static function notifyAdvanceRequest($advanceId, $userId) {
        $notification = new Notification();
        
        // Get advance details
        $db = Database::connect();
        $stmt = $db->prepare("SELECT a.*, u.name as user_name FROM advances a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
        $stmt->execute([$advanceId]);
        $advance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$advance) return false;
        
        // Notify admins and owners
        $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('admin', 'owner') AND status = 'active'");
        $stmt->execute();
        $approvers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($approvers as $approver) {
            $notification->create([
                'sender_id' => $userId,
                'receiver_id' => $approver['id'],
                'type' => 'info',
                'category' => 'approval',
                'title' => 'New Advance Request',
                'message' => "Advance request from {$advance['user_name']} - ₹" . number_format($advance['amount'], 2) . " for {$advance['reason']}",
                'reference_type' => 'advance',
                'reference_id' => $advanceId,
                'action_url' => Environment::getBaseUrl() . "/advances/view/{$advanceId}"
            ]);
        }
        
        return true;
    }
    
    public static function notifyTaskAssignment($taskId, $assignedTo, $assignedBy) {
        $notification = new Notification();
        
        // Get task details
        $db = Database::connect();
        $stmt = $db->prepare("SELECT t.*, u.name as assigner_name FROM tasks t JOIN users u ON u.id = ? WHERE t.id = ?");
        $stmt->execute([$assignedBy, $taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) return false;
        
        $notification->create([
            'sender_id' => $assignedBy,
            'receiver_id' => $assignedTo,
            'type' => 'info',
            'category' => 'task',
            'title' => 'New Task Assigned',
            'message' => "You have been assigned: {$task['title']}",
            'reference_type' => 'task',
            'reference_id' => $taskId,
            'action_url' => Environment::getBaseUrl() . "/tasks/view/{$taskId}"
        ]);
        
        return true;
    }
    
    public static function notifyTaskReminder($taskId, $userId) {
        $notification = new Notification();
        
        // Get task details
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task) return false;
        
        $notification->create([
            'sender_id' => 1, // System
            'receiver_id' => $userId,
            'type' => 'warning',
            'category' => 'reminder',
            'title' => 'Task Reminder',
            'message' => "Task '{$task['title']}' is due soon",
            'reference_type' => 'task',
            'reference_id' => $taskId,
            'action_url' => Environment::getBaseUrl() . "/tasks/view/{$taskId}",
            'priority' => 2
        ]);
        
        return true;
    }
    
    public static function notifyAdvanceStatusChange($advanceId, $status, $approverId) {
        $notification = new Notification();
        
        // Get advance details
        $db = Database::connect();
        $stmt = $db->prepare("SELECT a.*, u.name as user_name, ap.name as approver_name FROM advances a JOIN users u ON a.user_id = u.id JOIN users ap ON ap.id = ? WHERE a.id = ?");
        $stmt->execute([$approverId, $advanceId]);
        $advance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$advance) return false;
        
        $type = $status === 'approved' ? 'success' : 'warning';
        $title = "Advance Request " . ucfirst($status);
        $message = "Your advance request has been {$status} by {$advance['approver_name']} - Amount: ₹" . number_format($advance['amount'], 2);
        
        if ($status === 'rejected' && !empty($advance['rejection_reason'])) {
            $message .= ". Reason: {$advance['rejection_reason']}";
        }
        
        $notification->create([
            'sender_id' => $approverId,
            'receiver_id' => $advance['user_id'],
            'type' => $type,
            'category' => 'approval',
            'title' => $title,
            'message' => $message,
            'reference_type' => 'advance',
            'reference_id' => $advanceId,
            'action_url' => Environment::getBaseUrl() . "/advances/view/{$advanceId}"
        ]);
        
        return true;
    }
    
    public static function notifyUser($senderId, $receiverId, $module, $action, $message, $referenceId = null) {
        try {
            $notification = new Notification();
            $result = $notification->create([
                'sender_id'      => $senderId,
                'receiver_id'    => $receiverId,
                'title'          => ucfirst($module) . ' ' . ucfirst($action),
                'message'        => $message,
                'reference_type' => $module,
                'reference_id'   => $referenceId,
                'category'       => 'system'
            ]);

            if ($result) {
                self::sendPush(
                    (int)$receiverId,
                    ucfirst($module) . ' ' . ucfirst($action),
                    $message,
                    $referenceId ? Environment::getBaseUrl() . "/{$module}s/view/{$referenceId}" : ''
                );
            }

            return $result;
        } catch (Exception $e) {
            error_log('NotificationHelper::notifyUser error: ' . $e->getMessage());
            return false;
        }
    }

    // ── Attendance ──────────────────────────────────────────────────────────

    public static function notifyClockIn($userId, $userName, $locationName, $time) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $notification = new Notification();

            $msg = "{$userName} clocked in at {$locationName} at {$time}";

            // Notify the user themselves
            $notification->create([
                'sender_id'      => $userId,
                'receiver_id'    => $userId,
                'type'           => 'success',
                'category'       => 'attendance',
                'title'          => 'Clock In Recorded',
                'message'        => "You clocked in at {$locationName} at {$time}",
                'reference_type' => 'attendance',
                'action_url'     => Environment::getBaseUrl() . '/attendance'
            ]);

            // Notify admins and owners
            $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('admin','owner','company_owner') AND status='active'");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $approver) {
                if ($approver['id'] == $userId) continue;
                $notification->create([
                    'sender_id'      => $userId,
                    'receiver_id'    => $approver['id'],
                    'type'           => 'info',
                    'category'       => 'attendance',
                    'title'          => 'Employee Clocked In',
                    'message'        => $msg,
                    'reference_type' => 'attendance',
                    'action_url'     => Environment::getBaseUrl() . '/attendance'
                ]);
            }
        } catch (Exception $e) {
            error_log('notifyClockIn error: ' . $e->getMessage());
        }
    }

    public static function notifyClockOut($userId, $userName, $locationName, $time, $hoursWorked) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $notification = new Notification();

            $msg = "{$userName} clocked out at {$time} (worked {$hoursWorked})";

            // Notify the user themselves
            $notification->create([
                'sender_id'      => $userId,
                'receiver_id'    => $userId,
                'type'           => 'success',
                'category'       => 'attendance',
                'title'          => 'Clock Out Recorded',
                'message'        => "You clocked out at {$time}. Total worked: {$hoursWorked}",
                'reference_type' => 'attendance',
                'action_url'     => Environment::getBaseUrl() . '/attendance'
            ]);

            // Notify admins and owners
            $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('admin','owner','company_owner') AND status='active'");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $approver) {
                if ($approver['id'] == $userId) continue;
                $notification->create([
                    'sender_id'      => $userId,
                    'receiver_id'    => $approver['id'],
                    'type'           => 'info',
                    'category'       => 'attendance',
                    'title'          => 'Employee Clocked Out',
                    'message'        => $msg,
                    'reference_type' => 'attendance',
                    'action_url'     => Environment::getBaseUrl() . '/attendance'
                ]);
            }
        } catch (Exception $e) {
            error_log('notifyClockOut error: ' . $e->getMessage());
        }
    }

    // ── Advance Paid ────────────────────────────────────────────────────────

    public static function notifyAdvancePaid($advanceId, $paidByUserId) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $notification = new Notification();

            $stmt = $db->prepare("SELECT a.*, u.name as user_name, p.name as payer_name FROM advances a JOIN users u ON a.user_id = u.id JOIN users p ON p.id = ? WHERE a.id = ?");
            $stmt->execute([$paidByUserId, $advanceId]);
            $advance = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$advance) return;

            $amount = '₹' . number_format($advance['amount'], 2);
            $notification->create([
                'sender_id'      => $paidByUserId,
                'receiver_id'    => $advance['user_id'],
                'type'           => 'success',
                'category'       => 'approval',
                'title'          => 'Advance Payment Received',
                'message'        => "Your advance of {$amount} has been paid by {$advance['payer_name']}",
                'reference_type' => 'advance',
                'reference_id'   => $advanceId,
                'action_url'     => Environment::getBaseUrl() . "/advances/view/{$advanceId}"
            ]);
        } catch (Exception $e) {
            error_log('notifyAdvancePaid error: ' . $e->getMessage());
        }
    }

    // ── Expense Paid ────────────────────────────────────────────────────────

    public static function notifyExpensePaid($expenseId, $paidByUserId) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $notification = new Notification();

            $stmt = $db->prepare("SELECT e.*, u.name as user_name, p.name as payer_name FROM expenses e JOIN users u ON e.user_id = u.id JOIN users p ON p.id = ? WHERE e.id = ?");
            $stmt->execute([$paidByUserId, $expenseId]);
            $expense = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$expense) return;

            $amount = '₹' . number_format($expense['amount'], 2);
            $notification->create([
                'sender_id'      => $paidByUserId,
                'receiver_id'    => $expense['user_id'],
                'type'           => 'success',
                'category'       => 'approval',
                'title'          => 'Expense Payment Received',
                'message'        => "Your expense of {$amount} has been paid by {$expense['payer_name']}",
                'reference_type' => 'expense',
                'reference_id'   => $expenseId,
                'action_url'     => Environment::getBaseUrl() . "/expenses/view/{$expenseId}"
            ]);
        } catch (Exception $e) {
            error_log('notifyExpensePaid error: ' . $e->getMessage());
        }
    }

    // ── Task Status / Progress ──────────────────────────────────────────────

    public static function notifyTaskStatusChanged($taskId, $newStatus, $changedByUserId) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $notification = new Notification();

            $stmt = $db->prepare("SELECT t.*, u.name as changer_name FROM tasks t JOIN users u ON u.id = ? WHERE t.id = ?");
            $stmt->execute([$changedByUserId, $taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$task) return;

            // Notify assigner if different from changer
            $receivers = array_unique(array_filter([$task['assigned_by'], $task['assigned_to']], fn($id) => $id && $id != $changedByUserId));
            foreach ($receivers as $receiverId) {
                $notification->create([
                    'sender_id'      => $changedByUserId,
                    'receiver_id'    => $receiverId,
                    'type'           => $newStatus === 'completed' ? 'success' : 'info',
                    'category'       => 'task',
                    'title'          => 'Task Status Updated',
                    'message'        => "{$task['changer_name']} changed task '{$task['title']}' status to " . ucfirst(str_replace('_', ' ', $newStatus)),
                    'reference_type' => 'task',
                    'reference_id'   => $taskId,
                    'action_url'     => Environment::getBaseUrl() . "/tasks/view/{$taskId}"
                ]);
            }
        } catch (Exception $e) {
            error_log('notifyTaskStatusChanged error: ' . $e->getMessage());
        }
    }

    public static function notifyTaskProgressUpdated($taskId, $progress, $updatedByUserId) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $notification = new Notification();

            $stmt = $db->prepare("SELECT t.*, u.name as updater_name FROM tasks t JOIN users u ON u.id = ? WHERE t.id = ?");
            $stmt->execute([$updatedByUserId, $taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$task || $task['assigned_by'] == $updatedByUserId) return;

            $notification->create([
                'sender_id'      => $updatedByUserId,
                'receiver_id'    => $task['assigned_by'],
                'type'           => 'info',
                'category'       => 'task',
                'title'          => 'Task Progress Updated',
                'message'        => "{$task['updater_name']} updated '{$task['title']}' progress to {$progress}%",
                'reference_type' => 'task',
                'reference_id'   => $taskId,
                'action_url'     => Environment::getBaseUrl() . "/tasks/view/{$taskId}"
            ]);
        } catch (Exception $e) {
            error_log('notifyTaskProgressUpdated error: ' . $e->getMessage());
        }
    }

    // ── Task Deadline Approaching ───────────────────────────────────────────

    public static function notifyTaskDeadlineApproaching($taskId, $hoursLeft) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $notification = new Notification();

            $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$task) return;

            $label = $hoursLeft <= 1 ? 'less than 1 hour' : "{$hoursLeft} hours";
            foreach (array_unique([$task['assigned_to'], $task['assigned_by']]) as $receiverId) {
                if (!$receiverId) continue;
                $notification->create([
                    'sender_id'      => 1,
                    'receiver_id'    => $receiverId,
                    'type'           => 'warning',
                    'category'       => 'reminder',
                    'title'          => 'Task Deadline Approaching',
                    'message'        => "Task '{$task['title']}' is due in {$label}",
                    'reference_type' => 'task',
                    'reference_id'   => $taskId,
                    'action_url'     => Environment::getBaseUrl() . "/tasks/view/{$taskId}",
                    'priority'       => 2
                ]);
            }
        } catch (Exception $e) {
            error_log('notifyTaskDeadlineApproaching error: ' . $e->getMessage());
        }
    }

    // ── Work Category Budget Exceeded ───────────────────────────────────────

    public static function notifyBudgetExceeded($subcategoryId, $subcategoryName, $projectName, $budget, $spent) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            $notification = new Notification();

            $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('owner','company_owner') AND status='active'");
            $stmt->execute();
            $over = '₹' . number_format($spent - $budget, 2);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $owner) {
                $notification->create([
                    'sender_id'      => 1,
                    'receiver_id'    => $owner['id'],
                    'type'           => 'error',
                    'category'       => 'finance',
                    'title'          => 'Budget Exceeded',
                    'message'        => "Work category '{$subcategoryName}' in project '{$projectName}' has exceeded budget by {$over}",
                    'reference_type' => 'subcategory',
                    'reference_id'   => $subcategoryId,
                    'action_url'     => Environment::getBaseUrl() . "/project-subcategories/{$subcategoryId}"
                ]);
            }
        } catch (Exception $e) {
            error_log('notifyBudgetExceeded error: ' . $e->getMessage());
        }
    }

    public static function sendPush(int $userId, string $title, string $body, string $url = ''): void {
        try {
            require_once __DIR__ . '/../services/PushService.php';
            PushService::sendToUser($userId, $title, $body, $url);
        } catch (Exception $e) {
            error_log('NotificationHelper::sendPush error: ' . $e->getMessage());
        }
    }
}
?>

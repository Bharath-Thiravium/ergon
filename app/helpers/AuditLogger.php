<?php
require_once __DIR__ . '/../models/ActivityLog.php';

class AuditLogger {
    private static $activityLog;
    
    private static function getLogger() {
        if (!self::$activityLog) {
            self::$activityLog = new ActivityLog();
        }
        return self::$activityLog;
    }
    
    public static function log($userId, $action, $details = null) {
        return self::getLogger()->log($userId, $action, $details);
    }
    
    public static function logLogin($userId) {
        return self::log($userId, 'user_login', 'User logged in');
    }
    
    public static function logLogout($userId) {
        return self::log($userId, 'user_logout', 'User logged out');
    }
    
    public static function logTaskCreated($userId, $taskId, $taskTitle) {
        return self::log($userId, 'task_created', "Created task: {$taskTitle} (ID: {$taskId})");
    }
    
    public static function logTaskUpdated($userId, $taskId, $taskTitle) {
        return self::log($userId, 'task_updated', "Updated task: {$taskTitle} (ID: {$taskId})");
    }
    
    public static function logAttendance($userId, $type) {
        return self::log($userId, 'attendance_' . $type, "Clocked {$type}");
    }
    
    public static function logLeaveRequest($userId, $leaveId) {
        return self::log($userId, 'leave_requested', "Submitted leave request (ID: {$leaveId})");
    }
    
    public static function logLeaveApproval($userId, $leaveId, $status) {
        return self::log($userId, 'leave_' . $status, "Leave request {$status} (ID: {$leaveId})");
    }
    
    public static function logExpenseClaim($userId, $expenseId, $amount) {
        return self::log($userId, 'expense_claimed', "Submitted expense claim: ₹{$amount} (ID: {$expenseId})");
    }
    
    public static function logExpenseApproval($userId, $expenseId, $status) {
        return self::log($userId, 'expense_' . $status, "Expense claim {$status} (ID: {$expenseId})");
    }
    
    public static function logUserCreated($userId, $newUserId, $newUserName) {
        return self::log($userId, 'user_created', "Created user: {$newUserName} (ID: {$newUserId})");
    }
    
    public static function logUserUpdated($userId, $updatedUserId, $updatedUserName) {
        return self::log($userId, 'user_updated', "Updated user: {$updatedUserName} (ID: {$updatedUserId})");
    }
    
    public static function logPasswordChange($userId) {
        return self::log($userId, 'password_changed', 'Password changed');
    }
    
    public static function logProfileUpdate($userId) {
        return self::log($userId, 'profile_updated', 'Profile information updated');
    }
}
?>

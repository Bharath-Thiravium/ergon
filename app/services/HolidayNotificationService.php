<?php
/**
 * ERGON Holiday-Aware Notification System
 * Integrates with NotificationController to skip notifications on holidays
 */

require_once __DIR__ . '/../helpers/HolidayHelper.php';

class HolidayAwareNotification {
    
    /**
     * Check if notification should be sent
     * Call this before sending any attendance-related notification
     */
    public static function shouldSendAttendanceNotification($userId, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        // Don't send notifications on holidays
        if (HolidayHelper::isHoliday($date)) {
            return false;
        }
        
        // Check if user is on approved leave
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare(
                "SELECT id FROM leaves 
                 WHERE user_id = ? AND status = 'approved' 
                 AND ? BETWEEN start_date AND end_date 
                 LIMIT 1"
            );
            $stmt->execute([$userId, $date]);
            
            if ($stmt->fetch()) {
                return false; // Don't send if on leave
            }
        } catch (Exception $e) {
            error_log('Holiday aware notification check error: ' . $e->getMessage());
        }
        
        return true;
    }
    
    /**
     * Send holiday greeting notification to all users
     * Call this when a new holiday is created
     */
    public static function sendHolidayGreeting($holiday) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            require_once __DIR__ . '/../models/Notification.php';
            
            // Get applicable users
            $userQuery = "SELECT u.id FROM users u WHERE u.status = 'active'";
            $userParams = [];
            
            if ($holiday['applies_to'] === 'Department' && $holiday['department_id']) {
                $userQuery .= " AND u.department_id = ?";
                $userParams[] = $holiday['department_id'];
            }
            
            $userStmt = $db->prepare($userQuery);
            $userStmt->execute($userParams);
            $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $notificationModel = new Notification();
            $holidayDate = date('M d, Y', strtotime($holiday['holiday_date']));
            
            foreach ($users as $user) {
                $message = "🎉 {$holiday['holiday_name']} on {$holidayDate} has been marked as a holiday. "
                          . "You will not need to mark attendance on this day.";
                
                $notificationModel->create([
                    'user_id' => $user['id'],
                    'type' => 'holiday_notification',
                    'title' => 'Holiday Announced: ' . $holiday['holiday_name'],
                    'message' => $message,
                    'related_id' => $holiday['id'],
                    'priority' => 'normal',
                    'action_url' => '/ergon/holidays'
                ]);
            }
            
            return count($users);
        } catch (Exception $e) {
            error_log('Send holiday greeting error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Modify clock-in notification for holidays
     */
    public static function getClockInReminderText($userId, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $holiday = HolidayHelper::getHolidayInfo($date);
        
        if ($holiday) {
            return "🎉 {$holiday['holiday_name']} - Attendance not required. Enjoy your holiday!";
        }
        
        return "⏰ Time to clock in. Please mark your attendance.";
    }
    
    /**
     * Check for absent escalation eligibility
     * Skip escalation on holidays
     */
    public static function shouldEscalateAbsence($userId, $date) {
        // Don't escalate if it's a holiday
        if (HolidayHelper::isHoliday($date)) {
            return false;
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Check for approved leave
            $stmt = $db->prepare(
                "SELECT id FROM leaves 
                 WHERE user_id = ? AND status = 'approved' 
                 AND ? BETWEEN start_date AND end_date 
                 LIMIT 1"
            );
            $stmt->execute([$userId, $date]);
            
            if ($stmt->fetch()) {
                return false; // Don't escalate if on leave
            }
        } catch (Exception $e) {
            error_log('Escalation check error: ' . $e->getMessage());
        }
        
        return true;
    }
    
    /**
     * Get notification context for dashboard
     */
    public static function getHolidayNotificationContext($userId) {
        try {
            $today = date('Y-m-d');
            $holiday = HolidayHelper::getHolidayInfo($today);
            
            if ($holiday) {
                return [
                    'type' => 'holiday',
                    'title' => $holiday['holiday_name'],
                    'message' => "Today is a holiday. Attendance not required.",
                    'icon' => '🏖️',
                    'color' => 'info',
                    'priority' => 'high'
                ];
            }
            
            // Check upcoming holidays
            $upcomingHolidays = HolidayHelper::getUpcoming(7);
            if (!empty($upcomingHolidays)) {
                $nextHoliday = $upcomingHolidays[0];
                $daysUntil = (strtotime($nextHoliday['holiday_date']) - time()) / 86400;
                
                return [
                    'type' => 'upcoming_holiday',
                    'title' => $nextHoliday['holiday_name'],
                    'message' => "Next holiday in {$daysUntil} days",
                    'icon' => '📅',
                    'color' => 'primary',
                    'priority' => 'normal'
                ];
            }
            
            return null;
        } catch (Exception $e) {
            error_log('Get holiday notification context error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Process batch notifications
     * Call from cron job daily
     */
    public static function processDailyHolidayNotifications() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            require_once __DIR__ . '/../models/Notification.php';
            
            $today = date('Y-m-d');
            $holiday = HolidayHelper::getHolidayInfo($today);
            
            if (!$holiday) {
                return 0;
            }
            
            // Get applicable users
            $userQuery = "SELECT u.id FROM users u WHERE u.status = 'active'";
            $userParams = [];
            
            if ($holiday['applies_to'] === 'Department' && $holiday['department_id']) {
                $userQuery .= " AND u.department_id = ?";
                $userParams[] = $holiday['department_id'];
            }
            
            $userStmt = $db->prepare($userQuery);
            $userStmt->execute($userParams);
            $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($users)) {
                return 0;
            }
            
            $notificationModel = new Notification();
            $message = "🎉 Today is {$holiday['holiday_name']}. "
                      . "Attendance is not required. Have a great day!";
            
            $sentCount = 0;
            foreach ($users as $user) {
                // Check if notification already sent today
                $stmt = $db->prepare(
                    "SELECT id FROM notifications 
                     WHERE user_id = ? AND type = 'holiday_notification' 
                     AND DATE(created_at) = CURDATE() 
                     AND related_id = ? 
                     LIMIT 1"
                );
                $stmt->execute([$user['id'], $holiday['id']]);
                
                if (!$stmt->fetch()) {
                    $notificationModel->create([
                        'user_id' => $user['id'],
                        'type' => 'holiday_notification',
                        'title' => 'Holiday Today: ' . $holiday['holiday_name'],
                        'message' => $message,
                        'related_id' => $holiday['id'],
                        'priority' => 'high',
                        'action_url' => '/ergon/attendance'
                    ]);
                    $sentCount++;
                }
            }
            
            return $sentCount;
        } catch (Exception $e) {
            error_log('Process daily holiday notifications error: ' . $e->getMessage());
            return 0;
        }
    }
}

// Hook for existing notification system
// Add to /app/services/NotificationService.php or notification cron

if (!function_exists('send_attendance_notification')) {
    function send_attendance_notification($userId, $date = null) {
        // Check holiday status first
        if (!HolidayAwareNotification::shouldSendAttendanceNotification($userId, $date)) {
            return false; // Skip notification
        }
        
        // Continue with normal notification
        return true;
    }
}
?>

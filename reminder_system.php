<?php
/**
 * Daily Planner Reminder System
 * Run this script via cron job every 15 minutes to send reminders
 * Cron: */15 * * * * php /path/to/ergon/reminder_system.php
 */

require_once 'app/models/DailyPlanner.php';

try {
    $plannerModel = new DailyPlanner();
    $pendingReminders = $plannerModel->getPendingReminders();
    
    foreach ($pendingReminders as $reminder) {
        // Send notification (email, SMS, or push notification)
        $message = "Reminder: {$reminder['title']} is scheduled for today at {$reminder['reminder_time']}";
        
        // For now, just log the reminder (you can implement email/SMS later)
        error_log("REMINDER: User {$reminder['user_name']} ({$reminder['email']}) - {$message}");
        
        // Mark reminder as sent
        $plannerModel->markReminderSent($reminder['id']);
        
        echo "Reminder sent to {$reminder['user_name']}: {$reminder['title']}\n";
    }
    
    if (empty($pendingReminders)) {
        echo "No pending reminders at " . date('Y-m-d H:i:s') . "\n";
    }
    
} catch (Exception $e) {
    error_log("Reminder System Error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
?>
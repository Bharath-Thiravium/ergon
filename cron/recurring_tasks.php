<?php
/**
 * Recurring Tasks Cron Job
 *
 * Materializes due recurring tasks into daily_tasks for every active user.
 * Run daily at midnight BEFORE the rollover cron:
 *
 *   0 0 * * * php /path/to/ergon/cron/recurring_tasks.php >> /var/log/ergon_recurring.log 2>&1
 *
 * Duplicate prevention:
 *   - RecurringTask::getDueTasks() only returns rows where
 *     last_generated IS NULL OR DATE(last_generated) < today
 *   - daily_tasks is checked for an existing row before INSERT
 *   - updateNextDueDate() stamps last_generated = NOW() after each insert
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line');
}

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/DatabaseHelper.php';
require_once __DIR__ . '/../app/models/RecurringTask.php';

$today = date('Y-m-d');
$log   = fn(string $msg) => print('[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL);

$log('Starting recurring tasks generation for ' . $today);

try {
    $db             = Database::connect();
    $recurringModel = new RecurringTask();

    // Fetch all due recurring tasks across all users (with duplicate guard)
    $dueTasks = $recurringModel->getDueTasks();
    $log('Found ' . count($dueTasks) . ' recurring task(s) due for generation');

    $generated = 0;
    $skipped   = 0;

    foreach ($dueTasks as $rt) {
        $userId        = (int)$rt['assigned_to'];
        $nextDueDate   = $rt['next_due_date'];
        $endDate       = $rt['end_date'] ?? null;

        // Advance through every missed date up to today
        while ($nextDueDate <= $today) {
            // Stop if end_date has passed
            if ($endDate !== null && $nextDueDate > $endDate) {
                $recurringModel->deactivate((int)$rt['id']);
                $log("Deactivated recurring task #{$rt['id']} — end_date {$endDate} reached");
                break;
            }

            // Duplicate guard: skip if a row already exists for this date
            $checkStmt = $db->prepare("
                SELECT COUNT(*) FROM daily_tasks
                WHERE user_id = ? AND recurring_task_id = ? AND scheduled_date = ?
            ");
            $checkStmt->execute([$userId, $rt['id'], $nextDueDate]);

            if ((int)$checkStmt->fetchColumn() === 0) {
                $insertStmt = $db->prepare("
                    INSERT INTO daily_tasks
                        (user_id, recurring_task_id, title, description,
                         scheduled_date, planned_start_time, planned_duration,
                         priority, status, source_field, sla_hours,
                         sla_duration_seconds, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'not_started', 'recurring_daily', ?, ?, NOW())
                ");

                $slaHours        = max(0.0167, (float)($rt['sla_hours'] ?? 0.25));
                $slaDurationSecs = max(60, (int)round($slaHours * 3600));

                $insertStmt->execute([
                    $userId,
                    $rt['id'],
                    $rt['title'],
                    $rt['description'],
                    $nextDueDate,
                    $rt['planned_start_time'] ?? null,
                    $rt['planned_duration']   ?? 60,
                    $rt['priority']           ?? 'medium',
                    $slaHours,
                    $slaDurationSecs,
                ]);

                $generated++;
                $log("Generated daily_task for user #{$userId}, recurring #{$rt['id']} on {$nextDueDate}");
            } else {
                $skipped++;
            }

            // Advance to next day
            $nextDueDate = date('Y-m-d', strtotime($nextDueDate . ' +1 day'));
        }

        // Persist the advanced next_due_date and stamp last_generated = NOW()
        $recurringModel->updateNextDueDate((int)$rt['id'], $nextDueDate);
    }

    $log("Recurring tasks generation complete — generated: {$generated}, skipped (already existed): {$skipped}");

} catch (Exception $e) {
    $log('ERROR: ' . $e->getMessage());
    error_log('recurring_tasks cron error: ' . $e->getMessage());
    exit(1);
}

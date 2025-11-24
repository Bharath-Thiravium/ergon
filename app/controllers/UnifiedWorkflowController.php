<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class UnifiedWorkflowController extends Controller {
    

    
    public function dailyPlanner($date = null) {
        AuthMiddleware::requireAuth();
        
        $date = $date ?? date('Y-m-d');
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            header('Location: /ergon/workflow/daily-planner/' . date('Y-m-d'));
            exit;
        }
        
        // Allow future dates for planning (up to 30 days ahead)
        $maxFutureDate = date('Y-m-d', strtotime('+30 days'));
        if ($date > $maxFutureDate) {
            header('Location: /ergon/workflow/daily-planner/' . date('Y-m-d'));
            exit;
        }
        
        // Check if date is too far in the past (optional limit)
        $earliestDate = date('Y-m-d', strtotime('-90 days'));
        if ($date < $earliestDate) {
            $_SESSION['error_message'] = 'Historical data is only available for the last 90 days.';
        }
        
        $currentUserId = $_SESSION['user_id'];
        
        // Only carry forward for current date or future dates
        $shouldCarryForward = $date >= date('Y-m-d');
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Auto-rollover uncompleted tasks when accessing today's planner
            require_once __DIR__ . '/../models/DailyPlanner.php';
            $planner = new DailyPlanner();
            
            if ($date === date('Y-m-d')) {
                // Clean up any duplicate tasks first
                $cleanedCount = $planner->cleanupDuplicateTasks($currentUserId, $date);

                // ✅ USE SPEC-COMPLIANT ROLLOVER: Detect and perform rollover for the current user.
                $eligibleTasks = $planner->getRolloverTasks($currentUserId);
                $rolledCount = $planner->performRollover($eligibleTasks, $currentUserId);

                if ($rolledCount > 0 || $cleanedCount > 0) {
                    error_log("Daily planner maintenance for user {$currentUserId}: {$rolledCount} tasks rolled over, {$cleanedCount} duplicates cleaned");
                }
            }
            
            // The DailyPlanner model now handles fetching and syncing, so explicit checks here are redundant.
            // The call to $planner->getTasksForDate() will internally call fetchAssignedTasksForDate().
            // REMOVED: Redundant logic for ensureDailyTasksExist() and manual refresh.
            
            // Stable refresh - only sync new tasks without deleting existing ones (only for current date)
            if (isset($_GET['refresh']) && $_GET['refresh'] === '1' && $date === date('Y-m-d')) {
                // The new logic in DailyPlanner::getTasksForDate handles this automatically.
                // We can add a session message if needed.
                $syncedCount = $planner->fetchAssignedTasksForDate($currentUserId, $date); // Re-sync on refresh
                
                // Store sync result for display
                $_SESSION['sync_message'] = $syncedCount > 0 
                    ? "Added {$syncedCount} new task(s) from Tasks module"
                    : "No new tasks to sync";
            } elseif (isset($_GET['refresh']) && $_GET['refresh'] === '1' && $date < date('Y-m-d')) {
                $_SESSION['error_message'] = "Cannot refresh tasks for past dates. Historical data is read-only.";
            }
            
            // Use DailyPlanner model for both tasks and stats
            require_once __DIR__ . '/../models/DailyPlanner.php';
            $planner = new DailyPlanner();
            $plannedTasks = $planner->getTasksForDate($currentUserId, $date);
            $dailyStats = $planner->getDailyStats($currentUserId, $date);
            
            $this->view('daily_workflow/unified_daily_planner', [
                'planned_tasks' => $plannedTasks,
                'daily_stats' => $dailyStats,
                'selected_date' => $date,
                'current_user_id' => $currentUserId,
                'active_page' => 'daily-planner'
            ]);
        } catch (Exception $e) {
            error_log('Daily planner error: ' . $e->getMessage());
            $this->view('daily_workflow/unified_daily_planner', [
                'planned_tasks' => [], 
                'daily_stats' => [],
                'selected_date' => $date
            ]);
        }
    }
    

    

    
    public function followups() {
        AuthMiddleware::requireAuth();
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get followups from followups table including task-linked ones
            try {
                $stmt = $db->prepare("
                    SELECT f.*, 
                           t.title as task_title, 
                           t.assigned_to,
                           u.name as assigned_user,
                           c.name as contact_name,
                           c.company as contact_company
                    FROM followups f 
                    LEFT JOIN tasks t ON f.task_id = t.id 
                    LEFT JOIN users u ON t.assigned_to = u.id 
                    LEFT JOIN contacts c ON f.contact_id = c.id
                    WHERE (f.followup_type = 'standalone' OR (f.followup_type = 'task' AND t.assigned_to = ?) OR f.task_id IS NOT NULL)
                    ORDER BY f.follow_up_date ASC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Debug logging
                error_log('Followups query executed for user: ' . $_SESSION['user_id']);
                error_log('Found followups: ' . count($followups));
                if (!empty($followups)) {
                    error_log('First followup: ' . json_encode($followups[0]));
                }
            } catch (Exception $e) {
                error_log('Followups complex query failed, using fallback: ' . $e->getMessage());
                $stmt = $db->prepare("SELECT * FROM followups ORDER BY follow_up_date ASC");
                $stmt->execute();
                $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log('Fallback query found: ' . count($followups) . ' followups');
            }
            
            // Calculate KPIs
            $today = date('Y-m-d');
            $overdue = $today_count = $completed = 0;
            
            foreach ($followups as $followup) {
                if ($followup['status'] === 'completed') {
                    $completed++;
                } elseif ($followup['follow_up_date'] < $today) {
                    $overdue++;
                } elseif ($followup['follow_up_date'] === $today) {
                    $today_count++;
                }
            }
            
            $data = [
                'followups' => $followups,
                'overdue' => $overdue,
                'today_count' => $today_count,
                'completed' => $completed,
                'active_page' => 'followups'
            ];
            
            $this->view('followups/index', $data);
        } catch (Exception $e) {
            error_log('Followups error: ' . $e->getMessage());
            $this->view('followups/index', ['followups' => [], 'active_page' => 'followups']);
        }
    }
    
    // Calendar functionality moved to TasksController::getTaskSchedule()
    // This method redirects to the new task visualization layer
    public function calendar() {
        header('Location: /ergon/tasks/schedule');
        exit;
    }
    
    private function createPlannerEntry($db, $taskId, $taskData) {
        try {
            $stmt = $db->prepare("
                INSERT INTO daily_planner (user_id, task_id, date, title, description, priority_order, status, created_at)
                VALUES (?, ?, ?, ?, ?, 1, 'planned', NOW())
            ");
            
            $stmt->execute([
                $taskData['assigned_to'],
                $taskId,
                $taskData['planned_date'],
                $taskData['title'],
                $taskData['description']
            ]);
        } catch (Exception $e) {
            error_log('Planner entry creation error: ' . $e->getMessage());
        }
    }
    
    private function createFollowupsFromIncomplete($db, $date) {
        try {
            // Get incomplete tasks from today
            $stmt = $db->prepare("
                SELECT dp.*, t.id as task_id, t.title as task_title
                FROM daily_planner dp
                LEFT JOIN tasks t ON dp.task_id = t.id
                WHERE dp.user_id = ? AND dp.date = ? 
                AND dp.completion_status IN ('not_started', 'in_progress')
            ");
            $stmt->execute([$_SESSION['user_id'], $date]);
            $incompleteTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($incompleteTasks as $task) {
                if (!empty($task['task_id'])) {
                    // Mark task as requiring followup
                    $stmt = $db->prepare("UPDATE tasks SET followup_required = 1 WHERE id = ?");
                    $stmt->execute([$task['task_id']]);
                }
            }
        } catch (Exception $e) {
            error_log('Followup creation error: ' . $e->getMessage());
        }
    }
    
    private function getActiveUsers() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            if (($_SESSION['role'] ?? '') === 'user') {
                $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $stmt = $db->prepare("SELECT id, name, email, role FROM users ORDER BY name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error fetching users: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getDepartments() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error fetching departments: ' . $e->getMessage());
            return [];
        }
    }
    
}
?>
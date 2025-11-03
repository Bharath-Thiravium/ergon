<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class EveningUpdateController extends Controller {
    
    public function index() {
        AuthMiddleware::requireAuth();
        
        $date = $_GET['date'] ?? date('Y-m-d');
        $userId = $_SESSION['user_id'];
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();
            
            // Get today's planned tasks for update
            $plannedTasks = $this->getPlannedTasksForUpdate($db, $userId, $date);
            
            // Get existing evening updates for today
            $existingUpdates = $this->getExistingUpdates($db, $userId, $date);
            
            $data = [
                'planned_tasks' => $plannedTasks,
                'existing_updates' => $existingUpdates,
                'current_date' => $date,
                'active_page' => 'evening_update'
            ];
            
            $this->view('evening-update/index', $data);
        } catch (Exception $e) {
            error_log('Evening update error: ' . $e->getMessage());
            $this->view('evening-update/index', ['planned_tasks' => [], 'existing_updates' => [], 'current_date' => $date, 'active_page' => 'evening_update']);
        }
    }
    
    public function submit() {
        AuthMiddleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = Database::connect();
                
                $updates = json_decode($_POST['updates'], true);
                $date = $_POST['date'] ?? date('Y-m-d');
                $userId = $_SESSION['user_id'];
                
                $db->beginTransaction();
                
                foreach ($updates as $update) {
                    // Insert/Update evening update
                    $stmt = $db->prepare("
                        INSERT INTO evening_updates (user_id, date, planner_id, task_id, progress_percentage, actual_hours_spent, completion_status, blockers, notes) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        progress_percentage = VALUES(progress_percentage),
                        actual_hours_spent = VALUES(actual_hours_spent),
                        completion_status = VALUES(completion_status),
                        blockers = VALUES(blockers),
                        notes = VALUES(notes),
                        updated_at = NOW()
                    ");
                    
                    $stmt->execute([
                        $userId,
                        $date,
                        $update['planner_id'] ?? null,
                        $update['task_id'] ?? null,
                        $update['progress_percentage'] ?? 0,
                        $update['actual_hours_spent'] ?? 0,
                        $update['completion_status'] ?? 'not_started',
                        $update['blockers'] ?? '',
                        $update['notes'] ?? ''
                    ]);
                    
                    // Update task progress if it's an assigned task
                    if (!empty($update['task_id'])) {
                        $this->updateTaskProgress($db, $update['task_id'], $update['progress_percentage'], $update['actual_hours_spent']);
                    }
                    
                    // Update planner status
                    if (!empty($update['planner_id'])) {
                        $plannerStatus = $this->mapCompletionStatusToPlannerStatus($update['completion_status']);
                        $stmt = $db->prepare("UPDATE daily_planner SET status = ? WHERE id = ?");
                        $stmt->execute([$plannerStatus, $update['planner_id']]);
                    }
                }
                
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Evening update submitted successfully']);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }
    }
    
    private function getPlannedTasksForUpdate($db, $userId, $date) {
        $stmt = $db->prepare("
            SELECT dp.*, t.title as task_title, t.overall_progress, t.total_time_spent,
                   eu.progress_percentage, eu.actual_hours_spent, eu.completion_status, eu.blockers, eu.notes
            FROM daily_planner dp 
            LEFT JOIN tasks t ON dp.task_id = t.id 
            LEFT JOIN evening_updates eu ON dp.id = eu.planner_id AND eu.date = ?
            WHERE dp.user_id = ? AND dp.date = ? 
            ORDER BY dp.priority_order
        ");
        $stmt->execute([$date, $userId, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getExistingUpdates($db, $userId, $date) {
        $stmt = $db->prepare("SELECT * FROM evening_updates WHERE user_id = ? AND date = ?");
        $stmt->execute([$userId, $date]);
        $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $indexed = [];
        foreach ($updates as $update) {
            $indexed[$update['planner_id']] = $update;
        }
        return $indexed;
    }
    
    private function updateTaskProgress($db, $taskId, $progressPercentage, $actualHours) {
        // Get current total time spent
        $stmt = $db->prepare("SELECT total_time_spent FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $currentTime = $stmt->fetchColumn() ?: 0;
        
        // Update task with new progress and time
        $newTotalTime = $currentTime + $actualHours;
        $status = $progressPercentage >= 100 ? 'completed' : 'in_progress';
        
        $stmt = $db->prepare("
            UPDATE tasks 
            SET overall_progress = ?, total_time_spent = ?, status = ?, last_progress_update = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$progressPercentage, $newTotalTime, $status, $taskId]);
    }
    
    private function mapCompletionStatusToPlannerStatus($completionStatus) {
        $mapping = [
            'not_started' => 'planned',
            'in_progress' => 'in_progress',
            'completed' => 'completed',
            'blocked' => 'cancelled'
        ];
        return $mapping[$completionStatus] ?? 'planned';
    }
}
?>
<?php
require_once __DIR__ . '/../config/database.php';

class RecurringTask {
    private $db;
    private $table = 'recurring_tasks';
    
    public function __construct() {
        $this->db = Database::connect();
        $this->ensureTable();
    }

    private function ensureTable() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                assigned_to INT NOT NULL,
                frequency VARCHAR(20) NOT NULL DEFAULT 'daily',
                planned_start_time TIME NULL,
                planned_duration INT NOT NULL DEFAULT 60,
                priority VARCHAR(20) NOT NULL DEFAULT 'medium',
                next_due_date DATE NOT NULL,
                end_date DATE NULL,
                created_by INT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                last_generated DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_assigned_active_due (assigned_to, is_active, next_due_date)
            )
        ");

        $requiredColumns = [
            'planned_start_time' => "ALTER TABLE {$this->table} ADD COLUMN planned_start_time TIME NULL",
            'planned_duration' => "ALTER TABLE {$this->table} ADD COLUMN planned_duration INT NOT NULL DEFAULT 60",
            'priority' => "ALTER TABLE {$this->table} ADD COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'medium'",
            'end_date' => "ALTER TABLE {$this->table} ADD COLUMN end_date DATE NULL",
            'created_at' => "ALTER TABLE {$this->table} ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "ALTER TABLE {$this->table} ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];

        foreach ($requiredColumns as $column => $sql) {
            $escapedColumn = str_replace(['\\', "'"], ['\\\\', "\\'"], $column);
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE '{$escapedColumn}'");
            if (!$stmt || !$stmt->fetch()) {
                $this->db->exec($sql);
            }
        }
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
            (title, description, assigned_to, frequency, planned_start_time, planned_duration, priority, next_due_date, end_date, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['assigned_to'],
            $data['frequency'],
            $data['planned_start_time'] ?? null,
            $data['planned_duration'] ?? 60,
            $data['priority'] ?? 'medium',
            $data['next_due_date'],
            $data['end_date'] ?? null,
            $data['created_by']
        ]);
    }
    
    public function getDueTasks() {
        $stmt = $this->db->query("
            SELECT rt.*, u.name as assigned_user 
            FROM {$this->table} rt 
            JOIN users u ON rt.assigned_to = u.id 
            WHERE rt.next_due_date <= CURDATE() AND rt.is_active = 1
        ");
        return $stmt->fetchAll();
    }
    
    public function updateNextDueDate($id, $nextDate) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET next_due_date = ?, last_generated = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$nextDate, $id]);
    }
    
    public function getByUserId($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE assigned_to = ? AND is_active = 1 
            ORDER BY next_due_date ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getDueDailyTasksForUserUntilDate($userId, $date) {
        $stmt = $this->db->prepare("
            SELECT *
            FROM {$this->table}
            WHERE assigned_to = ?
            AND is_active = 1
            AND frequency = 'daily'
            AND next_due_date <= ?
            AND (end_date IS NULL OR end_date >= next_due_date)
            ORDER BY next_due_date ASC, id ASC
        ");
        $stmt->execute([$userId, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deactivate($id) {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET is_active = 0, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }
}
?>

<?php
require_once __DIR__ . '/../config/database.php';

class Followup {
    private $db;
    
    public function __construct() {
        $this->db = Database::connect();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO followups (user_id, task_id, title, description, company_name, contact_person, 
                                 contact_phone, contact_email, project_name, department_id, priority, 
                                 follow_up_date, original_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['user_id'],
            $data['task_id'] ?? null,
            $data['title'],
            $data['description'] ?? '',
            $data['company_name'] ?? '',
            $data['contact_person'] ?? '',
            $data['contact_phone'] ?? '',
            $data['contact_email'] ?? '',
            $data['project_name'] ?? '',
            $data['department_id'] ?? null,
            $data['priority'] ?? 'medium',
            $data['follow_up_date'],
            $data['follow_up_date'] // original_date same as first follow_up_date
        ]);
        
        if ($result) {
            $followupId = $this->db->lastInsertId();
            
            // Add to history
            $this->addHistory($followupId, 'created', null, $data['follow_up_date'], 'Follow-up created', $data['user_id']);
            
            // Create checklist items if provided
            if (!empty($data['items'])) {
                $this->addItems($followupId, $data['items']);
            }
            
            return $followupId;
        }
        
        return false;
    }
    
    public function getByUser($userId, $date = null) {
        $sql = "
            SELECT f.*, d.name as department_name,
                   (SELECT COUNT(*) FROM followup_items fi WHERE fi.followup_id = f.id) as total_items,
                   (SELECT COUNT(*) FROM followup_items fi WHERE fi.followup_id = f.id AND fi.is_completed = 1) as completed_items
            FROM followups f 
            LEFT JOIN departments d ON f.department_id = d.id 
            WHERE f.user_id = ?
        ";
        
        $params = [$userId];
        
        if ($date) {
            $sql .= " AND f.follow_up_date = ?";
            $params[] = $date;
        }
        
        $sql .= " ORDER BY f.follow_up_date ASC, f.priority DESC, f.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT f.*, d.name as department_name 
            FROM followups f 
            LEFT JOIN departments d ON f.department_id = d.id 
            WHERE f.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE followups SET 
                title = ?, description = ?, company_name = ?, contact_person = ?, 
                contact_phone = ?, contact_email = ?, project_name = ?, 
                department_id = ?, priority = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['title'],
            $data['description'] ?? '',
            $data['company_name'] ?? '',
            $data['contact_person'] ?? '',
            $data['contact_phone'] ?? '',
            $data['contact_email'] ?? '',
            $data['project_name'] ?? '',
            $data['department_id'] ?? null,
            $data['priority'] ?? 'medium',
            $data['status'] ?? 'pending',
            $id
        ]);
    }
    
    public function reschedule($id, $newDate, $reason, $userId) {
        // Get current date
        $stmt = $this->db->prepare("SELECT follow_up_date FROM followups WHERE id = ?");
        $stmt->execute([$id]);
        $oldDate = $stmt->fetchColumn();
        
        // Update follow-up date and increment reschedule count
        $stmt = $this->db->prepare("
            UPDATE followups SET 
                follow_up_date = ?, 
                reschedule_count = reschedule_count + 1,
                status = 'pending',
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$newDate, $id]);
        
        if ($result) {
            // Add to history
            $this->addHistory($id, 'rescheduled', $oldDate, $newDate, $reason, $userId);
        }
        
        return $result;
    }
    
    public function complete($id, $notes, $userId) {
        $stmt = $this->db->prepare("
            UPDATE followups SET 
                status = 'completed', 
                completed_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$id]);
        
        if ($result) {
            // Mark all items as completed
            $stmt = $this->db->prepare("
                UPDATE followup_items SET 
                    is_completed = 1, 
                    completed_at = NOW() 
                WHERE followup_id = ? AND is_completed = 0
            ");
            $stmt->execute([$id]);
            
            // Add to history
            $this->addHistory($id, 'completed', null, null, $notes, $userId);
        }
        
        return $result;
    }
    
    public function addItems($followupId, $items) {
        $stmt = $this->db->prepare("
            INSERT INTO followup_items (followup_id, item_text, sort_order) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($items as $index => $item) {
            if (!empty(trim($item))) {
                $stmt->execute([$followupId, trim($item), $index]);
            }
        }
    }
    
    public function getItems($followupId) {
        $stmt = $this->db->prepare("
            SELECT * FROM followup_items 
            WHERE followup_id = ? 
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$followupId]);
        return $stmt->fetchAll();
    }
    
    public function updateItem($itemId, $isCompleted) {
        $completedAt = $isCompleted ? 'NOW()' : 'NULL';
        $stmt = $this->db->prepare("
            UPDATE followup_items SET 
                is_completed = ?, 
                completed_at = $completedAt 
            WHERE id = ?
        ");
        return $stmt->execute([$isCompleted, $itemId]);
    }
    
    public function getHistory($followupId) {
        $stmt = $this->db->prepare("
            SELECT fh.*, u.name as user_name 
            FROM followup_history fh 
            JOIN users u ON fh.created_by = u.id 
            WHERE fh.followup_id = ? 
            ORDER BY fh.created_at DESC
        ");
        $stmt->execute([$followupId]);
        return $stmt->fetchAll();
    }
    
    public function getUpcoming($userId, $days = 7) {
        $stmt = $this->db->prepare("
            SELECT f.*, d.name as department_name,
                   (SELECT COUNT(*) FROM followup_items fi WHERE fi.followup_id = f.id) as total_items,
                   (SELECT COUNT(*) FROM followup_items fi WHERE fi.followup_id = f.id AND fi.is_completed = 1) as completed_items
            FROM followups f 
            LEFT JOIN departments d ON f.department_id = d.id 
            WHERE f.user_id = ? 
                AND f.status IN ('pending', 'in_progress') 
                AND f.follow_up_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY f.follow_up_date ASC, f.priority DESC
        ");
        $stmt->execute([$userId, $days]);
        return $stmt->fetchAll();
    }
    
    public function getOverdue($userId) {
        $stmt = $this->db->prepare("
            SELECT f.*, d.name as department_name,
                   (SELECT COUNT(*) FROM followup_items fi WHERE fi.followup_id = f.id) as total_items,
                   (SELECT COUNT(*) FROM followup_items fi WHERE fi.followup_id = f.id AND fi.is_completed = 1) as completed_items
            FROM followups f 
            LEFT JOIN departments d ON f.department_id = d.id 
            WHERE f.user_id = ? 
                AND f.status IN ('pending', 'in_progress') 
                AND f.follow_up_date < CURDATE()
            ORDER BY f.follow_up_date ASC, f.priority DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    private function addHistory($followupId, $actionType, $oldDate, $newDate, $notes, $userId) {
        $stmt = $this->db->prepare("
            INSERT INTO followup_history (followup_id, action_type, old_date, new_date, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$followupId, $actionType, $oldDate, $newDate, $notes, $userId]);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM followups WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>
<?php
require_once __DIR__ . '/../config/database.php';

class Holiday {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::connect();
    }
    
    /**
     * Create a new holiday
     */
    public function create($data) {
        try {
            $query = "INSERT INTO holidays (holiday_date, holiday_name, holiday_type, description, applies_to, department_id, repeat_yearly, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $data['holiday_date'],
                $data['holiday_name'],
                $data['holiday_type'] ?? 'Company',
                $data['description'] ?? null,
                $data['applies_to'] ?? 'All',
                $data['department_id'] ?? null,
                $data['repeat_yearly'] ? 1 : 0,
                $data['created_by']
            ]);
            
            if ($result) {
                $holidayId = $this->conn->lastInsertId();
                // Auto-mark all applicable employees as holiday
                $this->applyHolidayToAttendance($holidayId, $data);
                return $holidayId;
            }
            return false;
        } catch (Exception $e) {
            error_log('Holiday creation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all holidays
     */
    public function getAll($filters = []) {
        try {
            $query = "SELECT h.*, u.name as created_by_name, d.name as department_name 
                      FROM holidays h 
                      LEFT JOIN users u ON h.created_by = u.id 
                      LEFT JOIN departments d ON h.department_id = d.id 
                      WHERE h.is_active = 1";
            
            $params = [];
            
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $query .= " AND h.holiday_date BETWEEN ? AND ?";
                $params[] = $filters['start_date'];
                $params[] = $filters['end_date'];
            }
            
            if (!empty($filters['holiday_type'])) {
                $query .= " AND h.holiday_type = ?";
                $params[] = $filters['holiday_type'];
            }
            
            $query .= " ORDER BY h.holiday_date ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Holiday getAll error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get holiday by date
     */
    public function getByDate($date) {
        try {
            $query = "SELECT h.*, u.name as created_by_name, d.name as department_name 
                      FROM holidays h 
                      LEFT JOIN users u ON h.created_by = u.id 
                      LEFT JOIN departments d ON h.department_id = d.id 
                      WHERE h.holiday_date = ? AND h.is_active = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$date]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Holiday getByDate error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if a date is a holiday
     */
    public function isHoliday($date) {
        $holiday = $this->getByDate($date);
        return $holiday !== null && $holiday !== false;
    }
    
    /**
     * Apply holiday to attendance records for ALL applicable users
     */
    private function applyHolidayToAttendance($holidayId, $data) {
        try {
            $date = $data['holiday_date'];
            $appliesTo = $data['applies_to'] ?? 'All';
            $departmentId = $data['department_id'] ?? null;
            
            error_log('Applying holiday ' . $holidayId . ' to date ' . $date . ' (applies_to: ' . $appliesTo . ')');
            
            // Get users based on scope
            $userQuery = "SELECT u.id, u.name, u.department_id FROM users u WHERE u.status = 'active'";
            $userParams = [];
            
            if ($appliesTo === 'Department' && $departmentId) {
                $userQuery .= " AND u.department_id = ?";
                $userParams[] = $departmentId;
            } elseif ($appliesTo === 'Specific') {
                // Specific employees handled separately via holiday_employees table
                error_log('Holiday applies to specific employees - requires manual selection');
                return true;
            }
            
            // Add ORDER BY for consistency
            $userQuery .= " ORDER BY u.id";
            
            $userStmt = $this->conn->prepare($userQuery);
            $userStmt->execute($userParams);
            $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log('Found ' . count($users) . ' users to mark as holiday');
            
            // Mark/update attendance for all applicable users
            $successCount = 0;
            foreach ($users as $user) {
                if ($this->markAttendanceAsHoliday($user['id'], $date, $holidayId)) {
                    $successCount++;
                }
            }
            
            error_log('Successfully marked ' . $successCount . ' attendance records as holiday');
            
            return true;
        } catch (Exception $e) {
            error_log('Apply holiday error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark or create attendance record as holiday for a user
     */
    private function markAttendanceAsHoliday($userId, $date, $holidayId) {
        try {
            // Check if attendance exists for this date
            $stmt = $this->conn->prepare(
                "SELECT id FROM attendance WHERE user_id = ? AND DATE(check_in) = ?"
            );
            $stmt->execute([$userId, $date]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing record
                $stmt = $this->conn->prepare(
                    "UPDATE attendance SET is_holiday = 1, holiday_id = ?, status = 'holiday', is_counted_absent = 0 WHERE id = ?"
                );
                $result = $stmt->execute([$holidayId, $existing['id']]);
                if ($result) {
                    error_log('Updated attendance record ' . $existing['id'] . ' for user ' . $userId . ' as holiday');
                }
                return $result;
            } else {
                // Create new holiday attendance record for user
                $stmt = $this->conn->prepare(
                    "INSERT INTO attendance (user_id, holiday_id, check_in, status, location_name, is_holiday, is_counted_absent, created_at) 
                     VALUES (?, ?, ?, 'holiday', 'Holiday', 1, 0, NOW())"
                );
                $result = $stmt->execute([$userId, $holidayId, $date . ' 00:00:00']);
                if ($result) {
                    error_log('Created new holiday attendance record for user ' . $userId . ' on ' . $date);
                }
                return $result;
            }
        } catch (Exception $e) {
            error_log('Mark attendance as holiday error for user ' . $userId . ': ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update holiday
     */
    public function update($id, $data) {
        try {
            $query = "UPDATE holidays SET holiday_name = ?, holiday_type = ?, description = ?, applies_to = ?, department_id = ?, repeat_yearly = ?, updated_at = NOW() 
                      WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $data['holiday_name'],
                $data['holiday_type'] ?? 'Company',
                $data['description'] ?? null,
                $data['applies_to'] ?? 'All',
                $data['department_id'] ?? null,
                $data['repeat_yearly'] ? 1 : 0,
                $id
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Update related attendance records
                $holiday = $this->getById($id);
                if ($holiday) {
                    $this->updateAttendanceRecords($holiday);
                }
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log('Holiday update error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete holiday
     */
    public function delete($id) {
        try {
            // Get holiday details first
            $holiday = $this->getById($id);
            if (!$holiday) {
                return false;
            }
            
            // Mark as inactive instead of deleting
            $query = "UPDATE holidays SET is_active = 0, updated_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Remove holiday marking from attendance records
                $stmt = $this->conn->prepare("UPDATE attendance SET is_holiday = 0, holiday_id = NULL, is_counted_absent = 1 
                                             WHERE holiday_id = ? AND DATE(check_in) = ?");
                $stmt->execute([$id, $holiday['holiday_date']]);
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log('Holiday delete error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get holiday by ID
     */
    public function getById($id) {
        try {
            $query = "SELECT h.*, u.name as created_by_name, d.name as department_name 
                      FROM holidays h 
                      LEFT JOIN users u ON h.created_by = u.id 
                      LEFT JOIN departments d ON h.department_id = d.id 
                      WHERE h.id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Holiday getById error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check for duplicate holidays on same date
     */
    public function isDuplicate($date, $excludeId = null) {
        try {
            $query = "SELECT id FROM holidays WHERE holiday_date = ? AND is_active = 1";
            $params = [$date];
            
            if ($excludeId) {
                $query .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log('Holiday isDuplicate error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get upcoming holidays
     */
    public function getUpcoming($days = 30) {
        try {
            $query = "SELECT h.*, u.name as created_by_name, d.name as department_name 
                      FROM holidays h 
                      LEFT JOIN users u ON h.created_by = u.id 
                      LEFT JOIN departments d ON h.department_id = d.id 
                      WHERE h.is_active = 1 
                      AND h.holiday_date >= CURDATE() 
                      AND h.holiday_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                      ORDER BY h.holiday_date ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Holiday getUpcoming error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get today's holiday if applicable
     */
    public function getTodayHoliday() {
        return $this->getByDate(date('Y-m-d'));
    }
    
    /**
     * Update attendance records when holiday is modified
     */
    private function updateAttendanceRecords($holiday) {
        try {
            $query = "UPDATE attendance SET status = 'holiday' WHERE holiday_id = ? AND is_holiday = 1";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$holiday['id']]);
        } catch (Exception $e) {
            error_log('Update attendance records error: ' . $e->getMessage());
            return false;
        }
    }
}
?>

<?php

class HolidayHelper {
    private static $conn = null;
    
    public static function getConnection() {
        if (self::$conn === null) {
            require_once __DIR__ . '/../config/database.php';
            self::$conn = Database::connect();
        }
        return self::$conn;
    }
    
    /**
     * Check if a date is a holiday
     */
    public static function isHoliday($date) {
        try {
            $db = self::getConnection();
            $stmt = $db->prepare("SELECT id FROM holidays WHERE holiday_date = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$date]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log('HolidayHelper::isHoliday error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get holiday info for a specific date
     */
    public static function getHolidayInfo($date) {
        try {
            $db = self::getConnection();
            $stmt = $db->prepare("SELECT * FROM holidays WHERE holiday_date = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$date]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('HolidayHelper::getHolidayInfo error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get holidays for a date range
     */
    public static function getHolidaysInRange($startDate, $endDate) {
        try {
            $db = self::getConnection();
            $stmt = $db->prepare("SELECT holiday_date FROM holidays WHERE holiday_date BETWEEN ? AND ? AND is_active = 1");
            $stmt->execute([$startDate, $endDate]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $holidays = [];
            foreach ($result as $row) {
                $holidays[] = $row['holiday_date'];
            }
            return $holidays;
        } catch (Exception $e) {
            error_log('HolidayHelper::getHolidaysInRange error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate working days excluding holidays and weekends
     */
    public static function calculateWorkingDays($startDate, $endDate, $excludeWeekends = true) {
        try {
            $holidays = self::getHolidaysInRange($startDate, $endDate);
            $workingDays = 0;
            
            $current = strtotime($startDate);
            $end = strtotime($endDate);
            
            while ($current <= $end) {
                $date = date('Y-m-d', $current);
                $dayOfWeek = date('w', $current);
                
                // Skip weekends if configured
                if ($excludeWeekends && ($dayOfWeek == 0 || $dayOfWeek == 6)) {
                    $current = strtotime('+1 day', $current);
                    continue;
                }
                
                // Skip holidays
                if (!in_array($date, $holidays)) {
                    $workingDays++;
                }
                
                $current = strtotime('+1 day', $current);
            }
            
            return $workingDays;
        } catch (Exception $e) {
            error_log('HolidayHelper::calculateWorkingDays error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get attendance status considering holidays
     * Returns: 'present', 'absent', 'on_leave', 'holiday'
     */
    public static function getAttendanceStatus($userId, $date) {
        try {
            $db = self::getConnection();
            
            // Check if it's a holiday first
            if (self::isHoliday($date)) {
                return 'holiday';
            }
            
            // Check attendance record
            $stmt = $db->prepare(
                "SELECT status, is_holiday, check_in FROM attendance 
                 WHERE user_id = ? AND DATE(check_in) = ? 
                 LIMIT 1"
            );
            $stmt->execute([$userId, $date]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($record) {
                if ($record['is_holiday']) {
                    return 'holiday';
                }
                return $record['status'] ?: 'present';
            }
            
            // Check if on leave
            $stmt = $db->prepare(
                "SELECT id FROM leaves 
                 WHERE user_id = ? AND status = 'approved' 
                 AND ? BETWEEN start_date AND end_date 
                 LIMIT 1"
            );
            $stmt->execute([$userId, $date]);
            if ($stmt->fetch()) {
                return 'on_leave';
            }
            
            return 'absent';
        } catch (Exception $e) {
            error_log('HolidayHelper::getAttendanceStatus error: ' . $e->getMessage());
            return 'absent';
        }
    }
    
    /**
     * Get monthly attendance summary excluding holidays
     */
    public static function getMonthlyAttendanceSummary($userId, $month, $year) {
        try {
            $db = self::getConnection();
            $startDate = date('Y-m-01', strtotime("$year-$month-01"));
            $endDate = date('Y-m-t', strtotime("$year-$month-01"));
            
            $holidays = self::getHolidaysInRange($startDate, $endDate);
            
            $stmt = $db->prepare(
                "SELECT 
                    COUNT(CASE WHEN status = 'present' OR is_holiday = 1 THEN 1 END) as present_days,
                    COUNT(CASE WHEN status = 'absent' AND is_holiday = 0 THEN 1 END) as absent_days,
                    COUNT(CASE WHEN is_holiday = 1 THEN 1 END) as holiday_days,
                    SUM(IF(check_out IS NOT NULL, 
                        TIMESTAMPDIFF(MINUTE, check_in, check_out), 0)) as total_minutes
                 FROM attendance 
                 WHERE user_id = ? AND check_in BETWEEN ? AND ?
                 AND is_counted_absent = 1"
            );
            $stmt->execute([$userId, $startDate, $endDate . ' 23:59:59']);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'present_days' => intval($summary['present_days'] ?? 0),
                'absent_days' => intval($summary['absent_days'] ?? 0),
                'holiday_days' => intval($summary['holiday_days'] ?? 0),
                'total_holidays' => count($holidays),
                'working_days' => self::calculateWorkingDays($startDate, $endDate),
                'total_minutes' => intval($summary['total_minutes'] ?? 0),
                'total_hours' => intval($summary['total_minutes'] ?? 0) / 60
            ];
        } catch (Exception $e) {
            error_log('HolidayHelper::getMonthlyAttendanceSummary error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get absent count excluding holidays
     */
    public static function getAbsentCountExcludingHolidays($userId, $startDate, $endDate) {
        try {
            $db = self::getConnection();
            
            $stmt = $db->prepare(
                "SELECT COUNT(*) as absent_count FROM attendance 
                 WHERE user_id = ? 
                 AND check_in BETWEEN ? AND ? 
                 AND status = 'absent' 
                 AND is_holiday = 0
                 AND is_counted_absent = 1"
            );
            $stmt->execute([$userId, $startDate, $endDate]);
            $result = $stmt->fetch();
            return intval($result['absent_count'] ?? 0);
        } catch (Exception $e) {
            error_log('HolidayHelper::getAbsentCountExcludingHolidays error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get presence percentage excluding holidays
     */
    public static function getPresencePercentage($userId, $startDate, $endDate) {
        try {
            $db = self::getConnection();
            $holidays = self::getHolidaysInRange($startDate, $endDate);
            
            $stmt = $db->prepare(
                "SELECT COUNT(*) as present FROM attendance 
                 WHERE user_id = ? 
                 AND check_in BETWEEN ? AND ? 
                 AND (status = 'present' OR is_holiday = 1)"
            );
            $stmt->execute([$userId, $startDate, $endDate]);
            $present = intval($stmt->fetch()['present'] ?? 0);
            
            $workingDays = self::calculateWorkingDays($startDate, $endDate);
            
            if ($workingDays == 0) {
                return 0;
            }
            
            return round(($present / $workingDays) * 100, 2);
        } catch (Exception $e) {
            error_log('HolidayHelper::getPresencePercentage error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Mark attendance records for upcoming holidays (batch operation)
     */
    public static function syncHolidayAttendance() {
        try {
            $db = self::getConnection();
            
            // Get all active holidays
            $stmt = $db->prepare("SELECT * FROM holidays WHERE is_active = 1 AND holiday_date >= CURDATE()");
            $stmt->execute();
            $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $synced = 0;
            
            foreach ($holidays as $holiday) {
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
                
                // Create/update attendance records
                foreach ($users as $user) {
                    $checkStmt = $db->prepare(
                        "SELECT id FROM attendance 
                         WHERE user_id = ? AND DATE(check_in) = ? 
                         LIMIT 1"
                    );
                    $checkStmt->execute([$user['id'], $holiday['holiday_date']]);
                    
                    if ($checkStmt->fetch()) {
                        // Update existing
                        $updateStmt = $db->prepare(
                            "UPDATE attendance SET is_holiday = 1, holiday_id = ?, 
                             status = 'holiday', is_counted_absent = 0 
                             WHERE user_id = ? AND DATE(check_in) = ?"
                        );
                        $updateStmt->execute([$holiday['id'], $user['id'], $holiday['holiday_date']]);
                    } else {
                        // Create new
                        $insertStmt = $db->prepare(
                            "INSERT INTO attendance 
                             (user_id, holiday_id, check_in, status, location_name, is_holiday, is_counted_absent, created_at) 
                             VALUES (?, ?, ?, 'holiday', 'Holiday', 1, 0, NOW())"
                        );
                        $insertStmt->execute([$user['id'], $holiday['id'], $holiday['holiday_date'] . ' 00:00:00']);
                    }
                    $synced++;
                }
            }
            
            return $synced;
        } catch (Exception $e) {
            error_log('HolidayHelper::syncHolidayAttendance error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get holiday color by type
     */
    public static function getHolidayTypeColor($type) {
        $colors = [
            'National' => '#0066cc',
            'Festival' => '#ff6600',
            'Company' => '#00cc66',
            'Emergency' => '#cc0000',
            'Other' => '#9933cc'
        ];
        return $colors[$type] ?? '#0066cc';
    }
    
    /**
     * Format holidays for calendar display
     */
    public static function formatForCalendar($holidays) {
        $formatted = [];
        foreach ($holidays as $holiday) {
            $formatted[] = [
                'id' => $holiday['id'],
                'title' => $holiday['holiday_name'],
                'date' => $holiday['holiday_date'],
                'type' => $holiday['holiday_type'],
                'color' => self::getHolidayTypeColor($holiday['holiday_type']),
                'applies_to' => $holiday['applies_to']
            ];
        }
        return $formatted;
    }
}
?>

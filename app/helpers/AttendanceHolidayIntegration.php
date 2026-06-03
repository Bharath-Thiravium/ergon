<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/HolidayHelper.php';

class AttendanceHolidayIntegration {
    private $conn;
    
    public function __construct() {
        $this->conn = Database::connect();
    }
    
    /**
     * Get attendance with holiday consideration
     */
    public function getAttendanceWithHolidayStatus($userId, $date) {
        try {
            // First check if it's a holiday
            $holiday = HolidayHelper::getHolidayInfo($date);
            
            if ($holiday) {
                return [
                    'is_holiday' => true,
                    'holiday_id' => $holiday['id'],
                    'holiday_name' => $holiday['holiday_name'],
                    'status' => 'holiday',
                    'attendance_id' => null
                ];
            }
            
            // Get actual attendance
            $stmt = $this->conn->prepare(
                "SELECT id, status, check_in, check_out, is_holiday, holiday_id 
                 FROM attendance 
                 WHERE user_id = ? AND DATE(check_in) = ? 
                 LIMIT 1"
            );
            $stmt->execute([$userId, $date]);
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $attendance ? [
                'is_holiday' => (bool)$attendance['is_holiday'],
                'holiday_id' => $attendance['holiday_id'],
                'status' => $attendance['status'],
                'attendance_id' => $attendance['id'],
                'check_in' => $attendance['check_in'],
                'check_out' => $attendance['check_out']
            ] : [
                'is_holiday' => false,
                'status' => 'absent',
                'attendance_id' => null
            ];
        } catch (Exception $e) {
            error_log('getAttendanceWithHolidayStatus error: ' . $e->getMessage());
            return ['is_holiday' => false, 'status' => 'absent'];
        }
    }
    
    /**
     * Ensure holiday attendance is properly marked
     */
    public function syncHolidayAttendance($userId, $date) {
        try {
            $holiday = HolidayHelper::getHolidayInfo($date);
            
            if (!$holiday) {
                return false;
            }
            
            $stmt = $this->conn->prepare(
                "SELECT id FROM attendance 
                 WHERE user_id = ? AND DATE(check_in) = ? 
                 LIMIT 1"
            );
            $stmt->execute([$userId, $date]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $stmt = $this->conn->prepare(
                    "UPDATE attendance SET is_holiday = 1, holiday_id = ?, 
                     status = 'holiday', is_counted_absent = 0 
                     WHERE id = ?"
                );
                return $stmt->execute([$holiday['id'], $existing['id']]);
            } else {
                $stmt = $this->conn->prepare(
                    "INSERT INTO attendance 
                     (user_id, holiday_id, check_in, status, location_name, is_holiday, is_counted_absent, created_at) 
                     VALUES (?, ?, ?, 'holiday', 'Holiday', 1, 0, NOW())"
                );
                return $stmt->execute([$userId, $holiday['id'], $date . ' 00:00:00']);
            }
        } catch (Exception $e) {
            error_log('syncHolidayAttendance error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get monthly attendance summary with holiday exclusion
     */
    public function getMonthlyAttendanceSummary($userId, $month, $year) {
        try {
            return HolidayHelper::getMonthlyAttendanceSummary($userId, $month, $year);
        } catch (Exception $e) {
            error_log('getMonthlyAttendanceSummary error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get absence count excluding holidays
     */
    public function getAbsenceCountExcludingHolidays($userId, $startDate, $endDate) {
        try {
            return HolidayHelper::getAbsentCountExcludingHolidays($userId, $startDate, $endDate);
        } catch (Exception $e) {
            error_log('getAbsenceCountExcludingHolidays error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Disable notifications on holidays
     */
    public static function shouldSendAttendanceNotification($userId, $date) {
        // Return false if it's a holiday - no notifications on holidays
        if (HolidayHelper::isHoliday($date)) {
            return false;
        }
        return true;
    }
    
    /**
     * Get working days count excluding holidays
     */
    public static function getWorkingDaysCount($startDate, $endDate) {
        return HolidayHelper::calculateWorkingDays($startDate, $endDate);
    }
    
    /**
     * Format attendance row with holiday styling
     */
    public static function formatAttendanceRow($attendance, $date) {
        if (HolidayHelper::isHoliday($date)) {
            $attendance['display_status'] = 'Holiday';
            $attendance['badge_class'] = 'badge--info';
            $attendance['badge_icon'] = '🏖️';
            $attendance['is_excluded_from_absence'] = true;
        }
        return $attendance;
    }
}
?>

<?php
class EmployeeHelper {
    
    public static function generateEmployeeId($companyPrefix = 'EMP') {
        $timestamp = time();
        $random = mt_rand(100, 999);
        return strtoupper($companyPrefix) . date('y', $timestamp) . $random;
    }
    
    public static function calculateWorkingHours($clockIn, $clockOut, $breakMinutes = 60) {
        $start = new DateTime($clockIn);
        $end = new DateTime($clockOut);
        
        $diff = $start->diff($end);
        $totalMinutes = ($diff->h * 60) + $diff->i - $breakMinutes;
        
        return max(0, $totalMinutes / 60);
    }
    
    public static function getAttendanceStatus($userId, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        require_once __DIR__ . '/../models/Attendance.php';
        $attendanceModel = new Attendance();
        $records = $attendanceModel->getByUserId($userId, $date, $date);
        
        $clockIn = null;
        $clockOut = null;
        
        foreach ($records as $record) {
            if ($record['type'] === 'in' && !$clockIn) {
                $clockIn = $record['timestamp'];
            } elseif ($record['type'] === 'out') {
                $clockOut = $record['timestamp'];
            }
        }
        
        return [
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status' => $clockIn ? ($clockOut ? 'completed' : 'active') : 'absent',
            'working_hours' => $clockIn && $clockOut ? self::calculateWorkingHours($clockIn, $clockOut) : 0
        ];
    }
    
    public static function getLeaveBalance($userId, $leaveType = null) {
        require_once __DIR__ . '/../models/Leave.php';
        $leaveModel = new Leave();
        
        $currentYear = date('Y');
        $startDate = $currentYear . '-01-01';
        $endDate = $currentYear . '-12-31';
        
        $leaves = $leaveModel->getByUserId($userId);
        
        $used = 0;
        foreach ($leaves as $leave) {
            if ($leave['status'] === 'approved' && 
                $leave['start_date'] >= $startDate && 
                $leave['end_date'] <= $endDate &&
                (!$leaveType || $leave['type'] === $leaveType)) {
                
                $start = new DateTime($leave['start_date']);
                $end = new DateTime($leave['end_date']);
                $used += $start->diff($end)->days + 1;
            }
        }
        
        $allowances = [
            'casual' => 12,
            'sick' => 12,
            'annual' => 21,
            'emergency' => 5
        ];
        
        if ($leaveType) {
            return [
                'allowed' => $allowances[$leaveType] ?? 0,
                'used' => $used,
                'remaining' => max(0, ($allowances[$leaveType] ?? 0) - $used)
            ];
        }
        
        $balance = [];
        foreach ($allowances as $type => $allowed) {
            $typeUsed = 0;
            foreach ($leaves as $leave) {
                if ($leave['status'] === 'approved' && 
                    $leave['type'] === $type &&
                    $leave['start_date'] >= $startDate && 
                    $leave['end_date'] <= $endDate) {
                    
                    $start = new DateTime($leave['start_date']);
                    $end = new DateTime($leave['end_date']);
                    $typeUsed += $start->diff($end)->days + 1;
                }
            }
            
            $balance[$type] = [
                'allowed' => $allowed,
                'used' => $typeUsed,
                'remaining' => max(0, $allowed - $typeUsed)
            ];
        }
        
        return $balance;
    }
    
    public static function getTaskProductivity($userId, $days = 30) {
        require_once __DIR__ . '/../models/Task.php';
        $taskModel = new Task();
        
        $tasks = $taskModel->getByUserId($userId);
        
        $completed = 0;
        $total = 0;
        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
        
        foreach ($tasks as $task) {
            if ($task['created_at'] >= $cutoffDate) {
                $total++;
                if ($task['status'] === 'completed') {
                    $completed++;
                }
            }
        }
        
        return [
            'total_tasks' => $total,
            'completed_tasks' => $completed,
            'productivity_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
        ];
    }
}
?>
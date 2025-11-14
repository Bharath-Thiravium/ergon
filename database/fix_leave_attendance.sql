-- Fix Leave Attendance Records
-- This script corrects attendance records for approved leaves

-- Update existing attendance records for approved leaves
UPDATE attendance a
JOIN leaves l ON a.user_id = l.user_id 
SET a.status = 'present', 
    a.location_name = 'On Approved Leave'
WHERE l.status = 'approved' 
  AND DATE(a.check_in) BETWEEN l.start_date AND l.end_date
  AND (a.location_name != 'On Approved Leave' OR a.location_name IS NULL);

-- Create missing attendance records for approved leaves
INSERT INTO attendance (user_id, check_in, check_out, status, location_name, created_at)
SELECT DISTINCT 
    l.user_id,
    CONCAT(dates.date, ' 09:00:00') as check_in,
    NULL as check_out,
    'present' as status,
    'On Approved Leave' as location_name,
    NOW() as created_at
FROM leaves l
CROSS JOIN (
    SELECT DATE(start_date) + INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as date
    FROM (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as a
    CROSS JOIN (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as b
    CROSS JOIN (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as c
) dates
WHERE l.status = 'approved'
  AND dates.date BETWEEN l.start_date AND l.end_date
  AND dates.date <= CURDATE()
  AND NOT EXISTS (
      SELECT 1 FROM attendance a2 
      WHERE a2.user_id = l.user_id 
      AND DATE(a2.check_in) = dates.date
  );

-- Verify the fix
SELECT 'Leave Attendance Fix Complete' as message;
SELECT COUNT(*) as leave_attendance_records 
FROM attendance 
WHERE location_name = 'On Approved Leave';
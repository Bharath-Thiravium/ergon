# Holiday Display Fix - Employees & Monthly Attendance Register

## Problem
When marking a holiday, the employees list and monthly attendance register weren't showing the holiday as marked. Instead, employees still appeared as "Absent" on holiday dates.

## Root Causes
1. **Monthly Report Not Checking Holidays Table**: The `ReportsController::monthlyAttendance()` method was only checking for leaves and attendance, but NOT checking the `holidays` table when building the report.
2. **Missing Refresh After Holiday Creation**: The admin attendance page didn't automatically refresh to show updated holiday data after returning from the holiday marking page.
3. **Holiday Legend Not Clear**: The legend wasn't distinguishing between Holidays (H) and Sundays (WO).

## Solutions Implemented

### 1. Holiday Table Lookup in Monthly Report
**File**: `app/controllers/ReportsController.php`

Added holiday fetching and lookup map:
```php
// Get holidays for the month
$holidayStmt = $db->prepare("
    SELECT DISTINCT holiday_date
    FROM holidays
    WHERE is_active = 1
      AND holiday_date BETWEEN ? AND ?
");
$holidayStmt->execute([$firstDay->format('Y-m-d'), $lastDay->format('Y-m-d')]);
$holidays = $holidayStmt->fetchAll(PDO::FETCH_ASSOC);

// Build holiday lookup: [date] = true
$holidayMap = [];
foreach ($holidays as $hol) {
    $holidayMap[$hol['holiday_date']] = true;
}
```

Updated day-building logic to check holidays BEFORE checking attendance:
```php
if ($isSun) {
    $dayData[$date] = 'WO'; // Week Off (Sunday)
} elseif ($isHoliday) {
    $dayData[$date] = 'H'; // Holiday
} elseif ($att) {
    // ... present logic
} elseif ($onLeave) {
    // ... leave logic
} else {
    // ... absent logic
}
```

### 2. Updated Legend Display
**File**: `views/reports/monthly_attendance.php`

- Changed legend to show Holiday (H) as separate from Sunday (WO)
- Added distinct styling for holiday badges with pink color (#fce7f3)

### 3. Auto-Refresh After Holiday Marking
**File**: `views/attendance/admin_index.php`

Modified the holiday modal opening to set a session storage flag:
```php
function openHolidayModal() {
    sessionStorage.setItem('holidayMarked', 'true');
    window.location.href = '/ergon/holidays';
}
```

Added auto-refresh listener:
```php
window.addEventListener('pageshow', function(event) {
    if (sessionStorage.getItem('holidayMarked')) {
        sessionStorage.removeItem('holidayMarked');
        setTimeout(function() {
            refreshAttendance();
        }, 500);
    }
});
```

## Display Changes

### Monthly Attendance Report
- **Before**: Holiday dates showed as 'A' (Absent) for all employees
- **After**: Holiday dates show as 'H' (Holiday) in pink (#fce7f3)
- Holidays are checked before attendance, so even if someone has attendance, the holiday marker takes precedence

### Legend
- **Before**: Only showed 'H' for Holiday/Sunday (confusing)
- **After**: Clear distinction:
  - **P** = Present (Green)
  - **A** = Absent (Red)
  - **L** = Leave (Yellow)
  - **H** = Holiday (Pink)
  - **WO** = Sunday (Gray)
  - **S** = Saturday (Yellow)

### Employee List (Admin Dashboard)
- **Before**: List didn't update after returning from holiday marking
- **After**: Auto-refreshes when user navigates back from holiday page

## Holiday Processing Order
The monthly report now processes days in this priority order:
1. Check if it's Sunday → 'WO'
2. Check if it's a marked holiday → 'H'
3. Check if employee has attendance → 'P' with hours
4. Check if employee is on leave → 'L'
5. Otherwise → 'A' (Absent, if date is not in future)

## Database Requirements
- `holidays` table with columns: `id`, `holiday_date`, `is_active`
- `attendance` table for employee attendance records
- `leaves` table for employee leaves

## Verification
To verify the fix is working:

1. **Mark a Holiday**: Navigate to Admin → Mark Holiday, mark a date as holiday for "All Employees"
2. **Check Monthly Report**: Go to Reports → Monthly Attendance Register
   - The holiday date should show 'H' (Holiday) for all employees
   - Not 'A' (Absent)
3. **Check Admin Dashboard**: Return to Admin attendance page
   - List should refresh showing any holiday-marked employees

## Files Modified
1. `app/controllers/ReportsController.php` - Added holiday table lookup
2. `views/reports/monthly_attendance.php` - Updated legend and cell rendering
3. `views/attendance/admin_index.php` - Added auto-refresh on page return

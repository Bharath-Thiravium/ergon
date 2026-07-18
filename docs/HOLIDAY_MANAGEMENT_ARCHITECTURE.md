# 🏗️ ERGON Holiday Management System - Architecture & Workflow

## 📐 System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    ERGON Holiday Management System               │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Presentation Layer (UI)                                │   │
│  │  ┌────────────────────────────────────────────────────┐ │   │
│  │  │  Holiday Management Page                           │ │   │
│  │  │  - Holiday List/Grid View                          │ │   │
│  │  │  - Create/Edit/Delete Modal                        │ │   │
│  │  │  - Filters & Search                                │ │   │
│  │  │  - Statistics Dashboard                            │ │   │
│  │  └────────────────────────────────────────────────────┘ │   │
│  │  ┌────────────────────────────────────────────────────┐ │   │
│  │  │  Attendance Integration                            │ │   │
│  │  │  - Holiday Mark Button                             │ │   │
│  │  │  - Holiday Status Display (H / 🏖️)               │ │   │
│  │  │  - Holiday Indicators                              │ │   │
│  │  └────────────────────────────────────────────────────┘ │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  API Layer (Controllers & Routes)                        │   │
│  │  ┌────────────────────────────────────────────────────┐ │   │
│  │  │  HolidayController                                 │ │   │
│  │  │  - create(): POST /holiday/create                  │ │   │
│  │  │  - update(): POST /holiday/update                  │ │   │
│  │  │  - delete(): POST /holiday/delete                  │ │   │
│  │  │  - get(): GET /holiday/get                         │ │   │
│  │  │  - today(): GET /holiday/today                     │ │   │
│  │  │  - upcoming(): GET /holiday/upcoming               │ │   │
│  │  │  - calendar(): GET /holiday/calendar               │ │   │
│  │  │  - verify(): GET /holiday/verify-attendance        │ │   │
│  │  │  - index(): GET /holidays (management page)        │ │   │
│  │  └────────────────────────────────────────────────────┘ │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Business Logic Layer (Models & Helpers)                 │   │
│  │  ┌────────────────────────────────────────────────────┐ │   │
│  │  │  Holiday Model                                     │ │   │
│  │  │  - create()                   - getAll()           │ │   │
│  │  │  - update()                   - getByDate()        │ │   │
│  │  │  - delete()                   - getById()          │ │   │
│  │  │  - isHoliday()                - isDuplicate()      │ │   │
│  │  │  - getUpcoming()              - getTodayHoliday()  │ │   │
│  │  └────────────────────────────────────────────────────┘ │   │
│  │  ┌────────────────────────────────────────────────────┐ │   │
│  │  │  HolidayHelper (Static Utilities)                  │ │   │
│  │  │  - isHoliday()                - getHolidayInfo()   │ │   │
│  │  │  - getHolidaysInRange()       - getAttendanceStatus() │ │   │
│  │  │  - calculateWorkingDays()     - getMonthlyAttendanceSummary() │   │
│  │  │  - getPresencePercentage()    - syncHolidayAttendance() │   │
│  │  │  - getAbsentCountExcludingHolidays()               │ │   │
│  │  └────────────────────────────────────────────────────┘ │   │
│  │  ┌────────────────────────────────────────────────────┐ │   │
│  │  │  AttendanceHolidayIntegration                      │ │   │
│  │  │  - getAttendanceWithHolidayStatus()                │ │   │
│  │  │  - syncHolidayAttendance()    - shouldSendAttendanceNotification() │   │
│  │  │  - getWorkingDaysCount()      - formatAttendanceRow() │   │
│  │  └────────────────────────────────────────────────────┘ │   │
│  │  ┌────────────────────────────────────────────────────┐ │   │
│  │  │  HolidayAwareNotification                          │ │   │
│  │  │  - shouldSendAttendanceNotification()              │ │   │
│  │  │  - sendHolidayGreeting()      - processDailyHolidayNotifications() │   │
│  │  │  - getClockInReminderText()   - shouldEscalateAbsence() │   │
│  │  │  - getHolidayNotificationContext()                 │ │   │
│  │  └────────────────────────────────────────────────────┘ │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Data Layer (Database)                                   │   │
│  │  ┌────────────────────────────────────────────────────┐ │   │
│  │  │  holidays Table                                    │ │   │
│  │  │  - id, holiday_date, holiday_name                  │ │   │
│  │  │  - holiday_type, description                       │ │   │
│  │  │  - applies_to, department_id, repeat_yearly        │ │   │
│  │  │  - is_active, created_by, timestamps               │ │   │
│  │  └────────────────────────────────────────────────────┘ │   │
│  │  ┌────────────────────────────────────────────────────┐ │   │
│  │  │  attendance Table (Enhanced)                       │ │   │
│  │  │  - is_holiday (BOOLEAN)                            │ │   │
│  │  │  - holiday_id (INT FK)                             │ │   │
│  │  │  - is_counted_absent (BOOLEAN)                     │ │   │
│  │  │  - INDEX: idx_is_holiday, idx_holiday_date         │ │   │
│  │  └────────────────────────────────────────────────────┘ │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

## 🔄 Data Flow Diagrams

### 1. Holiday Creation Flow

```
User Interface
      │
      ├─→ Click "Mark Holiday" Button
      │
      ├─→ Holiday Management Page opens
      │   (or Modal)
      │
      ├─→ Fill Holiday Form:
      │   ├─ Holiday Date
      │   ├─ Holiday Name
      │   ├─ Holiday Type
      │   ├─ Description
      │   ├─ Apply To (All/Department/Specific)
      │   └─ Repeat Yearly (optional)
      │
      ├─→ Submit Form (POST /holiday/create)
      │
      ├─→ HolidayController::create()
      │   ├─ Validate input data
      │   ├─ Check for duplicates
      │   └─ Call Holiday::create()
      │
      ├─→ Holiday Model
      │   ├─ Insert into holidays table
      │   ├─ Get holiday_id
      │   └─ Call applyHolidayToAttendance()
      │
      ├─→ Attendance Sync
      │   ├─ Query applicable users (All/Department/Specific)
      │   ├─ For each user:
      │   │  ├─ Check if attendance exists for date
      │   │  ├─ If exists: UPDATE with is_holiday=1, holiday_id, status='holiday'
      │   │  └─ If not exists: INSERT new holiday attendance record
      │   └─ Mark is_counted_absent=0 (exclude from absence)
      │
      ├─→ Notification Service
      │   ├─ Send holiday greeting to applicable users
      │   └─ Update dashboard statistics
      │
      └─→ Return Success Response (JSON)
         └─ Display confirmation to user
```

### 2. Attendance Status Determination Flow

```
Attendance Page Load (Date Selected)
      │
      ├─→ For Each Employee:
      │
      ├─→ Query Holiday Status
      │   ├─ HolidayHelper::isHoliday(date)
      │   ├─ If yes → Return status: "Holiday" (🏖️)
      │   └─ If no → Continue to next check
      │
      ├─→ Query Attendance Record
      │   ├─ Look for attendance on date
      │   ├─ If is_holiday=1 → Return status: "Holiday" (🏖️)
      │   ├─ If check_in exists → Return status: "Present" (✅)
      │   └─ If no record → Continue to next check
      │
      ├─→ Query Leave Status
      │   ├─ Check if approved leave exists
      │   ├─ If yes → Return status: "On Leave" (🏖️)
      │   └─ If no → Continue to next check
      │
      ├─→ Default Status
      │   └─ Return status: "Absent" (❌)
      │
      └─→ Display formatted badge with appropriate color/icon
```

### 3. Monthly Attendance Calculation Flow

```
Generate Monthly Report (Month, Year Selected)
      │
      ├─→ Define Date Range
      │   ├─ Start: YYYY-MM-01 00:00:00
      │   └─ End: YYYY-MM-31 23:59:59
      │
      ├─→ Get All Holidays in Range
      │   └─ HolidayHelper::getHolidaysInRange()
      │
      ├─→ Calculate Working Days
      │   ├─ Iterate each day in range
      │   ├─ Skip weekends (Saturday, Sunday)
      │   ├─ Skip holidays from holidays table
      │   └─ Count remaining days
      │
      ├─→ For Each Employee:
      │
      ├─→ Query Attendance Records
      │   ├─ WHERE user_id=X AND check_in BETWEEN start AND end
      │   └─ WHERE is_counted_absent=1
      │
      ├─→ Count Present Days
      │   ├─ Count records with status='present' OR is_holiday=1
      │   ├─ EXCLUDING records marked as holidays
      │   └─ Store in present_days
      │
      ├─→ Count Absent Days
      │   ├─ Count records with status='absent'
      │   ├─ WHERE is_holiday=0
      │   └─ Store in absent_days
      │
      ├─→ Calculate Presence Percentage
      │   ├─ Formula: (present_days / working_days) * 100
      │   ├─ NOTE: Holiday days excluded from denominator
      │   └─ Store in percentage
      │
      ├─→ Calculate Total Hours
      │   ├─ Sum TIMESTAMPDIFF(MINUTE, check_in, check_out)
      │   ├─ Only for records with check_out
      │   └─ Convert to hours/minutes
      │
      ├─→ Generate Summary Report
      │   ├─ Present Days: X
      │   ├─ Absent Days: X
      │   ├─ Holiday Days: X
      │   ├─ Working Days: X
      │   ├─ Presence %: X%
      │   ├─ Total Hours: X:XX
      │   └─ Remarks: Based on configuration
      │
      └─→ Display/Export Report
         └─ Show in dashboard or export as PDF/CSV
```

## 🔗 System Integrations

### 1. Attendance Module Integration

```php
// In AttendanceController::handleAdminView()

// Before: Display attendance without holiday awareness
// After: Enhanced with holiday checking

$stmt = $db->prepare("
    SELECT 
        u.*, 
        a.*, 
        CASE 
            WHEN h.id IS NOT NULL THEN 'Holiday'
            WHEN a.status = 'present' THEN 'Present'
            ELSE 'Absent'
        END as status
    FROM users u
    LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
    LEFT JOIN holidays h ON DATE(a.check_in) = h.holiday_date 
                        AND h.is_active = 1
    ...
");
```

### 2. Notification Integration

```php
// In notification sending logic

// Before: Send all notifications
// After: Check holiday status first

if (!HolidayAwareNotification::shouldSendAttendanceNotification($userId, $date)) {
    return false; // Don't send on holidays
}

// Continue with notification sending
```

### 3. Reports Module Integration

```php
// In ReportsController::monthlyAttendance()

// Before: All absences counted
// After: Holidays excluded from absence calculation

$summary = HolidayHelper::getMonthlyAttendanceSummary($userId, $month, $year);
// Returns:
// - working_days (excludes holidays)
// - absent_days (excludes holidays)
// - presence % (correctly calculated)
```

### 4. Dashboard Integration

```php
// In DashboardController::admin()

// Add holiday widget
$todayHoliday = HolidayHelper::getTodayHoliday();
if ($todayHoliday) {
    // Display holiday banner
    // Disable clock-in prompts
}

// Show upcoming holidays
$upcomingHolidays = HolidayHelper::getUpcoming(30);
```

## 🎯 Feature Interactions

### Holiday Types & Behavior

| Type | Color | Applies To | Use Case |
|------|-------|-----------|----------|
| National | 🔵 Blue | All/Department | Government holidays |
| Festival | 🟠 Orange | All/Department | Religious festivals |
| Company | 🟢 Green | All/Department | Company-specific holidays |
| Emergency | 🔴 Red | All/Department | Unexpected closures |
| Other | 🟣 Purple | All/Department | Custom holidays |

### Holiday Scope Behavior

| Scope | Applies To | Example |
|-------|-----------|---------|
| All | All active employees | National holidays |
| Department | Specific department | Regional holidays |
| Specific | Individual employees | Ready for future use |

### Attendance Status Priority

1. **Holiday** (if date in holidays table & is_active=1)
2. **Present** (if check_in exists)
3. **On Leave** (if approved leave exists)
4. **Absent** (default, no record)

## 📊 Data Consistency Rules

### Insert Rules
- Holiday date must be unique (UNIQUE constraint)
- Holiday date cannot be in the past (enforced in controller)
- Created_by must be valid user ID
- Department ID must be valid (if scope is Department)

### Update Rules
- Cannot change holiday_date to duplicate date
- Can update all other fields
- Soft delete by setting is_active=0

### Attendance Sync Rules
- When holiday created → Auto-create/update attendance records
- When holiday deleted → Remove is_holiday marking (set is_counted_absent back to true)
- When holiday updated → Update related attendance records

## 🔐 Security & Validation

### Input Validation
```php
// Holiday date validation
$date = DateTime::createFromFormat('Y-m-d', $input);
if (!$date || $date->format('Y-m-d') !== $input) {
    throw new Exception('Invalid date format');
}

// Duplicate check
if (Holiday::isDuplicate($date)) {
    throw new Exception('Holiday already exists');
}

// Role validation
$this->requireRole(['admin', 'owner']);
```

### Data Integrity
- Foreign key constraints ensure referential integrity
- Cascade delete for holidays removes related records
- Audit trail via created_by and timestamps
- Soft delete preserves history

## 🔄 Workflow Scenarios

### Scenario 1: Mark National Holiday

```
Flow:
1. Admin opens Holiday Management
2. Clicks "Mark Holiday"
3. Enters: Date=2025-03-15, Name="Holi", Type="Festival", Apply To="All"
4. Clicks Save
5. System:
   - Creates holiday record
   - Gets all active users
   - Creates attendance records for all users with is_holiday=1
   - Sends holiday greeting notifications
   - Updates dashboard
6. Next attendance page load:
   - All employees show "Holiday" status on 2025-03-15
   - Not counted as absent
   - Excluded from working days calculations
```

### Scenario 2: Department-Specific Holiday

```
Flow:
1. Admin marks holiday for Sales department only
2. System:
   - Creates holiday with department_id
   - Queries users with department_id=Sales
   - Creates attendance only for those users
   - Sends notifications only to Sales team
3. Result:
   - Sales employees: Holiday status on that date
   - Other employees: No change (Absent if no clock-in)
```

### Scenario 3: Holiday Deletion

```
Flow:
1. Admin deletes a holiday
2. System:
   - Sets is_active=0 (soft delete)
   - Updates related attendance records:
     - Sets is_holiday=0
     - Sets is_counted_absent=1
     - Reverts status to 'absent' if no check-in
3. Result:
   - Holiday removed from calendar
   - Employees may now show as absent if no attendance
   - History preserved in database
```

## 🚀 Performance Considerations

### Query Optimization
- Indexes on: holiday_date, is_active, is_holiday
- Batch operations for attendance sync
- Caching for frequently accessed data

### Scalability
- Handles thousands of holidays
- Efficient employee queries with department filtering
- Optimized monthly calculations

### Best Practices
- Use HolidayHelper static methods (no object overhead)
- Cache holiday lists for a day
- Batch process notifications

## 🔮 Future Expansion Points

1. **Half-Day Holidays**
   - Add half_day_type column to holidays
   - Modify attendance calculation logic
   - Update UI to show different badge

2. **Regional Holidays**
   - Add region/state column to holidays
   - Add region to user profile
   - Query based on region

3. **Holiday Exceptions**
   - Create holiday_exceptions table
   - Allow removing specific users from holidays
   - Modify attendance sync logic

4. **Bulk Operations**
   - Import holidays from CSV
   - Copy holidays from previous year
   - Apply recurring holidays automatically

5. **Advanced Notifications**
   - Email digest of upcoming holidays
   - Customizable notification rules
   - Holiday calendar export (iCal)

---

**Architecture Version:** 1.0.0  
**Last Updated:** 2025  
**Status:** Production Ready ✅

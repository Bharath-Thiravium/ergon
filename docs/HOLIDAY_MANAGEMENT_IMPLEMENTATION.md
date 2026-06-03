# ERGON Holiday Management System - Complete Implementation Guide

## 📋 Overview

The Holiday Management System is a centralized feature that integrates with ERGON's attendance workflow to automatically mark holidays for all employees and exclude them from absence calculations.

## 🗂️ File Structure

```
ergon/
├── app/
│   ├── models/
│   │   └── Holiday.php                 # Holiday model with DB operations
│   ├── controllers/
│   │   └── HolidayController.php       # Holiday management endpoints
│   ├── helpers/
│   │   ├── HolidayHelper.php           # Holiday utility functions
│   │   └── AttendanceHolidayIntegration.php  # Attendance integration
│   └── config/
│       └── holiday_routes.php           # Holiday route configuration
├── views/
│   └── admin/
│       └── holidays_management.php      # Holiday management UI
├── migrations/
│   └── setup_holiday_system.php        # Database migration
└── sql/
    └── holidays_schema.sql              # Database schema
```

## 🚀 Installation Steps

### Step 1: Database Setup

Run the migration to create the holidays table and add columns to attendance:

```bash
php /ergon/migrations/setup_holiday_system.php
```

Or execute the SQL directly:
```bash
mysql -u username -p database_name < /ergon/sql/holidays_schema.sql
```

### Step 2: Copy Files

The following files have already been created:

1. **Models:**
   - `/app/models/Holiday.php` - Core holiday model

2. **Controllers:**
   - `/app/controllers/HolidayController.php` - API endpoints

3. **Helpers:**
   - `/app/helpers/HolidayHelper.php` - Utility functions
   - `/app/helpers/AttendanceHolidayIntegration.php` - Attendance integration

4. **Views:**
   - `/views/admin/holidays_management.php` - Admin UI

5. **Routes:**
   - `/app/config/holiday_routes.php` - Route definitions

### Step 3: Update Main Router

Add holiday routes to your main router (`/app/core/Router.php`):

```php
// Include holiday routes
$holidayRoutes = require __DIR__ . '/../config/holiday_routes.php';
$this->routes = array_merge($this->routes, $holidayRoutes);
```

### Step 4: Update Attendance Controller Integration

Modify `/app/controllers/AttendanceController.php` to check for holidays:

```php
// At the beginning of handleAdminView() method
require_once __DIR__ . '/../helpers/HolidayHelper.php';

// In the employee attendance query, add holiday checking
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.name,
        ...
        CASE 
            WHEN h.id IS NOT NULL THEN 'Holiday'
            WHEN a.location_name = 'On Approved Leave' THEN 'On Leave'
            WHEN a.check_in IS NOT NULL THEN 'Present'
            ELSE 'Absent'
        END as status
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
    LEFT JOIN holidays h ON DATE(a.check_in) = h.holiday_date AND h.is_active = 1
    ...
");
```

## 🔧 Core Features

### 1. Holiday Creation

**Endpoint:** `POST /ergon/holiday/create`

**Parameters:**
- `holiday_date` (required) - Date in YYYY-MM-DD format
- `holiday_name` (required) - Name of the holiday
- `holiday_type` - National, Festival, Company, Emergency, Other
- `description` - Optional description
- `applies_to` - All, Department, or Specific
- `department_id` - Required if applies_to is Department
- `repeat_yearly` - Boolean for yearly recurrence

**Response:**
```json
{
    "success": true,
    "message": "Holiday created successfully",
    "holiday_id": 1
}
```

### 2. Holiday Marking in Attendance

When a holiday is created or when attendance is checked for a holiday date:

- **Status:** Automatically set to 'holiday' (H)
- **is_counted_absent:** Set to false (not counted as absent)
- **check_in/check_out:** Auto-populated with holiday marker
- **location_name:** Set to "Holiday"

### 3. Monthly Register Integration

In monthly attendance calculations:

```php
// Example: Calculate presence percentage excluding holidays
$presencePercentage = HolidayHelper::getPresencePercentage(
    $userId, 
    $startDate, 
    $endDate
);

// Example: Get monthly summary with holiday exclusion
$summary = HolidayHelper::getMonthlyAttendanceSummary($userId, $month, $year);
// Returns:
// - present_days
// - absent_days
// - holiday_days
// - working_days (excluding weekends and holidays)
// - total_hours
```

### 4. Dashboard Integration

Add to attendance dashboard:

```php
// Check today's holiday status
$todayHoliday = HolidayHelper::getTodayHoliday();

if ($todayHoliday) {
    // Display holiday notification
    // Disable clock-in/out buttons
    // Disable escalation notifications
}

// Get upcoming holidays
$upcomingHolidays = HolidayHelper::getUpcoming(30); // Next 30 days
```

## 📊 Database Schema

### holidays table
```sql
CREATE TABLE holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  holiday_date DATE NOT NULL UNIQUE,
  holiday_name VARCHAR(255) NOT NULL,
  holiday_type ENUM('National', 'Festival', 'Company', 'Emergency', 'Other'),
  description TEXT,
  applies_to ENUM('All', 'Department', 'Specific'),
  department_id INT NULL,
  repeat_yearly BOOLEAN,
  is_active BOOLEAN,
  created_by INT NOT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  INDEX idx_holiday_date (holiday_date),
  INDEX idx_holiday_type (holiday_type),
  FOREIGN KEY (created_by) REFERENCES users(id),
  FOREIGN KEY (department_id) REFERENCES departments(id)
);
```

### attendance table additions
```sql
ALTER TABLE attendance ADD COLUMN is_holiday BOOLEAN DEFAULT FALSE;
ALTER TABLE attendance ADD COLUMN holiday_id INT NULL;
ALTER TABLE attendance ADD COLUMN is_counted_absent BOOLEAN DEFAULT TRUE;
ALTER TABLE attendance ADD FOREIGN KEY (holiday_id) REFERENCES holidays(id);
```

## 🎨 UI Components

### Holiday Management Page
- **Location:** `/views/admin/holidays_management.php`
- **Features:**
  - Grid view of all holidays
  - Create/Edit/Delete modals
  - Filter by date range and type
  - Statistics display

### "Mark Holiday" Button
Add to attendance admin page:

```html
<button class="btn btn--primary" onclick="openHolidayModal()">
    <span>🗓️</span> Mark Holiday
</button>
```

### Holiday Modal Form
- Holiday Date picker
- Holiday Name input
- Holiday Type selector
- Description textarea
- Apply To (All/Department/Specific)
- Yearly Repeat checkbox
- Save/Cancel buttons

## 🔄 Workflow Integration

### Attendance Clock-In/Out
```php
// Before allowing clock-in/out
if (HolidayHelper::isHoliday(date('Y-m-d'))) {
    return ['success' => false, 'error' => 'Cannot clock in/out on a holiday'];
}
```

### Notification System
```php
// Check before sending attendance reminders
if (!AttendanceHolidayIntegration::shouldSendAttendanceNotification($userId, $date)) {
    return; // Skip notification on holidays
}
```

### Escalation Rules
```php
// No absent escalation on holidays
if (!HolidayHelper::isHoliday($date)) {
    // Check for absent escalation
}
```

## 📈 Reporting Integration

### Monthly Attendance Register
```php
require_once 'helpers/HolidayHelper.php';

$summary = HolidayHelper::getMonthlyAttendanceSummary($userId, 3, 2025);
// Working Days: excludes weekends and holidays
// Present Days: attendance or holiday
// Absence Percentage: excludes holidays from calculation
```

### Payroll Attendance Calculation
```php
// Get working days for payroll
$workingDays = HolidayHelper::calculateWorkingDays($startDate, $endDate);

// Calculate salary based on working days excluding holidays
$dailyRate = $salary / $workingDays;
```

## 🔐 Access Control

Only admin/owner can:
- Create holidays
- Edit holidays
- Delete holidays
- View holiday management page

All users can:
- View holidays in their attendance
- See holiday markings in calendar

## 🧪 Testing Checklist

- [ ] Create a holiday and verify automatic attendance marking
- [ ] Check that employees' attendance shows "H" on holiday date
- [ ] Verify holiday doesn't count as absent
- [ ] Check monthly attendance register excludes holidays from absent count
- [ ] Test presence percentage calculation
- [ ] Verify working days calculation
- [ ] Test department-specific holidays
- [ ] Confirm notifications disabled on holidays
- [ ] Test deletion and re-marking of holidays
- [ ] Verify dashboard shows holiday status
- [ ] Test upcoming holidays display
- [ ] Check recurring yearly holidays

## 🎯 API Endpoints

### Create Holiday
```
POST /ergon/holiday/create
Content-Type: application/x-www-form-urlencoded

holiday_date=2025-03-15
&holiday_name=Holi
&holiday_type=Festival
&description=Spring Festival
&applies_to=All
```

### Update Holiday
```
POST /ergon/holiday/update
id=1&holiday_name=Updated Name&...
```

### Delete Holiday
```
POST /ergon/holiday/delete
id=1
```

### Get Holiday Details
```
GET /ergon/holiday/get?id=1
```

### Get Upcoming Holidays
```
GET /ergon/holiday/upcoming?days=30
```

### Check Today's Holiday
```
GET /ergon/holiday/today
```

### Get Calendar Format
```
GET /ergon/holiday/calendar?start=2025-01-01&end=2025-12-31
```

### Verify Attendance Sync
```
GET /ergon/holiday/verify-attendance?start_date=2025-01-01&end_date=2025-01-31
```

## 📝 Configuration

### Holiday Types
- **National:** Government-declared national holidays
- **Festival:** Religious/cultural festivals
- **Company:** Company-specific holidays
- **Emergency:** Unexpected closures
- **Other:** Any other type

### Color Coding
- National: #0066cc (Blue)
- Festival: #ff6600 (Orange)
- Company: #00cc66 (Green)
- Emergency: #cc0000 (Red)
- Other: #9933cc (Purple)

## 🚨 Important Notes

1. **Automatic Sync:** When a holiday is created, it automatically creates/updates attendance records for all applicable employees

2. **Duplicate Prevention:** System prevents creating two holidays on the same date

3. **Soft Delete:** Holidays are marked inactive (not deleted) to maintain history

4. **Timezone Support:** Holidays use server date, integrated with existing timezone helpers

5. **Scalability:** System supports:
   - All employees
   - Department-specific
   - Individual employee (ready for future implementation)

## 🔮 Future Enhancements

1. **Half-Day Holidays:** Mark partial holidays
2. **Regional Holidays:** Different holidays for different regions
3. **Holiday Exceptions:** Add/remove specific employees from holidays
4. **Bulk Upload:** Import holidays from CSV
5. **Holiday Calendar Sync:** iCal/ICS export
6. **Holiday Requests:** Employee request for additional holidays
7. **Holiday Rules:** Conditional holiday marking
8. **Notification Center:** Holiday announcements

## 📞 Support

For issues or questions about the Holiday Management System, refer to the code comments in:
- `/app/models/Holiday.php` - Detailed model documentation
- `/app/helpers/HolidayHelper.php` - Helper function documentation
- `/app/controllers/HolidayController.php` - API endpoint documentation

---

**Last Updated:** 2025
**Version:** 1.0.0
**Status:** Production Ready

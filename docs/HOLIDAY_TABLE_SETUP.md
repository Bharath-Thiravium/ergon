# Holiday Table Setup Guide

## Problem
The `holidays` table doesn't exist in the database, causing the 400 error when trying to create holidays.

```
Error: Table 'ergon_db.holidays' doesn't exist
```

## Solution

### Option 1: Run Migration Script (Recommended)

#### Via Web Browser:
1. Open: `http://localhost:8000/ergon/run_holiday_migration.php`
2. You should see:
   ```
   ✓ Holidays table created successfully!
   ✓ Table verified - ready to use.
   ```

#### Via Command Line:
```bash
cd /path/to/ergon
php run_holiday_migration.php
```

---

### Option 2: Run SQL Directly (MySQL/PhpMyAdmin)

#### Via MySQL Command Line:
```bash
mysql -u root -p ergon_db < HOLIDAY_TABLE_SETUP.sql
```

#### Via PhpMyAdmin:
1. Open phpMyAdmin
2. Select database: `ergon_db`
3. Click "SQL" tab
4. Paste the SQL from `HOLIDAY_TABLE_SETUP.sql`
5. Click "Go"

#### SQL Commands:
```sql
CREATE TABLE IF NOT EXISTS holidays (
    id INT PRIMARY KEY AUTO_INCREMENT,
    holiday_date DATE NOT NULL UNIQUE,
    holiday_name VARCHAR(255) NOT NULL,
    holiday_type VARCHAR(50) DEFAULT 'Company',
    description LONGTEXT,
    applies_to VARCHAR(50) DEFAULT 'All',
    department_id INT,
    repeat_yearly BOOLEAN DEFAULT 0,
    created_by INT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_holiday_date (holiday_date),
    KEY idx_applies_to (applies_to),
    KEY idx_department_id (department_id),
    KEY idx_created_by (created_by),
    KEY idx_is_active (is_active),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### Option 3: Use Database Migration in Migration System

If your system has a migrations folder:

```bash
cp migrations/create_holidays_table.php your-migration-folder/
php your-migration-runner.php
```

---

## Verify Setup

### Check 1: Table Exists
```bash
# MySQL
mysql -u root -p ergon_db
mysql> SHOW TABLES LIKE 'holidays';
```

Should show:
```
+------------------+
| Tables_in_ergon_db |
+------------------+
| holidays         |
+------------------+
```

### Check 2: Table Structure
```sql
DESCRIBE holidays;
```

Should show columns:
- id (INT, PRIMARY KEY)
- holiday_date (DATE, UNIQUE)
- holiday_name (VARCHAR)
- holiday_type (VARCHAR)
- description (LONGTEXT)
- applies_to (VARCHAR)
- department_id (INT, FK)
- repeat_yearly (BOOLEAN)
- created_by (INT, FK)
- is_active (BOOLEAN)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

### Check 3: Test Data
```sql
-- Insert a test holiday
INSERT INTO holidays (holiday_date, holiday_name, holiday_type, description, applies_to, created_by)
VALUES ('2026-06-15', 'Test Holiday', 'National', 'Test holiday for verification', 'All', 1);

-- View the holiday
SELECT * FROM holidays;
```

Should show your test holiday in the results.

---

## After Setup

### 1. Clear Browser Cache
```
Ctrl+Shift+Delete
Select: All Time
Clear All
```

### 2. Reload Application
```
http://localhost:8000/ergon/attendance
```

### 3. Test the Feature
1. Click "📅 Mark Holiday" button
2. Fill form:
   - Date: Select any future date
   - Name: "Test Holiday"
   - Type: "National Holiday"
3. Click "Save Holiday"

### Expected Result
✅ Modal closes
✅ Success message displays
✅ Page reloads
✅ Holiday saved to database
✅ No errors in console

---

## Troubleshooting

### Issue: "Still getting 400 error"
**Solution:**
1. Verify table was created: `SHOW TABLES LIKE 'holidays';`
2. Verify foreign keys exist: `SELECT * FROM users LIMIT 1;`, `SELECT * FROM departments LIMIT 1;`
3. Clear browser cache (Ctrl+Shift+Del)
4. Reload page (F5)

### Issue: "Access denied" when creating table
**Solution:**
- Use database credentials with CREATE TABLE privileges
- Typically: `mysql -u root -p` (with admin password)

### Issue: "Foreign key constraint fails"
**Solution:**
- Make sure `users` and `departments` tables exist first
- Check user_id exists in users table

### Issue: "Duplicate key on holiday_date"
**Solution:**
- Table allows only one holiday per date
- Don't insert duplicate dates
- To update: use UPDATE instead of INSERT

---

## Files Provided

1. **run_holiday_migration.php** - Migration runner script
2. **migrations/create_holidays_table.php** - Actual migration code
3. **HOLIDAY_TABLE_SETUP.sql** - Direct SQL commands

---

## Table Schema Details

### Columns

| Column | Type | Notes |
|--------|------|-------|
| id | INT | Primary key, auto-increment |
| holiday_date | DATE | UNIQUE - one holiday per date |
| holiday_name | VARCHAR(255) | Name of the holiday |
| holiday_type | VARCHAR(50) | National/Festival/Company/Emergency/Other |
| description | LONGTEXT | Detailed description |
| applies_to | VARCHAR(50) | All/Department/Specific |
| department_id | INT | Foreign key to departments |
| repeat_yearly | BOOLEAN | If true, repeats every year |
| created_by | INT | Foreign key to users |
| is_active | BOOLEAN | Soft delete flag |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

### Indexes

- `idx_holiday_date` - For fast date lookups
- `idx_applies_to` - For filtering by scope
- `idx_department_id` - For department filtering
- `idx_created_by` - For user filtering
- `idx_is_active` - For active/inactive filtering

---

## Integration Points

### Controllers Using Holidays Table:
- `HolidayController.php` - Main CRUD operations
- `AttendanceController.php` - Marks attendance as holiday
- `SimpleAttendanceController.php` - Checks if day is holiday

### Models Using Holidays Table:
- `Holiday.php` - Direct database access
- `Attendance.php` - References holiday_id

### API Endpoints:
- `POST /ergon/holiday/create` - Create new holiday
- `POST /ergon/holiday/update` - Update holiday
- `POST /ergon/holiday/delete` - Delete holiday (soft delete)
- `GET /ergon/holiday/get` - Get holiday details
- `GET /ergon/holiday/today` - Check if today is holiday
- `GET /ergon/holiday/upcoming` - Get upcoming holidays
- `GET /ergon/holiday/calendar` - Get holidays for date range

---

## Security Notes

✅ Foreign keys enforce referential integrity
✅ Soft deletes preserve data history
✅ Role-based access control (admin/owner only)
✅ Input validation in controller
✅ SQL prepared statements prevent injection

---

## Support

If setup fails:
1. Check error message carefully
2. Verify database credentials
3. Ensure database is running
4. Check file permissions
5. Review server error logs: `/storage/logs/error.log`

---

**Status:** Ready to Setup
**Setup Time:** ~2 minutes
**Difficulty:** Easy


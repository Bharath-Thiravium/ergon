# 🚀 ERGON Holiday Management System - Quick Start Guide

## ⚡ 5-Minute Setup

### Step 1: Run Database Migration (2 minutes)

Navigate to your ERGON root directory and run:

```bash
php /migrations/setup_holiday_system.php
```

**Expected Output:**
```
Starting Holiday Management System migration...
✓ Holidays table created
✓ Holiday columns added to attendance table
✓ Indexes created
✓ Foreign key constraints added

✅ Holiday Management System successfully initialized!
```

### Step 2: Update Router Configuration (1 minute)

Edit `/app/core/Router.php` and add holiday routes after line where attendance routes are loaded:

```php
// Around line 50-60, add this:
$holidayRoutes = require __DIR__ . '/../config/holiday_routes.php';
$this->routes = array_merge($this->routes, $holidayRoutes);
```

### Step 3: Add Holiday Button to Attendance Page (1 minute)

Edit `/views/attendance/admin_index.php` and add this button in the `page-actions` section (around line 20):

```html
<button class="btn btn--primary" onclick="window.location.href='/ergon/holidays'">
    <span>🗓️</span> Mark Holiday
</button>
```

### Step 4: Update Attendance Status Display (1 minute)

Edit `/views/attendance/admin_index.php` in the employee attendance table, update the status badge section (around line 120):

```php
<?php if ($employee['status'] === 'Holiday'): ?>
    <span class="badge badge--info">🏖️ Holiday</span>
<?php elseif ($employee['status'] === 'On Leave'): ?>
    <span class="badge badge--warning">🏖️ On Leave</span>
<?php else: ?>
    <span class="badge badge--<?= $employee['status'] === 'Present' ? 'success' : 'danger' ?>">
        <?= $employee['status'] === 'Present' ? '✅ Present' : '❌ Absent' ?>
    </span>
<?php endif; ?>
```

## ✅ Verification

After setup, verify everything works:

1. **Access Holiday Management Page:**
   - Go to `/ergon/holidays`
   - You should see the Holiday Management dashboard

2. **Create a Test Holiday:**
   - Click "Mark Holiday" button
   - Fill in: Name = "Test Holiday", Date = Tomorrow, Type = "Company", Apply To = "All"
   - Click "Save Holiday"

3. **Check Attendance:**
   - Go to `/ergon/attendance`
   - Look at tomorrow's attendance
   - All employees should show "Holiday" (🏖️) status
   - No one should be marked absent

4. **Verify Dashboard:**
   - Check the statistics showing:
     - Total Holidays count
     - Upcoming holidays
     - Today's holiday status

## 📝 Common Actions

### Mark a Holiday

1. Go to **Holiday Management** page
2. Click **"Mark Holiday"** button
3. Fill the form:
   - **Date**: Select the holiday date
   - **Name**: Enter holiday name (e.g., "Diwali", "New Year")
   - **Type**: Select from National, Festival, Company, Emergency
   - **Apply To**: Select All Employees or specific Department
   - **Yearly Repeat**: Check if it repeats every year
4. Click **Save Holiday**

### Edit a Holiday

1. On Holiday Management page, find the holiday card
2. Click **Edit** button
3. Modify details
4. Click **Save Holiday**

### Delete a Holiday

1. On Holiday Management page, find the holiday card
2. Click **Delete** button
3. Confirm deletion
4. Holiday will be removed and attendance records updated

### View Holiday in Attendance

1. Go to Attendance Management page
2. Select the holiday date in date picker
3. All employees will show **🏖️ Holiday** status

## 🎯 Features Activated

After setup, these features are automatically active:

✅ **Global Holiday Management**
- Create holidays for all employees
- Department-specific holidays
- Yearly recurring holidays

✅ **Automatic Attendance Marking**
- Holidays automatically marked as "H" (Holiday)
- Status shows 🏖️ Holiday
- Not counted as absent
- Excluded from absence calculations

✅ **Dashboard Integration**
- Holiday statistics displayed
- Upcoming holidays shown
- Today's holiday status visible
- Holiday information in tooltips

✅ **Attendance Calculations**
- Monthly presence percentage excludes holidays
- Working days calculated without holidays
- Absence count excludes holidays
- Payroll calculations accurate

✅ **Notifications Disabled**
- No clock-in reminders on holidays
- No absence escalation on holidays
- Holiday greeting sent when holiday created
- Daily holiday notification for users

✅ **Reporting Integration**
- Holiday details in reports
- Excluded from attendance summaries
- Accurate monthly registers
- Export includes holiday markers

## 📊 Dashboard Overview

### Statistics Cards
1. **Total Holidays** - All active holidays in system
2. **Upcoming (30 days)** - Holidays coming in next 30 days
3. **Today's Status** - Shows if today is a holiday

### Holiday List
Shows all holidays with:
- Holiday name and date
- Type (National, Festival, Company, etc.)
- Scope (All Employees, Department, Specific)
- Color-coded badges
- Action buttons (Edit, Delete)

### Filters
- Date range picker
- Holiday type filter
- Reset button

## 🔒 Access Control

| Action | Admin | Owner | User |
|--------|-------|-------|------|
| View Holiday Management | ✅ | ✅ | ❌ |
| Create Holiday | ✅ | ✅ | ❌ |
| Edit Holiday | ✅ | ✅ | ❌ |
| Delete Holiday | ✅ | ✅ | ❌ |
| View in Attendance | ✅ | ✅ | ✅ |
| See Holiday Status | ✅ | ✅ | ✅ |

## 🧪 Test Scenarios

### Scenario 1: Single Day Holiday
- Create holiday for tomorrow
- Check attendance page
- Verify all employees show "Holiday"
- Verify not counted as absent

### Scenario 2: Department-Specific Holiday
- Create holiday for specific department
- Check attendance
- Verify only that department shows holiday
- Other departments unaffected

### Scenario 3: Monthly Report
- Create 2 holidays in a month
- Generate attendance report
- Verify working days exclude holidays
- Verify attendance percentage correct

### Scenario 4: Yearly Holiday
- Create holiday with "Repeat Yearly" option
- Check calendar shows it for multiple years
- Verify auto-sync works next year

## 🐛 Troubleshooting

### Issue: Holiday not appearing in attendance
**Solution:** 
1. Check if holiday is marked as "active" in database
2. Run sync: `HolidayHelper::syncHolidayAttendance()`
3. Verify holiday date format (YYYY-MM-DD)

### Issue: Attendance not updated on holiday
**Solution:**
1. Check employee status is "active"
2. Verify holiday "applies_to" includes user's department
3. Check for duplicate attendance records

### Issue: Notifications still sent on holidays
**Solution:**
1. Verify `HolidayAwareNotification` is imported in notification system
2. Check notification service calls `shouldSendAttendanceNotification()`

### Issue: Cannot create holiday on past date
**Solution:**
1. This is a feature - holidays must be future dates
2. To mark past holidays, modify the holiday date validation

## 📞 Support Resources

- **Documentation:** `/docs/HOLIDAY_MANAGEMENT_IMPLEMENTATION.md`
- **Database Schema:** `/sql/holidays_schema.sql`
- **API Reference:** Available in HolidayController documentation
- **Code Examples:** See `/views/admin/holidays_management.php`

## 🎓 Next Steps

After verification:

1. **Train Admins**
   - Show how to mark holidays
   - Explain automatic attendance marking
   - Review monthly reports with holidays

2. **Communicate to Users**
   - Announce holiday marking feature
   - Explain automatic holiday marking
   - Show holiday status in attendance

3. **Schedule Recurring Holidays**
   - Mark all yearly holidays (Diwali, New Year, etc.)
   - Check "Repeat Yearly" option
   - System will auto-sync next year

4. **Monitor Integration**
   - Check monthly reports are accurate
   - Verify absence calculations correct
   - Ensure notifications working

## 🚀 Production Deployment

Before going live:

- [ ] Run database migration
- [ ] Update router configuration
- [ ] Add button to attendance page
- [ ] Test all scenarios
- [ ] Verify reporting accuracy
- [ ] Train admin users
- [ ] Communicate to employees
- [ ] Set up recurring holidays
- [ ] Enable in production
- [ ] Monitor for first week

## 📈 Expected Outcomes

✅ **Improved Attendance Management**
- Clear holiday marking system
- No manual absence marking on holidays
- Automated attendance records

✅ **Accurate Reports**
- Correct absence calculations
- Proper working day counts
- Accurate payroll calculations

✅ **Better User Experience**
- No clock-in pressure on holidays
- Clear holiday information
- Automatic attendance updates

✅ **Administrative Efficiency**
- Quick holiday creation
- Centralized management
- Automatic employee notification

---

**Questions?** Refer to the full documentation or contact the development team.

**Version:** 1.0.0  
**Last Updated:** 2025  
**Status:** Production Ready ✅

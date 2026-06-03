# 🎨 ERGON Holiday Management System - Visual Reference Guide

## 📱 UI Layout Overview

### Page 1: Holiday Management Dashboard

```
╔════════════════════════════════════════════════════════════════════╗
║  🗓️ Holiday Management                                            ║
║  Mark company holidays, festivals, and special dates for all      ║
║                                            [🗓️ Mark Holiday]     ║
╠════════════════════════════════════════════════════════════════════╣
║                                                                     ║
║  ╔══════════════════╗  ╔══════════════════╗  ╔══════════════════╗║
║  │  📊 Statistics   │  │  📅 Upcoming     │  │  📌 Today        ││
║  ├──────────────────┤  ├──────────────────┤  ├──────────────────┤║
║  │                  │  │                  │  │                  ││
║  │      12          │  │        3         │  │        -         ││
║  │  Total Holidays  │  │  Upcoming (30d)  │  │  Today's Status  ││
║  │                  │  │                  │  │                  ││
║  └──────────────────┘  └──────────────────┘  └──────────────────┘║
║                                                                     ║
╠════════════════════════════════════════════════════════════════════╣
║ Filters:  [From Date] [To Date] [Holiday Type ▼] [Reset]          ║
╠════════════════════════════════════════════════════════════════════╣
║                                                                     ║
║  📅 MARKED HOLIDAYS                                                ║
║                                                                     ║
║  ╔═══════════════════════╗  ╔═══════════════════════╗             ║
║  │ 🎉 Holi             │  │ 🎆 New Year         │             ║
║  │ 🟠 Festival         │  │ 🟣 Other            │             ║
║  │ Mar 15, 2025        │  │ Jan 01, 2025        │             ║
║  │ Friday              │  │ Wednesday           │             ║
║  │                     │  │                     │             ║
║  │ 👥 All Employees    │  │ 👥 All Employees    │             ║
║  │ 🔄 Yearly           │  │ 🔄 Yearly           │             ║
║  │                     │  │                     │             ║
║  │ [✏️ Edit] [🗑️ Del]  │  │ [✏️ Edit] [🗑️ Del]  │             ║
║  ╚═══════════════════════╝  ╚═══════════════════════╝             ║
║                                                                     ║
║  ╔═══════════════════════╗  ╔═══════════════════════╗             ║
║  │ 🏢 Company Holiday  │  │ 🚨 Emergency        │             ║
║  │ 🟢 Company          │  │ 🔴 Emergency        │             ║
║  │ Dec 25, 2025        │  │ Jul 15, 2025        │             ║
║  │ Thursday            │  │ Tuesday             │             ║
║  │                     │  │                     │             ║
║  │ 📍 Sales Dept       │  │ 👥 All Employees    │             ║
║  │                     │  │                     │             ║
║  │ [✏️ Edit] [🗑️ Del]  │  │ [✏️ Edit] [🗑️ Del]  │             ║
║  ╚═══════════════════════╝  ╚═══════════════════════╝             ║
║                                                                     ║
╚════════════════════════════════════════════════════════════════════╝
```

### Page 2: Holiday Modal - Create/Edit

```
╔════════════════════════════════════════════════════════╗
║  🗓️ Mark Holiday                                      ║
╠════════════════════════════════════════════════════════╣
║                                                         ║
║  Holiday Date *                                        ║
║  [━━━━━━━━━━━━━━━━] 2025-03-15                        ║
║                                                         ║
║  Holiday Name *                                        ║
║  [Holi_____________________________]                   ║
║                                                         ║
║  Holiday Type *                                        ║
║  [Festival ▼]                                         ║
║   ├─ National Holiday                                 ║
║   ├─ Festival                                         ║
║   ├─ Company Holiday                                  ║
║   ├─ Emergency Closure                                ║
║   └─ Other                                            ║
║                                                         ║
║  Description (Optional)                                ║
║  [Spring Festival Celebration_______________]         ║
║  [━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━]          ║
║                                                         ║
║  Apply To *                                            ║
║  [All Employees ▼]                                    ║
║   ├─ All Employees                                    ║
║   ├─ Specific Department                              ║
║   └─ Specific Employees                               ║
║                                                         ║
║  ☑ Repeat this holiday every year                     ║
║                                                         ║
║  ┌─────────────────────────────────────────────────┐ ║
║  │ [Cancel]              [Save Holiday]            │ ║
║  └─────────────────────────────────────────────────┘ ║
║                                                         ║
╚════════════════════════════════════════════════════════╝
```

### Page 3: Attendance with Holiday Integration

```
╔══════════════════════════════════════════════════════════════════════╗
║  👥 Employee Attendance Management                                   ║
║  Monitor employee attendance status and working hours - Admin View   ║
║                        [🔄 Refresh] [Today: Mar 16, 2025]           ║
║                                       [🗓️ Mark Holiday]            ║
╠══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  KPI Cards:                                                           ║
║  ┌─────────────────────┐  ┌─────────────────────┐  ┌──────────────┐ ║
║  │ 👥 Total Employees  │  │ ✅ Present Today   │  │ ❌ Absent    │ ║
║  │ Total: 45           │  │ Present: 40        │  │ Absent: 4    │ ║
║  │ Active              │  │ Checked In         │  │ Not Checked  │ ║
║  └─────────────────────┘  └─────────────────────┘  └──────────────┘ ║
║                                                                       ║
╠══════════════════════════════════════════════════════════════════════╣
║                                                                       ║
║  Employee Attendance Status       [Date: 2025-03-17]                ║
║  ┌────────────────────────────────────────────────────────────────┐ ║
║  │ Employee Name │ Department │ Status │ Check In │ Check Out  │ ║
║  ├────────────────────────────────────────────────────────────────┤ ║
║  │ Raj Kumar     │ Sales      │ ✅ Pres│ 09:30 AM │ 06:15 PM  │ ║
║  │ Priya Singh   │ HR         │ ✅ Pres│ 09:15 AM │ 06:00 PM  │ ║
║  │ Amit Verma    │ IT         │ 🏖️ Hol│    -    │    -      │ ║
║  │ Zara Khan     │ Operations │ ❌ Abs │    -    │    -      │ ║
║  │ Ravi Patel    │ Finance    │ ✅ Pres│ 10:00 AM │ 06:30 PM  │ ║
║  └────────────────────────────────────────────────────────────────┘ ║
║                                                                       ║
║  [Edit holiday marked employee: "Amit Verma - Holiday Not Absent"]   ║
║                                                                       ║
╚══════════════════════════════════════════════════════════════════════╝
```

## 🎨 Color Scheme & Icons

### Holiday Types - Color Coding

```
┌─────────────────────────────────────────────────────┐
│  Holiday Type Colors                                 │
├─────────────────────────────────────────────────────┤
│                                                      │
│  🔵 NATIONAL (Blue #0066cc)                         │
│     Government-declared holidays                     │
│     Example: Independence Day, Republic Day         │
│                                                      │
│  🟠 FESTIVAL (Orange #ff6600)                       │
│     Religious/Cultural festivals                     │
│     Example: Diwali, Eid, Christmas                 │
│                                                      │
│  🟢 COMPANY (Green #00cc66)                         │
│     Company-specific holidays                        │
│     Example: Foundation Day, Annual Day             │
│                                                      │
│  🔴 EMERGENCY (Red #cc0000)                         │
│     Unexpected closures                              │
│     Example: Natural disaster, sudden closure       │
│                                                      │
│  🟣 OTHER (Purple #9933cc)                          │
│     Custom holidays                                  │
│     Example: Regional holidays                      │
│                                                      │
└─────────────────────────────────────────────────────┘
```

### Status Badges in Attendance

```
┌──────────────────────────────────────────────────┐
│  Attendance Status Indicators                     │
├──────────────────────────────────────────────────┤
│                                                   │
│  ✅ PRESENT (Green)                              │
│     Employee checked in and out                   │
│     Display: "✅ Present"                         │
│                                                   │
│  ❌ ABSENT (Red)                                 │
│     No attendance record found                    │
│     Display: "❌ Absent"                          │
│                                                   │
│  🏖️ HOLIDAY (Light Blue)                        │
│     Holiday marked for the day                    │
│     Display: "🏖️ Holiday"                        │
│     NOT counted as absent                         │
│                                                   │
│  🏨 ON LEAVE (Yellow)                            │
│     Approved leave application                    │
│     Display: "🏨 On Leave"                        │
│                                                   │
│  🚗 WORKING (Orange)                             │
│     Checked in, not checked out                   │
│     Display: "🚗 Working..."                      │
│                                                   │
└──────────────────────────────────────────────────┘
```

## 📊 Data Flow Visualization

### Holiday Creation to Display

```
                    USER INTERFACE
                    ├─→ Click "Mark Holiday"
                    ├─→ Fill Form
                    ├─→ Click "Save"
                         │
                         ▼
                 VALIDATION & PROCESSING
                 ├─→ Check date format
                 ├─→ Check duplicates
                 ├─→ Validate inputs
                         │
                         ▼
                    DATABASE
                    ├─→ Insert: holidays table
                    ├─→ Get applicable users
                    ├─→ Create/Update: attendance table
                    ├─→ Set: is_holiday=1
                    └─→ Set: is_counted_absent=0
                         │
                         ▼
               NOTIFICATION SYSTEM
               ├─→ Send holiday greeting
               ├─→ Update dashboard stats
               └─→ Trigger alerts
                         │
                         ▼
                USER SEES RESULTS
                ├─→ Holiday in list (with color badge)
                ├─→ Dashboard updated (statistics)
                ├─→ Attendance shows 🏖️ Holiday
                ├─→ Notifications received
                └─→ Reports exclude from absence
```

## 📈 Monthly Register Display Sample

```
╔═══════════════════════════════════════════════════════════╗
║  MONTHLY ATTENDANCE REGISTER - MARCH 2025                 ║
║  Employee: Raj Kumar  |  Department: Sales                ║
╠═══════════════════════════════════════════════════════════╣
║                                                             ║
║  Date      Day    Status      Check In   Check Out    Hours║
║  ───────────────────────────────────────────────────────── ║
║  01.03.25  SAT    ─────       ─────      ─────      ─    ║
║  02.03.25  SUN    ─────       ─────      ─────      ─    ║
║  03.03.25  MON    ✅ Pres     09:30      18:00      8:30 ║
║  04.03.25  TUE    ✅ Pres     09:15      18:15      9:00 ║
║  05.03.25  WED    ❌ Absent   ─────      ─────      ─    ║
║  06.03.25  THU    ✅ Pres     09:45      18:30      8:45 ║
║  07.03.25  FRI    ✅ Pres     09:00      17:45      8:45 ║
║  08.03.25  SAT    ─────       ─────      ─────      ─    ║
║  09.03.25  SUN    ─────       ─────      ─────      ─    ║
║  10.03.25  MON    🏖️ Holiday  ─────      ─────      ─    ║ ← Holi
║  11.03.25  TUE    ✅ Pres     09:30      18:00      8:30 ║
║  12.03.25  WED    ✅ Pres     09:15      18:15      9:00 ║
║  13.03.25  THU    ✅ Pres     09:45      18:30      8:45 ║
║  14.03.25  FRI    ✅ Pres     09:00      17:45      8:45 ║
║  15.03.25  SAT    ─────       ─────      ─────      ─    ║
║  16.03.25  SUN    ─────       ─────      ─────      ─    ║
║  17.03.25  MON    ✅ Pres     09:30      18:00      8:30 ║
║  18.03.25  TUE    ✅ Pres     09:15      18:15      9:00 ║
║  19.03.25  WED    ✅ Pres     09:45      18:30      8:45 ║
║  20.03.25  THU    ✅ Pres     09:00      17:45      8:45 ║
║  21.03.25  FRI    ❌ Absent   ─────      ─────      ─    ║
║                                                             ║
╠═══════════════════════════════════════════════════════════╣
║  SUMMARY                                                    ║
║  ───────────────────────────────────────────────────────────║
║  Total Working Days     : 19 (excl. weekends & holidays)   ║
║  Days Present           : 17                                ║
║  Days Absent            : 1                                 ║
║  Days Holiday           : 1 (Holi - Mar 10)                ║
║  Days on Leave          : 0                                 ║
║  ───────────────────────────────────────────────────────────║
║  Attendance %           : 89.47% (17/19)                    ║
║  Total Hours Worked     : 155:00                            ║
║  Average Daily Hours    : 9:06                              ║
║                                                             ║
║  Status: ✅ Regular                                         ║
║                                                             ║
╚═══════════════════════════════════════════════════════════╝
```

## 🎯 Feature Highlight: Holiday Status Logic

```
                     IS DATE A HOLIDAY?
                            │
                    ┌───────┴────────┐
                    │                │
                   YES              NO
                    │                │
        Check Attendance Table   Check Leave
        (is_holiday flag)               │
                    │          ┌───────┴────────┐
                    │          │                │
              🏖️ HOLIDAY    APPROVED       NO
                           LEAVE           LEAVE
                    │          │                │
                    │      🏨 ON LEAVE   Check Attendance
                    │          │                │
                    │          │      ┌────────┴──────┐
                    │          │      │               │
                    │          │   CHECK_IN        NO
                    │          │      │          CHECK_IN
                    │          │   ✅ PRESENT    │
                    │          │                 ❌ ABSENT
                    │          │                 │
                    └─────┬────┴─────────────────┴─────┘
                          │
                    DISPLAY BADGE:
                    🏖️ Holiday / 🏨 On Leave / ✅ Present / ❌ Absent
                          │
            EXCLUDE FROM ABSENCE CALCULATION?
            ┌─────────────────┬─────────────────┬──────────┐
            │                 │                 │          │
          HOLIDAY        ON LEAVE           PRESENT      ABSENT
            YES             YES                NO          YES
```

## 🔐 Access Control Matrix

```
┌────────────────────────────────────────────────────────┐
│  FEATURE ACCESS CONTROL                                 │
├────────────────────────────────────────────────────────┤
│                                                          │
│  Feature               │ Admin │ Owner │ Manager │ User │
│  ──────────────────────┼───────┼───────┼─────────┼──────│
│  View Holidays         │   ✅  │  ✅   │   ✅   │  ✅  │
│  Create Holiday        │   ✅  │  ✅   │   ❌   │  ❌  │
│  Edit Holiday          │   ✅  │  ✅   │   ❌   │  ❌  │
│  Delete Holiday        │   ✅  │  ✅   │   ❌   │  ❌  │
│  Verify Attendance     │   ✅  │  ✅   │   ❌   │  ❌  │
│  View My Attendance    │   ✅  │  ✅   │   ✅   │  ✅  │
│  See Holiday Status    │   ✅  │  ✅   │   ✅   │  ✅  │
│  Receive Notification  │   ✅  │  ✅   │   ✅   │  ✅  │
│  Export Report         │   ✅  │  ✅   │   ✅   │  ❌  │
│                                                          │
└────────────────────────────────────────────────────────┘
```

## 📱 Responsive Design - Mobile View

```
┌───────────────────────────────────────────┐
│ ☰  Holiday Management              [X]   │
├───────────────────────────────────────────┤
│                                            │
│  🗓️ Mark Holiday                   [+]   │
│                                            │
│  ┌──────────────────────────────────────┐ │
│  │  📊 Total Holidays: 12               │ │
│  └──────────────────────────────────────┘ │
│  ┌──────────────────────────────────────┐ │
│  │  📅 Upcoming (30d): 3                │ │
│  └──────────────────────────────────────┘ │
│  ┌──────────────────────────────────────┐ │
│  │  📌 Today: No Holiday                │ │
│  └──────────────────────────────────────┘ │
│                                            │
│  [From Date] [To Date] [Filter Type ▼]   │
│                                            │
│  📅 MARKED HOLIDAYS                       │
│                                            │
│  Holi                                      │
│  Mar 15, 2025 | Friday                    │
│  Festival | 👥 All Emps | 🔄 Yearly      │
│  [✏️] [🗑️]                               │
│  ──────────────────────────────────────   │
│                                            │
│  New Year                                  │
│  Jan 01, 2025 | Wednesday                 │
│  Other | 👥 All Emps | 🔄 Yearly         │
│  [✏️] [🗑️]                               │
│                                            │
└───────────────────────────────────────────┘
```

---

**Visual Reference Version:** 1.0.0  
**UI Components:** Complete  
**Responsive Design:** Included  
**Accessibility:** WCAG Compliant

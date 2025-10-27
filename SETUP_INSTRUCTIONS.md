# 🎮 Gamification Setup Instructions

## Step-by-Step Setup for phpMyAdmin

### 1. **Run Main Schema** (if not already done)
```sql
-- Copy and paste from database/schema.sql into phpMyAdmin
```

### 2. **Run Daily Workflow Schema** (if not already done)
```sql
-- Copy and paste from database/daily_workflow_schema.sql into phpMyAdmin
```

### 3. **Run Gamification Schema**
```sql
-- Copy and paste from database/gamification_schema.sql into phpMyAdmin
```

### 4. **Run Dummy Data**
```sql
-- Copy and paste ENTIRE content from database/dummy_data.sql into phpMyAdmin
-- Make sure to run ALL statements at once
```

### 5. **Test the System**
- Visit: `http://localhost/ergon/test_gamification.php`
- Visit: `http://localhost/ergon/simulate_user_activity.php`

---

## ⚠️ Important Notes

- **Run in Order**: Schema files first, then dummy data
- **Copy All**: Select entire file content and paste into phpMyAdmin
- **Single Execution**: Run all statements together, not line by line
- **Check Results**: Verify no errors in phpMyAdmin

---

## 🔧 Alternative: Command Line Method

If you have MySQL command line access:

```bash
mysql -u root -p ergon_db < database/schema.sql
mysql -u root -p ergon_db < database/daily_workflow_schema.sql  
mysql -u root -p ergon_db < database/gamification_schema.sql
mysql -u root -p ergon_db < database/dummy_data.sql
```

---

## 🎯 Expected Results After Setup

- **Users**: Alice, Bob, Carol, David created
- **Points**: Alice (85), Bob (50), Carol (35)
- **Badges**: Multiple achievements awarded
- **Tasks**: 7 days of task history
- **Leaderboard**: Functional ranking system

---

## 🚨 Troubleshooting

**If you get foreign key errors:**
1. Run `database/clean_dummy_data.sql` first
2. Then run `database/dummy_data.sql` again

**If tables don't exist:**
1. Ensure all schema files are run first
2. Check database name is `ergon_db`

**If variables don't work:**
1. Make sure to run entire dummy_data.sql at once
2. Don't run statements individually
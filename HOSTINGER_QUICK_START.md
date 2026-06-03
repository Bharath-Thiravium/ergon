# ERGON Quick Start - Hostinger Deployment (20 Minutes)

## TL;DR - 5 Step Deployment

### 1️⃣ Upload Files (5 min)
```
FTP to public_html/ergon/
Upload all ERGON files
```

### 2️⃣ Set Permissions (2 min)
```
chmod -R 755 public_html/ergon/
chmod 777 public_html/ergon/storage
chmod 777 public_html/ergon/logs
```

### 3️⃣ Update Database Config (1 min)
Edit `app/config/database.php`:
```php
const HOST = 'localhost';
const USER = 'your_db_user';
const PASS = 'your_db_pass';
const NAME = 'your_db_name';
```

### 4️⃣ Run Migration (3 min)
Visit: `https://yourdomain.com/ergon/migrations/run_migration.php`

### 5️⃣ Test & Delete (2 min)
- Login to app
- Mark a holiday
- Delete migration file

**Done! ✓**

---

## Complete Step-by-Step Guide

### Prerequisites
- Hostinger account with cPanel access
- FTP/SFTP client (FileZilla, WinSCP, etc.)
- Text editor (Notepad++, VS Code, etc.)
- 20 minutes

### Step 1: Get Your Database Credentials

**Where to find them:**

1. Login to Hostinger dashboard
2. Click **Hosting** → Your domain
3. Scroll down to **Database Information**
4. Note these values:
   ```
   Hostname: localhost (usually)
   Database: ergon_db (or your_database_name)
   User: ergon_user (or your_username)
   Password: ••••••••••
   ```

### Step 2: Upload Files via FTP

**Using FileZilla:**

1. Open FileZilla
2. File → Site Manager → New Site
3. Enter:
   - Host: `ftp.yourdomain.com` or from Hostinger
   - User: `your_ftp_username`
   - Password: `your_ftp_password`
4. Click "Connect"
5. Navigate to **Remote site**: `public_html`
6. Create new folder: `ergon`
7. Enter folder and upload ERGON files
8. Wait for completion

**Alternative (Hostinger File Manager):**

1. Login to Hostinger cPanel
2. Find **File Manager**
3. Navigate to `public_html`
4. Create folder: `ergon`
5. Upload files (may need to zip first, then extract)

### Step 3: Configure Database Connection

1. In File Manager or FTP, open:
   - Path: `public_html/ergon/app/config/database.php`
2. Edit with text editor
3. Find and update:
   ```php
   const HOST = 'localhost';
   const USER = 'ergon_user';        // Your DB user
   const PASS = 'your_password';     // Your DB password
   const NAME = 'ergon_db';          // Your DB name
   ```
4. Save file

### Step 4: Run Database Migration

**EASIEST METHOD - Migration Script:**

1. Open browser
2. Visit: `https://yourdomain.com/ergon/migrations/run_migration.php`
3. Wait for page to load
4. Should see:
   ```
   ✓ Database connection successful
   ✓ Users table created
   ✓ Departments table created
   ✓ Attendance table created
   ✓ Leaves table created
   ✓ Holidays table created ← NEW FEATURE
   ✓ Projects table created
   ✓ Settings table created
   ✓ Tasks table created
   ✓ All required tables created successfully
   ```

5. Screenshot the success page (for records)
6. **Delete the migration file** for security:
   - File Manager → `ergon/migrations/run_migration.php`
   - Right-click → Delete

**ALTERNATIVE - PhpMyAdmin:**

1. Login to Hostinger cPanel
2. Find **PhpMyAdmin**
3. Click your ERGON database
4. Click **Import** tab
5. Choose file: `migrations/create_tables.sql`
6. Click **Go**
7. Wait for success message

### Step 5: Verify Everything Works

**Test 1: Application Login**
1. Visit: `https://yourdomain.com/ergon/`
2. Login with your credentials
3. Should see dashboard without errors

**Test 2: Holiday Feature**
1. Go to **Admin** → **Attendance**
2. Look for **🗓️ Mark Holiday** button
3. Click it
4. Fill form:
   - Holiday Date: (pick a future date)
   - Holiday Name: "Test Holiday"
   - Holiday Type: Company
   - Description: Testing
5. Click "Submit"
6. Should see success message

**Test 3: Check Monthly Report**
1. Go to **Reports** → **Monthly Attendance**
2. Select current/next month
3. Mark a holiday date if not already
4. Employee rows should show "H" on holiday date (not "A" for Absent)

If all tests pass, you're done! ✓

### Step 6: Security Cleanup

1. Delete temporary files:
   - `migrations/run_migration.php`
   - Any `test_*.php` files

2. Update `.htaccess` to prevent directory listing:
   - Add this line at top:
   ```apache
   Options -Indexes
   ```

3. Enable SSL (free on Hostinger):
   - cPanel → SSL/TLS
   - Install AutoSSL certificate

### Step 7: Enable Regular Backups

1. Hostinger cPanel → Backup
2. Enable automatic daily backups
3. Download backup monthly for safekeeping

---

## Troubleshooting

### ❌ "Cannot connect to database"

**Solution:**
1. Double-check credentials in `database.php`
2. Verify database exists in PhpMyAdmin
3. Verify user has full permissions
4. Try hostname `127.0.0.1` instead of `localhost`

### ❌ "404 Not Found" on routes

**Solution:**
1. Ensure `.htaccess` is in `ergon/` folder
2. Check it has RewriteEngine On
3. Restart Hostinger app (sometimes needed)

### ❌ "Permission Denied"

**Solution:**
```bash
chmod -R 755 public_html/ergon/
chmod 777 public_html/ergon/storage
chmod 777 public_html/ergon/logs
```

### ❌ Migration script shows error

**Solution:**
1. Check exact database credentials
2. Verify database exists
3. Check PHP version is 7.4+
4. Try PhpMyAdmin import method instead

### ❌ Holiday table missing after migration

**Solution:**
1. Re-run migration script
2. Check PhpMyAdmin - table should appear
3. Try manual SQL import from `create_tables.sql`

---

## Important Commands (if you have SSH access)

```bash
# Navigate to folder
cd public_html/ergon

# Fix permissions
chmod -R 755 .
chmod 777 storage logs

# View PHP errors
tail -f logs/php-errors.log

# Test database
php -r "require 'app/config/database.php'; Database::connect(); echo 'OK';"
```

---

## What Gets Created

**Database Tables:**
- `users` - Employee accounts
- `departments` - Company departments
- `attendance` - Clock in/out records
- `leaves` - Leave requests
- `holidays` - **NEW** - Holiday calendar
- `projects` - Work projects/sites
- `settings` - System configuration
- `tasks` - Task management

**Folders Created:**
- `storage/` - Cache and temporary files
- `logs/` - Error and activity logs
- `uploads/` - User uploads (if needed)

---

## Next Steps After Deployment

1. ✓ Create test user accounts
2. ✓ Mark some test holidays
3. ✓ Train staff on new features
4. ✓ Set up daily backups
5. ✓ Monitor logs for errors
6. ✓ Document any customizations

---

## Quick Reference

| Task | Command/URL |
|------|-------------|
| Run Migration | `https://yourdomain.com/ergon/migrations/run_migration.php` |
| Application | `https://yourdomain.com/ergon/` |
| PhpMyAdmin | Hostinger cPanel → PhpMyAdmin |
| File Manager | Hostinger cPanel → File Manager |
| cPanel | hostinger.com/cpanel |

---

## Support

- **Hostinger Help**: support@hostinger.com
- **Hostinger Docs**: docs.hostinger.com
- **Check Logs**: `public_html/ergon/logs/php-errors.log`

---

**Version**: 1.0
**Updated**: 2024
**Tested on**: Hostinger Basic Hosting Plan

✓ Deployment Ready!

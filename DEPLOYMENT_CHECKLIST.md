# ERGON Live Deployment Checklist - Hostinger

## Pre-Deployment (Do This First!)

- [ ] **Backup your current database** (if upgrading)
  - Go to Hostinger cPanel → MySQL → Backup
  - Download and save locally
  
- [ ] **Backup any existing files**
  - Download current ergon/ folder via FTP
  - Save to safe location

- [ ] **Get Hostinger credentials ready**
  - Hostname: _____________ (usually `localhost`)
  - Database name: _____________
  - Database user: _____________
  - Database password: _____________
  - Server SSH (optional): _____________

## Upload & Configuration (15-20 minutes)

### Step 1: Upload Files
- [ ] Connect to Hostinger via FTP/SFTP
- [ ] Navigate to `public_html/` folder
- [ ] Upload ERGON files to `public_html/ergon/`
- [ ] Verify all files uploaded (check file count)

**Files to check:**
- [ ] `index.php`
- [ ] `.htaccess`
- [ ] `app/config/` folder
- [ ] `migrations/` folder
- [ ] `views/` folder
- [ ] `assets/` folder

### Step 2: Set File Permissions
- [ ] Open Hostinger File Manager
- [ ] Right-click `ergon/` folder → Change Permissions
- [ ] Set to `755` for folders
- [ ] Set to `644` for files
- [ ] Create `storage/` folder → permissions `777`
- [ ] Create `logs/` folder → permissions `777`

### Step 3: Update Database Configuration
- [ ] Open `app/config/database.php` with file editor
- [ ] Update database credentials:
  - [ ] Hostname: _____________
  - [ ] Database: _____________
  - [ ] Username: _____________
  - [ ] Password: _____________
- [ ] Save file

## Run Migrations (5 minutes)

### Option A: Using Migration Script (RECOMMENDED)
- [ ] Upload `migrations/run_migration.php` to `ergon/migrations/`
- [ ] Open browser to: `https://yourdomain.com/ergon/migrations/run_migration.php`
- [ ] Wait for completion (should see all green checkmarks)
- [ ] Screenshot success page
- [ ] **DELETE** `run_migration.php` file after running

### Option B: Using PhpMyAdmin
- [ ] Open Hostinger cPanel → PhpMyAdmin
- [ ] Select ERGON database
- [ ] Click "Import" tab
- [ ] Choose file: `migrations/create_tables.sql`
- [ ] Click "Go"
- [ ] Verify "success" message

### Option C: Using Raw SQL
- [ ] Open PhpMyAdmin → SQL tab
- [ ] Copy contents of `migrations/create_tables.sql`
- [ ] Paste into SQL editor
- [ ] Execute (Ctrl+Enter)

## Verify Installation (10 minutes)

### Database Verification
- [ ] Open PhpMyAdmin
- [ ] Check database has these tables:
  - [ ] `users`
  - [ ] `departments`
  - [ ] `attendance`
  - [ ] `leaves`
  - [ ] `holidays` ← NEW!
  - [ ] `projects`
  - [ ] `settings`
  - [ ] `tasks`

**Verify Holiday Table Columns:**
- [ ] `holiday_date`
- [ ] `holiday_name`
- [ ] `holiday_type`
- [ ] `is_active`

### File Verification
- [ ] `.htaccess` exists in `ergon/` folder
- [ ] `storage/` folder exists and writable
- [ ] `logs/` folder exists and writable

### Application Verification
1. [ ] Visit: `https://yourdomain.com/ergon/`
2. [ ] Login screen appears
3. [ ] Login with your credentials
4. [ ] Dashboard loads without errors
5. [ ] Navigate to Admin section

### Holiday Feature Verification
- [ ] Admin → Attendance page opens
- [ ] "🗓️ Mark Holiday" button visible
- [ ] Click button (or navigate to /ergon/holidays)
- [ ] Holiday form appears
- [ ] Fill form and submit
- [ ] Success message shows
- [ ] Go to Reports → Monthly Attendance
- [ ] Holiday appears as "H" in report (not "A")

## Post-Deployment (Security & Optimization)

### Security
- [ ] [ ] Remove test files (test_db.php if created)
- [ ] [ ] Remove migration scripts after running
- [ ] [ ] Enable HTTPS/SSL certificate (Hostinger provides free)
- [ ] [ ] Change default admin password
- [ ] [ ] Update `app/config/database.php` permissions to `600`
- [ ] [ ] Disable directory listing in `.htaccess`:
  ```apache
  Options -Indexes
  ```

### Optimization
- [ ] Enable Hostinger's caching (if available)
- [ ] Set up automatic daily backups
- [ ] Set up error logging to track issues
- [ ] Configure cron job for maintenance (if needed)

### Monitoring
- [ ] [ ] Check logs for errors: `logs/php-errors.log`
- [ ] [ ] Monitor database size in PhpMyAdmin
- [ ] [ ] Set up regular backup schedule
- [ ] [ ] Test login and key functions weekly

## Troubleshooting Checklist

### Issue: 404 Errors on Routes
- [ ] `.htaccess` exists in `ergon/` folder
- [ ] `.htaccess` has correct content
- [ ] mod_rewrite is enabled (Hostinger usually has it)
- [ ] Try adding: `RewriteBase /ergon/`

### Issue: Database Connection Fails
- [ ] Database credentials are exact match
- [ ] Hostname is correct (usually `localhost`)
- [ ] Database user has full permissions
- [ ] Database exists in PhpMyAdmin
- [ ] Check `logs/php-errors.log` for exact error

### Issue: Permission Denied
- [ ] Folders have `755` permissions
- [ ] Files have `644` permissions
- [ ] `storage/` and `logs/` have `777`
- [ ] Try: `chmod -R 755 ergon/` via SSH

### Issue: Blank White Page
- [ ] Check `logs/php-errors.log`
- [ ] Enable `display_errors` temporarily
- [ ] Verify database connection
- [ ] Check PHP version (7.4+)

### Issue: Holiday Table Missing
- [ ] Re-run migration script
- [ ] Or import `migrations/create_tables.sql` via PhpMyAdmin
- [ ] Verify in PhpMyAdmin table list

## Quick Links for Hostinger

| Task | URL |
|------|-----|
| cPanel | hostinger.com/cpanel |
| PhpMyAdmin | hostinger.com/phpmyadmin |
| File Manager | hostinger.com/files |
| Database Manager | hostinger.com/db |
| Email | hostinger.com/mail |
| Domain Settings | hostinger.com/domains |

## Support Contacts

- **Hostinger Support**: support@hostinger.com
- **Documentation**: docs.hostinger.com
- **Status Page**: status.hostinger.com

## Deployment Sign-Off

| Item | Status | Date | Notes |
|------|--------|------|-------|
| Files Uploaded | ✓ / ✗ | __/__/__ | |
| Permissions Set | ✓ / ✗ | __/__/__ | |
| Config Updated | ✓ / ✗ | __/__/__ | |
| Migration Run | ✓ / ✗ | __/__/__ | |
| Verified Login | ✓ / ✗ | __/__/__ | |
| Holiday Feature | ✓ / ✗ | __/__/__ | |
| Security Setup | ✓ / ✗ | __/__/__ | |

**Deployment Completed By**: _______________
**Date**: __/__/____
**Time**: __:__ (AM/PM)

## Important Notes

⚠️ **DELETE after deployment:**
- `migrations/run_migration.php`
- `test_db.php` (if created)
- Any temporary files

⚠️ **SECURE before going live:**
- Change all default passwords
- Enable SSL certificate
- Set up regular backups
- Monitor error logs

## Next Steps

1. ✓ Verify everything works
2. ✓ Test with real users
3. ✓ Set up backup schedule
4. ✓ Monitor logs for errors
5. ✓ Plan maintenance windows
6. ✓ Document any customizations

---

**Need Help?** Check the full guide: `LIVE_SERVER_SETUP_GUIDE.md`

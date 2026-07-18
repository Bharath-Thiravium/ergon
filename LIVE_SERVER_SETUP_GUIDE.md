# ERGON Live Server Setup Guide - Hostinger Basic Web Hosting

## Overview
This guide covers deploying the ERGON attendance system to a Hostinger basic web hosting account with holiday feature updates.

## Prerequisites
- Hostinger basic web hosting account (cPanel access)
- FTP/SFTP access or File Manager
- PhpMyAdmin access
- PHP 7.4+ with PDO support
- MySQL 5.7+

## Step 1: Upload Files to Server

### Option A: Using FTP (Recommended for large files)
1. Open FileZilla or your FTP client
2. Connect with credentials from Hostinger dashboard
3. Navigate to `public_html` folder
4. Upload entire ERGON folder

### Option B: Using Hostinger File Manager
1. Login to Hostinger cPanel
2. Go to File Manager
3. Navigate to `public_html`
4. Upload ERGON files (may need to extract locally then upload)

### Correct Directory Structure
```
public_html/
├── ergon/
│   ├── app/
│   │   ├── config/
│   │   ├── controllers/
│   │   ├── models/
│   │   ├── core/
│   │   └── helpers/
│   ├── views/
│   ├── assets/
│   ├── migrations/
│   ├── index.php
│   └── .htaccess
```

## Step 2: Set File Permissions

1. In Hostinger File Manager, right-click on `ergon` folder
2. Select "Change Permissions" or use terminal
3. Set permissions:
   - Folders: `755`
   - PHP files: `644`
   - config directory: `750` (more secure)

**Terminal Commands** (if SSH available):
```bash
cd public_html/ergon
chmod -R 755 .
chmod -R 644 app/config/*.php
chmod 777 storage logs
```

## Step 3: Update Database Configuration

1. Open `app/config/database.php` with file editor
2. Update credentials:
   ```php
   const HOST = 'localhost';      // Usually localhost for Hostinger
   const USER = 'your_db_user';   // From Hostinger dashboard
   const PASS = 'your_db_pass';   // From Hostinger dashboard
   const NAME = 'your_db_name';   // Database name from Hostinger
   ```

3. Find these in Hostinger:
   - Go to cPanel → Databases → MySQL
   - Copy exact credentials

## Step 4: Create Database Tables (Choose One Method)

### METHOD A: Using Migration Script (EASIEST - Recommended)

1. Upload `migrations/run_migration.php` to `ergon/` folder
2. Open browser and visit:
   ```
   https://yourdomain.com/ergon/migrations/run_migration.php
   ```
3. You'll see a log of all migrations executed
4. Verify "Success" messages appear

### METHOD B: Using PhpMyAdmin (Manual)

1. Login to Hostinger cPanel
2. Go to PhpMyAdmin
3. Select your ERGON database
4. Click "Import" tab
5. Choose file: `migrations/create_tables.sql`
6. Click "Go"

### METHOD C: Using Direct SQL (Advanced)

1. In PhpMyAdmin, go to SQL tab
2. Copy contents of `migrations/create_tables.sql`
3. Paste into SQL editor
4. Click "Go"

## Step 5: Run Holiday Feature Migration

After main tables are created, run the holiday migration:

1. Upload `migrations/add_holiday_tables.php` to `ergon/migrations/`
2. Visit: `https://yourdomain.com/ergon/migrations/add_holiday_tables.php`
3. Or in PhpMyAdmin, import `migrations/holidays.sql`

## Step 6: Update .htaccess (URL Routing)

Ensure `.htaccess` exists in `ergon/` folder with:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /ergon/
    
    # Skip if file or folder exists
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    # Route all requests to index.php
    RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
</IfModule>
```

## Step 7: Create directories if missing

Via Hostinger File Manager:
1. Navigate to `ergon/`
2. Create these folders if missing:
   - `storage/` (for logs, cache)
   - `logs/` (for error logs)
3. Right-click each → Change Permissions → Set to `777`

## Step 8: Test the Installation

### Test 1: Check Database Connection
1. Create file `ergon/test_db.php`:
```php
<?php
require_once 'app/config/database.php';
try {
    $db = Database::connect();
    echo "✓ Database connected successfully!";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<br>Users in database: " . $result['count'];
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage();
}
?>
```

2. Visit: `https://yourdomain.com/ergon/test_db.php`
3. Delete file after testing

### Test 2: Check Holiday Tables
1. In PhpMyAdmin, select your database
2. Look for tables:
   - `holidays` ✓
   - `attendance` ✓
   - `leaves` ✓
   - `users` ✓

### Test 3: Login to Application
1. Visit: `https://yourdomain.com/ergon/`
2. Login with your credentials
3. Navigate to Admin → Attendance
4. Try marking a holiday

## Step 9: Enable Error Logging

Edit `app/config/database.php` and add logging:

```php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php-errors.log');
```

## Step 10: Set Up Cron Jobs (Optional but Recommended)

For automatic tasks like sending attendance reminders:

1. Go to Hostinger cPanel → Cron Jobs
2. Add new cron:
   ```
   /usr/bin/php /home/username/public_html/ergon/cron/attendance_cron.php
   ```
3. Frequency: Daily at 6:00 AM

## Troubleshooting

### Issue: 404 Errors on Routes
- Ensure `.htaccess` is in `ergon/` folder
- Check mod_rewrite is enabled (usually is on Hostinger)
- Try adding this to `.htaccess`:
```apache
<IfModule mod_dir.c>
    DirectoryIndex index.php
</IfModule>
```

### Issue: Database Connection Fails
- Verify credentials in `app/config/database.php`
- Check hostname: usually `localhost` but sometimes `127.0.0.1`
- Ensure database exists in PhpMyAdmin
- Check user permissions (usually full access for Hostinger)

### Issue: Permission Denied Errors
- Ensure folders have `777` or `755` permissions
- Check `storage/` and `logs/` folders exist
- Set them to writable: `chmod 777 storage logs`

### Issue: Blank White Page
- Enable error logging as in Step 9
- Check `logs/php-errors.log` file
- Usually means PHP error - check logs

### Issue: Migration Script Not Running
- Ensure PHP can write to database
- Check database user has CREATE TABLE permissions
- Try running SQL queries manually in PhpMyAdmin

## Step 11: Post-Deployment Checklist

- [ ] Database connected successfully
- [ ] All tables created (check in PhpMyAdmin)
- [ ] Can login to application
- [ ] Admin panel accessible
- [ ] Mark Holiday button visible
- [ ] Monthly report shows holidays
- [ ] File permissions set correctly
- [ ] Error logs accessible
- [ ] SSL certificate enabled (if using HTTPS)
- [ ] Backup created before deployment

## Important Security Notes

1. **Change Default Credentials**: Update any default admin passwords immediately
2. **Secure Database Config**: Move `database.php` outside web root if possible
3. **Remove Test Files**: Delete `test_db.php` and other test files
4. **Enable HTTPS**: Always use SSL certificate (Hostinger usually provides free)
5. **Disable Directory Listing**: Ensure `.htaccess` prevents directory browsing
6. **Regular Backups**: Set up automatic backups in Hostinger

## Rollback Plan (If Issues)

1. Don't panic - keep original files backed up
2. Restore database from backup
3. Delete problematic migrations
4. Restore from previous version
5. Hostinger usually has automatic backups - can restore from dashboard

## Getting Help

1. Check `logs/` folder for error messages
2. Enable `display_errors` temporarily to see PHP errors
3. Check database credentials match exactly
4. Ensure all files uploaded completely
5. Try clearing browser cache

## Next Steps After Successful Deployment

1. Create test user accounts
2. Mark some test holidays
3. Check monthly report displays correctly
4. Train staff on new features
5. Set up regular backups
6. Monitor logs for errors

---

**Deployment Date**: ___________
**Database Backup Location**: ___________
**Support Contact**: ___________

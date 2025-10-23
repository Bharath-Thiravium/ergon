# 🚀 ERGON Deployment Guide

## Development Environment (Localhost)

### Requirements:
- Laragon/XAMPP with PHP 8.x
- MySQL/MariaDB
- Apache with mod_rewrite enabled

### Setup:
1. Place project in `C:\laragon\www\Ergon\`
2. Create database `ergon_db`
3. Visit `http://localhost/ergon/setup-database.php`
4. Access application at `http://localhost/ergon/login.php`

### Default Credentials:
- **Owner:** owner@company.com / owner123
- **Admin:** admin@company.com / admin123
- **User:** user@company.com / user123

---

## Production Environment (Hostinger)

### Database Details:
- **Host:** localhost
- **Database:** u494785662_ergon
- **Username:** u494785662_ergon
- **Password:** @Admin@2025@

### Deployment Steps:

1. **Upload Files:**
   - Upload entire project to `public_html/` directory
   - Ensure all files maintain directory structure

2. **Set Permissions:**
   ```bash
   chmod 755 public_html/
   chmod 644 public_html/*.php
   chmod 755 public_html/storage/
   chmod 755 public_html/logs/
   ```

3. **Run Setup:**
   - Visit `yourdomain.com/production-setup.php`
   - Follow setup instructions
   - **DELETE setup file after completion**

4. **Access Application:**
   - Login at `yourdomain.com/login.php`
   - Use owner credentials from setup

### File Structure:
```
public_html/
├── app/
├── config/
├── public/
├── storage/
├── logs/
├── index.php
├── login.php
├── dashboard.php
├── logout.php
└── .htaccess
```

---

## Environment Detection

The system automatically detects:
- **Development:** localhost, 127.0.0.1, *.local, *.test
- **Production:** All other domains

### Features:
- ✅ Auto database configuration
- ✅ Dynamic asset URLs
- ✅ Environment-specific settings
- ✅ Proper URL routing

---

## Security Notes

### Production Checklist:
- [ ] Delete setup files after deployment
- [ ] Change default passwords
- [ ] Enable HTTPS
- [ ] Set proper file permissions
- [ ] Configure backup system
- [ ] Monitor error logs

### Files to Delete After Setup:
- `production-setup.php`
- `setup-database.php`
- `DEPLOYMENT.md`
- Any test files

---

## Troubleshooting

### Common Issues:
1. **404 Errors:** Check .htaccess and mod_rewrite
2. **Database Connection:** Verify credentials in config/database.php
3. **Asset Loading:** Check Environment::getBaseUrl() output
4. **Permissions:** Ensure proper file/folder permissions

### Support:
- Check error logs in `/logs/` directory
- Verify environment detection at `/check-environment.php`
- Test database connection at setup scripts
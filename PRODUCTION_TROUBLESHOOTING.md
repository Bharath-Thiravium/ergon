# Production Server Issues - Troubleshooting Guide

## Issue: CSS Changes Not Reflecting in Production

### Root Causes
1. **Browser Cache** - Old CSS cached in browser
2. **Server Cache** - CDN or server-side caching
3. **File Permissions** - CSS file not readable
4. **CSS Minification** - Minified files not updated

### Solutions

#### 1. Clear Browser Cache
```bash
# Hard refresh in browser
Ctrl+Shift+Delete (Windows)
Cmd+Shift+Delete (Mac)

# Or use cache buster in HTML
<link rel="stylesheet" href="funnel-styles.css?v=<?php echo time(); ?>">
```

#### 2. Clear Server Cache
```bash
# Clear OPcache
php -r "opcache_reset();"

# Clear APCu cache
php -r "apcu_clear_cache();"

# Restart PHP-FPM
sudo systemctl restart php-fpm

# Clear Nginx cache
sudo rm -rf /var/cache/nginx/*
sudo systemctl reload nginx
```

#### 3. Verify File Permissions
```bash
# Check CSS file permissions
ls -la views/finance/funnel-styles.css

# Should be readable (644 or 755)
chmod 644 views/finance/funnel-styles.css
```

#### 4. Verify CSS Content
```bash
# Check if CSS rule exists
grep -n "funnel-stage:hover::before" views/finance/funnel-styles.css

# Should output the rule with opacity
```

#### 5. Use Cache Buster in Production
```php
<?php
// In your HTML template
require_once 'assets/css/cache-buster.php';
?>

<link rel="stylesheet" href="<?php echo cacheBustCss('views/finance/funnel-styles.css'); ?>">
```

#### 6. Check .htaccess for Caching Headers
```apache
# Add to .htaccess to prevent CSS caching
<FilesMatch "\.(css|js)$">
    Header set Cache-Control "max-age=0, no-cache, no-store, must-revalidate"
</FilesMatch>
```

#### 7. Minified CSS Issue
```bash
# If using minified CSS, regenerate it
# Check if there's a build process
npm run build
# or
gulp build
# or
grunt build
```

### Quick Production Fix Checklist

- [ ] Uncomment CSS rule (already done)
- [ ] Hard refresh browser (Ctrl+Shift+Delete)
- [ ] Clear server cache (opcache_reset)
- [ ] Verify file permissions (644)
- [ ] Check CSS file content (grep)
- [ ] Add cache buster parameter (?v=timestamp)
- [ ] Restart web server (nginx/apache)
- [ ] Check .htaccess caching headers
- [ ] Verify no minified CSS override

### Verify Fix
```bash
# Test in production
curl -I "http://production-server/ergon/views/finance/funnel-styles.css"

# Should show Cache-Control headers
# Check for "opacity: 0.7" in response
curl "http://production-server/ergon/views/finance/funnel-styles.css" | grep "opacity"
```

### Production Deployment Best Practices

1. **Always use cache busters for CSS/JS**
2. **Set proper Cache-Control headers**
3. **Use version numbers in file names** (style.v1.2.3.css)
4. **Clear caches before deployment**
5. **Test in staging before production**
6. **Monitor browser console for errors**
7. **Use CDN cache invalidation if applicable**
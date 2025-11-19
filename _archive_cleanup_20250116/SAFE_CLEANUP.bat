@echo off
echo ========================================
echo ERGON PROJECT SAFE CLEANUP
echo ========================================
echo.
echo This will remove:
echo - Development artifacts and logs
echo - Duplicate nested ergon folder
echo - Debug files and test scripts
echo - Backup CSS files
echo - Migration scripts (already applied)
echo.
pause

REM ========================================
REM 1. DELETE DEVELOPMENT MARKDOWN FILES
REM ========================================
echo Removing development documentation...
del "CARD_STANDARDIZATION_COMPLETE.md" 2>nul
del "CARD_STANDARDIZATION_MINIMAL.md" 2>nul
del "CSS_OPTIMIZATION_REPORT.md" 2>nul
del "CSS_REFACTOR_LOG.md" 2>nul
del "FINAL_OPTIMIZATION_REPORT.md" 2>nul
del "git-conflict-fix.md" 2>nul

REM ========================================
REM 2. DELETE DEBUG PHP FILES
REM ========================================
echo Removing debug files...
del "debug_attendance_users.php" 2>nul
del "debug_location.php" 2>nul
del "debug_settings.php" 2>nul
del "debug_users.php" 2>nul
del "debug_view_data.php" 2>nul
del "debug-page.php" 2>nul
del "debug-remove-action.php" 2>nul

REM ========================================
REM 3. DELETE MIGRATION SCRIPTS (ALREADY APPLIED)
REM ========================================
echo Removing applied migration scripts...
del "fix_columns.bat" 2>nul
del "fix_no_employees.php" 2>nul
del "fix_radius.php" 2>nul
del "fix_rejection_columns.php" 2>nul
del "fix-live-status.php" 2>nul
del "fix-status-column.php" 2>nul
del "quick_fix.php" 2>nul
del "check_columns.sql" 2>nul
del "fix_rejection_columns.sql" 2>nul

REM ========================================
REM 4. DELETE BACKUP CSS FILES
REM ========================================
echo Removing backup CSS files...
del "assets\css\components.css" 2>nul
REM KEEP action-button-clean.css - it's actively used in dashboard.php
del "assets\css\task-components.css" 2>nul
del "assets\css\utilities.css" 2>nul
del "assets\css\ergon-backup.css" 2>nul
del "assets\css\ergon-consolidated.css" 2>nul
del "assets\css\ergon.css.bak_20241218_143000" 2>nul

REM ========================================
REM 5. DELETE TEST AND DEVELOPMENT FOLDERS
REM ========================================
echo Removing test folders...
rmdir /S /Q "tests" 2>nul
rmdir /S /Q "test-results" 2>nul
rmdir /S /Q "reports" 2>nul

REM ========================================
REM 6. DELETE DUPLICATE NESTED ERGON FOLDER
REM ========================================
echo Removing duplicate nested ergon folder...
rmdir /S /Q "ergon" 2>nul

REM ========================================
REM 7. DELETE DEVELOPMENT TOOLS
REM ========================================
echo Removing development tools...
del "css_audit_script.php" 2>nul
del "optimize-css.sh" 2>nul
del "deploy-fix.sh" 2>nul
del "package.json" 2>nul
del "package-lock.json" 2>nul
del "playwright.config.js" 2>nul
del "postcss.config.js" 2>nul
del "purgecss.config.js" 2>nul

REM ========================================
REM 8. DELETE BACKUP TAR FILE
REM ========================================
echo Removing backup archive...
del "ergon_css_backup_20241218_143000.tar.gz" 2>nul

REM ========================================
REM 9. DELETE DUMMY DATA FILES (KEEP ESSENTIAL SQL)
REM ========================================
echo Removing dummy data files...
del "dummy_data.sql" 2>nul
del "dummy_data_simple.sql" 2>nul
del "dummy_data_fixed.sql" 2>nul

REM ========================================
REM 10. DELETE TEMPORARY SETUP FILES
REM ========================================
echo Removing temporary setup files...
del "add_employees.php" 2>nul
del "test_data_setup.php" 2>nul
del "standardize-icons.php" 2>nul

REM ========================================
REM 11. CLEAN SESSION FILES (OPTIONAL)
REM ========================================
echo Cleaning old session files...
del "storage\sessions\sess_*" 2>nul

echo.
echo ========================================
echo CLEANUP COMPLETE!
echo ========================================
echo.
echo Your project structure is now clean.
echo Essential files preserved:
echo - index.php, .htaccess, .env
echo - /app, /api, /views, /assets, /database
echo - /storage, /public
echo - composer.json
echo - action-button-clean.css (fixed in dashboard.php)
echo.
echo Removed:
echo - Debug files and test artifacts
echo - Duplicate nested ergon folder
echo - Development documentation
echo - Backup CSS files
echo - Applied migration scripts
echo.
pause
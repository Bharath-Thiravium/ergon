@echo off
echo ========================================
echo ERGON CLEANUP PREVIEW
echo ========================================
echo.
echo ESSENTIAL FILES THAT WILL BE KEPT:
echo ========================================
echo.
echo ROOT LEVEL:
if exist "index.php" echo [KEEP] index.php
if exist ".htaccess" echo [KEEP] .htaccess
if exist ".env" echo [KEEP] .env
if exist ".env.example" echo [KEEP] .env.example
if exist "composer.json" echo [KEEP] composer.json
if exist "ergon_db.sql" echo [KEEP] ergon_db.sql
if exist "add_missing_column.sql" echo [KEEP] add_missing_column.sql
if exist "add_rejection_columns.sql" echo [KEEP] add_rejection_columns.sql
if exist "setup_test_data.sql" echo [KEEP] setup_test_data.sql
if exist "check_db.php" echo [KEEP] check_db.php
if exist "test_attendance_query.php" echo [KEEP] test_attendance_query.php
if exist "test_db_connection.php" echo [KEEP] test_db_connection.php

echo.
echo ESSENTIAL FOLDERS:
if exist "app" echo [KEEP] /app (MVC structure)
if exist "api" echo [KEEP] /api (API endpoints)
if exist "views" echo [KEEP] /views (templates)
if exist "assets" echo [KEEP] /assets (CSS/JS)
if exist "database" echo [KEEP] /database (SQL files)
if exist "storage" echo [KEEP] /storage (logs/uploads)
if exist "public" echo [KEEP] /public (public files)
if exist "cron" echo [KEEP] /cron (scheduled tasks)

echo.
echo ESSENTIAL CSS FILES IN /assets/css:
if exist "assets\css\ergon.css" echo [KEEP] ergon.css
if exist "assets\css\ergon.production.min.css" echo [KEEP] ergon.production.min.css
if exist "assets\css\ergon.min.css" echo [KEEP] ergon.min.css
if exist "assets\css\theme-enhanced.css" echo [KEEP] theme-enhanced.css
if exist "assets\css\utilities-new.css" echo [KEEP] utilities-new.css
if exist "assets\css\global-tooltips.css" echo [KEEP] global-tooltips.css
if exist "assets\css\instant-theme.css" echo [KEEP] instant-theme.css
if exist "assets\css\critical.css" echo [KEEP] critical.css
if exist "assets\css\daily-planner.css" echo [KEEP] daily-planner.css
if exist "assets\css\action-button-clean.css" echo [KEEP] action-button-clean.css

echo.
echo ========================================
echo FILES THAT WILL BE DELETED:
echo ========================================
echo.
echo DEVELOPMENT DOCUMENTATION:
if exist "CARD_STANDARDIZATION_COMPLETE.md" echo [DELETE] CARD_STANDARDIZATION_COMPLETE.md
if exist "CARD_STANDARDIZATION_MINIMAL.md" echo [DELETE] CARD_STANDARDIZATION_MINIMAL.md
if exist "CSS_OPTIMIZATION_REPORT.md" echo [DELETE] CSS_OPTIMIZATION_REPORT.md
if exist "CSS_REFACTOR_LOG.md" echo [DELETE] CSS_REFACTOR_LOG.md
if exist "FINAL_OPTIMIZATION_REPORT.md" echo [DELETE] FINAL_OPTIMIZATION_REPORT.md
if exist "git-conflict-fix.md" echo [DELETE] git-conflict-fix.md

echo.
echo DEBUG FILES:
if exist "debug_attendance_users.php" echo [DELETE] debug_attendance_users.php
if exist "debug_location.php" echo [DELETE] debug_location.php
if exist "debug_settings.php" echo [DELETE] debug_settings.php
if exist "debug_users.php" echo [DELETE] debug_users.php
if exist "debug_view_data.php" echo [DELETE] debug_view_data.php
if exist "debug-page.php" echo [DELETE] debug-page.php
if exist "debug-remove-action.php" echo [DELETE] debug-remove-action.php

echo.
echo MIGRATION SCRIPTS (ALREADY APPLIED):
if exist "fix_columns.bat" echo [DELETE] fix_columns.bat
if exist "fix_no_employees.php" echo [DELETE] fix_no_employees.php
if exist "fix_radius.php" echo [DELETE] fix_radius.php
if exist "fix_rejection_columns.php" echo [DELETE] fix_rejection_columns.php
if exist "fix-live-status.php" echo [DELETE] fix-live-status.php
if exist "fix-status-column.php" echo [DELETE] fix-status-column.php
if exist "quick_fix.php" echo [DELETE] quick_fix.php

echo.
echo BACKUP CSS FILES:
if exist "assets\css\components.css" echo [DELETE] components.css
REM action-button-clean.css is KEPT - actively used in dashboard
if exist "assets\css\task-components.css" echo [DELETE] task-components.css
if exist "assets\css\utilities.css" echo [DELETE] utilities.css
if exist "assets\css\ergon-backup.css" echo [DELETE] ergon-backup.css
if exist "assets\css\ergon-consolidated.css" echo [DELETE] ergon-consolidated.css

echo.
echo DEVELOPMENT FOLDERS:
if exist "tests" echo [DELETE] /tests (Playwright tests)
if exist "test-results" echo [DELETE] /test-results
if exist "reports" echo [DELETE] /reports
if exist "ergon" echo [DELETE] /ergon (duplicate nested folder)

echo.
echo DEVELOPMENT TOOLS:
if exist "package.json" echo [DELETE] package.json
if exist "package-lock.json" echo [DELETE] package-lock.json
if exist "playwright.config.js" echo [DELETE] playwright.config.js
if exist "postcss.config.js" echo [DELETE] postcss.config.js
if exist "purgecss.config.js" echo [DELETE] purgecss.config.js

echo.
echo BACKUP ARCHIVE:
if exist "ergon_css_backup_20241218_143000.tar.gz" echo [DELETE] ergon_css_backup_20241218_143000.tar.gz

echo.
echo ========================================
echo SUMMARY:
echo ========================================
echo This cleanup will remove development artifacts
echo while preserving all production-essential files.
echo.
echo To proceed with cleanup, run: SAFE_CLEANUP.bat
echo.
pause
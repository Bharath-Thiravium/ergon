@echo off
echo ========================================
echo CSS REFERENCE FIX VERIFICATION
echo ========================================
echo.
echo Checking if action-button-clean.css is properly referenced...
echo.

findstr /n "action-button-clean.css" "views\layouts\dashboard.php" >nul
if %errorlevel%==0 (
    echo [✅ FIXED] action-button-clean.css is now included in dashboard.php
    findstr /n "action-button-clean.css" "views\layouts\dashboard.php"
) else (
    echo [❌ ERROR] action-button-clean.css is still missing from dashboard.php
)

echo.
echo Checking if action-button-clean.js is referenced...
findstr /n "action-button-clean.js" "views\layouts\dashboard.php" >nul
if %errorlevel%==0 (
    echo [✅ OK] action-button-clean.js is properly included
) else (
    echo [❌ ERROR] action-button-clean.js is missing
)

echo.
echo ========================================
echo VERIFICATION COMPLETE
echo ========================================
echo.
echo The attendance page at http://localhost/ergon/attendance
echo should now have proper CSS styling for action buttons.
echo.
pause
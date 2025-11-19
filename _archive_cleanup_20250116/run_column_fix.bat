@echo off
echo Checking daily_tasks table structure...
php check_daily_tasks_structure.php

echo.
echo Adding missing columns...
php fix_missing_columns.php

echo.
echo Verifying final structure...
php check_daily_tasks_structure.php

pause
@echo off
echo Setting up Daily Task Rollover...

REM Create Windows Task Scheduler entry for daily rollover
schtasks /create /tn "Ergon Daily Task Rollover" /tr "php.exe \"c:\laragon\www\ergon\cron\daily_rollover.php\"" /sc daily /st 00:01 /f

if %errorlevel% equ 0 (
    echo Daily rollover task scheduled successfully!
    echo Task will run daily at 12:01 AM
) else (
    echo Failed to schedule task. Please run as administrator.
)

echo.
echo Manual test: You can test the rollover by running:
echo php c:\laragon\www\ergon\cron\daily_rollover.php
echo.
echo Or visit: http://localhost/ergon/cron/daily_rollover.php?manual=1

pause
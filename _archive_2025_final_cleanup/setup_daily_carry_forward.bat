@echo off
echo Setting up Daily Carry Forward Scheduled Task...

REM Create scheduled task to run daily at 12:01 AM
schtasks /create /tn "Ergon Daily Carry Forward" /tr "php \"C:\laragon\www\ergon\cron\daily_carry_forward.php\"" /sc daily /st 00:01 /f

if %errorlevel% equ 0 (
    echo ✓ Scheduled task created successfully
    echo Task will run daily at 12:01 AM
) else (
    echo ✗ Failed to create scheduled task
    echo Run as Administrator if needed
)

echo.
echo Manual test: php cron\daily_carry_forward.php
pause
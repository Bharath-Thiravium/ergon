@echo off
echo 🎮 ERGON Gamification Setup Script
echo ================================

echo.
echo Step 1: Setting up database schemas...
mysql -u root -p ergon_db < database/schema.sql
if %errorlevel% neq 0 (
    echo ❌ Main schema setup failed
    pause
    exit /b 1
)
echo ✅ Main schema loaded

mysql -u root -p ergon_db < database/daily_workflow_schema.sql
if %errorlevel% neq 0 (
    echo ❌ Daily workflow schema setup failed
    pause
    exit /b 1
)
echo ✅ Daily workflow schema loaded

mysql -u root -p ergon_db < database/gamification_schema.sql
if %errorlevel% neq 0 (
    echo ❌ Gamification schema setup failed
    pause
    exit /b 1
)
echo ✅ Gamification schema loaded

echo.
echo Step 2: Loading dummy data...
mysql -u root -p ergon_db < database/dummy_data.sql
if %errorlevel% neq 0 (
    echo ❌ Dummy data loading failed
    pause
    exit /b 1
)
echo ✅ Dummy data loaded

echo.
echo Step 3: Starting web server...
echo Opening gamification test in browser...
start http://localhost/ergon/test_gamification.php

echo.
echo 🎯 Setup Complete!
echo ==================
echo.
echo Test URLs:
echo - Gamification Test: http://localhost/ergon/test_gamification.php
echo - ERGON Login: http://localhost/ergon/login
echo.
echo Test Credentials:
echo - Owner: owner@ergon.local / password
echo - Alice: alice@ergon.test / password  
echo - Bob: bob@ergon.test / password
echo - Carol: carol@ergon.test / password
echo.
pause
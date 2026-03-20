@echo off
echo Checking PostgreSQL Service Status...
sc query postgresql-x64-17

echo.
echo Checking if PostgreSQL is listening on port 5432...
netstat -an | findstr :5432

echo.
echo Testing PHP connection...
php test_local_postgres.php

pause
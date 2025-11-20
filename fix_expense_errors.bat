@echo off
echo ========================================
echo    EXPENSE SYSTEM COMPLETE FIX
echo ========================================
echo.
echo This will fix the expense approval/rejection errors by:
echo 1. Adding missing columns to expenses table
echo 2. Creating accounting tables
echo 3. Setting up default accounts
echo.
echo Errors being fixed:
echo - SQLSTATE[42S22]: Column not found: 1054 Unknown column 'approved_by'
echo - SQLSTATE[42S22]: Column not found: 1054 Unknown column 'journal_entry_id'
echo.
pause

cd /d "c:\laragon\www\ergon"
php fix_expense_system_complete.php

echo.
echo ========================================
echo Fix completed. You can now test at:
echo http://localhost/ergon/expenses
echo ========================================
pause
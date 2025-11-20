@echo off
echo ========================================
echo    EXPENSE COLUMNS FIX
echo ========================================
echo.
echo This will add missing columns to the expenses table:
echo - approved_by (INT NULL)
echo - journal_entry_id (INT NULL)
echo.
pause

cd /d "c:\laragon\www\ergon"
php run_expense_column_fix.php

echo.
echo ========================================
echo Fix completed. Check the output above.
echo ========================================
pause
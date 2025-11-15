@echo off
echo Executing Conservative Cleanup (LIVE)...
echo This will move 40 files to _archive_unused_files folder
echo.
pause
C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe conservative_cleanup.php "C:\laragon\www\ergon" --live
pause
@echo off
echo Running Conservative Cleanup (Dry Run)...
C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe conservative_cleanup.php "C:\laragon\www\ergon"
echo.
echo To perform actual cleanup, run:
echo C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe conservative_cleanup.php "C:\laragon\www\ergon" --live
pause
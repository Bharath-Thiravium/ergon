@echo off
echo Setting up SSH tunnel to VPS PostgreSQL...
echo ==========================================
echo.
echo This will create a secure tunnel from localhost:5432 to VPS:5432
echo.
echo Command to run:
echo ssh -L 5432:localhost:5432 root@72.60.218.167 -N
echo.
echo Instructions:
echo 1. Open a new Command Prompt or PowerShell
echo 2. Run the above SSH command
echo 3. Enter your VPS root password when prompted
echo 4. Keep that terminal open (tunnel stays active)
echo 5. Run this script again to test connection
echo.
echo Alternative (background tunnel):
echo ssh -L 5432:localhost:5432 root@72.60.218.167 -N -f
echo.
pause
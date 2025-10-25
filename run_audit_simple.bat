@echo off
echo Running Ergon Deep Audit...
echo Project: %cd%

REM Check critical files
echo.
echo === FILE STRUCTURE CHECK ===
if exist "app\" (echo ✓ app/ directory found) else (echo ✗ app/ directory missing)
if exist "public\" (echo ✓ public/ directory found) else (echo ✗ public/ directory missing)
if exist "composer.json" (echo ✓ composer.json found) else (echo ✗ composer.json missing)
if exist ".env" (echo ✓ .env found) else (echo ✗ .env missing)
if exist "vendor\" (echo ✓ vendor/ directory found) else (echo ✗ vendor/ directory missing - run composer install)

REM Check for security issues
echo.
echo === SECURITY CHECKS ===
if exist "public\.env" (
    echo ✗ CRITICAL: public/.env found - SECURITY RISK!
) else (
    echo ✓ No .env in public directory
)

if exist "public\uploads\" (
    echo ✓ uploads directory exists
    dir /b "public\uploads\*.php" >nul 2>&1 && (
        echo ✗ WARNING: PHP files found in uploads directory
    ) || (
        echo ✓ No PHP files in uploads directory
    )
) else (
    echo ⚠ uploads directory not found
)

REM Check .env for secrets
echo.
echo === ENVIRONMENT CHECK ===
if exist ".env" (
    findstr /i "DB_PASSWORD DB_USER JWT_SECRET APP_KEY" ".env" >nul 2>&1 && (
        echo ⚠ .env contains sensitive data - ensure not web accessible
    ) || (
        echo ✓ No obvious secrets in .env
    )
)

REM Count PHP files
echo.
echo === PROJECT STATISTICS ===
for /f %%i in ('dir /s /b *.php 2^>nul ^| find /c /v ""') do echo PHP files: %%i
for /f %%i in ('dir /s /b app\*.php 2^>nul ^| find /c /v ""') do echo App PHP files: %%i

echo.
echo === RECOMMENDATIONS ===
echo 1. Ensure .env is not in public/ directory
echo 2. No PHP files should be in uploads/ directory
echo 3. Use htmlspecialchars() for all output
echo 4. Use prepared statements for database queries
echo 5. Implement CSRF protection on forms

echo.
echo Audit completed at %date% %time%
pause
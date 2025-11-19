@echo off
echo 🧹 Fixing CSS Issues...
echo.

echo ✅ Step 1: Auto-fixing Stylelint errors...
call npm run fix:css
if %errorlevel% neq 0 (
    echo ❌ Stylelint auto-fix failed
    pause
    exit /b 1
)

echo ✅ Step 2: Formatting with Prettier...
call npm run format:css
if %errorlevel% neq 0 (
    echo ❌ Prettier formatting failed
    pause
    exit /b 1
)

echo ✅ Step 3: Final lint check...
call npm run lint:css
if %errorlevel% neq 0 (
    echo ❌ Final lint check failed - manual fixes needed
    pause
    exit /b 1
)

echo.
echo 🎉 All CSS issues fixed successfully!
echo 📊 Your CSS is now clean and audit-ready
pause
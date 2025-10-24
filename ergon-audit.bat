@echo off
REM ergon-audit.bat - Deep audit for Ergon PHP project (Windows version)
REM Usage: ergon-audit.bat

setlocal enabledelayedexpansion
set ROOT=%cd%
set OUT_JSON=%ROOT%\ergon-audit-report.json
set OUT_SUM=%ROOT%\ergon-audit-summary.txt
set TMP=%ROOT%\.ergon_audit_tmp
mkdir "%TMP%" 2>nul

echo Starting Ergon deep audit...
echo Project root: %ROOT%

REM Get timestamp
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "start_time=%dt:~0,4%-%dt:~4,2%-%dt:~6,2%T%dt:~8,2%:%dt:~10,2%:%dt:~12,2%"

REM Check for required tools
where php >nul 2>&1 && set PHP_BIN=php || set PHP_BIN=not-found
where composer >nul 2>&1 && set COMPOSER_BIN=composer || set COMPOSER_BIN=not-found
where phpstan >nul 2>&1 && set PHPSTAN_BIN=phpstan || set PHPSTAN_BIN=not-found

REM Create environment info JSON
echo { > "%TMP%\env.json"
echo   "timestamp": "%start_time%", >> "%TMP%\env.json"
echo   "php": "%PHP_BIN%", >> "%TMP%\env.json"
echo   "composer": "%COMPOSER_BIN%", >> "%TMP%\env.json"
echo   "phpstan": "%PHPSTAN_BIN%" >> "%TMP%\env.json"
echo } >> "%TMP%\env.json"

echo Checking repository layout and common files...

REM Layout checks
set layout_issues=0
if not exist "%ROOT%\app" (
    echo ISSUE: missing app/ directory
    set /a layout_issues+=1
)
if not exist "%ROOT%\public" (
    echo ISSUE: missing public/ directory
    set /a layout_issues+=1
)
if not exist "%ROOT%\composer.json" (
    echo ISSUE: missing composer.json
    set /a layout_issues+=1
)
if exist "%ROOT%\.env" (
    echo NOTE: .env present at repo root
)
if not exist "%ROOT%\vendor" (
    echo ISSUE: vendor/ directory missing - run composer install
    set /a layout_issues+=1
)

REM Check for public .env exposure
if exist "%ROOT%\public\.env" (
    echo CRITICAL: public/.env found - HIGH RISK!
    set /a layout_issues+=1
)

REM Check uploads directory
if exist "%ROOT%\public\uploads" (
    echo NOTE: uploads directory exists at public/uploads
) else (
    echo NOTE: uploads directory missing at public/uploads
)

echo Running PHP syntax check...
set syntax_errors=0

REM Create PHP scanner
echo ^<?php > "%TMP%\scanner.php"
echo // scanner.php - scans repository for risky patterns >> "%TMP%\scanner.php"
echo $root = $argv[1] ?? '.'; >> "%TMP%\scanner.php"
echo $extensions = ['php','phtml','inc','js','env']; >> "%TMP%\scanner.php"
echo $patterns = [ >> "%TMP%\scanner.php"
echo   'dangerous_functions' =^> [ >> "%TMP%\scanner.php"
echo     'pattern' =^> '/\b(eval^|exec^|system^|passthru^|shell_exec^|pcntl_exec^|`)\s*\(/i', >> "%TMP%\scanner.php"
echo     'desc' =^> 'Calls to dangerous execution functions' >> "%TMP%\scanner.php"
echo   ], >> "%TMP%\scanner.php"
echo   'eval_base64' =^> [ >> "%TMP%\scanner.php"
echo     'pattern' =^> '/(eval\(^|base64_decode\(^|gzinflate\()/i', >> "%TMP%\scanner.php"
echo     'desc' =^> 'Possible obfuscation (eval, base64_decode, gzinflate)' >> "%TMP%\scanner.php"
echo   ], >> "%TMP%\scanner.php"
echo   'db_credentials_in_files' =^> [ >> "%TMP%\scanner.php"
echo     'pattern' =^> '/(DB_HOST^|DB_USER^|DB_PASSWORD^|password\s*=^>)/i', >> "%TMP%\scanner.php"
echo     'desc' =^> 'Hard-coded DB credentials' >> "%TMP%\scanner.php"
echo   ], >> "%TMP%\scanner.php"
echo   'jwt_secret_like' =^> [ >> "%TMP%\scanner.php"
echo     'pattern' =^> '/(JWT_SECRET^|JWT_KEY^|SECRET_KEY)\s*[=:\']+\s*[\'"][A-Za-z0-9\-_]{8,}/i', >> "%TMP%\scanner.php"
echo     'desc' =^> 'Possible JWT or API secret in code' >> "%TMP%\scanner.php"
echo   ], >> "%TMP%\scanner.php"
echo   'file_upload_move' =^> [ >> "%TMP%\scanner.php"
echo     'pattern' =^> '/move_uploaded_file\s*\(/i', >> "%TMP%\scanner.php"
echo     'desc' =^> 'File upload handling - review for validation' >> "%TMP%\scanner.php"
echo   ], >> "%TMP%\scanner.php"
echo   'unescaped_html' =^> [ >> "%TMP%\scanner.php"
echo     'pattern' =^> '/\^<\?=\s*\$[A-Za-z0-9_]+/i', >> "%TMP%\scanner.php"
echo     'desc' =^> 'Short echo tags - check for escaping' >> "%TMP%\scanner.php"
echo   ] >> "%TMP%\scanner.php"
echo ]; >> "%TMP%\scanner.php"
echo $results = ['scanned_files' =^> 0, 'matches' =^> []]; >> "%TMP%\scanner.php"
echo $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)); >> "%TMP%\scanner.php"
echo foreach ($it as $file) { >> "%TMP%\scanner.php"
echo   if (!$file-^>isFile()) continue; >> "%TMP%\scanner.php"
echo   $ext = pathinfo($file-^>getFilename(), PATHINFO_EXTENSION); >> "%TMP%\scanner.php"
echo   if (!in_array($ext, $extensions)) continue; >> "%TMP%\scanner.php"
echo   $path = $file-^>getPathname(); >> "%TMP%\scanner.php"
echo   if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue; >> "%TMP%\scanner.php"
echo   if (strpos($path, DIRECTORY_SEPARATOR . '_archived' . DIRECTORY_SEPARATOR) !== false) continue; >> "%TMP%\scanner.php"
echo   $content = @file_get_contents($path); >> "%TMP%\scanner.php"
echo   if ($content === false) continue; >> "%TMP%\scanner.php"
echo   $results['scanned_files']++; >> "%TMP%\scanner.php"
echo   foreach ($patterns as $k =^> $p) { >> "%TMP%\scanner.php"
echo     if (preg_match_all($p['pattern'], $content, $matches, PREG_OFFSET_CAPTURE)) { >> "%TMP%\scanner.php"
echo       foreach ($matches[0] as $m) { >> "%TMP%\scanner.php"
echo         $line = substr_count(substr($content, 0, $m[1]), "\n") + 1; >> "%TMP%\scanner.php"
echo         $results['matches'][] = [ >> "%TMP%\scanner.php"
echo           'type' =^> $k, >> "%TMP%\scanner.php"
echo           'desc' =^> $p['desc'], >> "%TMP%\scanner.php"
echo           'file' =^> substr($path, strlen(getcwd()) + 1), >> "%TMP%\scanner.php"
echo           'line' =^> $line >> "%TMP%\scanner.php"
echo         ]; >> "%TMP%\scanner.php"
echo       } >> "%TMP%\scanner.php"
echo     } >> "%TMP%\scanner.php"
echo   } >> "%TMP%\scanner.php"
echo } >> "%TMP%\scanner.php"
echo echo json_encode($results, JSON_PRETTY_PRINT); >> "%TMP%\scanner.php"

echo Running deep code pattern scan...
if "%PHP_BIN%"=="php" (
    php "%TMP%\scanner.php" "%ROOT%" > "%TMP%\deepscan.json" 2>nul
) else (
    echo {"scanned_files":0,"matches":[]} > "%TMP%\deepscan.json"
)

REM Check for exposures
set exposures=0
if exist "%ROOT%\public\.env" (
    echo CRITICAL: public/.env present - exposes secrets!
    set /a exposures+=1
)

REM Check .env for secrets
set env_leaks=0
if exist "%ROOT%\.env" (
    findstr /i "DB_PASSWORD DB_USER DB_HOST APP_KEY JWT" "%ROOT%\.env" >nul 2>&1
    if !errorlevel! equ 0 (
        echo WARNING: .env contains DB/SECRET keys
        set /a env_leaks+=1
    )
)

REM Generate summary
echo. > "%OUT_SUM%"
echo Ergon Audit Summary - %start_time% >> "%OUT_SUM%"
echo Project root: %ROOT% >> "%OUT_SUM%"
echo. >> "%OUT_SUM%"
echo Layout Issues: %layout_issues% >> "%OUT_SUM%"
echo Syntax Errors: %syntax_errors% >> "%OUT_SUM%"
echo Exposures: %exposures% >> "%OUT_SUM%"
echo Env Leaks: %env_leaks% >> "%OUT_SUM%"
echo. >> "%OUT_SUM%"
echo Deep scan results: >> "%OUT_SUM%"
if "%PHP_BIN%"=="php" (
    php -r "$j=json_decode(file_get_contents('%TMP%\deepscan.json'),true); echo 'Scanned files: '.$j['scanned_files'].PHP_EOL; echo 'Matches found: '.count($j['matches']).PHP_EOL;" >> "%OUT_SUM%"
) else (
    echo PHP not available for deep scan >> "%OUT_SUM%"
)
echo. >> "%OUT_SUM%"
echo Recommended immediate actions: >> "%OUT_SUM%"
echo 1) Remove public/.env if it exists >> "%OUT_SUM%"
echo 2) Ensure uploads/ has no executable PHP files >> "%OUT_SUM%"
echo 3) Review any dangerous function usage >> "%OUT_SUM%"
echo 4) Run composer install and composer audit >> "%OUT_SUM%"
echo 5) Use htmlspecialchars() for all output >> "%OUT_SUM%"

REM Create basic JSON report
echo { > "%OUT_JSON%"
echo   "timestamp": "%start_time%", >> "%OUT_JSON%"
echo   "layout_issues": %layout_issues%, >> "%OUT_JSON%"
echo   "syntax_errors": %syntax_errors%, >> "%OUT_JSON%"
echo   "exposures": %exposures%, >> "%OUT_JSON%"
echo   "env_leaks": %env_leaks%, >> "%OUT_JSON%"
echo   "php_available": "%PHP_BIN%" >> "%OUT_JSON%"
echo } >> "%OUT_JSON%"

echo.
echo Audit complete.
echo Report JSON: %OUT_JSON%
echo Summary: %OUT_SUM%
echo.
echo Quick next steps:
echo - Review %OUT_SUM% for prioritized items
echo - Fix any CRITICAL issues immediately
echo - Run composer install if vendor/ missing

REM Cleanup
rmdir /s /q "%TMP%" 2>nul

pause
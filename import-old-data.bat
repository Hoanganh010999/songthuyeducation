@echo off
REM ============================================
REM IMPORT OLD DATABASE SCRIPT
REM ============================================

echo.
echo ========================================
echo   IMPORT OLD DATABASE TO NEW SYSTEM
echo ========================================
echo.

REM Check if in correct directory
if not exist "artisan" (
    echo ERROR: Please run this script from the project root directory!
    pause
    exit /b 1
)

echo Step 1: Backing up current database...
php artisan db:backup
if errorlevel 1 (
    echo ERROR: Backup failed!
    pause
    exit /b 1
)
echo [DONE]
echo.

echo Step 2: Wiping old data (preserving admin)...
echo WARNING: This will DELETE ALL DATA except admin@songthuy.edu.vn
echo.
set /p confirm="Type 'YES' to continue: "
if /i not "%confirm%"=="YES" (
    echo Operation cancelled.
    pause
    exit /b 0
)

php artisan db:wipe-preserve-admin --force
if errorlevel 1 (
    echo ERROR: Wipe failed!
    pause
    exit /b 1
)
echo [DONE]
echo.

echo Step 3: Running dry-run import (preview)...
php artisan import:old-database old_database --dry-run
if errorlevel 1 (
    echo ERROR: Dry-run failed!
    pause
    exit /b 1
)
echo [DONE]
echo.

echo.
echo Preview completed. Check the output above.
echo.
set /p confirm2="Continue with actual import? (YES/NO): "
if /i not "%confirm2%"=="YES" (
    echo Operation cancelled.
    pause
    exit /b 0
)

echo.
echo Step 4: Importing data (ACTUAL)...
php artisan import:old-database old_database
if errorlevel 1 (
    echo ERROR: Import failed!
    pause
    exit /b 1
)
echo [DONE]
echo.

echo ========================================
echo   IMPORT COMPLETED SUCCESSFULLY!
echo ========================================
echo.
echo Admin account:
echo   Email: admin@songthuy.edu.vn
echo   Password: 2K3h0o1n9g@
echo.
echo Default passwords for imported users: 123456
echo.
echo Please verify the import by checking the database.
echo.
pause


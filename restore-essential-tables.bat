@echo off
REM ============================================
REM RESTORE ESSENTIAL TABLES FROM BACKUP
REM ============================================

echo.
echo ========================================
echo   RESTORE PERMISSIONS, SETTINGS, etc
echo ========================================
echo.

set BACKUP_FILE=school_database_backup.sql
set DB_NAME=school_db
set MYSQL_PATH=C:\xampp\mysql\bin\mysql.exe

if not exist "%BACKUP_FILE%" (
    echo ERROR: Backup file not found: %BACKUP_FILE%
    pause
    exit /b 1
)

echo Found backup: %BACKUP_FILE%
echo.
echo This will restore:
echo   - permissions
echo   - permission_role
echo   - settings
echo   - translations  
echo   - languages
echo.
echo Your imported data (students, classes, etc) will NOT be affected!
echo.
set /p confirm="Continue? (YES/NO): "
if /i not "%confirm%"=="YES" (
    echo Operation cancelled.
    pause
    exit /b 0
)

echo.
echo Step 1: Creating temporary SQL files...

REM Extract CREATE TABLE and INSERT for each table
echo Extracting permissions...
findstr /C:"CREATE TABLE `permissions`" /C:"INSERT INTO `permissions`" "%BACKUP_FILE%" > temp_permissions.sql 2>nul

echo Extracting permission_role...
findstr /C:"CREATE TABLE `permission_role`" /C:"INSERT INTO `permission_role`" "%BACKUP_FILE%" > temp_permission_role.sql 2>nul

echo Extracting settings...
findstr /C:"CREATE TABLE `settings`" /C:"INSERT INTO `settings`" "%BACKUP_FILE%" > temp_settings.sql 2>nul

echo Extracting translations...
findstr /C:"CREATE TABLE `translations`" /C:"INSERT INTO `translations`" "%BACKUP_FILE%" > temp_translations.sql 2>nul

echo Extracting languages...
findstr /C:"CREATE TABLE `languages`" /C:"INSERT INTO `languages`" "%BACKUP_FILE%" > temp_languages.sql 2>nul

echo.
echo Step 2: Dropping old tables...
"%MYSQL_PATH%" -u root %DB_NAME% -e "DROP TABLE IF EXISTS permissions; DROP TABLE IF EXISTS permission_role; DROP TABLE IF EXISTS settings; DROP TABLE IF EXISTS translations; DROP TABLE IF EXISTS languages;"

echo.
echo Step 3: Restoring tables...

REM Note: This simple extraction may not work perfectly with SQL dumps
REM Better approach below using PHP or manual extraction

echo.
echo ⚠️  NOTICE: Windows findstr cannot reliably extract SQL tables.
echo.
echo 📝 RECOMMENDED APPROACH:
echo 1. Open phpMyAdmin (http://localhost/phpmyadmin)
echo 2. Select database: school_db
echo 3. Click "Import" tab
echo 4. Choose file: school_database_backup.sql
echo 5. Under "Import", check "Partial import"
echo 6. Select ONLY these tables:
echo    ✓ permissions
echo    ✓ permission_role
echo    ✓ settings
echo    ✓ translations
echo    ✓ languages
echo 7. Click "Go"
echo.
echo This will restore only these tables without affecting your imported data!
echo.

REM Cleanup
del temp_*.sql 2>nul

pause


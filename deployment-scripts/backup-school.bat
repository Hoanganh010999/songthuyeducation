@echo off
REM ============================================================================
REM BACKUP SCRIPT CHO DỰ ÁN SCHOOL (WINDOWS VERSION)
REM
REM Script này sẽ:
REM 1. Export database
REM 2. Đóng gói source code
REM 3. Tạo file .zip để upload lên VPS
REM
REM Sử dụng: backup-school.bat
REM ============================================================================

echo ================================
echo   BACKUP DỰ ÁN SCHOOL (LARAVEL)
echo ================================
echo.

REM Lấy timestamp
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set TIMESTAMP=%datetime:~0,8%_%datetime:~8,6%
set BACKUP_DIR=school-backup-%TIMESTAMP%
set PROJECT_ROOT=c:\xampp\htdocs\school

echo [1/6] Tao thu muc backup...
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

echo [2/6] Export database...
REM Đọc thông tin database từ .env
for /f "tokens=1,2 delims==" %%a in ('findstr /r "^DB_" "%PROJECT_ROOT%\.env"') do (
    if "%%a"=="DB_CONNECTION" set DB_CONNECTION=%%b
    if "%%a"=="DB_DATABASE" set DB_DATABASE=%%b
    if "%%a"=="DB_USERNAME" set DB_USERNAME=%%b
    if "%%a"=="DB_PASSWORD" set DB_PASSWORD=%%b
)

REM Export MySQL database
if "%DB_CONNECTION%"=="mysql" (
    echo   Exporting MySQL database: %DB_DATABASE%
    c:\xampp\mysql\bin\mysqldump.exe -u%DB_USERNAME% -p%DB_PASSWORD% %DB_DATABASE% > "%BACKUP_DIR%\database.sql"
    echo   Database exported!
) else if "%DB_CONNECTION%"=="sqlite" (
    echo   Copying SQLite database...
    if exist "%PROJECT_ROOT%\database\database.sqlite" (
        copy "%PROJECT_ROOT%\database\database.sqlite" "%BACKUP_DIR%\database.sqlite"
        echo   Database copied!
    )
)

echo [3/6] Dong goi source code...
REM Copy essential folders
echo   Copying app...
xcopy /E /I /Q "%PROJECT_ROOT%\app" "%BACKUP_DIR%\app"
echo   Copying bootstrap...
xcopy /E /I /Q "%PROJECT_ROOT%\bootstrap" "%BACKUP_DIR%\bootstrap"
echo   Copying config...
xcopy /E /I /Q "%PROJECT_ROOT%\config" "%BACKUP_DIR%\config"
echo   Copying database...
xcopy /E /I /Q "%PROJECT_ROOT%\database\migrations" "%BACKUP_DIR%\migrations"
if exist "%PROJECT_ROOT%\database\seeders" xcopy /E /I /Q "%PROJECT_ROOT%\database\seeders" "%BACKUP_DIR%\seeders"
echo   Copying public...
xcopy /E /I /Q "%PROJECT_ROOT%\public" "%BACKUP_DIR%\public"
echo   Copying resources...
xcopy /E /I /Q "%PROJECT_ROOT%\resources" "%BACKUP_DIR%\resources"
echo   Copying routes...
xcopy /E /I /Q "%PROJECT_ROOT%\routes" "%BACKUP_DIR%\routes"
echo   Copying storage...
xcopy /E /I /Q "%PROJECT_ROOT%\storage" "%BACKUP_DIR%\storage"

REM Copy Zalo service
echo   Copying zalo-service...
xcopy /E /I /Q "%PROJECT_ROOT%\zalo-service" "%BACKUP_DIR%\zalo-service"
REM Remove node_modules
if exist "%BACKUP_DIR%\zalo-service\node_modules" (
    echo   Removing zalo-service node_modules...
    rmdir /S /Q "%BACKUP_DIR%\zalo-service\node_modules"
)

echo [4/6] Copying config files...
copy "%PROJECT_ROOT%\composer.json" "%BACKUP_DIR%\" >nul
copy "%PROJECT_ROOT%\package.json" "%BACKUP_DIR%\" >nul
if exist "%PROJECT_ROOT%\composer.lock" copy "%PROJECT_ROOT%\composer.lock" "%BACKUP_DIR%\" >nul
if exist "%PROJECT_ROOT%\package-lock.json" copy "%PROJECT_ROOT%\package-lock.json" "%BACKUP_DIR%\" >nul
if exist "%PROJECT_ROOT%\vite.config.js" copy "%PROJECT_ROOT%\vite.config.js" "%BACKUP_DIR%\" >nul
if exist "%PROJECT_ROOT%\tailwind.config.js" copy "%PROJECT_ROOT%\tailwind.config.js" "%BACKUP_DIR%\" >nul
if exist "%PROJECT_ROOT%\postcss.config.js" copy "%PROJECT_ROOT%\postcss.config.js" "%BACKUP_DIR%\" >nul
copy "%PROJECT_ROOT%\.env.example" "%BACKUP_DIR%\" >nul
copy "%PROJECT_ROOT%\artisan" "%BACKUP_DIR%\" >nul

echo [5/6] Copying documentation...
if exist "%PROJECT_ROOT%\README.md" copy "%PROJECT_ROOT%\README.md" "%BACKUP_DIR%\" >nul
if exist "%PROJECT_ROOT%\DEPLOYMENT_GUIDE.md" copy "%PROJECT_ROOT%\DEPLOYMENT_GUIDE.md" "%BACKUP_DIR%\" >nul
if exist "%PROJECT_ROOT%\DEPLOYMENT_QUICKSTART.md" copy "%PROJECT_ROOT%\DEPLOYMENT_QUICKSTART.md" "%BACKUP_DIR%\" >nul
if exist "%PROJECT_ROOT%\deployment-scripts" xcopy /E /I /Q "%PROJECT_ROOT%\deployment-scripts" "%BACKUP_DIR%\deployment-scripts"

echo [6/6] Nen thanh file ZIP...
REM Sử dụng PowerShell để nén (Windows 10+)
powershell -command "Compress-Archive -Path '%BACKUP_DIR%' -DestinationPath '%BACKUP_DIR%.zip' -Force"

if exist "%BACKUP_DIR%.zip" (
    echo   Cleaning up...
    rmdir /S /Q "%BACKUP_DIR%"
    echo.
    echo ================================
    echo   BACKUP HOAN TAT!
    echo ================================
    echo.
    echo File backup: %BACKUP_DIR%.zip
    echo Vi tri: %CD%\%BACKUP_DIR%.zip
    echo.
    echo Buoc tiep theo:
    echo   1. Upload file nay len VPS
    echo   2. Chay script deploy-vps.sh tren VPS
    echo.
) else (
    echo LOI: Khong the nen file!
    echo Vui long kiem tra PowerShell co san hay khong.
)

pause

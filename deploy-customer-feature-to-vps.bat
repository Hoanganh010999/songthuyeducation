@echo off
REM =====================================================================
REM Deploy Customer View All Feature to VPS
REM =====================================================================
REM Author: Claude AI Assistant
REM Date: 2025-11-23
REM Purpose: Deploy customer view_all permission and zalo unread count fix
REM =====================================================================

setlocal EnableDelayedExpansion

echo.
echo ========================================
echo  DEPLOY CUSTOMER FEATURE TO VPS
echo ========================================
echo.

REM Configuration
set SSH_KEY=~/.ssh/vps_key
set SSH_PORT=26266
set SSH_HOST=root@103.121.90.143
set REMOTE_PATH=/var/www/school
set LOCAL_PATH=c:/xampp/htdocs/school

echo [1/8] Checking local files...
if not exist "%LOCAL_PATH%\database\seeders\CustomersViewAllPermissionSeeder.php" (
    echo ERROR: CustomersViewAllPermissionSeeder.php not found!
    pause
    exit /b 1
)
echo   ✓ Seeder file found

echo.
echo [2/8] Creating backup on VPS...
ssh -i %SSH_KEY% -p %SSH_PORT% %SSH_HOST% "cd %REMOTE_PATH% && mysqldump -u root -p'Kh0ngbiet@' school_db > backup_before_customer_feature_$(date +%%Y%%m%%d_%%H%%M%%S).sql && echo '✓ Database backed up'"

echo.
echo [3/8] Backing up code on VPS...
ssh -i %SSH_KEY% -p %SSH_PORT% %SSH_HOST% "cd %REMOTE_PATH% && tar -czf backup_code_$(date +%%Y%%m%%d_%%H%%M%%S).tar.gz app routes resources database && echo '✓ Code backed up'"

echo.
echo [4/8] Uploading seeder file...
scp -i %SSH_KEY% -P %SSH_PORT% "%LOCAL_PATH%\database\seeders\CustomersViewAllPermissionSeeder.php" %SSH_HOST%:%REMOTE_PATH%/database/seeders/
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to upload seeder!
    pause
    exit /b 1
)
echo   ✓ Seeder uploaded

echo.
echo [5/8] Uploading Customer Model...
scp -i %SSH_KEY% -P %SSH_PORT% "%LOCAL_PATH%\app\Models\Customer.php" %SSH_HOST%:%REMOTE_PATH%/app/Models/
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to upload Customer.php!
    pause
    exit /b 1
)
echo   ✓ Customer.php uploaded

echo.
echo [6/8] Uploading ZaloController...
scp -i %SSH_KEY% -P %SSH_PORT% "%LOCAL_PATH%\app\Http\Controllers\Api\ZaloController.php" %SSH_HOST%:%REMOTE_PATH%/app/Http/Controllers/Api/
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to upload ZaloController!
    pause
    exit /b 1
)
echo   ✓ ZaloController.php uploaded

echo.
echo [7/8] Uploading routes...
scp -i %SSH_KEY% -P %SSH_PORT% "%LOCAL_PATH%\routes\api.php" %SSH_HOST%:%REMOTE_PATH%/routes/
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to upload api.php!
    pause
    exit /b 1
)
echo   ✓ api.php uploaded

echo.
echo [8/8] Uploading DashboardLayout...
scp -i %SSH_KEY% -P %SSH_PORT% "%LOCAL_PATH%\resources\js\layouts\DashboardLayout.vue" %SSH_HOST%:%REMOTE_PATH%/resources/js/layouts/
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to upload DashboardLayout.vue!
    pause
    exit /b 1
)
echo   ✓ DashboardLayout.vue uploaded

echo.
echo ========================================
echo  FILES UPLOADED SUCCESSFULLY
echo ========================================
echo.
echo Now running post-deployment tasks...
echo.

echo [BACKEND] Running seeder on VPS...
ssh -i %SSH_KEY% -p %SSH_PORT% %SSH_HOST% "cd %REMOTE_PATH% && php artisan db:seed --class=CustomersViewAllPermissionSeeder"
if %ERRORLEVEL% NEQ 0 (
    echo WARNING: Seeder failed! Check VPS logs.
) else (
    echo   ✓ Seeder completed
)

echo.
echo [BACKEND] Clearing caches...
ssh -i %SSH_KEY% -p %SSH_PORT% %SSH_HOST% "cd %REMOTE_PATH% && php artisan config:clear && php artisan route:clear && php artisan cache:clear"
echo   ✓ Caches cleared

echo.
echo [FRONTEND] Building assets on VPS...
echo This may take 1-2 minutes...
ssh -i %SSH_KEY% -p %SSH_PORT% %SSH_HOST% "cd %REMOTE_PATH% && npm run build"
if %ERRORLEVEL% NEQ 0 (
    echo WARNING: Build failed! Check VPS logs.
) else (
    echo   ✓ Build completed
)

echo.
echo [VERIFY] Checking permission in database...
ssh -i %SSH_KEY% -p %SSH_PORT% %SSH_HOST% "mysql -u root -p'Kh0ngbiet@' school_db -e \"SELECT name, display_name FROM permissions WHERE name='customers.view_all'\""

echo.
echo ========================================
echo  DEPLOYMENT COMPLETED!
echo ========================================
echo.
echo Next steps:
echo 1. Test the application: https://admin.songthuy.edu.vn
echo 2. Check console logs (F12) for errors
echo 3. Verify badge on Sales (Customers) icon
echo 4. Test with different user roles
echo.
echo If there are issues, rollback with:
echo   ssh -i %SSH_KEY% -p %SSH_PORT% %SSH_HOST% "cd %REMOTE_PATH% && ls -lt backup_*.sql backup_*.tar.gz"
echo.
pause

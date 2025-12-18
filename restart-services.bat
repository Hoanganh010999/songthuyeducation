@echo off
echo ========================================
echo  RESTART LARAVEL AND ZALO SERVICES
echo ========================================
echo.

echo [1/4] Killing old Laravel processes...
taskkill /F /PID 157104 2>nul
taskkill /F /PID 181644 2>nul
timeout /t 2 /nobreak >nul

echo [2/4] Killing old zalo-service processes...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :3001 ^| findstr LISTENING') do taskkill /F /PID %%a 2>nul
timeout /t 2 /nobreak >nul

echo [3/4] Starting Laravel server...
cd /d c:\xampp\htdocs\school
start "Laravel Server" cmd /k "php artisan serve"
timeout /t 3 /nobreak >nul

echo [4/4] Starting zalo-service...
cd /d c:\xampp\htdocs\school\zalo-service
start "Zalo Service" cmd /k "npm start"

echo.
echo ========================================
echo  DONE! Services are restarting...
echo ========================================
echo.
echo Laravel: http://127.0.0.1:8000
echo Zalo:    http://127.0.0.1:3001
echo.
pause

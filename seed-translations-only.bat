@echo off
echo ========================================
echo SEED TRANSLATIONS ONLY (QUICK FIX)
echo ========================================
echo.

echo Seeding Branches translations...
php artisan db:seed --class=BranchTranslationsSeeder
echo.

echo Seeding Complete Sales translations...
php artisan db:seed --class=CompleteSalesTranslations  
echo.

echo Seeding Change Password translations...
php artisan db:seed --class=ChangePasswordTranslations
echo.

echo Clearing cache...
php artisan cache:clear
php artisan config:clear
echo.

echo ========================================
echo ✅ DONE! Translations updated
echo ========================================
echo.
echo Now refresh your browser (Ctrl + Shift + R)
echo.

pause


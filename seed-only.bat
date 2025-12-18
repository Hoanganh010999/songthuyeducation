@echo off
echo ========================================
echo SEED DATABASE (without dropping)
echo ========================================
echo.

echo Seeding all data...
php artisan db:seed --class=CompleteDatabaseSeeder
echo.

echo ========================================
echo ✅ DONE! Database has been seeded
echo ========================================
echo.

pause


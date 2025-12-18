@echo off
REM ============================================
REM IMPORT OLD DATABASE WITHOUT WIPING
REM ============================================

echo.
echo ========================================
echo   IMPORT OLD DATA (NO WIPE)
echo ========================================
echo.

echo This will:
echo   1. Keep ALL existing data
echo   2. Add students, classes, attendance from CSV
echo   3. Preserve settings, translations, permissions
echo.

set /p confirm="Continue? (YES/NO): "
if /i not "%confirm%"=="YES" (
    echo Operation cancelled.
    pause
    exit /b 0
)

echo.
echo Step 1: Creating admin account if missing...
php artisan tinker --execute="$admin = \App\Models\User::firstOrCreate(['email' => 'admin@songthuy.edu.vn'], ['name' => 'Super Admin', 'password' => \Illuminate\Support\Facades\Hash::make('2K3h0o1n9g@'), 'email_verified_at' => now()]); $role = \App\Models\Role::where('name', 'super-admin')->first(); if ($role && !$admin->roles()->where('role_id', $role->id)->exists()) { $admin->roles()->attach($role->id); } echo 'Admin created/verified';"

echo.
echo Step 2: Importing CSV data...
php artisan import:old-database old_database

echo.
echo ========================================
echo   IMPORT COMPLETE!
echo ========================================
echo.
echo Admin: admin@songthuy.edu.vn / 2K3h0o1n9g@
echo.
pause


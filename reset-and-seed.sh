#!/bin/bash

echo "========================================"
echo "RESET DATABASE AND SEED ALL DATA"
echo "========================================"
echo ""

echo "[1/4] Dropping all tables..."
php artisan db:wipe --force
echo ""

echo "[2/4] Running migrations..."
php artisan migrate --force
echo ""

echo "[3/4] Seeding all data (this may take a while)..."
php artisan db:seed --class=CompleteDatabaseSeeder
echo ""

echo "[4/4] Clearing cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo ""

echo "========================================"
echo "✅ DONE! Database has been reset and seeded"
echo "========================================"
echo ""

echo "Test accounts:"
echo "- Super Admin: admin@example.com / password"
echo "- Admin HN: admin.hn@example.com / password"
echo "- Manager: manager.multi@example.com / password"
echo "- Staff: staff.dn@example.com / password"
echo "- User: user.hcm@example.com / password"
echo ""


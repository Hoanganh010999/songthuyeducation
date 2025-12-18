╔══════════════════════════════════════════════════════════════╗
║                                                              ║
║   🎯 DATABASE SEEDING - QUICK START                         ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝

📋 ĐÃ TẠO:
  ✅ CompleteDatabaseSeeder.php - Master seeder (56 seeders)
  ✅ reset-and-seed.bat - Script reset & seed (Windows)
  ✅ reset-and-seed.sh - Script reset & seed (Linux/Mac)
  ✅ seed-only.bat - Chỉ seed (không xóa DB)
  ✅ ChangePasswordTranslations.php - Translations mới
  ✅ 2 files hướng dẫn chi tiết

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🚀 CHẠY NGAY (3 CÁCH):

Cách 1 - NHANH NHẤT (Windows):
  > Double click: reset-and-seed.bat
  
Cách 2 - Terminal (Windows):
  > reset-and-seed.bat
  
Cách 3 - Manual:
  > php artisan db:wipe --force
  > php artisan migrate --force
  > php artisan db:seed --class=CompleteDatabaseSeeder
  > php artisan cache:clear

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📊 SẼ SEED:
  • 2 Languages (en, vi)
  • 500+ Translations (28 seeders)
  • 100+ Permissions (13 seeders)
  • 8 Roles
  • 3 Branches
  • 5 Test Users
  • Sample Data (teachers, customers, classes, students...)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

👥 TEST ACCOUNTS (sau khi seed):
  Email: admin@example.com        Password: password  (Super Admin)
  Email: admin.hn@example.com     Password: password  (Admin HN)
  Email: manager.multi@example.com Password: password (Manager)
  Email: staff.dn@example.com     Password: password  (Staff)
  Email: user.hcm@example.com     Password: password  (User)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⏱️ THỜI GIAN: Khoảng 2-3 phút

📖 CHI TIẾT: Xem file DATABASE_SEEDING_GUIDE.md

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✨ SAU KHI SEED XONG:
  1. Refresh browser: Ctrl + Shift + R
  2. Clear cache: php artisan optimize:clear
  3. Test login: http://localhost/auth/login
  4. Test reset password modal: Click icon chìa khóa trong Users
  5. Test change password: Click avatar → Đổi mật khẩu

╔══════════════════════════════════════════════════════════════╗
║  💡 TIP: Backup database trước khi chạy!                     ║
╚══════════════════════════════════════════════════════════════╝


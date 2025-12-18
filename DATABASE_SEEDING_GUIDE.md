# 📚 DATABASE SEEDING GUIDE

Hướng dẫn seed database hoàn chỉnh cho hệ thống School Management

---

## 🚀 QUICK START

### Cách 1: Sử dụng Script (Khuyến nghị)

**Windows:**
```bash
# Reset toàn bộ database và seed lại từ đầu
reset-and-seed.bat

# Chỉ seed thêm data (không xóa database)
seed-only.bat
```

**Linux/Mac:**
```bash
chmod +x reset-and-seed.sh
./reset-and-seed.sh
```

---

### Cách 2: Chạy Manual

```bash
# Bước 1: Xóa toàn bộ tables
php artisan db:wipe --force

# Bước 2: Chạy migrations
php artisan migrate --force

# Bước 3: Seed toàn bộ data
php artisan db:seed --class=CompleteDatabaseSeeder

# Bước 4: Clear cache
php artisan cache:clear
php artisan config:clear
```

---

## 📋 NỘI DUNG ĐƯỢC SEED

### 1. **Languages & Translations**
- English (en) và Tiếng Việt (vi)
- 27 file translations covering:
  - Core: Customers, Branches, Settings, Swal
  - Modules: Sales, Calendar, HR, Quality, Accounting, Holidays
  - UI: Sidebar, User Menu, Reset Password

### 2. **Roles & Permissions**
- 13 file permissions covering:
  - Base: super-admin, admin, manager, staff, user, teacher, parent, student
  - Modules: HR, Sales, Quality, Classes, Subjects, Calendar, Accounting, Holidays

### 3. **Master Data**
- Branches (3 chi nhánh: Hà Nội, TP.HCM, Đà Nẵng)
- Positions & Job Titles
- Customer Settings (Interaction Types, Results, Sources)
- Products, Vouchers, Campaigns

### 4. **Sample Data**
- Teachers (10 giáo viên mẫu)
- Customers (15 khách hàng mẫu)
- Subjects & Syllabus (IELTS courses)
- Classes (5 lớp mẫu)
- Students (20 học viên mẫu)
- Calendar Events
- Accounting Transactions

### 5. **Test Users**
| Role | Email | Password | Branches |
|------|-------|----------|----------|
| Super Admin | admin@example.com | password | All (HN primary) |
| Admin HN | admin.hn@example.com | password | Hà Nội only |
| Manager | manager.multi@example.com | password | HCM (primary), DN |
| Staff | staff.dn@example.com | password | Đà Nẵng only |
| User | user.hcm@example.com | password | TP.HCM only |

---

## 🔧 TROUBLESHOOTING

### Lỗi "Class not found"
```bash
composer dump-autoload
php artisan optimize:clear
```

### Lỗi "Database connection"
- Kiểm tra file `.env`
- Đảm bảo MySQL/SQLite đang chạy

### Lỗi "Foreign key constraint"
```bash
# Xóa toàn bộ và chạy lại
php artisan db:wipe --force
php artisan migrate:fresh --seed --seeder=CompleteDatabaseSeeder
```

### Muốn seed chỉ một phần
```bash
# Chỉ seed translations
php artisan db:seed --class=CustomersTranslationsSeeder

# Chỉ seed permissions
php artisan db:seed --class=RolePermissionSeeder
```

---

## 📝 CÁC FILE SEEDER QUAN TRỌNG

### Translations (27 files)
- `CustomersTranslationsSeeder.php`
- `SalesTranslationsSeeder.php`
- `QualityManagementTranslationsSeeder.php`
- `AccountingTranslationsSeeder.php`
- `ResetPasswordTranslations.php`
- `UserMenuTranslations.php`
- ... và 21 files khác

### Permissions (13 files)
- `RolePermissionSeeder.php` (Base roles)
- `HRPermissionsSeeder.php`
- `SalesModulesPermissionsSeeder.php`
- `QualityManagementPermissionsSeeder.php`
- `AccountingPermissionsSeeder.php`
- ... và 8 files khác

### Master Seeder
- `CompleteDatabaseSeeder.php` - **GỌI TẤT CẢ 50+ SEEDERS**

---

## ⚡ TIPS

1. **Backup trước khi reset:**
```bash
php artisan db:backup
```

2. **Chạy trong production:**
```bash
# Thêm flag --force
php artisan migrate:fresh --seed --seeder=CompleteDatabaseSeeder --force
```

3. **Kiểm tra kết quả:**
```bash
php artisan tinker
>>> \App\Models\Translation::count()
>>> \App\Models\Permission::count()
>>> \App\Models\User::count()
```

---

## 🎯 MAINTENANCE

Khi thêm translations hoặc permissions mới:

1. Tạo file seeder mới trong `database/seeders/`
2. Thêm vào `CompleteDatabaseSeeder.php` ở vị trí phù hợp
3. Chạy: `php artisan db:seed --class=CompleteDatabaseSeeder`

---

## 📞 SUPPORT

Nếu gặp vấn đề, kiểm tra:
- Laravel log: `storage/logs/laravel.log`
- Console output khi chạy seeder
- Database structure: `php artisan migrate:status`


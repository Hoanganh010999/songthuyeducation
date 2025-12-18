# 🚀 HƯỚNG DẪN IMPORT DỮ LIỆU CŨ
## Yên Tâm English Center - Song Thủy System

**Ngày tạo:** 2025-11-24  
**Trạng thái:** ✅ READY TO EXECUTE

---

## 📋 CHUẨN BỊ

### ✅ Đã hoàn thành:

1. ✅ CSV files đã được export từ Excel (encoding UTF-8)
2. ✅ CSV files đã được đặt trong folder `old_database/`
3. ✅ Import Commands đã được tạo
4. ✅ Wipe Command đã được tạo
5. ✅ Batch script đã được tạo

### 📂 Cấu trúc files:

```
school/
├── old_database/
│   ├── YÊN TÂM - BẢNG ĐIỂM DANH.xlsx - IELTS - K1.csv
│   ├── YÊN TÂM - BẢNG ĐIỂM DANH.xlsx - IELTS - K2.csv
│   ├── YÊN TÂM - BẢNG ĐIỂM DANH.xlsx - ISS 1_ 2018-2019.csv
│   ├── YÊN TÂM - BẢNG ĐIỂM DANH.xlsx - ISS 5.csv
│   ├── YÊN TÂM - BẢNG ĐIỂM DANH.xlsx - YT Kindy 1.csv
│   ├── YÊN TÂM - BẢNG ĐIỂM DANH.xlsx - YT Kindy 2.csv
│   └── YÊN TÂM - BẢNG ĐIỂM DANH.xlsx - THỜI KHOÁ BIỂU.csv
├── app/Console/Commands/
│   ├── WipeAndPreserveAdmin.php
│   └── ImportOldDatabase.php
└── import-old-data.bat
```

---

## 🚀 CÁCH SỬ DỤNG

### Option 1: Sử dụng Script Tự Động (Khuyến nghị)

```bash
# Mở Command Prompt/PowerShell tại thư mục school
cd C:\xampp\htdocs\school

# Chạy script
import-old-data.bat
```

**Script sẽ tự động:**
1. ✅ Backup database hiện tại
2. ✅ Wipe dữ liệu cũ (giữ admin)
3. ✅ Chạy dry-run (xem trước)
4. ✅ Hỏi confirm
5. ✅ Import thật
6. ✅ Hiển thị summary

### Option 2: Chạy Từng Bước Thủ Công

#### Bước 1: Backup Database
```bash
php artisan db:backup
```

#### Bước 2: Wipe Data (giữ admin)
```bash
php artisan db:wipe-preserve-admin
```

**Admin được giữ lại:**
- Email: `admin@songthuy.edu.vn`
- Password: `2K3h0o1n9g@`
- Role: `super-admin`

#### Bước 3: Dry-Run (Test)
```bash
php artisan import:old-database old_database --dry-run
```

**Output mẫu:**
```
🔍 DRY-RUN MODE - No data will be saved
📥 Starting import from: old_database

Step 1: Creating branch...
✓ Branch: YT01

Step 2: Creating subjects...
✓ Created 3 subjects

Step 3: Creating teachers...
✓ Created 4 teachers

📄 Processing: IELTS - K1.csv
  ✓ Imported 11 students
  ✓ Created 550 attendance records
...

📊 IMPORT SUMMARY:
+-------------+-------+
| Type        | Count |
+-------------+-------+
| Teachers    | 4     |
| Parents     | 55    |
| Students    | 70    |
| Classes     | 6     |
| Enrollments | 70    |
| Attendances | 5500  |
+-------------+-------+
```

#### Bước 4: Import Thật
```bash
php artisan import:old-database old_database
```

---

## 📊 DỮ LIỆU SẼ ĐƯỢC IMPORT

### 1. Branch
```
Code: YT01
Name: Yên Tâm English Center
```

### 2. Teachers (4)
```
1. Mr. Mike       → mike@songthuy.edu.vn
2. Mrs. Phượng    → phuong@songthuy.edu.vn
3. Ms. Linh       → linh@songthuy.edu.vn
4. Mrs. Thùy      → thuy@songthuy.edu.vn

Password: 123456
```

### 3. Classes (6)
```
1. Pre IELTS K1 - Mr. Mike (11 students)
2. Pre IELTS K2 - Ms. Linh (10 students)
3. ISS 1 - Mrs. Phượng (20 students)
4. ISS 5 - Mrs. Phượng (10 students)
5. YT Kindy 1 - Mrs. Phượng (8 students)
6. YT Kindy 2 - Mrs. Phượng (10 students)
```

### 4. Students (~70)
```
Email format: student_[name]_[phone]@songthuy.edu.vn
Password: 123456

Example:
- student_minhtam_0397622289@songthuy.edu.vn
```

### 5. Parents (~55)
```
Email format: parent_[name]_[phone]@songthuy.edu.vn
Password: 123456

Example:
- parent_thauminh_0397622289@songthuy.edu.vn
```

### 6. Enrollments (70)
```
Mỗi student có 1 enrollment
Tính học phí: Lộ trình × 100,000 đ
```

### 7. Attendance (~5,500 records)
```
Từ các cột điểm danh trong CSV
Status mapping:
- 2, 2.5, 1 → present
- 0 → absent
- OFF, Nghỉ → excused
```

---

## ✅ KIỂM TRA SAU KHI IMPORT

### 1. Check Database
```bash
php artisan tinker

# Count records
>>> User::count()
=> 129 (4 teachers + 55 parents + 70 students)

>>> Student::count()
=> 70

>>> ClassModel::count()
=> 6

>>> Enrollment::count()
=> 70

>>> Attendance::count()
=> ~5500
```

### 2. Test Login

**Admin:**
```
Email: admin@songthuy.edu.vn
Password: 2K3h0o1n9g@
```

**Teacher:**
```
Email: mike@songthuy.edu.vn
Password: 123456
```

**Student:**
```
Email: student_minhtam_0397622289@songthuy.edu.vn
Password: 123456
```

**Parent:**
```
Email: parent_thauminh_0397622289@songthuy.edu.vn
Password: 123456
```

### 3. Verify Data Integrity
```sql
-- No orphaned students
SELECT COUNT(*) FROM students 
WHERE user_id NOT IN (SELECT id FROM users);
-- Expected: 0

-- All students have parent
SELECT COUNT(*) FROM students s
WHERE NOT EXISTS (
    SELECT 1 FROM parent_student ps WHERE ps.student_id = s.id
);
-- Expected: 0

-- All students in classes
SELECT COUNT(*) FROM students s
WHERE NOT EXISTS (
    SELECT 1 FROM class_students cs WHERE cs.student_id = s.user_id
);
-- Expected: 0
```

---

## 🔧 TROUBLESHOOTING

### Issue 1: "Class 'Role' not found"
**Solution:**
```bash
php artisan clear-compiled
php artisan config:clear
composer dump-autoload
```

### Issue 2: "Cannot open CSV file"
**Solution:**
- Check file encoding (must be UTF-8)
- Check file path
- Ensure files are in `old_database/` folder

### Issue 3: "Duplicate email"
**Solution:**
Script tự động handle bằng cách thêm phone vào email.

### Issue 4: Import bị lỗi giữa chừng
**Solution:**
```bash
# Restore from backup
# Find backup file in storage/backups/
# Then restore

# Or wipe and start over
php artisan db:wipe-preserve-admin --force
php artisan import:old-database old_database
```

---

## 📝 NOTES

### Email Format:
Tất cả email được tạo tự động theo format:
```
[type]_[name_slug]_[phone]@songthuy.edu.vn
```

### Phone Numbers:
Nếu phone không hợp lệ (Zalo, FB, trống), script tự tạo số fake: `09XXXXXXXX`

### Attendance Dates:
Dates được parse từ format `DD/MM` và assume năm 2024.

### Status Mapping:
```
CSV → Database
"đăng ký" → active
"Dừng học" → cancelled/dropped
"Nghỉ" → dropped
```

---

## ⚠️ QUAN TRỌNG

### Trước khi chạy:
1. ✅ Backup database hiện tại
2. ✅ Đảm bảo không ai đang sử dụng hệ thống
3. ✅ Review dry-run output trước
4. ✅ Có thời gian ~30 phút để hoàn thành

### Sau khi chạy:
1. ✅ Test login với tất cả user types
2. ✅ Verify data integrity
3. ✅ Check attendance records
4. ✅ Thông báo cho users về email/password mới

---

## 📞 SUPPORT

Nếu gặp vấn đề:
1. Check log file: `storage/logs/laravel.log`
2. Run với `--dry-run` để debug
3. Contact admin

---

## 🎯 EXPECTED TIMELINE

| Step | Time | Description |
|------|------|-------------|
| Backup | 1 min | Backup current DB |
| Wipe | 2 min | Clear old data |
| Dry-run | 5 min | Preview import |
| Import | 15 min | Actual import |
| Verify | 5 min | Check results |
| **Total** | **~30 min** | |

---

## ✅ SUCCESS CRITERIA

Import thành công khi:
- ✅ 70 students created
- ✅ 55 parents created
- ✅ 6 classes created
- ✅ 70 enrollments created
- ✅ ~5,500 attendance records created
- ✅ All students linked to classes
- ✅ All students linked to parents
- ✅ Admin account working
- ✅ Teacher accounts working
- ✅ Student/Parent accounts working

---

**Status:** 🟢 READY TO EXECUTE  
**Last Updated:** 2025-11-24

**🚀 CÓ THỂ BẮT ĐẦU NGAY!**


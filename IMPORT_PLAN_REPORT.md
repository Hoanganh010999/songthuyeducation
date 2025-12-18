# BÁO CÁO KẾ HOẠCH IMPORT DỮ LIỆU TỪ CSV CŨ

## 📊 TỔNG QUAN DỮ LIỆU

### Dữ Liệu Hiện Tại Trong Database:
- **Users**: 184 (bao gồm giáo viên và học sinh đã có)
- **Classes**: 4 lớp (Pre IELTS K1, Pre IELTS K2, ISS 5, ISS 1)
- **Subjects**: 1 (Tiếng Anh)
- **Lesson Plans**: 5

### Dữ Liệu Trong CSV (Old Database):
- **Số file CSV**: 10 files
- **Tổng học sinh**: 164 học sinh
- **Tổng buổi điểm danh**: ~549 buổi

## 📋 CHI TIẾT CÁC LỚP TRONG CSV

| STT | Tên Lớp | File CSV | Giáo Viên | Học Sinh | Điểm Danh |
|-----|---------|----------|-----------|----------|-----------|
| 1 | FLYER | FLYERS 1.csv | MS LINH | 11 | 74 buổi |
| 2 | 6.0 | IELTS K2.csv | Vũ Thị Hồng | 16 | 0 buổi |
| 3 | 6.0 | IELTS K3.csv | N/A | 6 | 2 buổi |
| 4 | (Chưa rõ) | IELTS K4 TN.csv | MS Linh | 17 | 56 buổi |
| 5 | 6.0 | IELTS K5.csv | N/A | 20 | 10 buổi |
| 6 | STARTERS 2 | LOOK 1.csv | MS Linh | 16 | 90 buổi |
| 7 | Up 1 | LOOK STARTER.csv | MS LINH | 12 | 58 buổi |
| 8 | (Chưa rõ) | MOVERS 1.csv | MS PHƯƠNG LINH | 20 | 90 buổi |
| 9 | Pre_Movers | MOVERS NP.csv | Ms Linh | 25 | 6 buổi |
| 10 | Ollie | OLLIE.csv | MS. PHƯƠNG LINH | 21 | 83 buổi |

---

## 🎯 KẾ HOẠCH IMPORT CHI TIẾT

### GIAI ĐOẠN 1: CHUẨN BỊ (5-10 phút)

#### 1.1. Backup Database
```bash
# Backup database hiện tại
cd c:\xampp\mysql\bin
mysqldump -u root school_db > c:\xampp\htdocs\school\backup_before_import_$(date +%Y%m%d).sql
```

#### 1.2. Chuẩn Bị Dữ Liệu
- ✅ Đã phân tích xong 10 files CSV
- ✅ Đã xác định được 164 học sinh
- ✅ Đã xác định được 3-4 giáo viên chính

---

### GIAI ĐOẠN 2: IMPORT GIÁO VIÊN (2-3 phút)

#### Danh Sách Giáo Viên Cần Tạo:

| STT | Tên Giáo Viên | Email Đề Xuất | Lớp Giảng Dạy |
|-----|---------------|---------------|---------------|
| 1 | Linh | linh@songthuy.edu.vn | FLYER, IELTS K4 TN, STARTERS 2, Up 1, Pre_Movers |
| 2 | Phương Linh | phuonglinh@songthuy.edu.vn | Ollie, Movers 1 |
| 3 | Vũ Thị Hồng | vuthihong@songthuy.edu.vn | IELTS K2 (6.0) |

**Lưu ý**:
- Email linh@songthuy.edu.vn đã tồn tại trong database (ID: 193, Ms. Linh)
- Cần kiểm tra xem có phải cùng người không

**Action Items**:
- [ ] Xác nhận danh sách giáo viên với user
- [ ] Tạo tài khoản mới hoặc sử dụng tài khoản hiện có
- [ ] Gán quyền `teacher` cho các tài khoản giáo viên

---

### GIAI ĐOẠN 3: IMPORT LỚP HỌC (5-8 phút)

#### Kế Hoạch Tạo Lớp:

##### 3.1. Mapping Với Lesson Plans Hiện Có:

Database hiện có 5 lesson plans:
1. Pre-IELTS 1 (ID: 18)
2. Pre-IELTS 2 (ID: 19)
3. Kindy 1 (ID: 20)
4. ISS 1 (ID: 21)
5. ISS5 (ID: 22)

##### 3.2. Danh Sách Lớp Cần Tạo:

```
1. FLYER (FLYERS_1_2024)
   - Giáo viên: Ms. Linh (ID: 193)
   - Subject: Tiếng Anh (ID: 2)
   - Lesson Plan: Cần tạo mới "FLYER"
   - Học sinh: 11 em
   - Level: elementary

2. IELTS 6.0 K2 (IELTS_6_0_K2_2024)
   - Giáo viên: Vũ Thị Hồng
   - Subject: Tiếng Anh (ID: 2)
   - Lesson Plan: Pre-IELTS 1 (ID: 18)
   - Học sinh: 16 em
   - Level: high

3. IELTS 6.0 K3 (IELTS_6_0_K3_2024)
   - Giáo viên: TBD
   - Subject: Tiếng Anh (ID: 2)
   - Lesson Plan: Pre-IELTS 1 (ID: 18)
   - Học sinh: 6 em
   - Level: high

4. IELTS K4 TN (IELTS_K4_TN_2024)
   - Giáo viên: Ms. Linh (ID: 193)
   - Subject: Tiếng Anh (ID: 2)
   - Lesson Plan: Pre-IELTS 2 (ID: 19)
   - Học sinh: 17 em
   - Level: high

5. IELTS 6.0 K5 (IELTS_6_0_K5_2024)
   - Giáo viên: TBD
   - Subject: Tiếng Anh (ID: 2)
   - Lesson Plan: Pre-IELTS 2 (ID: 19)
   - Học sinh: 20 em
   - Level: high

6. STARTERS 2 (STARTERS_2_2024)
   - Giáo viên: Ms. Linh (ID: 193)
   - Subject: Tiếng Anh (ID: 2)
   - Lesson Plan: Kindy 1 (ID: 20) hoặc tạo mới
   - Học sinh: 16 em
   - Level: elementary

7. Up 1 (UP_1_2024)
   - Giáo viên: Ms. Linh (ID: 193)
   - Subject: Tiếng Anh (ID: 2)
   - Lesson Plan: Cần tạo mới "UP 1"
   - Học sinh: 12 em
   - Level: elementary

8. MOVERS 1 (MOVERS_1_2024)
   - Giáo viên: Ms. Phương Linh
   - Subject: Tiếng Anh (ID: 2)
   - Lesson Plan: Cần tạo mới "MOVERS 1"
   - Học sinh: 20 em
   - Level: middle

9. Pre Movers (PRE_MOVERS_2024)
   - Giáo viên: Ms. Linh (ID: 193)
   - Subject: Tiếng Anh (ID: 2)
   - Lesson Plan: Cần tạo mới "PRE MOVERS"
   - Học sinh: 25 em
   - Level: elementary

10. Ollie (OLLIE_2024)
    - Giáo viên: Ms. Phương Linh
    - Subject: Tiếng Anh (ID: 2)
    - Lesson Plan: Cần tạo mới "OLLIE"
    - Học sinh: 21 em
    - Level: elementary
```

**Action Items**:
- [ ] Tạo 5-6 lesson plans mới (FLYER, UP 1, MOVERS 1, PRE MOVERS, OLLIE)
- [ ] Tạo 10 lớp học mới
- [ ] Gán giáo viên cho mỗi lớp
- [ ] Link với lesson plan tương ứng

---

### GIAI ĐOẠN 4: IMPORT HỌC SINH (10-15 phút)

#### Thống Kê Học Sinh:

- **Tổng số**: 164 học sinh
- **Có SĐT phụ huynh**: ~140 học sinh (~85%)
- **Có ngày sinh**: ~150 học sinh (~91%)

#### Quy Trình Import:

```
Cho mỗi học sinh trong CSV:
1. Tạo user account:
   - name: Tên tiếng Việt
   - email: userXXXXX@student.songthuy.edu.vn (auto-increment)
   - password: mã hóa từ ngày sinh hoặc mật khẩu mặc định
   - phone: SĐT phụ huynh
   - date_of_birth: Ngày sinh (format: YYYY-MM-DD)
   - employment_status: active

2. Tạo student record (nếu có bảng students riêng)
3. Liên kết student với class (class_student pivot table)
4. Lưu English name vào metadata hoặc trường riêng
```

#### Xử Lý Dữ Liệu:

**Format Ngày Sinh**:
```php
// Input: 21/07/2019, 05/08/2019, 2019, 17/05/2020
// Output: 2019-07-21, 2019-08-05, 2019-01-01, 2020-05-17

function convertDateFormat($dateStr) {
    // Xử lý các format khác nhau
    if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $dateStr, $matches)) {
        return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
    }
    if (preg_match('/^\d{4}$/', $dateStr)) {
        return $dateStr . '-01-01'; // Nếu chỉ có năm
    }
    return null;
}
```

**Format SĐT**:
```php
function formatPhone($phone) {
    // Loại bỏ ký tự không phải số
    $phone = preg_replace('/\D/', '', $phone);

    // Nếu bắt đầu bằng 84, thêm +
    if (substr($phone, 0, 2) === '84') {
        return '+' . $phone;
    }

    // Nếu 10-11 số, giữ nguyên
    if (strlen($phone) >= 10 && strlen($phone) <= 11) {
        return $phone;
    }

    return null;
}
```

**Action Items**:
- [ ] Tạo seeder hoặc import script
- [ ] Xử lý duplicate (tên học sinh trùng)
- [ ] Validate dữ liệu trước khi import
- [ ] Import theo từng lớp để dễ tracking

---

### GIAI ĐOẠN 5: IMPORT ĐIỂM DANH (TÙY CHỌN)

⚠️ **Lưu ý**: Giai đoạn này có thể BỎ QUA nếu không cần lịch sử điểm danh cũ.

#### Nếu Cần Import Điểm Danh:

**Cần tạo bảng**: `attendance_records`
```sql
CREATE TABLE attendance_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    INDEX idx_user_class_date (user_id, class_id, attendance_date)
);
```

**Thống kê**:
- Tổng records: ~8,000-10,000 bản ghi
- Thời gian import: ~10-15 phút

**Mapping trạng thái**:
- `1` → `present`
- `0` → `absent`
- `OFF` → `excused`
- Trống → không import

---

## ⚠️ CÁC VẤN ĐỀ CẦN XỬ LÝ

### 1. Tên Lớp Trùng
**Vấn đề**: 3 lớp đều tên "6.0" (IELTS K2, K3, K5)

**Giải pháp**:
- IELTS K2 → "IELTS 6.0 K2"
- IELTS K3 → "IELTS 6.0 K3"
- IELTS K5 → "IELTS 6.0 K5"

### 2. Lớp Không Có Tên
**Vấn đề**: IELTS K4 TN và MOVERS 1 không có tên lớp trong CSV

**Giải pháp**:
- Dùng tên file làm tên lớp
- Hoặc hỏi user để xác nhận tên chính xác

### 3. Giáo Viên Thiếu/Trùng
**Vấn đề**:
- Một số lớp không có giáo viên
- "Ms. Linh" xuất hiện với nhiều format khác nhau

**Giải pháp**:
- Chuẩn hóa tên giáo viên
- Gán giáo viên mặc định hoặc để trống nếu không rõ
- Xác nhận với user

### 4. Ký Tự Đặc Biệt
**Vấn đề**: Tên tiếng Việt có dấu, encoding UTF-8

**Giải pháp**:
- Đảm bảo CSV đọc đúng encoding
- Database charset: utf8mb4
- Collation: utf8mb4_unicode_ci

### 5. Lesson Plans Thiếu
**Vấn đề**: Cần tạo 5-6 lesson plans mới

**Giải pháp**:
- Tạo lesson plans với thông tin cơ bản
- Có thể cập nhật chi tiết sau
- Link Google Drive nếu có

---

## 📊 TỔNG KẾT DỮ LIỆU SAU KHI IMPORT

### Trước Import:
- Users: 184
- Classes: 4
- Lesson Plans: 5

### Sau Import:
- Users: **~348-350** (184 + 3-4 giáo viên + 164 học sinh)
- Classes: **14** (4 + 10 lớp mới)
- Lesson Plans: **10-11** (5 + 5-6 mới)

### Tăng Trưởng:
- Users: +89% (~164 học sinh mới)
- Classes: +250% (từ 4 lên 14 lớp)
- Lesson Plans: +120% (từ 5 lên 10-11)

---

## ⏱️ THỜI GIAN ƯỚC TÍNH

| Giai Đoạn | Thời Gian | Độ Phức Tạp |
|-----------|-----------|-------------|
| Chuẩn bị & Backup | 5-10 phút | Dễ |
| Import Giáo viên | 2-3 phút | Dễ |
| Tạo Lesson Plans | 5-8 phút | Trung bình |
| Import Lớp học | 5-8 phút | Trung bình |
| Import Học sinh | 10-15 phút | Phức tạp |
| Import Điểm danh (optional) | 10-15 phút | Phức tạp |
| Validation & Testing | 5-10 phút | Trung bình |
| **TỔNG CỘNG** | **30-45 phút** | - |

---

## ✅ CHECKLIST TRƯỚC KHI IMPORT

### Chuẩn Bị:
- [ ] Backup database hoàn tất
- [ ] Đọc và hiểu kế hoạch import
- [ ] Chuẩn bị danh sách giáo viên
- [ ] Xác nhận mapping lesson plans
- [ ] Test import script trên môi trường dev

### Validation:
- [ ] Kiểm tra charset/encoding CSV files
- [ ] Validate format ngày sinh
- [ ] Validate format SĐT
- [ ] Kiểm tra duplicate học sinh
- [ ] Kiểm tra tên lớp trùng

### Sau Import:
- [ ] Verify số lượng records
- [ ] Test đăng nhập với tài khoản mới
- [ ] Kiểm tra relations (class-student, class-teacher)
- [ ] Test frontend với dữ liệu mới
- [ ] Backup database sau import

---

## 🔧 CÔNG CỤ IMPORT ĐỀ XUẤT

### Option 1: Laravel Seeder (Khuyến nghị)
```bash
php artisan make:seeder OldDataImportSeeder
php artisan db:seed --class=OldDataImportSeeder
```

**Ưu điểm**:
- Tích hợp sẵn với Laravel
- Dễ rollback nếu có lỗi
- Có transaction support

### Option 2: Custom PHP Script
```bash
php import_old_data.php --class=FLYERS_1 --dry-run
php import_old_data.php --class=ALL
```

**Ưu điểm**:
- Linh hoạt hơn
- Có thể import từng lớp
- Có dry-run mode

### Option 3: Laravel Command
```bash
php artisan import:old-data --file=FLYERS_1.csv
php artisan import:old-data --all
```

**Ưu điểm**:
- Professional
- Progress bar
- Error handling tốt

---

## 📝 GHI CHÚ QUAN TRỌNG

1. **PHẢI backup database trước khi import**
2. **Test trên môi trường dev trước**
3. **Import theo từng lớp để dễ tracking lỗi**
4. **Validate dữ liệu sau mỗi giai đoạn**
5. **Lưu log chi tiết quá trình import**
6. **Chuẩn bị rollback plan nếu có vấn đề**

---

## 🎯 BƯỚC TIẾP THEO

1. **Review kế hoạch này với user**
2. **Xác nhận danh sách giáo viên và tên lớp**
3. **Chọn công cụ import (Seeder/Script/Command)**
4. **Tạo migration cho attendance_records nếu cần**
5. **Viết import script/seeder**
6. **Test trên dữ liệu mẫu**
7. **Thực hiện import thực tế**
8. **Validation và testing sau import**

---

**Ngày tạo**: 2025-11-25
**Tổng học sinh cần import**: 164
**Tổng lớp cần tạo**: 10
**Thời gian ước tính**: 30-45 phút

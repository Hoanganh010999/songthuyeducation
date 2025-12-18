# 📥 HƯỚNG DẪN IMPORT DỮ LIỆU CŨ
## School Management System

---

## MỤC ĐÍCH

Hướng dẫn import dữ liệu từ hệ thống cũ (Excel/CSV) vào database mới của School Management System.

---

## BƯỚC 1: CHUẨN BỊ DỮ LIỆU

### Convert Excel sang CSV

#### Trong Excel:
1. Mở file Excel
2. Chọn **File → Save As**
3. Format: **CSV UTF-8 (Comma delimited)**
4. Lưu từng sheet thành file riêng:
   - `students.csv`
   - `teachers.csv`
   - `classes.csv`
   - `customers.csv`
   - v.v.

#### Trong Google Sheets:
1. **File → Download → Comma-separated values (.csv)**
2. Đổi tên file cho phù hợp

### Kiểm tra Encoding
- **Bắt buộc**: UTF-8 encoding
- **Kiểm tra**: Mở bằng Notepad++, check encoding ở góc dưới phải
- **Nếu không phải UTF-8**: Convert bằng Notepad++ (Encoding → Convert to UTF-8)

---

## BƯỚC 2: ĐỊNH DẠNG DỮ LIỆU

### Format CSV chuẩn:

```csv
column1,column2,column3
"value1","value2","value3"
"value with, comma","value2","value3"
```

**Lưu ý:**
- ✅ Dòng đầu tiên là tên cột
- ✅ Dữ liệu bắt đầu từ dòng 2
- ✅ Sử dụng double quotes cho text có dấu phẩy
- ✅ Ngày tháng format: YYYY-MM-DD (2024-11-24)
- ✅ Số điện thoại: không có dấu cách hoặc ký tự đặc biệt

---

## BƯỚC 3: MAPPING DỮ LIỆU

### A. STUDENTS (Học sinh)

**Cấu trúc cũ → mới:**

| Excel Column | Database Field | Required | Format | Example |
|--------------|----------------|----------|--------|---------|
| Mã HS | student_code | No (auto) | STD202500001 | - |
| Họ tên | users.name | Yes | String | Nguyễn Văn A |
| Email | users.email | No | email@domain.com | student@example.com |
| Điện thoại | users.phone | No | 0123456789 | 0901234567 |
| Ngày sinh | users.date_of_birth | No | YYYY-MM-DD | 2010-05-15 |
| Giới tính | users.gender | No | male/female/other | male |
| Địa chỉ | users.address | No | String | 123 Đường ABC |
| Chi nhánh | branch_id | Yes | ID hoặc code | HN01 |
| Ngày nhập học | enrollment_date | No | YYYY-MM-DD | 2024-09-01 |

**Sample CSV:**
```csv
full_name,email,phone,date_of_birth,gender,address,branch_code,enrollment_date
"Nguyễn Văn A","nguyenvana@email.com","0901234567","2010-05-15","male","123 Đường ABC, Hà Nội","HN01","2024-09-01"
"Trần Thị B","tranthib@email.com","0907654321","2011-03-20","female","456 Đường XYZ, Hà Nội","HN01","2024-09-01"
```

### B. TEACHERS (Giáo viên)

**Mapping:**

| Excel Column | Database Field | Required | Format | Example |
|--------------|----------------|----------|--------|---------|
| Mã GV | employee_code | No (auto) | String | GV001 |
| Họ tên | users.name | Yes | String | Phạm Văn C |
| Email | users.email | Yes | email | teacher@example.com |
| Điện thoại | users.phone | No | 0123456789 | 0912345678 |
| Ngày sinh | users.date_of_birth | No | YYYY-MM-DD | 1990-01-01 |
| Môn dạy | subjects | No | String (comma-sep) | Toán, Lý |
| Chức vụ | positions | No | String | Giáo viên |
| Chi nhánh | branch_id | Yes | ID hoặc code | HN01 |
| Ngày vào làm | join_date | No | YYYY-MM-DD | 2020-08-01 |

**Sample CSV:**
```csv
employee_code,full_name,email,phone,date_of_birth,subjects,position,branch_code,join_date
"GV001","Phạm Văn C","phamvanc@email.com","0912345678","1990-01-01","Toán;Lý","Giáo viên","HN01","2020-08-01"
```

### C. CLASSES (Lớp học)

**Mapping:**

| Excel Column | Database Field | Required | Format | Example |
|--------------|----------------|----------|--------|---------|
| Mã lớp | code | No (auto) | String | CLASS_10A1 |
| Tên lớp | name | Yes | String | Lớp 10A1 |
| GVCN | homeroom_teacher_id | No | Email hoặc code | teacher@example.com |
| Năm học | academic_year | Yes | YYYY-YYYY | 2024-2025 |
| Cấp học | level | Yes | elementary/middle/high | high |
| Sĩ số | capacity | No | Number | 40 |
| Chi nhánh | branch_id | Yes | Code | HN01 |
| Môn học chính | subject_id | No | Name hoặc code | Toán |

**Sample CSV:**
```csv
class_name,homeroom_teacher_email,academic_year,level,capacity,branch_code,main_subject
"Lớp 10A1","teacher1@example.com","2024-2025","high","40","HN01","Toán"
"Lớp 10A2","teacher2@example.com","2024-2025","high","40","HN01","Lý"
```

### D. CUSTOMERS (Khách hàng)

**Mapping:**

| Excel Column | Database Field | Required | Format | Example |
|--------------|----------------|----------|--------|---------|
| Mã KH | code | No (auto) | CUS20241124001 | - |
| Họ tên | name | Yes | String | Lê Thị D |
| Điện thoại | phone | Yes | 0123456789 | 0903456789 |
| Email | email | No | email | customer@example.com |
| Nguồn | source | No | String | Facebook, Google | Facebook |
| Trạng thái | stage | No | lead/contacted/... | lead |
| Chi nhánh | branch_id | Yes | Code | HN01 |
| Người phụ trách | assigned_to | No | Email | sales@example.com |

**Sample CSV:**
```csv
full_name,phone,email,source,stage,branch_code,assigned_to_email
"Lê Thị D","0903456789","lethid@email.com","Facebook","lead","HN01","sales@example.com"
```

---

## BƯỚC 4: TẠO IMPORT COMMAND

Tôi sẽ tạo một Laravel Command để import dữ liệu:

### Cấu trúc Command:

```bash
php artisan import:students /path/to/students.csv
php artisan import:teachers /path/to/teachers.csv
php artisan import:classes /path/to/classes.csv
php artisan import:customers /path/to/customers.csv
```

### Command sẽ có các tính năng:

1. ✅ **Validation**: Kiểm tra dữ liệu trước khi import
2. ✅ **Dry-run**: Test import không lưu DB
3. ✅ **Progress bar**: Hiển thị tiến độ
4. ✅ **Error log**: Ghi lại lỗi vào file
5. ✅ **Rollback**: Có thể undo nếu cần
6. ✅ **Mapping**: Tự động map từ cũ sang mới

---

## BƯỚC 5: VALIDATION RULES

Trước khi import, dữ liệu sẽ được validate:

### Students:
- ✅ Email phải unique (nếu có)
- ✅ Phone format đúng
- ✅ Branch phải tồn tại
- ✅ Date format: YYYY-MM-DD
- ✅ Gender: male/female/other

### Teachers:
- ✅ Email bắt buộc và unique
- ✅ Branch phải tồn tại
- ✅ Subjects phải tồn tại (nếu có)

### Classes:
- ✅ Code unique
- ✅ Branch tồn tại
- ✅ Homeroom teacher tồn tại (nếu có)

---

## BƯỚC 6: SAMPLE IMPORT WORKFLOW

### 1. Prepare CSV:
```bash
students.csv
teachers.csv
classes.csv
```

### 2. Validate (Dry-run):
```bash
php artisan import:students students.csv --dry-run
```

### 3. Review log:
```
Validating 100 records...
✓ 95 records valid
✗ 5 records have errors:
  - Row 10: Email already exists
  - Row 25: Invalid date format
  - Row 40: Branch not found
  ...

Errors saved to: storage/logs/import_errors_20241124_150530.log
```

### 4. Fix errors và import:
```bash
php artisan import:students students.csv
```

### 5. Verify:
```bash
php artisan tinker
>>> Student::count()
=> 95
```

---

## BƯỚC 7: ERROR HANDLING

### Common Errors:

| Error | Solution |
|-------|----------|
| "Invalid CSV format" | Check UTF-8 encoding |
| "Branch not found" | Import branches first |
| "Email already exists" | Remove duplicates or update existing |
| "Invalid date format" | Use YYYY-MM-DD format |
| "Foreign key constraint" | Import in correct order |

### Import Order:
1. **Branches** (chi nhánh)
2. **Users** (base users)
3. **Positions** (chức vụ)
4. **Departments** (phòng ban)
5. **Subjects** (môn học)
6. **Teachers** (liên kết users với subjects)
7. **Students** (học sinh)
8. **Classes** (lớp học)
9. **Class Students** (học sinh trong lớp)
10. **Customers** (khách hàng)

---

## BƯỚC 8: SAMPLE DATA

### Để test, tạo file sample:

**students_sample.csv:**
```csv
full_name,email,phone,date_of_birth,gender,address,branch_code,enrollment_date
"Nguyễn Văn A","student1@test.com","0901234567","2010-05-15","male","123 ABC, Hà Nội","HN01","2024-09-01"
"Trần Thị B","student2@test.com","0907654321","2011-03-20","female","456 XYZ, Hà Nội","HN01","2024-09-01"
```

**Import:**
```bash
php artisan import:students students_sample.csv --dry-run
```

---

## TOOLS HỖ TRỢ

### Online CSV Validator:
- https://csvlint.io/
- https://www.convertcsv.com/csv-viewer-editor.htm

### CSV Editor:
- Excel
- Google Sheets
- CSV Editor (VS Code extension)
- LibreOffice Calc

---

## NEXT STEPS

### Sau khi có file CSV:

1. ✅ Share sample data (5-10 rows) để tôi xem cấu trúc
2. ✅ Tôi sẽ tạo Import Commands phù hợp
3. ✅ Tôi sẽ tạo Mapping logic
4. ✅ Test với sample data
5. ✅ Import full data

---

## LIÊN HỆ

Khi sẵn sàng, hãy:
- Upload CSV sample (5-10 dòng đầu)
- Hoặc mô tả cấu trúc Excel của bạn

Tôi sẽ tạo import script phù hợp! 🚀

---

**Version:** 1.0  
**Date:** 2025-11-24


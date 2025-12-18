# 📥 KẾ HOẠCH MIGRATION DỮ LIỆU CŨ
## School Management System - Migration từ Old Database

---

## 🎯 TỔNG QUAN

### Nguồn dữ liệu:
- **Folder:** `old_database/`
- **Format:** CSV files + SQLite database
- **Tổng số files CSV:** 7 files
- **Vấn đề:** Encoding không phải UTF-8

### Files cần import:
1. ✅ `IELTS K1.csv` - Lớp IELTS K1
2. ✅ `IELTS K2.csv` - Lớp IELTS K2  
3. ✅ `ISS 1 2018-2019.csv` - Lớp ISS 1
4. ✅ `iss5.csv` - Lớp ISS 5
5. ✅ `YT Kindy 1.csv` - Lớp YT Kindy 1
6. ✅ `YT Kindy 2.csv` - Lớp YT Kindy 2
7. ✅ `YÊN TÂM - BẢNG ĐIỂM DANH.csv` - Lịch học/Thời khóa biểu

---

## 🚨 BƯỚC 1: FIX ENCODING (BẮT BUỘC)

### Vấn đề hiện tại:
```
File hiện tại: DANH S�CH L?P - Encoding sai
Cần chuyển về: DANH SÁCH LỚP - UTF-8
```

### Giải pháp:

#### Option A: Sử dụng Python Script (Khuyến nghị)
```bash
cd C:\xampp\htdocs\school\old_database

# Cài đặt Python (nếu chưa có)
python --version

# Chạy script convert
python fix_encoding.py
```

Script sẽ tạo các file mới:
- `IELTS K1.utf8.csv`
- `IELTS K2.utf8.csv`
- `ISS 1 2018-2019.utf8.csv`
- v.v.

#### Option B: Sử dụng Excel (Manual)
1. Mở file CSV trong Excel
2. **File → Save As**
3. Format: **CSV UTF-8 (Comma delimited) (*.csv)**
4. Lưu với tên mới (thêm _utf8)

#### Option C: Sử dụng Notepad++
1. Mở file trong Notepad++
2. **Encoding → Convert to UTF-8**
3. Save

### Kiểm tra sau khi convert:
```bash
# Mở file .utf8.csv và kiểm tra
# Các ký tự tiếng Việt phải hiển thị đúng:
# ✓ Nguyễn, Trần, Đỗ
# ✓ Họ tên, Số điện thoại
```

---

## 📊 BƯỚC 2: PHÂN TÍCH CẤU TRÚC DỮ LIỆU

### A. STUDENT DATA (Dữ liệu học sinh)

Files: `IELTS K1.csv`, `IELTS K2.csv`, `ISS 1.csv`, `iss5.csv`, `YT Kindy 1.csv`, `YT Kindy 2.csv`

**Cấu trúc CSV cũ:**
```csv
STT, Họ tên HV, Tên tiếng anh, Phụ huynh, Số điện thoại, Lớp/tuổi, Tên Lớp, 
Tình trạng, Lộ trình, Số buổi đã học, Còn, Nộp tiền lần 1, Nộp tiền lần 2,
[Các cột điểm danh theo ngày: 07/10, 09/10, 14/10, ...]
```

**Ví dụ dòng dữ liệu:**
```csv
1,NGUYỄN TƯỜNG VY,Nguyễn Đồng Phương,0986346467,Pre IELTS,Đã đăng ký,340,26,314,2,2,2,2,2,2,2,2...
```

**Mapping sang database mới:**

| CSV Column | New DB Table | Field | Notes |
|------------|--------------|-------|-------|
| STT | - | - | Bỏ qua (auto-increment) |
| Họ tên HV | `users` | name | Required |
| Tên tiếng anh | `users` | metadata→english_name | JSON field |
| Phụ huynh | `parents` | name | Tạo parent record |
| Số điện thoại | `parents` | phone | Link parent |
| Lớp/tuổi | - | - | Parse để xác định level |
| Tên Lớp | `classes` | name | Tìm hoặc tạo class |
| Tình trạng | `class_students` | status | Map: "Đã đăng ký" → "active" |
| Lộ trình | `enrollments` | total_sessions | Số giờ total |
| Số buổi đã học | `enrollments` | attended_sessions | Đã học |
| Còn | `enrollments` | remaining_sessions | Còn lại |
| Nộp tiền lần 1 | `enrollments` | paid_amount | Số tiền đã nộp |
| [Ngày điểm danh] | `attendances` | - | Tạo attendance records |

### B. SCHEDULE DATA (Lịch học)

File: `YÊN TÂM - BẢNG ĐIỂM DANH.csv`

**Cấu trúc:**
```csv
Time, MON, TUE, WED, THU, FRI, SAT, SUN
7h00-9h00, , , , , , , Pre IELTS K1
Teachers & TA, , , , , , , Mr. Mike
```

**Mapping:**

| CSV Data | New DB Table | Field |
|----------|--------------|-------|
| Time slot | `study_periods` | name (Tiết 1, Tiết 2) |
| Class name | `classes` | name |
| Day | `class_schedules` | day_of_week |
| Teacher | `users` | homeroom_teacher_id |

---

## 🔄 BƯỚC 3: DATA TRANSFORMATION LOGIC

### 3.1. Parse Student Data

```python
def parse_student_row(row):
    """
    Parse một dòng dữ liệu học sinh
    """
    return {
        'student_name': row['Họ tên HV'],
        'english_name': row['Tên tiếng anh'],
        'parent_name': row['Phụ huynh'],
        'parent_phone': row['Số điện thoại'],
        'class_name': row['Tên Lớp'],
        'status': map_status(row['Tình trạng']),
        'total_hours': parse_int(row['Lộ trình']),
        'hours_completed': parse_int(row['Số buổi đã học']),
        'hours_remaining': parse_int(row['Còn']),
        'payment_1': parse_payment(row['Nộp tiền lần 1']),
        'payment_2': parse_payment(row['Nộp tiền lần 2']),
        'attendance_dates': extract_attendance_dates(row)
    }
```

### 3.2. Status Mapping

```python
STATUS_MAP = {
    'Đã đăng ký': 'active',
    'Nghỉ': 'dropped',
    'Dừng học': 'dropped',
    'Đang học': 'active',
    '': 'pending'
}
```

### 3.3. Class Name Parsing

```python
def parse_class_info(class_name):
    """
    Parse class name để lấy thông tin
    
    Examples:
    - "Pre IELTS" → level: pre-ielts
    - "IELTS K1" → level: ielts, code: K1
    - "YT Kindy 1" → level: kindy, code: 1
    - "ISS 1" → level: iss, code: 1
    """
    return {
        'name': class_name,
        'level': extract_level(class_name),
        'code': generate_class_code(class_name),
        'academic_year': '2018-2019'  # From filename
    }
```

---

## 📝 BƯỚC 4: IMPORT ORDER (Thứ tự import)

### Stage 1: Master Data (Dữ liệu cơ bản)
```
1. Branches (nếu chưa có)
   → Tạo branch "Yên Tâm" hoặc "Default Branch"

2. Academic Years
   → 2018-2019, 2019-2020, v.v.

3. Study Periods
   → 7h00-9h00, 9h30-11h00, 17h00-18h30, v.v.

4. Subjects (nếu cần)
   → IELTS, Pre-IELTS, ISS, Kindy
```

### Stage 2: Users (Người dùng)
```
1. Teachers
   → Mr. Mike, Mrs. Phương, Ms. Linh
   → Từ cột "Teachers & TA" trong schedule

2. Parents
   → Từ cột "Phụ huynh" + "Số điện thoại"
   → Tạo User + Parent record

3. Students
   → Từ cột "Họ tên HV"
   → Tạo User + Student record
   → Link với Parents (parent_student pivot)
```

### Stage 3: Classes (Lớp học)
```
1. Create Classes
   → Pre IELTS K1, K2
   → ISS 1, ISS 5
   → YT Kindy 1, 2
   → Assign homeroom teacher

2. Class Schedules
   → Từ file "YÊN TÂM - BẢNG ĐIỂM DANH.csv"
   → Time slots + days of week
```

### Stage 4: Enrollments & Class Students
```
1. Create Enrollments
   → customer_id = parent
   → student_id = student
   → total_sessions từ "Lộ trình"
   → attended_sessions từ "Số buổi đã học"

2. Link Students to Classes
   → class_students table
   → status từ "Tình trạng"
```

### Stage 5: Attendance Records
```
1. Parse attendance columns
   → Các cột ngày (07/10, 09/10, ...)
   → Values: 1, 0, OFF, "Nghỉ"

2. Create attendance records
   → For each date with value
   → Map: 1 → present, 0 → absent, OFF → excused
```

---

## 🛠️ BƯỚC 5: IMPORT COMMANDS

Tôi sẽ tạo các Laravel Commands:

### 5.1. Import Classes
```bash
php artisan import:old-classes old_database/
```

**Chức năng:**
- Đọc tất cả CSV files
- Extract unique class names
- Create classes in DB

### 5.2. Import Students
```bash
php artisan import:old-students old_database/
```

**Chức năng:**
- Parse student data từ CSV
- Create User + Student + Parent records
- Link students to classes

### 5.3. Import Attendance
```bash
php artisan import:old-attendance old_database/
```

**Chức năng:**
- Parse attendance columns
- Create attendance records
- Link to class_lesson_sessions

### 5.4. Import All
```bash
php artisan import:old-database old_database/ --dry-run
```

**Flags:**
- `--dry-run`: Test without saving
- `--branch=HN01`: Specify branch
- `--academic-year=2018-2019`: Specify year

---

## ⚠️ BƯỚC 6: VALIDATION RULES

### Pre-import Validation:

```python
def validate_student_data(row):
    errors = []
    
    # Required fields
    if not row.get('Họ tên HV'):
        errors.append('Tên học sinh không được để trống')
    
    # Phone format
    phone = row.get('Số điện thoại', '')
    if phone and not is_valid_phone(phone):
        errors.append(f'Số điện thoại không hợp lệ: {phone}')
    
    # Numeric fields
    for field in ['Lộ trình', 'Số buổi đã học', 'Còn']:
        value = row.get(field, '')
        if value and not str(value).replace('-', '').isdigit():
            errors.append(f'{field} phải là số: {value}')
    
    return errors
```

### Post-import Verification:

```sql
-- Check students created
SELECT COUNT(*) FROM students;

-- Check classes created
SELECT COUNT(*) FROM classes;

-- Check enrollments
SELECT COUNT(*) FROM enrollments;

-- Check attendances
SELECT COUNT(*) FROM attendances;

-- Check orphaned records
SELECT * FROM students WHERE user_id NOT IN (SELECT id FROM users);
```

---

## 📈 BƯỚC 7: EXECUTION PLAN

### Timeline ước tính:

| Giai đoạn | Thời gian | Mô tả |
|-----------|-----------|-------|
| 1. Fix Encoding | 30 phút | Convert all CSV to UTF-8 |
| 2. Verify Data | 1 giờ | Check data quality |
| 3. Setup Commands | 2 giờ | Create import commands |
| 4. Test Import | 1 giờ | Dry-run testing |
| 5. Actual Import | 30 phút | Real import |
| 6. Verification | 1 giờ | Verify results |
| **TOTAL** | **6 giờ** | **End-to-end** |

### Checklist:

```
□ Step 1: Fix encoding tất cả CSV files
□ Step 2: Verify encoding đúng UTF-8
□ Step 3: Backup database mới (trước khi import)
□ Step 4: Create branch "Yên Tâm" (nếu chưa có)
□ Step 5: Run import commands với --dry-run
□ Step 6: Review dry-run results
□ Step 7: Fix any validation errors
□ Step 8: Run actual import
□ Step 9: Verify data imported correctly
□ Step 10: Create backup after import
```

---

## 🔍 BƯỚC 8: EXAMPLE DATA FLOW

### Example: Import 1 học sinh

**Input CSV:**
```csv
1,NGUYỄN TƯỜNG VY,,Nguyễn Đồng Phương,0986346467,Pre IELTS,Đã đăng ký,340,26,314,ĐN,ĐN,2,2,2,2...
```

**Processing:**
```
1. Create Parent:
   → users: name="Nguyễn Đồng Phương", phone="0986346467"
   → parents: user_id=X

2. Create Student:
   → users: name="NGUYỄN TƯỜNG VY"
   → students: user_id=Y, student_code="STD202500001"
   → parent_student: parent_id=X, student_id=Y

3. Find/Create Class:
   → classes: name="Pre IELTS", code="PRE_IELTS_2018"

4. Create Enrollment:
   → enrollments:
     - customer_id = parent_id
     - student_type = "Student"
     - student_id = student_id
     - total_sessions = 340
     - attended_sessions = 26
     - remaining_sessions = 314

5. Create ClassStudent:
   → class_students:
     - class_id = class_id
     - student_id = student_user_id
     - status = "active"

6. Create Attendances:
   → For each date column (07/10, 09/10, ...):
     → attendances:
       - student_id = student_user_id
       - session_id = find_or_create_session(class, date)
       - status = "present" (if value=2) or "absent" (if value=0)
```

**Output:**
```
✓ Created 1 parent
✓ Created 1 student  
✓ Linked parent-student
✓ Created 1 enrollment
✓ Added to class
✓ Created 12 attendance records
```

---

## 🚀 NEXT STEPS

### Immediate Actions:

1. **RUN ENCODING FIX:**
```bash
cd C:\xampp\htdocs\school\old_database
python fix_encoding.py
```

2. **VERIFY CONVERTED FILES:**
```bash
# Mở các file .utf8.csv
# Check tiếng Việt hiển thị đúng
```

3. **SHARE SAMPLE:**
```
Gửi cho tôi 5-10 dòng đầu từ file .utf8.csv
để tôi tạo import commands chính xác
```

---

## 📞 SUPPORT

### Nếu gặp vấn đề:

**Encoding vẫn sai:**
- Thử encoding khác: `gb2312`, `big5`, `shift_jis`
- Hoặc manual qua Excel

**Python không có:**
- Download: https://www.python.org/downloads/
- Hoặc dùng Excel method

**Cần trợ giúp:**
- Share screenshot lỗi
- Share sample data converted

---

## 📊 EXPECTED RESULTS

Sau khi import xong:

```sql
-- Students
SELECT COUNT(*) FROM students; 
-- Expected: ~50-100 students

-- Classes  
SELECT COUNT(*) FROM classes;
-- Expected: 7 classes (IELTS K1, K2, ISS 1, ISS 5, Kindy 1, 2, Pre IELTS)

-- Parents
SELECT COUNT(*) FROM parents;
-- Expected: ~50-100 parents

-- Enrollments
SELECT COUNT(*) FROM enrollments;
-- Expected: ~50-100 enrollments

-- Attendances
SELECT COUNT(*) FROM attendances;
-- Expected: ~500-1000 records (depending on date columns)

-- Class Students
SELECT COUNT(*) FROM class_students;
-- Expected: ~50-100 links
```

---

**Version:** 1.0  
**Date:** 2025-11-24  
**Status:** 🔴 WAITING FOR ENCODING FIX

**Next Action:** RUN `python fix_encoding.py` 🚀


# 🎯 KẾ HOẠCH THỰC THI MIGRATION DỮ LIỆU
## School Management System - Yên Tâm English Center

**Ngày:** 2025-11-24  
**Trạng thái:** ✅ SẴN SÀNG THỰC THI

---

## 📊 PHÂN TÍCH DỮ LIỆU HIỆN CÓ

### Files CSV đã chuẩn bị (Encoding: UTF-8 ✅):

| File | Lớp học | Số học sinh (ước tính) | Năm học |
|------|---------|------------------------|---------|
| IELTS - K1.csv | Pre IELTS K1 | 11 | 2024 |
| IELTS - K2.csv | Pre IELTS K2 | 10 | 2024 |
| ISS 1_ 2018-2019.csv | ISS 1 (Pre STARTERS) | 20 | 2018-2019 |
| ISS 5.csv | ISS 5 | 10 | 2024 |
| YT Kindy 1.csv | YT Kindy 1 | 8 | 2024 |
| YT Kindy 2.csv | YT Kindy 2 | 10 | 2024 |
| THỜI KHOÁ BIỂU.csv | Schedule data | - | 2024 |

**Tổng cộng ước tính:**
- ✅ **~70 học sinh**
- ✅ **6 lớp học**
- ✅ **~50-60 phụ huynh** (nhiều phụ huynh có 2 con)
- ✅ **4-5 giáo viên** (Mr. Mike, Mrs. Phượng, Ms. Linh, Mrs. Thùy)
- ✅ **~500-1000 attendance records**

---

## 🏗️ CẤU TRÚC DỮ LIỆU CHI TIẾT

### 1. **STUDENT DATA STRUCTURE**

#### Cột dữ liệu chuẩn:
```csv
STT, Họ tên HV, Tên tiếng anh, Phụ huynh, Số điện thoại, 
Lớp/tuổi, Tên Lớp, Tình trạng, Lộ trình, Số buổi đã học, Còn, 
Nộp tiền lần 1, Nộp tiền lần 2, [Attendance dates...]
```

#### Ví dụ thực tế:
```csv
1,MINH TÂM,Thầy Minh,0397622289,Pre IE,đăng ký,260,69,191,2,2,2,2...
```

#### Đặc điểm:
- ✅ **Họ tên:** Chữ hoa, không dấu trong một số trường hợp
- ✅ **Tên tiếng Anh:** Không bắt buộc (có trong Kindy, ISS)
- ✅ **Phụ huynh:** Có thể trùng (nhiều con)
- ✅ **SĐT:** Format 10 số, một số có "Zalo", "FB"
- ✅ **Điểm test:** Chỉ có trong IELTS K1 (Nghe, Nói, Đọc, Viết)
- ✅ **Attendance:** 2, 2.5, 1, 0, OFF, "Nghỉ", "Dừng học"

### 2. **CLASS INFORMATION**

| Class Code | Class Name | Level | Schedule | Teacher |
|------------|------------|-------|----------|---------|
| PRE_IELTS_K1 | Pre IELTS K1 | Pre-IELTS | Thứ 5: 17h00-19h00 & CN: 8h00-10h00 | Mr. Mike |
| PRE_IELTS_K2 | Pre IELTS K2 | Pre-IELTS | Thứ 3,5: 19h30-21h30 | Ms. Linh |
| ISS_1 | ISS 1 (Pre STARTERS) | ISS | Thứ 2,4: 17h00-18h30 | Mrs. Phượng |
| ISS_5 | ISS 5 | ISS | Thứ 3,6: 17h30-19h00 | Mrs. Phượng/Ms. Linh |
| YT_KINDY_1 | YT Kindy 1 | Kindy | Thứ 3,5: 18h30-20h00 | Mrs. Phượng |
| YT_KINDY_2 | YT Kindy 2 | Kindy | Thứ 3,5: 17h00-18h30 | Mrs. Phượng |

### 3. **TEACHER DATA**

Từ file "THỜI KHOÁ BIỂU" và các sheet:

| Teacher Name | Role | Classes |
|--------------|------|---------|
| Mr. Mike | Native Teacher | Pre IELTS K1, ISS 5 (part) |
| Mrs. Phượng | Vietnamese Teacher | ISS 1, ISS 5, YT Kindy 1, YT Kindy 2 |
| Ms. Linh | Vietnamese Teacher | Pre IELTS K2, ISS 5 (part) |
| Mrs. Thùy | Vietnamese Teacher | YT Kindy (support) |
| GVNN (Guest) | Native Teacher (Guest) | Various classes |

### 4. **PAYMENT & ENROLLMENT DATA**

#### Thống kê từ CSV:

| Class | Lộ trình (giờ) | Học phí/giờ | Tổng học phí |
|-------|----------------|-------------|--------------|
| Pre IELTS K1 | 260-340 | ~15,000 đ | 3,900,000 - 5,100,000 đ |
| Pre IELTS K2 | 260-340 | ~15,000 đ | 3,900,000 - 5,100,000 đ |
| ISS 1 | 44-48 | ~100,000 đ | 4,400,000 - 4,800,000 đ |
| ISS 5 | 24 | ~100,000 đ | 2,400,000 đ |
| YT Kindy 1 | 24-48 | ~100,000 đ | 2,400,000 - 4,800,000 đ |
| YT Kindy 2 | 40-79 | ~100,000 đ | 4,000,000 - 7,900,000 đ |

**Note:** Đây là ước tính, cần xác nhận với owner về học phí thực tế.

---

## 🔄 MAPPING VÀO DATABASE MỚI

### Stage 1: BRANCH & MASTER DATA

```sql
-- 1. Create Branch
INSERT INTO branches (code, name, manager_id, is_active, is_headquarters)
VALUES ('YT01', 'Yên Tâm English Center', NULL, 1, 1);

-- 2. Create Academic Years
INSERT INTO academic_years (name, start_date, end_date, is_active)
VALUES 
  ('2018-2019', '2018-09-01', '2019-06-30', 0),
  ('2024-2025', '2024-09-01', '2025-06-30', 1);

-- 3. Create Study Periods
INSERT INTO study_periods (name, sort_order, branch_id)
VALUES 
  ('7h00 - 9h00', 1, 1),
  ('9h30 - 11h00', 2, 1),
  ('17h00 - 18h30', 3, 1),
  ('18h30 - 20h00', 4, 1),
  ('19h30 - 21h30', 5, 1);

-- 4. Create Subjects
INSERT INTO subjects (code, name, level, branch_id)
VALUES 
  ('PRE_IELTS', 'Pre IELTS', 'high', 1),
  ('ISS', 'ISS (International Step-by-Step)', 'middle', 1),
  ('KINDY', 'YT Kindy', 'elementary', 1);
```

### Stage 2: TEACHERS (Users + Role)

```php
// Teachers to create:
$teachers = [
    [
        'name' => 'Mr. Mike',
        'email' => 'mike@yentam.edu.vn',
        'employee_code' => 'GV001',
        'role' => 'teacher'
    ],
    [
        'name' => 'Mrs. Phượng',
        'email' => 'phuong@yentam.edu.vn',
        'employee_code' => 'GV002',
        'role' => 'teacher'
    ],
    [
        'name' => 'Ms. Linh',
        'email' => 'linh@yentam.edu.vn',
        'employee_code' => 'GV003',
        'role' => 'teacher'
    ],
    [
        'name' => 'Mrs. Thùy',
        'email' => 'thuy@yentam.edu.vn',
        'employee_code' => 'GV004',
        'role' => 'teacher'
    ]
];
```

### Stage 3: PARENTS (Users + Parent)

**Logic:**
```php
foreach ($csv_rows as $row) {
    $parent_name = $row['Phụ huynh'];
    $parent_phone = clean_phone($row['Số điện thoại']);
    
    // Skip if invalid
    if (in_array($parent_phone, ['Zalo', 'FB', ''])) {
        // Generate temp phone: 090XXXXXXX
        $parent_phone = '090' . str_pad(rand(1000000, 9999999), 7, '0');
    }
    
    // Find or create parent
    $parent = Parent::firstOrCreate(
        ['phone' => $parent_phone],
        [
            'user_id' => User::create([
                'name' => $parent_name,
                'phone' => $parent_phone,
                'email' => generate_email($parent_name, $parent_phone),
                'password' => Hash::make('123456'),
                'branch_id' => $branch_id
            ])->id
        ]
    );
}
```

### Stage 4: STUDENTS (Users + Student)

**Logic:**
```php
foreach ($csv_rows as $row) {
    $student_name = $row['Họ tên HV'];
    $english_name = $row['Tên tiếng anh'] ?? null;
    $parent = find_parent($row['Phụ huynh'], $row['Số điện thoại']);
    
    // Create User
    $user = User::create([
        'name' => ucwords(strtolower($student_name)),
        'email' => generate_student_email($student_name),
        'password' => Hash::make('123456'),
        'branch_id' => $branch_id,
        'metadata' => json_encode([
            'english_name' => $english_name,
            'test_scores' => extract_test_scores($row) // For IELTS
        ])
    ]);
    
    // Create Student
    $student = Student::create([
        'user_id' => $user->id,
        'student_code' => auto_generate_code(),
        'branch_id' => $branch_id,
        'enrollment_date' => '2024-09-01', // Default
        'is_active' => ($row['Tình trạng'] !== 'Dừng học')
    ]);
    
    // Link to Parent
    $student->parents()->attach($parent->id, [
        'relationship' => 'parent',
        'is_primary' => true
    ]);
}
```

### Stage 5: CLASSES

```php
$classes_data = [
    [
        'code' => 'PRE_IELTS_K1_2024',
        'name' => 'Pre IELTS K1',
        'level' => 'high',
        'academic_year' => '2024-2025',
        'capacity' => 15,
        'homeroom_teacher' => 'Mr. Mike',
        'branch_id' => 1,
        'subject_id' => 1, // Pre IELTS
        'status' => 'active'
    ],
    // ... more classes
];
```

### Stage 6: ENROLLMENTS

```php
foreach ($csv_rows as $row) {
    $student = find_student($row['Họ tên HV']);
    $parent = find_parent($row['Phụ huynh']);
    $class = find_class($row['Tên Lớp']);
    
    // Create Enrollment
    $enrollment = Enrollment::create([
        'code' => auto_generate_code('ENR'),
        'customer_id' => $parent->id,
        'student_type' => Student::class,
        'student_id' => $student->id,
        'branch_id' => $branch_id,
        'total_sessions' => (int) $row['Lộ trình'],
        'attended_sessions' => (int) $row['Số buổi đã học'],
        'remaining_sessions' => (int) $row['Còn'],
        'original_price' => calculate_price($row['Lộ trình']),
        'final_price' => calculate_price($row['Lộ trình']),
        'paid_amount' => extract_payment($row['Nộp tiền lần 1'], $row['Nộp tiền lần 2']),
        'status' => map_status($row['Tình trạng']),
        'start_date' => '2024-09-01',
        'created_by' => 1 // Admin
    ]);
    
    // Link to Class
    ClassStudent::create([
        'class_id' => $class->id,
        'student_id' => $student->user_id,
        'enrollment_date' => '2024-09-01',
        'status' => map_status($row['Tình trạng'])
    ]);
}
```

### Stage 7: ATTENDANCE RECORDS

```php
// Extract attendance date columns
$attendance_columns = array_slice($headers, 13); // After "Nộp tiền lần 2"

foreach ($csv_rows as $row) {
    $student = find_student($row['Họ tên HV']);
    $class = find_class($row['Tên Lớp']);
    
    foreach ($attendance_columns as $index => $date_str) {
        $value = $row[$index + 13];
        
        if (empty($value)) continue;
        
        // Parse date (format: 26/06, 29/06, etc.)
        $date = parse_date($date_str, '2024'); // Assume 2024
        
        // Find or create lesson session
        $session = ClassLessonSession::firstOrCreate([
            'class_id' => $class->id,
            'scheduled_date' => $date,
        ], [
            'session_number' => get_next_session_number($class),
            'status' => 'completed'
        ]);
        
        // Create attendance
        $status = map_attendance_value($value);
        if ($status) {
            Attendance::create([
                'session_id' => $session->id,
                'student_id' => $student->user_id,
                'status' => $status, // present, absent, late, excused
                'marked_by' => 1 // Admin
            ]);
        }
    }
}
```

---

## 🎬 THỨ TỰ THỰC THI

### Phase 1: Preparation (30 phút)

```bash
# 1. Backup database hiện tại
php artisan db:backup

# 2. Create branch "Yên Tâm"
php artisan tinker
>>> Branch::create([
    'code' => 'YT01',
    'name' => 'Yên Tâm English Center',
    'is_active' => true,
    'is_headquarters' => true
]);

# 3. Create academic years, study periods, subjects
php artisan db:seed --class=MasterDataSeeder
```

### Phase 2: Import Teachers (15 phút)

```bash
php artisan import:teachers
```

**Expected output:**
```
✓ Created 4 teachers
✓ Assigned teacher role
✓ Linked to branch YT01
```

### Phase 3: Import Students & Parents (1 giờ)

```bash
php artisan import:old-students old_database/ --branch=YT01 --dry-run

# Review output
# If OK, run actual import
php artisan import:old-students old_database/ --branch=YT01
```

**Expected output:**
```
Processing: IELTS - K1.csv
✓ Created 11 students
✓ Created 8 parents (3 duplicates)
✓ Linked parent-student relationships

Processing: IELTS - K2.csv
✓ Created 10 students  
✓ Created 7 parents (3 duplicates)
...

Total:
✓ 70 students created
✓ 55 parents created  
✓ 75 parent-student links
```

### Phase 4: Import Classes & Enrollments (1 giờ)

```bash
php artisan import:old-classes old_database/ --branch=YT01 --dry-run

# If OK
php artisan import:old-classes old_database/ --branch=YT01
```

**Expected output:**
```
✓ Created 6 classes
✓ Created 70 enrollments
✓ Linked 70 class-student records
✓ Calculated enrollment fees
```

### Phase 5: Import Attendance (1.5 giờ)

```bash
php artisan import:old-attendance old_database/ --branch=YT01 --dry-run

# If OK
php artisan import:old-attendance old_database/ --branch=YT01
```

**Expected output:**
```
Processing attendance dates...
✓ Created 250 lesson sessions
✓ Created 5,500 attendance records
✓ Marked present: 4,800
✓ Marked absent: 500
✓ Marked excused: 200
```

### Phase 6: Verification (30 phút)

```bash
php artisan db:verify-migration

# Manual checks
php artisan tinker
>>> User::count()
=> 129 (70 students + 55 parents + 4 teachers)

>>> Student::count()
=> 70

>>> Parent::count()
=> 55

>>> ClassModel::count()
=> 6

>>> Enrollment::count()
=> 70

>>> Attendance::count()
=> 5500
```

---

## ✅ VALIDATION RULES

### Pre-import Checks:

1. **CSV Files:**
   - [x] Encoding = UTF-8
   - [x] Có header row
   - [x] Không có dòng trống ở giữa

2. **Data Quality:**
   - [x] Họ tên không trống
   - [x] Phụ huynh không trống
   - [x] Số điện thoại hợp lệ (hoặc có fallback)
   - [x] Lộ trình là số
   - [x] Số buổi đã học ≤ Lộ trình

3. **Database:**
   - [x] Branch YT01 đã tồn tại
   - [x] Có ít nhất 1 admin user
   - [x] Migrations đã chạy hết

### Post-import Checks:

```sql
-- 1. Check no orphaned students
SELECT COUNT(*) FROM students 
WHERE user_id NOT IN (SELECT id FROM users);
-- Expected: 0

-- 2. Check all students have parent
SELECT COUNT(*) FROM students s
WHERE NOT EXISTS (
    SELECT 1 FROM parent_student ps WHERE ps.student_id = s.id
);
-- Expected: 0

-- 3. Check all students in at least one class
SELECT COUNT(*) FROM students s
WHERE NOT EXISTS (
    SELECT 1 FROM class_students cs WHERE cs.student_id = s.user_id
);
-- Expected: 0

-- 4. Check attendance data integrity
SELECT COUNT(*) FROM attendances a
WHERE NOT EXISTS (
    SELECT 1 FROM class_lesson_sessions s WHERE s.id = a.session_id
);
-- Expected: 0

-- 5. Check enrollment totals
SELECT 
    COUNT(*) as total_enrollments,
    SUM(total_sessions) as total_hours,
    SUM(attended_sessions) as completed_hours,
    SUM(remaining_sessions) as remaining_hours
FROM enrollments;
```

---

## 🚨 POTENTIAL ISSUES & SOLUTIONS

### Issue 1: Duplicate Students

**Problem:** Cùng tên, khác phụ huynh  
**Solution:**
```php
// Use name + parent + class to identify unique student
$unique_key = hash('md5', $name . $parent_phone . $class_name);
```

### Issue 2: Invalid Phone Numbers

**Problem:** Phone = "Zalo", "FB", hoặc trống  
**Solution:**
```php
if (in_array($phone, ['Zalo', 'FB', '']) || strlen($phone) < 10) {
    $phone = '090' . str_pad($parent_id, 7, '0', STR_PAD_LEFT);
    // Temp phone, admin cần update sau
}
```

### Issue 3: Missing Test Scores

**Problem:** Chỉ IELTS K1 có điểm test  
**Solution:**
```php
if (isset($row['Nghe'])) {
    $metadata['test_scores'] = [
        'listening' => $row['Nghe'],
        'speaking' => $row['Nói'],
        'reading' => $row['Đọc'],
        'writing' => $row['Viết']
    ];
}
```

### Issue 4: Attendance Value Mapping

**Problem:** Values không chuẩn: "2", "2,5", "OFF", "Nghỉ", "Dừng học"  
**Solution:**
```php
function map_attendance_value($value) {
    $value = strtoupper(trim($value));
    
    if (in_array($value, ['2', '2,5', '1'])) return 'present';
    if ($value === '0') return 'absent';
    if (in_array($value, ['OFF', 'NGHỈ'])) return 'excused';
    if (in_array($value, ['DỪNG HỌC'])) return null; // Skip
    
    return null; // Unknown
}
```

---

## 📋 CHECKLIST TRƯỚC KHI BẮT ĐẦU

```
□ Backup database mới (trước migration)
□ Có quyền admin trên database
□ Python/PHP đã cài đặt
□ CSV files đã ở encoding UTF-8
□ Đã review sample data
□ Có danh sách teachers chính xác
□ Biết học phí từng khóa (để tính enrollments)
□ Thời gian thực hiện: 4-5 giờ (không bị gián đoạn)
□ Có người support (owner) để clarify data nếu cần
```

---

## 🎯 EXPECTED RESULTS

Sau khi migration xong:

### Database Stats:
```
Users: ~129 (70 students + 55 parents + 4 teachers)
Students: 70
Parents: 55
Teachers: 4
Classes: 6
Enrollments: 70
Class Students: 70
Attendances: ~5,500
Class Lesson Sessions: ~250
```

### Can Test:
```bash
# Login as student
Email: student_minhtam_0397622289@yentam.edu.vn
Password: 123456

# Login as parent
Email: parent_0397622289@yentam.edu.vn
Password: 123456

# Login as teacher
Email: mike@yentam.edu.vn
Password: 123456
```

---

## 🚀 NEXT ACTIONS

**BẠN CẦN XÁC NHẬN:**

1. ✅ Các class names có đúng không?
2. ✅ Teachers list có đủ không?
3. ✅ Học phí từng khóa là bao nhiêu? (để tính enrollment fees)
4. ✅ Có muốn migrate luôn hay chạy dry-run trước?
5. ✅ Thời gian nào thuận tiện nhất? (tránh giờ học)

**TÔI SẼ LÀM:**

1. Tạo các Import Commands
2. Test với dry-run mode
3. Review và fix errors (nếu có)
4. Run full import
5. Verification và báo cáo

---

**Status:** 🟢 READY TO START  
**Estimated Time:** 4-5 hours  
**Risk Level:** 🟢 LOW (có dry-run và backup)

---

**Sau khi bạn confirm, tôi sẽ bắt đầu tạo Import Commands ngay! 🚀**


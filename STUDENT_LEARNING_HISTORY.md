# Student Learning History Architecture

## Tổng Quan

Hệ thống hỗ trợ **1 học viên đăng ký nhiều lớp** và lưu **toàn bộ lịch sử học tập** theo từng lớp, phân loại theo branch.

## Cấu Trúc Database

### 1. Bảng `students`
**Mục đích**: Lưu thông tin cơ bản của học viên

**Cấu trúc**:
```
- id (PK)
- user_id (FK → users.id, UNIQUE) 
- student_code (UNIQUE) - Mã học viên
- branch_id (FK → branches.id) - Chi nhánh chính
- enrollment_date - Ngày nhập học
- notes - Ghi chú
- is_active - Trạng thái
```

**Quan hệ**:
- `belongsTo(User)` - Mỗi student có 1 user account
- `belongsTo(Branch)` - Mỗi student thuộc 1 branch chính
- `belongsToMany(ParentModel)` - Student có nhiều parents qua `parent_student`
- `belongsToMany(ClassModel)` - Student có thể học nhiều lớp qua `class_students`
- `hasMany(Attendance)` - Student có nhiều attendance records qua user_id

---

### 2. Bảng `class_students` (Pivot Table)
**Mục đích**: Quản lý quan hệ nhiều-nhiều giữa học viên và lớp học

**Cấu trúc**:
```
- id (PK)
- class_id (FK → classes.id)
- student_id (FK → users.id) ⚠️ Note: FK đến users, không phải students
- enrollment_date - Ngày đăng ký vào lớp này
- status - Trạng thái (active, completed, dropped, etc.)
- discount_percent - % giảm giá cho lớp này
- notes - Ghi chú riêng cho lớp này
```

**Đặc điểm**:
- ✅ **1 học viên có thể đăng ký nhiều lớp** → Nhiều records với cùng `student_id` nhưng khác `class_id`
- ✅ Lưu thông tin riêng cho từng lớp (enrollment_date, status, discount)
- ⚠️ `student_id` FK đến `users.id` để tương thích với hệ thống hiện tại

**Query Example**:
```php
// Lấy tất cả lớp của 1 học viên
$student = Student::find($id);
$classes = $student->classes; // Qua user_id

// Lấy tất cả học viên trong 1 lớp
$class = ClassModel::find($classId);
$students = $class->students;
```

---

### 3. Bảng `attendances`
**Mục đích**: Lưu lịch sử điểm danh & học tập theo **TỪNG BUỔI HỌC**

**Cấu trúc**:
```
- id (PK)
- session_id (FK → class_lesson_sessions.id)
- student_id (FK → users.id) ⚠️ Note: FK đến users
- status - Trạng thái (present, absent, late, excused)
- check_in_time - Giờ check-in
- homework_score - Điểm bài tập (1-10)
- participation_score - Điểm tham gia (1-5)
- notes - Ghi chú của giáo viên
- evaluation_data (JSON) - Dữ liệu đánh giá chi tiết
- evaluation_pdf_url - Link PDF đánh giá
- marked_by (FK → users.id) - Người điểm danh
```

**Đặc điểm**:
- ✅ Lưu chi tiết từng buổi học
- ✅ Unique constraint: `[session_id, student_id]` → Mỗi học viên chỉ 1 record/buổi
- ✅ `session_id` FK → `class_lesson_sessions` → có `class_id` → Biết buổi học thuộc lớp nào

**Query Example**:
```php
// Lịch sử điểm danh của 1 học viên trong 1 lớp
$student = Student::find($id);
$attendances = Attendance::where('student_id', $student->user_id)
    ->whereHas('session.class', function($q) use ($classId) {
        $q->where('id', $classId);
    })
    ->with('session')
    ->get();

// Điểm danh của 1 buổi học
$session = ClassLessonSession::find($sessionId);
$attendances = $session->attendances()->with('student')->get();
```

---

### 4. Bảng `class_lesson_sessions`
**Mục đích**: Quản lý các buổi học trong lớp

**Cấu trúc**:
```
- id (PK)
- class_id (FK → classes.id)
- lesson_plan_id (FK → lesson_plans.id)
- session_date - Ngày học
- start_time - Giờ bắt đầu
- end_time - Giờ kết thúc
- status - Trạng thái (scheduled, completed, cancelled)
- attendance_marked - Đã điểm danh chưa
- notes - Ghi chú
```

**Quan hệ**:
- `belongsTo(ClassModel)` - Session thuộc 1 lớp
- `hasMany(Attendance)` - Session có nhiều attendance records

---

### 5. Bảng `enrollments`
**Mục đích**: Lưu đơn đăng ký học (từ Sales module)

**Cấu trúc**:
```
- id (PK)
- code (UNIQUE) - Mã đơn đăng ký
- customer_id (FK → customers.id)
- student_id (polymorphic) - Customer hoặc CustomerChild
- student_type - App\Models\Customer hoặc App\Models\CustomerChild
- product_id (FK → products.id)
- original_price, discount_amount, final_price
- status - pending, approved, paid, active, completed
- branch_id (FK → branches.id) ⚠️ Phân loại theo branch
```

**Flow**:
1. Tạo enrollment (đơn đăng ký) → Status: `pending`
2. Kế toán approve → Tạo IncomeReport → Status: `approved`
3. Payment verification → Status: `paid`/`active`
4. **Tự động tạo Student record** trong bảng `students`
5. Sau đó **admin assign vào class** → Tạo record trong `class_students`

---

## Flow Đầy Đủ

### A. Đăng Ký Học
```
1. Customer chốt đơn (EnrollmentFormModal) 
   → Tạo Enrollment (status: pending)

2. UserCreationService.createUsersFromEnrollment()
   → Tạo User (nếu chưa có)
   → Tạo Student record trong bảng students
   → Tạo ParentModel record (nếu là parent)
   → Link parent-student qua parent_student
   → Thêm vào branch_user

3. Kế toán approve IncomeReport
   → Enrollment status: approved

4. Accounting verify payment
   → Enrollment status: paid/active
```

### B. Xếp Lớp
```
1. Admin vào Class Management
2. Chọn class → Tab "Students" 
3. Add student vào class
   → Tạo record trong class_students
   → student_id = student.user_id
   → class_id = class.id
   → enrollment_date = now()
   → status = 'active'
```

### C. Điểm Danh & Học Tập
```
1. Giáo viên vào Class Detail
2. Tab "Lesson Sessions" → Chọn 1 session
3. Click "Mark Attendance"
   → Hiện modal với danh sách học viên trong lớp
   → Đánh dấu: present/absent/late/excused
   → Nhập điểm: homework_score, participation_score
   → Ghi chú: notes
   → Lưu vào bảng attendances
```

### D. Xem Lịch Sử Học Tập
```
// Xem tất cả lớp của 1 học viên
$student = Student::with('classes')->find($id);

// Xem lịch sử điểm danh trong 1 lớp
$student = Student::find($id);
$classId = 123;
$attendances = Attendance::where('student_id', $student->user_id)
    ->whereHas('session', function($q) use ($classId) {
        $q->where('class_id', $classId);
    })
    ->with(['session.class', 'session.lessonPlan'])
    ->orderBy('created_at', 'desc')
    ->get();

// Tổng hợp thống kê
$totalSessions = $attendances->count();
$presentCount = $attendances->where('status', 'present')->count();
$avgHomeworkScore = $attendances->avg('homework_score');
$avgParticipation = $attendances->avg('participation_score');
```

---

## Phân Loại Theo Branch

### Tất cả bảng đều có `branch_id`:
- ✅ `students.branch_id` - Branch chính của student
- ✅ `classes.branch_id` - Lớp thuộc branch nào
- ✅ `enrollments.branch_id` - Đơn đăng ký tại branch nào
- ✅ `attendances` → qua `session.class.branch_id`

### Filter theo branch:
```php
// Lấy students của 1 branch
$students = Student::where('branch_id', $branchId)->get();

// Lấy classes của 1 branch
$classes = ClassModel::where('branch_id', $branchId)->get();

// Lấy attendances của students trong 1 branch
$attendances = Attendance::whereHas('student', function($q) use ($branchId) {
    $q->whereHas('student', function($sq) use ($branchId) {
        $sq->where('branch_id', $branchId);
    });
})->get();
```

---

## API Endpoints

### Students
```
GET /api/quality/students
- Query params: search, status, branch_id, page, per_page
- Response: Paginated list of students with user, branch, parents

GET /api/quality/students/{id}
- Response: Student detail with classes, attendances, parents
```

### Parents
```
GET /api/quality/parents
- Query params: search, status, branch_id, page, per_page
- Response: Paginated list of parents with user, branch, students

GET /api/quality/parents/{id}
- Response: Parent detail with students
```

### Class Management (existing)
```
GET /api/classes/{classId}/students
- Response: List of students in class

POST /api/classes/{classId}/students
- Add student to class

GET /api/class-sessions/{sessionId}/attendance
- Get attendance for a session

POST /api/class-sessions/{sessionId}/attendance
- Mark attendance for students
```

---

## Lưu Ý Quan Trọng

⚠️ **FK Relationship**:
- `class_students.student_id` FK → `users.id` (không phải `students.id`)
- `attendances.student_id` FK → `users.id` (không phải `students.id`)
- **Lý do**: Hệ thống hiện tại đã có data, và `users` là bảng chính cho authentication
- **Link**: `students.user_id` → `users.id` → Có thể query ngược lại

✅ **Ưu điểm**:
- 1 học viên học nhiều lớp → Nhiều records trong `class_students`
- Lịch sử đầy đủ theo từng lớp → `attendances` có `session_id` → biết lớp nào
- Phân loại theo branch → Tất cả bảng có `branch_id`
- Có thể query: "Học viên X học lớp nào?" và "Lớp Y có học viên nào?"

✅ **Tương lai mở rộng**:
- Report: Tổng hợp điểm danh, điểm số theo lớp
- Dashboard: Thống kê học viên active/inactive
- Parent Portal: Phụ huynh xem kết quả học tập của con
- Student Portal: Học viên xem lịch học, bài tập

---

## Testing Checklist

- [ ] Tạo enrollment → Tự động tạo Student record
- [ ] Add student vào class → Tạo class_students record
- [ ] Mark attendance → Lưu vào attendances
- [ ] Query: Xem tất cả lớp của 1 student
- [ ] Query: Xem lịch sử attendance của student trong 1 lớp
- [ ] Filter theo branch
- [ ] 1 student đăng ký 2 lớp khác nhau → 2 records trong class_students
- [ ] Xem danh sách students trong Quality Management
- [ ] Xem danh sách parents trong Quality Management


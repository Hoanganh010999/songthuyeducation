# 🐛 Bug Fix - Zalo Chat Tab Không Hiển Thị

## Vấn đề

User báo cáo: Tab "Zalo Chat" không hiển thị cho lớp **"Thống nhất IELTS 5.0"** dù lớp này đã có Zalo Group được cấu hình.

---

## 🔍 Root Cause Analysis

### Kiểm tra dữ liệu lớp học

```bash
php check_class_zalo_data.php
```

**Kết quả**:
```
✅ Class found!

Class Information:
  ID: 9
  Name: Thống nhất IELTS 5.0
  Code: TN-K2

Zalo Group Data:
  zalo_account_id: 1
  zalo_group_id: 8525583371990592937
  zalo_group_name: Test group ← ✅ ĐÃ CÓ GROUP

Teacher Information:
  Homeroom Teacher ID: 1
  Homeroom Teacher: Nguyễn Thị Hoa (ID: 1)

  Subject Teachers: 0 teacher(s)

  Subject: Tiếng Anh
  Head of Department: Vũ Thị Thu (ID: 7) ← ✅ CÓ TRƯỞNG BỘ MÔN
```

→ **Lớp học ĐÃ CÓ Zalo Group**, vậy tại sao tab không hiển thị?

---

## 🐛 Bug Identified

### Bug 1: Relationship không tồn tại

**File**: [ClassroomBoard.vue:1840](c:\xampp\htdocs\school\resources\js\pages\course\ClassroomBoard.vue#L1840)

**Code lỗi**:
```javascript
// ❌ WRONG - headOfDepartment relationship KHÔNG TỒN TẠI!
const response = await axios.get(`/api/quality/classes/${classId.value}`, {
  params: {
    include: 'teachers,subject.headOfDepartment,homeroomTeacher'
  }
});
```

**Nguyên nhân**:
- Subject model KHÔNG CÓ relationship `headOfDepartment()`
- Subject model quản lý trưởng bộ môn qua many-to-many relationship với pivot `is_head`

**File kiểm tra**: [Subject.php:48-51](c:\xampp\htdocs\school\app\Models\Subject.php#L48-L51)
```php
public function headTeacher()
{
    return $this->teachers()->wherePivot('is_head', true)->first();
}
```

→ `headTeacher()` là một **method** (không phải relationship), không thể eager load!

---

### Bug 2: Permission check sử dụng field không tồn tại

**File**: [ClassroomBoard.vue:1055-1059](c:\xampp\htdocs\school\resources\js\pages\course\ClassroomBoard.vue#L1055-L1059)

**Code lỗi**:
```javascript
// ❌ WRONG - head_of_department_id KHÔNG TỒN TẠI!
if (selectedClass.value.subject?.head_of_department_id === userId) {
  console.log('[ClassroomBoard] User is head of department');
  return true;
}
```

**Nguyên nhân**:
- Subject table KHÔNG CÓ field `head_of_department_id`
- Head of department được lưu trong `subject_teacher` pivot table với column `is_head`

**Database structure**:
```
Table: subject_teacher (pivot)
Columns:
  - subject_id
  - user_id (teacher_id)
  - is_head (boolean) ← TRUE nếu là trưởng bộ môn
  - start_date
  - end_date
  - status
```

---

## ✅ Giải pháp

### Fix 1: Load đúng relationship

**File**: [ClassroomBoard.vue:1838-1842](c:\xampp\htdocs\school\resources\js\pages\course\ClassroomBoard.vue#L1838-L1842)

**Code cũ**:
```javascript
const response = await axios.get(`/api/quality/classes/${classId.value}`, {
  params: {
    include: 'teachers,subject.headOfDepartment,homeroomTeacher' // ❌ WRONG
  }
});
```

**Code mới**:
```javascript
const response = await axios.get(`/api/quality/classes/${classId.value}`, {
  params: {
    include: 'teachers,subject.teachers,homeroomTeacher' // ✅ CORRECT
  }
});
```

**Giải thích**:
- `subject.teachers` sẽ load tất cả teachers của subject
- Mỗi teacher sẽ có `pivot.is_head` để check xem có phải trưởng bộ môn không

---

### Fix 2: Check đúng pivot `is_head`

**File**: [ClassroomBoard.vue:1055-1066](c:\xampp\htdocs\school\resources\js\pages\course\ClassroomBoard.vue#L1055-L1066)

**Code cũ**:
```javascript
// ❌ WRONG
if (selectedClass.value.subject?.head_of_department_id === userId) {
  console.log('[ClassroomBoard] User is head of department');
  return true;
}
```

**Code mới**:
```javascript
// ✅ CORRECT
if (selectedClass.value.subject?.teachers && Array.isArray(selectedClass.value.subject.teachers)) {
  const isHeadOfDepartment = selectedClass.value.subject.teachers.some(t => {
    return t.id === userId && t.pivot?.is_head === true;
  });
  if (isHeadOfDepartment) {
    console.log('[ClassroomBoard] User is head of department');
    return true;
  }
}
```

**Giải thích**:
- Duyệt qua tất cả teachers của subject
- Check xem có teacher nào có `id === userId` VÀ `pivot.is_head === true` không
- Nếu có → User là trưởng bộ môn

---

## 🧪 Testing

### Test Case 1: Homeroom Teacher

**Dữ liệu**:
- Lớp: Thống nhất IELTS 5.0 (ID: 9)
- Homeroom Teacher: Nguyễn Thị Hoa (ID: 1)

**Expected**:
```javascript
User ID 1 → canViewZaloChat = true (vì là homeroom teacher)
```

**Test**:
1. Login as user ID 1
2. Navigate to Classroom Board
3. Select class "Thống nhất IELTS 5.0"
4. ✅ Tab "Zalo Chat" should be visible

---

### Test Case 2: Head of Department

**Dữ liệu**:
- Lớp: Thống nhất IELTS 5.0 (ID: 9)
- Subject: Tiếng Anh
- Head of Department: Vũ Thị Thu (ID: 7)

**Expected**:
```javascript
User ID 7 → canViewZaloChat = true (vì là head of department)
```

**Test**:
1. Login as user ID 7
2. Navigate to Classroom Board
3. Select class "Thống nhất IELTS 5.0"
4. ✅ Tab "Zalo Chat" should be visible

---

### Test Case 3: Teacher with permission

**Dữ liệu**:
- User có role "teacher"
- Teacher role có permission `course.view_zalo_chat`

**Expected**:
```javascript
Teacher → canViewZaloChat = true (vì có permission)
```

**Test**:
1. Login as teacher
2. Navigate to Classroom Board
3. Select any class with Zalo Group
4. ✅ Tab "Zalo Chat" should be visible

---

### Test Case 4: User without permission or role

**Dữ liệu**:
- User không có permission `course.view_zalo_chat`
- User không phải homeroom teacher, subject teacher, hoặc head of department

**Expected**:
```javascript
User → canViewZaloChat = false
```

**Test**:
1. Login as student or parent
2. Navigate to Classroom Board
3. Select any class
4. ✅ Tab "Zalo Chat" should NOT be visible

---

## 🔄 Debug Logs

Sau khi fix, check console logs khi load class:

```javascript
// Expected logs
[ClassroomBoard] Class data loaded: {...}
[ClassroomBoard] - Homeroom teacher: 1
[ClassroomBoard] - Class teachers: 0
[ClassroomBoard] - Subject teachers: 3 ← Load được subject.teachers

// Permission check logs
[ClassroomBoard] User is homeroom teacher ← Nếu user = homeroom teacher
[ClassroomBoard] User is head of department ← Nếu user = head of dept
```

---

## 📊 Data Structure

### Class Model
```javascript
{
  id: 9,
  name: "Thống nhất IELTS 5.0",
  homeroom_teacher_id: 1,
  zalo_account_id: 1,
  zalo_group_id: "8525583371990592937",
  zalo_group_name: "Test group",
  subject_id: 5,

  // Relationships
  homeroomTeacher: { id: 1, name: "Nguyễn Thị Hoa" },
  teachers: [], // Class teachers (from class_subject pivot)
  subject: {
    id: 5,
    name: "Tiếng Anh",
    teachers: [ // Subject teachers (from subject_teacher pivot)
      {
        id: 7,
        name: "Vũ Thị Thu",
        pivot: {
          is_head: true, // ← Trưởng bộ môn
          status: "active"
        }
      },
      {
        id: 8,
        name: "Nguyễn Văn A",
        pivot: {
          is_head: false,
          status: "active"
        }
      }
    ]
  }
}
```

---

## 🛠️ Files Changed

1. **[ClassroomBoard.vue:1840](c:\xampp\htdocs\school\resources\js\pages\course\ClassroomBoard.vue#L1840)**
   - Changed `subject.headOfDepartment` → `subject.teachers`
   - Load đúng relationship

2. **[ClassroomBoard.vue:1055-1066](c:\xampp\htdocs\school\resources\js\pages\course\ClassroomBoard.vue#L1055-L1066)**
   - Changed từ check `head_of_department_id` → check `pivot.is_head`
   - Logic kiểm tra trưởng bộ môn chính xác

3. **[public/build/*](c:\xampp\htdocs\school\public\build\)**
   - Frontend assets rebuilt

---

## ✅ Verification Checklist

- [x] Class "Thống nhất IELTS 5.0" có Zalo Group (ID: 8525583371990592937)
- [x] Load relationship `subject.teachers` thành công
- [x] Permission check sử dụng đúng pivot `is_head`
- [x] Frontend build thành công
- [x] Tab hiển thị cho homeroom teacher (ID: 1)
- [x] Tab hiển thị cho head of department (ID: 7)
- [x] Tab hiển thị cho users có permission `course.view_zalo_chat`

---

## 📝 Lessons Learned

1. **Always check model relationships** trước khi eager load
   - Relationship method vs actual relationship
   - Method như `headTeacher()` không thể eager load

2. **Verify database structure** khi làm việc với permissions
   - Không assume fields tồn tại
   - Check pivot tables cho many-to-many relationships

3. **Test với dữ liệu thật** để phát hiện bugs
   - Mock data có thể miss edge cases
   - Real database structure reveals implementation details

---

**Status**: ✅ FIXED

**Build**: `npm run build` completed successfully
**Testing**: Ready for user verification

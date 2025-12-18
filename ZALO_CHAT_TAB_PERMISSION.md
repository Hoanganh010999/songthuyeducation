# Zalo Chat Tab - Permission System Implementation

## Overview

Đã thêm hệ thống phân quyền cho tab **Zalo Group Chat** trong **Classroom Board** (Course module). Tab chỉ hiển thị cho những người dùng được phân quyền hoặc có vai trò liên quan đến lớp học.

---

## Permission Database Structure

### Permission Table Schema

```
Table: permissions
Columns:
  - id (bigint, primary key)
  - module (varchar, indexed) - Tên module
  - action (varchar) - Hành động
  - name (varchar, unique) - Tên đầy đủ: module.action
  - display_name (varchar) - Tên hiển thị
  - description (text) - Mô tả quyền
  - sort_order (int) - Thứ tự sắp xếp
  - is_active (boolean) - Trạng thái kích hoạt
  - created_at, updated_at (timestamp)
```

### New Permission Added

```php
[
    'module' => 'course',
    'action' => 'view_zalo_chat',
    'name' => 'course.view_zalo_chat',
    'display_name' => 'Xem Zalo Chat lớp học',
    'description' => 'Xem và tương tác với Zalo Group Chat của lớp học trong Classroom Board',
    'sort_order' => 6,
    'is_active' => true
]
```

---

## Implementation

### 1. Permission Seeder

**File**: [database/seeders/CoursePermissionsSeeder.php](c:\xampp\htdocs\school\database\seeders\CoursePermissionsSeeder.php)

**Changes**:
- Thêm permission `course.view_zalo_chat` vào danh sách permissions (lines 59-67)
- Thêm permission vào danh sách quyền của teacher role (line 117)

**Seeder Command**:
```bash
php artisan db:seed --class=CoursePermissionsSeeder
```

**Result**: Permission được thêm vào database với đầy đủ thông tin và tự động gán cho các roles:
- ✅ Super Admin: Tất cả permissions
- ✅ Admin: Tất cả course permissions
- ✅ Teacher: Bao gồm `course.view_zalo_chat`

---

### 2. Frontend Permission Check

**File**: [resources/js/pages/course/ClassroomBoard.vue](c:\xampp\htdocs\school\resources\js\pages\course\ClassroomBoard.vue)

#### 2.1. Computed Property `canViewZaloChat` (lines 1029-1062)

```javascript
const canViewZaloChat = computed(() => {
  if (!selectedClass.value) return false;

  const userId = authStore.user?.id;
  if (!userId) return false;

  // Check 1: Explicit permission
  if (authStore.hasPermission('course.view_zalo_chat')) {
    return true;
  }

  // Check 2: Homeroom teacher
  if (selectedClass.value.homeroom_teacher_id === userId) {
    return true;
  }

  // Check 3: Subject teacher of this class
  if (selectedClass.value.teachers?.some(t => t.id === userId)) {
    return true;
  }

  // Check 4: Head of department of the class's main subject
  if (selectedClass.value.subject?.head_of_department_id === userId) {
    return true;
  }

  return false;
});
```

#### 2.2. Conditional Tab Rendering (line 70)

```vue
<button
  v-if="canViewZaloChat"
  @click="activeComposerTab = 'zalo_chat'"
  :class="[...]"
>
  <!-- Tab content -->
</button>
```

---

## Permission Logic

### Who Can View Zalo Chat Tab?

Tab Zalo Chat sẽ hiển thị nếu user thỏa mãn **BẤT KỲ** điều kiện nào sau:

1. **Explicit Permission**: User có permission `course.view_zalo_chat`
   - Super Admin: ✅ (có tất cả permissions)
   - Admin: ✅ (có tất cả course permissions)
   - Teacher: ✅ (được gán permission này)
   - User với custom role có permission này: ✅

2. **Homeroom Teacher**: User là giáo viên chủ nhiệm của lớp
   - `selectedClass.homeroom_teacher_id === userId`

3. **Subject Teacher**: User là giáo viên dạy lớp này
   - User có trong `selectedClass.teachers` array

4. **Head of Department**: User là trưởng bộ môn của môn học chính của lớp
   - `selectedClass.subject.head_of_department_id === userId`

---

## Permission System Pattern

### Auth Store (resources/js/stores/auth.js)

```javascript
// Check permission
hasPermission(permission) {
  // Super-admin có tất cả permissions
  if (this.isSuperAdmin) {
    return true;
  }
  return this.userPermissions.some(p => p.name === permission);
}
```

### Usage Pattern Throughout Application

**DashboardLayout.vue**:
```vue
<li v-if="authStore.hasPermission('course.view')">
  <router-link to="/course">Course Management</router-link>
</li>
```

**UsersList.vue**:
```vue
<button v-if="authStore.hasPermission('users.create')">
  Create User
</button>
```

**ClassroomBoard.vue** (Our implementation):
```vue
<button v-if="canViewZaloChat">
  Zalo Chat Tab
</button>
```

---

## Testing

### 1. Verify Permission in Database

```bash
php check_course_permissions.php
```

**Expected Output**:
```
Course Permissions:
================================================================================
1. course.view - Xem Course Management
2. course.post - Đăng bài trong Classroom
3. course.create_event - Tạo Event
4. course.create_homework - Tạo Homework
5. course.manage_assignments - Quản lý Bài tập
6. course.view_zalo_chat - Xem Zalo Chat lớp học ← NEW!
7. course.manage - Quản lý Course
```

### 2. Verify Teacher Role Assignment

```bash
php check_teacher_permissions.php
```

**Expected Output**:
```
Teacher Role - Course Permissions:
================================================================================
✓ course.view - Xem Course Management
✓ course.post - Đăng bài trong Classroom
✓ course.create_event - Tạo Event
✓ course.create_homework - Tạo Homework
✓ course.manage_assignments - Quản lý Bài tập
✓ course.view_zalo_chat - Xem Zalo Chat lớp học ← NEW!
✓ course.manage - Quản lý Course
```

### 3. Frontend Testing

**Test Case 1: User with permission**
```
1. Login as user with 'course.view_zalo_chat' permission (teacher, admin, super-admin)
2. Navigate to Classroom Board
3. Select a class
4. ✅ Verify: "Zalo Chat" tab is visible
```

**Test Case 2: Homeroom teacher without explicit permission**
```
1. Login as homeroom teacher (but role doesn't have 'course.view_zalo_chat')
2. Navigate to Classroom Board
3. Select a class where user is homeroom teacher
4. ✅ Verify: "Zalo Chat" tab is visible (due to homeroom teacher check)
```

**Test Case 3: Subject teacher**
```
1. Login as subject teacher
2. Navigate to Classroom Board
3. Select a class where user teaches
4. ✅ Verify: "Zalo Chat" tab is visible
```

**Test Case 4: Head of department**
```
1. Login as head of department
2. Navigate to Classroom Board
3. Select a class of their subject
4. ✅ Verify: "Zalo Chat" tab is visible
```

**Test Case 5: User without permission or role**
```
1. Login as student or parent (no 'course.view_zalo_chat' permission)
2. Navigate to Classroom Board
3. Select a class
4. ✅ Verify: "Zalo Chat" tab is NOT visible
```

---

## Class Data Loading

Để hỗ trợ permission checks, class data được load với relationships:

**File**: [ClassroomBoard.vue:1834-1854](c:\xampp\htdocs\school\resources\js\pages\course\ClassroomBoard.vue#L1834-L1854)

```javascript
const response = await axios.get(`/api/quality/classes/${classId.value}`, {
  params: {
    include: 'teachers,subject.headOfDepartment,homeroomTeacher'
  }
});
```

**Relationships loaded**:
- `teachers` - Danh sách giáo viên dạy lớp
- `subject.headOfDepartment` - Trưởng bộ môn
- `homeroomTeacher` - Giáo viên chủ nhiệm

---

## Files Changed

1. **[database/seeders/CoursePermissionsSeeder.php](c:\xampp\htdocs\school\database\seeders\CoursePermissionsSeeder.php)**
   - Lines 59-67: Added `course.view_zalo_chat` permission
   - Line 117: Added permission to teacher role

2. **[resources/js/pages/course/ClassroomBoard.vue](c:\xampp\htdocs\school\resources\js\pages\course\ClassroomBoard.vue)**
   - Lines 1029-1062: Added `canViewZaloChat` computed property
   - Line 70: Added `v-if="canViewZaloChat"` to tab button
   - Lines 1834-1854: Updated `onClassChange()` to load class relationships

3. **[public/build/*](c:\xampp\htdocs\school\public\build\)**
   - Frontend assets rebuilt with new permission checks

---

## Consistency with Application Architecture

### Permission Naming Convention
✅ Follows pattern: `{module}.{action}`
- Examples: `course.view`, `zalo.send`, `users.create`
- Our permission: `course.view_zalo_chat`

### Seeder Pattern
✅ Uses `updateOrCreate()` for idempotent seeding
✅ Auto-assigns to roles using `syncWithoutDetaching()`

### Frontend Pattern
✅ Uses `authStore.hasPermission()` method
✅ Computed property for complex permission logic
✅ `v-if` directive for conditional rendering

### Role Assignment Pattern
✅ Super Admin: All permissions
✅ Admin: All module permissions
✅ Teacher: Relevant course permissions
✅ Student/Parent: View-only permissions

---

## Database Migration Status

✅ **Permission added**: Via seeder (not migration)
✅ **Idempotent**: Re-running seeder won't create duplicates
✅ **Role assignments**: Automatically synced

**Note**: Permissions are managed via seeders, not migrations, to allow flexible updates without schema changes.

---

## Summary

### What Was Done

1. ✅ Added `course.view_zalo_chat` permission to database
2. ✅ Assigned permission to teacher, admin, and super-admin roles
3. ✅ Implemented 4-tier permission check in frontend
4. ✅ Conditionally render Zalo Chat tab based on permissions
5. ✅ Load class relationships for permission validation
6. ✅ Built and verified frontend assets

### Consistency Verified

- ✅ Permission structure matches application schema
- ✅ Seeder pattern follows existing seeders
- ✅ Frontend permission check follows application-wide pattern
- ✅ Auth store integration is standard
- ✅ Database operations are idempotent

### Final Result

Users can now only see the **Zalo Group Chat** tab in Classroom Board if they have:
- Explicit `course.view_zalo_chat` permission, OR
- Are homeroom teacher of the class, OR
- Are subject teacher teaching the class, OR
- Are head of department of the class's subject

This ensures proper access control while maintaining flexibility for different user roles.

---

**Status**: ✅ COMPLETED

**Build**: `npm run build` completed successfully
**Seeder**: `php artisan db:seed --class=CoursePermissionsSeeder` completed successfully

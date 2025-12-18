# ✅ Quality Management - Permissions đã áp dụng đầy đủ

## 📊 Tổng quan Permissions

### **1. Course Module** ✅
| Permission | Display Name | Controllers | Routes |
|------------|--------------|-------------|--------|
| `course.post` | Đăng bài trong Classroom | CoursePostController::store | ✅ Applied |
| `course.create_event` | Tạo Event | CoursePostController::store | ✅ Applied |
| `course.create_homework` | Tạo Homework | HomeworkAssignmentController::store | ✅ Applied |

---

### **2. Students Management** ✅
| Permission | Display Name | Controllers | Routes |
|------------|--------------|-------------|--------|
| `students.view_all` | Xem toàn bộ Học viên | StudentController::index | ✅ Applied |
| `students.manage` | Quản lý Học viên (CRUD) | - | ⚠️ Planned |
| `students.create` | Tạo Học viên | - | ⚠️ Planned |
| `students.edit` | Sửa Học viên | - | ⚠️ Planned |
| `students.delete` | Xóa Học viên | - | ⚠️ Planned |

**Authorization Logic:**
```php
// StudentController::index()
if (!$user->hasPermission('students.view_all') && !$user->hasRole('teacher')) {
    return 403;
}
```

---

### **3. Parents Management** ✅
| Permission | Display Name | Controllers | Routes |
|------------|--------------|-------------|--------|
| `parents.view_all` | Xem toàn bộ Phụ huynh | ParentController::index | ✅ Applied |
| `parents.manage` | Quản lý Phụ huynh (CRUD) | - | ⚠️ Planned |
| `parents.create` | Tạo Phụ huynh | - | ⚠️ Planned |
| `parents.edit` | Sửa Phụ huynh | - | ⚠️ Planned |
| `parents.delete` | Xóa Phụ huynh | - | ⚠️ Planned |

**Authorization Logic:**
```php
// ParentController::index()
if (!$user->hasPermission('parents.view_all') && !$user->hasRole('teacher')) {
    return 403;
}
```

---

### **4. Syllabus/Lesson Plans Management** ✅
| Permission | Display Name | Controllers | Routes |
|------------|--------------|-------------|--------|
| `syllabus.view` | Xem Giáo án | LessonPlanController | ✅ Applied |
| `syllabus.create` | Tạo Giáo án | LessonPlanController::store | ✅ Applied |
| `syllabus.edit` | Sửa Giáo án | LessonPlanController::update | ✅ Applied |
| `syllabus.delete` | Xóa Giáo án | LessonPlanController::destroy | ✅ Applied |
| `syllabus.manage_materials` | Quản lý Tài liệu Giáo án | LessonPlanController (sessions) | ✅ Applied |
| `syllabus.manage` | Quản lý toàn bộ Syllabus | - | ⚠️ Planned |

**Backwards Compatibility:**
- Routes accept BOTH `lesson_plans.*` AND `syllabus.*` permissions
- Teachers với `syllabus.manage_materials` có thể upload tài liệu

**Authorization Logic:**
```php
// Routes with inline middleware
Route::get('/lesson-plans', ...)
    ->middleware(function ($request, $next) {
        if (!$request->user()->hasPermission('lesson_plans.view') && 
            !$request->user()->hasPermission('syllabus.view')) {
            return response()->json(['message' => 'No permission'], 403);
        }
        return $next($request);
    });
```

---

### **5. Subjects Management** ✅
| Permission | Display Name | Routes |
|------------|--------------|--------|
| `subjects.view` | Xem Môn học | ✅ Middleware |
| `subjects.manage` | Quản lý Môn học (tổng) | - |
| `subjects.create` | Tạo Môn học | ✅ Middleware |
| `subjects.edit` | Sửa Môn học | ✅ Middleware |
| `subjects.delete` | Xóa Môn học | ✅ Middleware |
| `subjects.assign_teachers` | Gán Giáo viên | ✅ Middleware |

---

### **6. Classes Management** ✅
| Permission | Display Name | Routes |
|------------|--------------|--------|
| `classes.view` | Xem Danh sách Lớp học | ✅ Middleware |
| `classes.manage` | Quản lý Lớp học (tổng) | - |
| `classes.create` | Tạo Lớp học | ⚠️ Planned |
| `classes.edit` | Sửa Lớp học | ⚠️ Planned |
| `classes.delete` | Xóa Lớp học | ⚠️ Planned |
| `classes.manage_students` | Quản lý Học viên trong Lớp | ✅ Middleware |

---

### **7. Teachers Management** ✅
| Permission | Display Name | Routes |
|------------|--------------|--------|
| `teachers.view` | Xem Danh sách Giáo viên | ✅ Group Middleware |
| `teachers.create` | Thêm Giáo viên | ⚠️ Planned |
| `teachers.edit` | Sửa Giáo viên | ⚠️ Planned |
| `teachers.delete` | Xóa Giáo viên | ⚠️ Planned |
| `teachers.settings` | Thiết lập Mã vị trí Giáo viên | ✅ Middleware |

---

## 🎭 Role Assignments

### **Super Admin**
```php
✅ ALL PERMISSIONS (*)
```

### **Admin**
```php
✅ quality.*
✅ syllabus.*
✅ subjects.*
✅ lesson_plans.* (backwards compatibility)
✅ students.*
✅ parents.*
✅ classes.*
✅ teachers.*
```

### **Teacher**
```php
✅ quality.view
✅ students.view_all
✅ parents.view_all
✅ teachers.view
✅ classes.view
✅ syllabus.view
✅ syllabus.manage_materials  // ⭐ Đặc biệt: Upload tài liệu
✅ lesson_plans.view
✅ subjects.view
✅ course.view
✅ course.post
✅ course.create_event
✅ course.create_homework
✅ course.manage_assignments
```

### **Student**
```php
✅ course.view
```

### **Parent**
```php
✅ course.view
```

---

## 🛡️ Authorization Implementation

### **1. Controller-level (✅ Applied)**
```php
// CoursePostController::store()
// HomeworkAssignmentController::store()
// StudentController::index()
// ParentController::index()
```

### **2. Route Middleware (✅ Applied)**
```php
// Subjects routes - có middleware
Route::post('/subjects', ...) 
    ->middleware('permission:subjects.create');

// Lesson Plans - inline middleware cho flexibility
Route::post('/lesson-plans', ...)
    ->middleware(function($request, $next) { ... });
```

### **3. Conditional Display (✅ Frontend)**
```vue
<!-- ClassroomBoard.vue -->
<button v-if="authStore.hasPermission('course.post')">
<button v-if="authStore.hasPermission('course.create_event')">
<button v-if="authStore.hasPermission('course.create_homework')">
```

---

## 🔄 Backwards Compatibility

### **Lesson Plans ↔ Syllabus**
- Old permission: `lesson_plans.*`
- New permission: `syllabus.*`
- **Solution**: Routes accept BOTH

```php
if (!$user->hasPermission('lesson_plans.view') && 
    !$user->hasPermission('syllabus.view')) {
    return 403;
}
```

---

## ⚠️ Planned (Chưa implement)

### **CRUD Operations**
- `students.create/edit/delete` - Controllers chưa có
- `parents.create/edit/delete` - Controllers chưa có
- `classes.create/edit/delete` - Có controller nhưng chưa có permission check
- `teachers.create/edit/delete` - Có controller nhưng chưa có permission check

### **Frontend Permission Checks**
- Syllabus List/Detail pages
- Student List page
- Parent List page
- Classes Management pages

---

## 📝 Testing Checklist

### **Course Module**
- [ ] User không có quyền → Không thấy composer
- [ ] User chỉ có `course.post` → Chỉ thấy tab Post
- [ ] User chỉ có `course.create_event` → Chỉ thấy tab Event
- [ ] Teacher → Thấy cả 3 tabs
- [ ] Backend block nếu bypass frontend

### **Students/Parents**
- [ ] Teacher → Có thể xem list (cho mention system)
- [ ] Admin → Có thể xem list
- [ ] User không có quyền → 403

### **Syllabus**
- [ ] Teacher với `syllabus.manage_materials` → Upload được
- [ ] Teacher không có quyền edit → Không edit được
- [ ] Admin → Full access
- [ ] Cả `lesson_plans.*` và `syllabus.*` đều hoạt động

---

## 🎯 Summary

| Module | Permissions Added | Controllers Updated | Routes Updated | Frontend Updated |
|--------|-------------------|---------------------|----------------|------------------|
| Course | +2 | ✅ | ✅ | ✅ |
| Students | +5 | ✅ | ✅ | ⚠️ |
| Parents | +4 | ✅ | ✅ | ⚠️ |
| Syllabus | +6 | ⚠️ | ✅ | ⚠️ |
| Subjects | +4 | ⚠️ | ✅ | ⚠️ |
| Classes | +4 | ⚠️ | ✅ | ⚠️ |

**Total New Permissions: +25**

---

## 🚀 Commands Run

```bash
✅ php artisan db:seed --class=CoursePermissionsSeeder
✅ php artisan db:seed --class=QualityManagementPermissionsSeeder  
✅ php artisan db:seed --class=UpdateQualityPermissionsSeeder
✅ npm run build
```

---

## ✨ Next Steps

1. **Implement CRUD authorization** cho Students, Parents, Teachers, Classes
2. **Frontend permission checks** cho Quality pages
3. **Test coverage** cho tất cả permission scenarios
4. **Documentation** cho developers
5. **Migration plan** từ `lesson_plans.*` sang `syllabus.*`


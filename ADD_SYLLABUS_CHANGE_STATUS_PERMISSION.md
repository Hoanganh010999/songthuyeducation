# Hướng Dẫn Thêm Permission: syllabus.change_status

## 📋 Tổng Quan

Permission `syllabus.change_status` hoặc `lesson_plans.change_status` cho phép người dùng thay đổi trạng thái giáo án bằng cách click vào badge trạng thái.

## 🔐 Phân Quyền Hiện Tại

Code đã được thiết kế để hỗ trợ **fallback tự động**:

```php
// Backend: LessonPlanController.php (line 319-321)
$canChangeStatus = $user->hasPermission('syllabus.change_status') ||
                  $user->hasPermission('lesson_plans.change_status') ||
                  $this->checkPermission($user, 'edit');
```

```javascript
// Frontend: SyllabusList.vue (line 144-146)
const canChangeStatus = authStore.hasPermission('syllabus.change_status') ||
                        authStore.hasPermission('lesson_plans.change_status') ||
                        authStore.hasPermission('lesson_plans.edit');
```

### Thứ tự ưu tiên:
1. ✅ `syllabus.change_status` (quyền riêng cho thay đổi trạng thái)
2. ✅ `lesson_plans.change_status` (tương thích ngược)
3. ✅ `lesson_plans.edit` hoặc `syllabus.edit` (quyền chỉnh sửa chung)

## 🎯 Ai Có Thể Thay Đổi Trạng Thái?

### Tự Động (Không Cần Cài Đặt Thêm):
- Người có quyền `lesson_plans.edit`
- Người có quyền `syllabus.edit`

### Sau Khi Thêm Permission Mới:
- Người có quyền `syllabus.change_status` (chỉ đổi trạng thái, không cần quyền edit)
- Người có quyền `lesson_plans.change_status`

## ⚙️ Cách Thêm Permission Vào Database

### Option 1: Qua PHP Script

Tạo file `add_syllabus_change_status_permission.php`:

```php
<?php
require __DIR__.'/vendor/autoload.php';
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'school_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Check if permission exists
$existing = Capsule::table('permissions')
    ->where('name', 'syllabus.change_status')
    ->first();

if ($existing) {
    echo "Permission 'syllabus.change_status' đã tồn tại!\n";
    exit;
}

// Add permission
Capsule::table('permissions')->insert([
    'name' => 'syllabus.change_status',
    'display_name' => 'Thay đổi trạng thái giáo án',
    'description' => 'Cho phép thay đổi trạng thái giáo án (draft, approved, in_use, archived)',
    'category' => 'lesson_plans',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
]);

$permissionId = Capsule::getPdo()->lastInsertId();

echo "✅ Đã tạo permission 'syllabus.change_status' (ID: $permissionId)\n";
echo "\n";
echo "Bước tiếp theo:\n";
echo "1. Gán permission này cho các roles cần thiết\n";
echo "2. Ví dụ: Trưởng khoa, Giám đốc, etc.\n";
echo "\n";
```

Chạy:
```bash
php add_syllabus_change_status_permission.php
```

### Option 2: Qua SQL Trực Tiếp

```sql
-- Add permission
INSERT INTO permissions (name, display_name, description, category, created_at, updated_at)
VALUES (
    'syllabus.change_status',
    'Thay đổi trạng thái giáo án',
    'Cho phép thay đổi trạng thái giáo án (draft, approved, in_use, archived)',
    'lesson_plans',
    NOW(),
    NOW()
);

-- Get permission ID
SET @permission_id = LAST_INSERT_ID();

-- Assign to role (ví dụ: role_id = 2 là Head of Department)
INSERT INTO permission_role (permission_id, role_id)
VALUES (@permission_id, 2);
```

## 👥 Gán Permission Cho Roles

### Cách 1: Qua Frontend (Admin Panel)
1. Vào **Settings** → **Roles & Permissions**
2. Chọn role cần gán (ví dụ: "Trưởng Khoa", "Giám Đốc")
3. Tìm permission "Thay đổi trạng thái giáo án"
4. Tick vào checkbox
5. Lưu lại

### Cách 2: Qua SQL

```sql
-- Gán cho role "Head of Department" (giả sử role_id = 2)
INSERT INTO permission_role (permission_id, role_id)
SELECT id, 2
FROM permissions
WHERE name = 'syllabus.change_status';

-- Gán cho role "Director" (giả sử role_id = 3)
INSERT INTO permission_role (permission_id, role_id)
SELECT id, 3
FROM permissions
WHERE name = 'syllabus.change_status';
```

## 🧪 Kiểm Tra Permission

### Kiểm tra qua PHP:

```php
$user = auth()->user();

// Check nếu user có quyền thay đổi trạng thái
if ($user->hasPermission('syllabus.change_status') ||
    $user->hasPermission('lesson_plans.change_status') ||
    $user->hasPermission('lesson_plans.edit')) {
    echo "User có thể thay đổi trạng thái giáo án";
} else {
    echo "User KHÔNG có quyền thay đổi trạng thái giáo án";
}
```

### Kiểm tra qua SQL:

```sql
-- Kiểm tra user_id = 1 có quyền không
SELECT
    u.id,
    u.name,
    p.name as permission_name,
    r.name as role_name
FROM users u
JOIN role_user ru ON u.id = ru.user_id
JOIN roles r ON ru.role_id = r.id
JOIN permission_role pr ON r.id = pr.role_id
JOIN permissions p ON pr.permission_id = p.id
WHERE u.id = 1
  AND p.name IN ('syllabus.change_status', 'lesson_plans.change_status', 'lesson_plans.edit');
```

## 🎨 UI/UX Behavior

### Với Permission:
- Badge hiển thị với **mũi tên dropdown** (▼)
- Click vào badge → Hiện dropdown với 4 trạng thái
- Chọn trạng thái mới → Update ngay lập tức
- Hiển thị thông báo thành công

### Không Có Permission:
- Badge hiển thị **không có mũi tên**
- Không thể click
- Chỉ xem trạng thái hiện tại

## 📊 Các Trạng Thái Có Sẵn

| Status | Label | Màu | Ý nghĩa |
|--------|-------|-----|---------|
| `draft` | Bản nháp | Xám | Đang soạn thảo, chưa hoàn thành |
| `approved` | Đã duyệt | Xanh dương | Đã được duyệt, sẵn sàng sử dụng |
| `in_use` | Đang sử dụng | Xanh lá | Đang được sử dụng bởi các lớp học |
| `archived` | Lưu trữ | Xám | Không còn sử dụng, lưu trữ |

## 🔄 Workflow Đề Xuất

```
Draft → Approved → In Use → Archived
  ↑        ↓         ↓          ↓
  ←────────←─────────←──────────←
```

1. **Draft**: Giáo viên tạo giáo án mới
2. **Approved**: Trưởng khoa duyệt
3. **In Use**: Gán cho lớp học, đang sử dụng
4. **Archived**: Hết năm học, lưu trữ

## 📝 Notes

- ✅ Backend đã hoàn thành
- ✅ Frontend đã hoàn thành
- ✅ Backward compatible (fallback to edit permission)
- ⏳ Cần thêm permission vào database (optional)
- ⏳ Cần gán permission cho roles phù hợp (optional)

---

Ngày tạo: 2025-11-24

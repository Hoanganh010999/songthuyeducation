# 🚀 BRANCHES MODULE - QUICK START

## ✅ Hoàn Tất 100%

Module Quản Lý Chi Nhánh đã được triển khai đầy đủ cả Backend và Frontend!

---

## 🎯 Truy Cập Ngay

### 1. Đảm Bảo Server Đang Chạy
```bash
# Terminal 1: Laravel
php artisan serve

# Terminal 2: MySQL (nếu dùng XAMPP thì đã chạy rồi)
```

### 2. Truy Cập
```
URL: http://localhost:8000
Login: admin@example.com
Password: password
```

### 3. Xem Module Chi Nhánh
```
Sidebar → Chi Nhánh
hoặc
Trực tiếp: http://localhost:8000/branches
```

---

## 📋 Tính Năng Đã Có

### ✅ Backend API
- `GET /api/branches` - Danh sách chi nhánh (có phân trang)
- `GET /api/branches/list` - Dropdown list (không phân trang)
- `POST /api/branches` - Tạo chi nhánh mới
- `GET /api/branches/{id}` - Chi tiết chi nhánh
- `PUT /api/branches/{id}` - Cập nhật chi nhánh
- `DELETE /api/branches/{id}` - Xóa chi nhánh
- `GET /api/branches/{id}/users` - Danh sách users của chi nhánh
- `GET /api/branches/{id}/statistics` - Thống kê chi nhánh

### ✅ Frontend UI
- **BranchesList** - Danh sách chi nhánh với:
  - Pagination
  - Search (tìm theo tên, mã, thành phố)
  - Filter theo trạng thái (Hoạt động/Ngừng)
  - Actions: View, Edit, Delete
  - Badge "TRỤ SỞ CHÍNH"
  - Hiển thị số nhân sự
  
- **BranchModal** - Form tạo/sửa chi nhánh với:
  - Mã chi nhánh (unique, không sửa được)
  - Tên chi nhánh
  - Thông tin liên hệ (phone, email)
  - Địa chỉ (address, city, district, ward)
  - Mô tả
  - Checkbox: Hoạt động, Trụ sở chính
  
- **BranchDetailModal** - Xem chi tiết với:
  - Thông tin đầy đủ
  - Quản lý chi nhánh
  - Thống kê (nhân sự, học viên, khách hàng)

### ✅ Permissions
- `branches.view` - Xem chi nhánh
- `branches.create` - Tạo chi nhánh
- `branches.edit` - Sửa chi nhánh
- `branches.delete` - Xóa chi nhánh

### ✅ Sample Data
- **HN01** - Chi Nhánh Hà Nội (Trụ sở chính)
  - Manager: Admin Hà Nội
  - 2 nhân sự
  
- **HCM01** - Chi Nhánh TP.HCM
  - Manager: Manager TP.HCM
  - 2 nhân sự
  
- **DN01** - Chi Nhánh Đà Nẵng
  - 1 nhân sự

---

## 🧪 Test Scenarios

### Test 1: Xem Danh Sách
```
1. Login as admin@example.com
2. Click "Chi Nhánh" trong sidebar
3. Thấy 3 chi nhánh: HN01, HCM01, DN01
```

### Test 2: Tạo Chi Nhánh Mới
```
1. Click "Tạo Chi Nhánh"
2. Nhập:
   - Mã: CT01
   - Tên: Chi Nhánh Cần Thơ
   - Thành phố: Cần Thơ
3. Click "Lưu"
4. Thấy chi nhánh mới trong danh sách
```

### Test 3: Xem Chi Tiết
```
1. Click icon "Xem" (mắt) trên branch HN01
2. Modal hiển thị:
   - Thông tin đầy đủ
   - Manager: Admin Hà Nội
   - 2 nhân sự
```

### Test 4: Sửa Chi Nhánh
```
1. Click icon "Sửa" (bút) trên branch HCM01
2. Thay đổi tên thành "Chi Nhánh TP. Hồ Chí Minh"
3. Click "Lưu"
4. Thấy tên đã thay đổi
```

### Test 5: Xóa Chi Nhánh (Có Nhân Sự)
```
1. Click icon "Xóa" (thùng rác) trên branch HN01
2. Confirm xóa
3. Thấy lỗi: "Không thể xóa chi nhánh đang có nhân sự"
```

### Test 6: Xóa Trụ Sở Chính
```
1. Click icon "Xóa" trên branch HN01 (trụ sở chính)
2. Icon "Xóa" KHÔNG hiển thị (disabled)
```

### Test 7: Search
```
1. Nhập "Hà Nội" vào ô search
2. Chỉ thấy chi nhánh HN01
```

### Test 8: Filter
```
1. Chọn "Ngừng hoạt động" trong dropdown filter
2. Không thấy chi nhánh nào (vì tất cả đang hoạt động)
```

---

## 🔐 Permissions Test

### Test với User Không Có Permission
```bash
# Tạo user mới không có permission branches.view
POST /api/register
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password"
}

# Login
POST /api/login
{
  "email": "test@example.com",
  "password": "password"
}

# Try to access branches
GET /api/branches
→ Expected: 403 Forbidden

# Frontend: Sidebar không hiển thị "Chi Nhánh"
```

### Test với Super-Admin
```
Login: admin@example.com
→ Thấy "Chi Nhánh" trong sidebar
→ Có thể CRUD tất cả branches
→ Thấy tất cả branches (không bị filter)
```

---

## 🏗️ Cấu Trúc Files

### Backend
```
app/
├── Models/
│   ├── Branch.php                    ✅ Model với relationships
│   └── User.php                      ✅ Updated với branch relationship
├── Http/
│   ├── Controllers/Api/
│   │   └── BranchController.php     ✅ Full CRUD API
│   └── Middleware/
│       └── CheckBranchAccess.php    ✅ Middleware filter by branch
database/
├── migrations/
│   ├── 2025_10_31_045535_create_branches_table.php           ✅
│   └── 2025_10_31_045603_add_branch_id_to_users_table.php    ✅
└── seeders/
    ├── BranchSeeder.php              ✅ Sample branches + permissions
    └── DatabaseSeeder.php            ✅ Updated
routes/
└── api.php                           ✅ 8 routes cho branches
bootstrap/
└── app.php                           ✅ Registered middleware
```

### Frontend
```
resources/js/
├── pages/
│   └── branches/
│       └── BranchesList.vue          ✅ Main list page
├── components/
│   └── branches/
│       ├── BranchModal.vue           ✅ Create/Edit modal
│       └── BranchDetailModal.vue     ✅ Detail modal
├── router/
│   └── index.js                      ✅ Route /branches
└── layouts/
    └── DashboardLayout.vue           ✅ Sidebar menu item
```

---

## 📊 Database Schema

### Table: branches
```sql
id              BIGINT UNSIGNED PRIMARY KEY
code            VARCHAR(255) UNIQUE         -- Mã chi nhánh
name            VARCHAR(255)                -- Tên chi nhánh
phone           VARCHAR(255) NULL
email           VARCHAR(255) NULL
address         TEXT NULL
city            VARCHAR(255) NULL
district        VARCHAR(255) NULL
ward            VARCHAR(255) NULL
manager_id      BIGINT UNSIGNED NULL        -- FK to users
is_active       TINYINT(1) DEFAULT 1
is_headquarters TINYINT(1) DEFAULT 0
description     TEXT NULL
metadata        JSON NULL
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### Table: users (Updated)
```sql
-- Thêm cột
branch_id       BIGINT UNSIGNED NULL        -- FK to branches
```

---

## 🎨 UI Screenshots (Mô tả)

### Sidebar
```
┌─────────────────────┐
│ Dashboard           │
│ Users               │
│ Chi Nhánh      ← NEW│
│                     │
│ System Settings     │
│  └─ Access Control  │
│  └─ Languages       │
└─────────────────────┘
```

### Branches List
```
┌──────────────────────────────────────────────────────────┐
│ Quản Lý Chi Nhánh                    [+ Tạo Chi Nhánh]  │
├──────────────────────────────────────────────────────────┤
│ [Search...] [Filter Status] [Reset]                      │
├──────────────────────────────────────────────────────────┤
│ Mã & Tên    │ Địa chỉ  │ Quản lý │ Nhân sự │ Actions   │
├──────────────────────────────────────────────────────────┤
│ HN01        │ Hà Nội   │ Admin   │ 2 người │ 👁 ✏️ 🗑️  │
│ [TRỤ SỞ]    │ Đống Đa  │ HN      │         │           │
│ Chi Nhánh   │          │         │         │           │
│ Hà Nội      │          │         │         │           │
├──────────────────────────────────────────────────────────┤
│ HCM01       │ TP.HCM   │ Manager │ 2 người │ 👁 ✏️ 🗑️  │
│ Chi Nhánh   │ Quận 1   │ HCM     │         │           │
│ TP.HCM      │          │         │         │           │
└──────────────────────────────────────────────────────────┘
```

---

## 🚀 Next Steps - Tích Hợp Branches

### 1. Thêm Branch Dropdown vào UserModal
```vue
<!-- resources/js/components/users/UserModal.vue -->
<div>
  <label>Chi Nhánh</label>
  <select v-model="form.branch_id">
    <option value="">Chọn chi nhánh</option>
    <option v-for="branch in branches" :key="branch.id" :value="branch.id">
      {{ branch.name }}
    </option>
  </select>
</div>

<script>
// Load branches
const branches = ref([]);
const loadBranches = async () => {
  const response = await api.get('/api/branches/list');
  branches.value = response.data.data;
};
</script>
```

### 2. Hiển thị Branch trong UsersList
```vue
<!-- Thêm cột Branch -->
<td>{{ user.branch?.name || 'Chưa có' }}</td>
```

### 3. Tạo Module Students với Branch
```php
// Migration
Schema::create('students', function (Blueprint $table) {
    $table->id();
    $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
    // ... other fields
});

// Route
Route::get('/students', [StudentController::class, 'index'])
    ->middleware(['auth:sanctum', 'branch.access', 'permission:students.view']);

// Controller
public function index(Request $request)
{
    $query = Student::query();
    
    // Filter by branch if user has branch_id
    if ($branchId = $request->input('user_branch_id')) {
        $query->where('branch_id', $branchId);
    }
    
    return $query->paginate();
}
```

---

## ✅ Checklist

- [x] Database migrations
- [x] Models và relationships
- [x] API Controller
- [x] Routes với permissions
- [x] Middleware CheckBranchAccess
- [x] Seeder với sample data
- [x] Frontend pages
- [x] Frontend components
- [x] Router config
- [x] Sidebar navigation
- [x] Build frontend
- [x] Test cơ bản

---

## 🎉 Kết Quả

**Module Branches đã sẵn sàng 100%!**

- ✅ Backend API hoàn chỉnh
- ✅ Frontend UI đẹp và responsive
- ✅ Permissions đầy đủ
- ✅ Sample data để test
- ✅ Middleware sẵn sàng cho filter by branch
- ✅ Documentation đầy đủ

**Reload trang và xem "Chi Nhánh" trong sidebar!** 🏢


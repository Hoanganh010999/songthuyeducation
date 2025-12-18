# 🏢 MODULE QUẢN LÝ CHI NHÁNH (BRANCHES)

## Tổng Quan
Module quản lý chi nhánh là **module chính** của hệ thống (không phải module trong System Settings). Sau này, mọi danh sách khách hàng, học viên, nhân sự sẽ được gắn với một chi nhánh cụ thể để admin của chi nhánh đó có quyền xử lý tài nguyên của chi nhánh mình.

---

## ✅ Đã Triển Khai

### 1. Database Schema

#### Bảng `branches`
```sql
CREATE TABLE branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(255) NOT NULL UNIQUE COMMENT 'Mã chi nhánh (VD: HN01, HCM01)',
    name VARCHAR(255) NOT NULL COMMENT 'Tên chi nhánh',
    phone VARCHAR(255) NULL COMMENT 'Số điện thoại',
    email VARCHAR(255) NULL COMMENT 'Email liên hệ',
    address TEXT NULL COMMENT 'Địa chỉ chi nhánh',
    city VARCHAR(255) NULL COMMENT 'Thành phố',
    district VARCHAR(255) NULL COMMENT 'Quận/Huyện',
    ward VARCHAR(255) NULL COMMENT 'Phường/Xã',
    manager_id BIGINT UNSIGNED NULL COMMENT 'Quản lý chi nhánh',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái hoạt động',
    is_headquarters TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Là trụ sở chính',
    description TEXT NULL COMMENT 'Mô tả',
    metadata JSON NULL COMMENT 'Thông tin bổ sung (JSON)',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_is_active (is_active),
    INDEX idx_manager_id (manager_id)
);
```

#### Cập nhật bảng `users`
```sql
ALTER TABLE users ADD COLUMN branch_id BIGINT UNSIGNED NULL AFTER language_id;
ALTER TABLE users ADD FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL;
ALTER TABLE users ADD INDEX idx_branch_id (branch_id);
```

---

### 2. Backend - Models

#### Branch Model (`app/Models/Branch.php`)

**Relationships:**
- `manager()` - BelongsTo User (Quản lý chi nhánh)
- `users()` - HasMany User (Nhân sự của chi nhánh)

**Attributes:**
- `full_address` - Địa chỉ đầy đủ (computed)

**Scopes:**
- `active()` - Chỉ lấy chi nhánh đang hoạt động
- `search($search)` - Tìm kiếm theo name, code, city

**Static Methods:**
- `headquarters()` - Lấy trụ sở chính

#### User Model - Cập nhật

**Thêm Relationships:**
```php
// User thuộc một Branch
public function branch(): BelongsTo
{
    return $this->belongsTo(Branch::class);
}

// User quản lý nhiều Branches
public function managedBranches(): HasMany
{
    return $this->hasMany(Branch::class, 'manager_id');
}
```

**Thêm Fillable:**
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'language_id',
    'branch_id', // ← New
];
```

---

### 3. Backend - Controller

#### BranchController (`app/Http/Controllers/Api/BranchController.php`)

**Endpoints:**
- `GET /api/branches` - Danh sách branches (paginated)
- `GET /api/branches/list` - Danh sách branches (no pagination, for dropdown)
- `POST /api/branches` - Tạo branch mới
- `GET /api/branches/{id}` - Chi tiết branch
- `PUT /api/branches/{id}` - Cập nhật branch
- `DELETE /api/branches/{id}` - Xóa branch
- `GET /api/branches/{id}/users` - Danh sách users của branch
- `GET /api/branches/{id}/statistics` - Thống kê branch

**Features:**
- ✅ Validation đầy đủ
- ✅ Tự động bỏ flag `is_headquarters` của branch khác khi set branch mới
- ✅ Không cho xóa branch đang có users
- ✅ Không cho xóa trụ sở chính
- ✅ Eager loading relationships
- ✅ Count users per branch

---

### 4. Routes API

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('branches')->group(function () {
        Route::get('/', [BranchController::class, 'index'])
            ->middleware('permission:branches.view');
        
        Route::get('/list', [BranchController::class, 'list']);
        
        Route::post('/', [BranchController::class, 'store'])
            ->middleware('permission:branches.create');
        
        Route::get('/{id}', [BranchController::class, 'show'])
            ->middleware('permission:branches.view');
        
        Route::put('/{id}', [BranchController::class, 'update'])
            ->middleware('permission:branches.edit');
        
        Route::delete('/{id}', [BranchController::class, 'destroy'])
            ->middleware('permission:branches.delete');
        
        Route::get('/{id}/users', [BranchController::class, 'users'])
            ->middleware('permission:branches.view');
        
        Route::get('/{id}/statistics', [BranchController::class, 'statistics'])
            ->middleware('permission:branches.view');
    });
});
```

---

### 5. Permissions

**Module:** `branches`

**Permissions:**
1. `branches.view` - Xem Chi Nhánh
2. `branches.create` - Tạo Chi Nhánh
3. `branches.edit` - Sửa Chi Nhánh
4. `branches.delete` - Xóa Chi Nhánh

**Tự động gán cho:** Super-admin role

---

### 6. Middleware - CheckBranchAccess

**File:** `app/Http/Middleware/CheckBranchAccess.php`

**Logic:**
```php
// Super-admin → Truy cập tất cả
if ($user->isSuperAdmin()) {
    return $next($request);
}

// User không có branch_id (HQ users) → Truy cập tất cả
if (!$user->branch_id) {
    return $next($request);
}

// User có branch_id → Attach vào request để filter
$request->merge(['user_branch_id' => $user->branch_id]);
return $next($request);
```

**Registered as:** `branch.access`

**Sử dụng sau này:**
```php
// Trong routes cho modules khác (students, customers, etc.)
Route::get('/students', [StudentController::class, 'index'])
    ->middleware(['auth:sanctum', 'branch.access', 'permission:students.view']);

// Trong controller:
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

### 7. Seeder

#### BranchSeeder

**Sample Branches:**
1. **HN01** - Chi Nhánh Hà Nội (Trụ sở chính)
   - Manager: Admin Hà Nội (admin.hn@example.com)
   - City: Hà Nội
   
2. **HCM01** - Chi Nhánh TP.HCM
   - Manager: Manager TP.HCM (manager.hcm@example.com)
   - City: TP. Hồ Chí Minh
   
3. **DN01** - Chi Nhánh Đà Nẵng
   - City: Đà Nẵng

**Sample Users với Branches:**
- Super Admin → Branch HN01 (Trụ sở chính)
- Admin Hà Nội → Branch HN01 (Manager)
- Manager TP.HCM → Branch HCM01 (Manager)
- Staff Đà Nẵng → Branch DN01
- User TP.HCM → Branch HCM01

---

### 8. Frontend (Đang triển khai)

#### Pages Created:
- ✅ `resources/js/pages/branches/BranchesList.vue`
- ⏳ `resources/js/components/branches/BranchModal.vue` (TODO)
- ⏳ `resources/js/components/branches/BranchDetailModal.vue` (TODO)

#### Features trong BranchesList:
- ✅ Danh sách branches với pagination
- ✅ Search và filter theo trạng thái
- ✅ Hiển thị thông tin: code, name, địa chỉ, manager, số nhân sự
- ✅ Badge cho trụ sở chính
- ✅ Actions: View, Edit, Delete
- ✅ Không cho xóa trụ sở chính
- ✅ Modal xác nhận xóa
- ✅ Empty state

---

## 🎯 Cách Hoạt Động Phân Quyền Theo Branch

### Scenario 1: Super-Admin
```
Super-Admin login → Không bị filter → Thấy TẤT CẢ branches, TẤT CẢ users, TẤT CẢ data
```

### Scenario 2: HQ User (không có branch_id)
```
HQ User login → branch_id = NULL → Không bị filter → Thấy TẤT CẢ branches, users, data
```

### Scenario 3: Branch Admin/Manager
```
Branch Admin (HCM) login
  ↓
branch_id = 2 (HCM01)
  ↓
Middleware CheckBranchAccess → Attach user_branch_id vào request
  ↓
Controller filter: ->where('branch_id', 2)
  ↓
CHỈ thấy data của branch HCM01
```

---

## 📊 Use Cases

### Use Case 1: Quản Lý Học Viên Theo Branch
```
Tạo bảng: students
  ↓
Thêm cột: branch_id
  ↓
Route: ->middleware('branch.access')
  ↓
Controller filter by user_branch_id
  ↓
Admin HCM chỉ thấy học viên HCM
Admin HN chỉ thấy học viên HN
Super-admin thấy tất cả
```

### Use Case 2: Báo Cáo Theo Branch
```
GET /api/reports/revenue
  ↓
Middleware branch.access
  ↓
Filter revenue by branch_id
  ↓
Admin HCM chỉ thấy doanh thu HCM
```

### Use Case 3: Gán Nhân Sự Vào Branch
```
Tạo user mới
  ↓
Chọn branch trong dropdown
  ↓
User->branch_id = selected branch
  ↓
User này chỉ quản lý data của branch đó
```

---

## 🚀 Tiếp Theo - Cần Làm

### Backend (Hoàn thành ✅)
- [x] Migration branches table
- [x] Migration add branch_id to users
- [x] Branch Model
- [x] Update User Model
- [x] BranchController
- [x] Routes
- [x] Permissions
- [x] Middleware CheckBranchAccess
- [x] Seeder

### Frontend (Đang làm ⏳)
- [x] BranchesList component
- [ ] BranchModal (Create/Edit)
- [ ] BranchDetailModal
- [ ] Router config
- [ ] Navigation menu
- [ ] Translations (i18n)

### Tích Hợp (TODO 📝)
- [ ] Thêm dropdown "Chi nhánh" vào UserModal
- [ ] Cập nhật UsersList hiển thị branch
- [ ] Tạo module Students với branch_id
- [ ] Tạo module Customers với branch_id
- [ ] Apply middleware branch.access cho tất cả modules cần filter

---

## 🧪 Test Scenarios

### Test 1: Tạo Branch
```bash
POST /api/branches
{
  "code": "TEST01",
  "name": "Chi Nhánh Test",
  "city": "Hà Nội",
  "is_active": true
}
```

### Test 2: List Branches
```bash
GET /api/branches?page=1&per_page=15&search=Hà Nội
```

### Test 3: Assign User to Branch
```bash
PUT /api/users/5
{
  "branch_id": 2  // Gán user vào branch HCM
}
```

### Test 4: Test Branch Access Filter
```bash
# Login as branch admin
POST /api/login { email: "manager.hcm@example.com" }

# Get students - Should only see HCM students
GET /api/students
→ Middleware adds user_branch_id = 2
→ Controller filters: where('branch_id', 2)
→ Only HCM students returned
```

---

## 📝 Notes

1. **Branch Code:**
   - Nên follow pattern: `{City Code}{Number}`
   - Ví dụ: HN01, HCM01, DN01
   - Unique constraint

2. **Headquarters:**
   - Chỉ được có 1 trụ sở chính
   - Không thể xóa
   - Tự động bỏ flag cũ khi set mới

3. **Manager:**
   - Một user có thể là manager của nhiều branches
   - Branch có thể không có manager

4. **Branch Access Logic:**
   - Super-admin: Bypass tất cả
   - HQ users (branch_id = NULL): Xem tất cả
   - Branch users: Chỉ xem branch của mình

5. **Future Modules:**
   - Students, Customers, Orders, etc. đều nên có `branch_id`
   - Tất cả đều dùng middleware `branch.access`
   - Controller filter by `user_branch_id` từ request

---

**Module Branches đã sẵn sàng để mở rộng hệ thống!** 🏢


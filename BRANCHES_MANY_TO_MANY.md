# 🔄 BRANCHES - MANY-TO-MANY RELATIONSHIP

## ✅ Đã Cập Nhật

Hệ thống đã được chuyển từ **One-to-Many** sang **Many-to-Many** relationship giữa Users và Branches.

**1 User giờ có thể thuộc về NHIỀU chi nhánh!**

---

## 🎯 Thay Đổi Chính

### Trước (One-to-Many)
```
users table:
  - branch_id (FK to branches)

User → belongsTo → Branch
Branch → hasMany → Users

❌ 1 User chỉ thuộc 1 Branch
```

### Sau (Many-to-Many)
```
branch_user pivot table:
  - branch_id (FK to branches)
  - user_id (FK to users)
  - is_primary (boolean)

User → belongsToMany → Branches
Branch → belongsToMany → Users

✅ 1 User có thể thuộc NHIỀU Branches
✅ Có khái niệm "Primary Branch" (chi nhánh chính)
```

---

## 📊 Database Schema

### Pivot Table: `branch_user`
```sql
CREATE TABLE branch_user (
    id BIGINT UNSIGNED PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY (branch_id, user_id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (branch_id),
    INDEX (user_id),
    INDEX (is_primary)
);
```

**Fields:**
- `branch_id` - ID chi nhánh
- `user_id` - ID user
- `is_primary` - Chi nhánh chính của user (chỉ 1 branch được đánh dấu primary)
- Unique constraint: User không thể được gán vào cùng 1 branch nhiều lần

---

## 🔧 User Model - Updated

### Relationships

#### 1. `branches()` - Many-to-Many
```php
public function branches(): BelongsToMany
{
    return $this->belongsToMany(Branch::class, 'branch_user')
        ->withPivot('is_primary')
        ->withTimestamps();
}
```

**Usage:**
```php
$user = User::find(1);

// Get all branches của user
$branches = $user->branches;

// Get với pivot data
foreach ($user->branches as $branch) {
    echo $branch->name;
    echo $branch->pivot->is_primary ? ' (Primary)' : '';
}
```

#### 2. `getPrimaryBranch()` - Get Primary Branch
```php
public function getPrimaryBranch()
{
    return $this->branches()->wherePivot('is_primary', true)->first();
}
```

**Usage:**
```php
$user = User::find(1);
$primaryBranch = $user->getPrimaryBranch();

if ($primaryBranch) {
    echo "Primary branch: " . $primaryBranch->name;
}
```

### Helper Methods

#### 1. `assignBranch()` - Gán User vào Branch
```php
public function assignBranch(Branch|int $branch, bool $isPrimary = false): void
{
    $branchId = $branch instanceof Branch ? $branch->id : $branch;
    
    // Nếu set làm primary, bỏ primary của các branch khác
    if ($isPrimary) {
        $this->branches()->updateExistingPivot(
            $this->branches()->pluck('branches.id'),
            ['is_primary' => false]
        );
    }
    
    $this->branches()->syncWithoutDetaching([
        $branchId => ['is_primary' => $isPrimary]
    ]);
}
```

**Usage:**
```php
$user = User::find(1);

// Gán user vào branch HN01
$user->assignBranch(1); // branch_id = 1

// Gán user vào branch HCM01 và set làm primary
$user->assignBranch(2, true); // branch_id = 2, is_primary = true

// Gán nhiều branches
$user->assignBranch(1, true);  // Primary
$user->assignBranch(2);        // Secondary
$user->assignBranch(3);        // Secondary
```

#### 2. `removeBranch()` - Xóa User khỏi Branch
```php
public function removeBranch(Branch|int $branch): void
{
    $branchId = $branch instanceof Branch ? $branch->id : $branch;
    $this->branches()->detach($branchId);
}
```

**Usage:**
```php
$user = User::find(1);

// Remove user khỏi branch
$user->removeBranch(2); // branch_id = 2
```

---

## 🏢 Branch Model - Updated

### Relationships

#### 1. `users()` - Many-to-Many
```php
public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'branch_user')
        ->withPivot('is_primary')
        ->withTimestamps();
}
```

**Usage:**
```php
$branch = Branch::find(1);

// Get all users của branch
$users = $branch->users;

// Count users
$totalUsers = $branch->users()->count();
```

#### 2. `primaryUsers()` - Get Users có Primary Branch là branch này
```php
public function primaryUsers()
{
    return $this->users()->wherePivot('is_primary', true);
}
```

**Usage:**
```php
$branch = Branch::find(1);

// Get users có branch này là primary
$primaryUsers = $branch->primaryUsers()->get();

echo "Primary users: " . $primaryUsers->count();
```

---

## 🔐 CheckBranchAccess Middleware - Updated

### Logic Mới
```php
// Super-admin → Access tất cả
if ($user->isSuperAdmin()) {
    return $next($request);
}

// User không có branches → Access tất cả (HQ users)
$userBranches = $user->branches()->pluck('branches.id')->toArray();
if (empty($userBranches)) {
    return $next($request);
}

// User có branches → Attach branch_ids vào request
$request->merge(['user_branch_ids' => $userBranches]);
return $next($request);
```

### Sử dụng trong Controller
```php
// BEFORE (One-to-Many):
if ($branchId = $request->input('user_branch_id')) {
    $query->where('branch_id', $branchId);
}

// AFTER (Many-to-Many):
if ($branchIds = $request->input('user_branch_ids')) {
    $query->whereIn('branch_id', $branchIds);
}
```

---

## 📊 Sample Data

### Users và Branches

**Super Admin** (admin@example.com)
- ✅ HN01 (Primary)
- ✅ HCM01
- ✅ DN01
- **Total: 3 branches**

**Admin Hà Nội** (admin.hn@example.com)
- ✅ HN01 (Primary)
- **Total: 1 branch**

**Manager Multi-Branch** (manager.multi@example.com)
- ✅ HCM01 (Primary)
- ✅ DN01
- **Total: 2 branches**

**Staff Đà Nẵng** (staff.dn@example.com)
- ✅ DN01 (Primary)
- **Total: 1 branch**

**User TP.HCM** (user.hcm@example.com)
- ✅ HCM01 (Primary)
- **Total: 1 branch**

---

## 🧪 Test Scenarios

### Test 1: User với Multiple Branches
```php
$user = User::where('email', 'manager.multi@example.com')->first();

// Get all branches
$branches = $user->branches;
echo "Total branches: " . $branches->count(); // 2

// Get primary branch
$primary = $user->getPrimaryBranch();
echo "Primary: " . $primary->name; // Chi Nhánh TP.HCM

// Check if user belongs to branch
$belongsToHCM = $user->branches()->where('branches.id', 2)->exists();
echo $belongsToHCM ? 'Yes' : 'No'; // Yes
```

### Test 2: Assign User vào Multiple Branches
```php
$user = User::find(5);

// Gán vào 3 branches
$user->assignBranch(1, true);  // HN01 - Primary
$user->assignBranch(2);        // HCM01
$user->assignBranch(3);        // DN01

// Verify
echo $user->branches()->count(); // 3
echo $user->getPrimaryBranch()->code; // HN01
```

### Test 3: Change Primary Branch
```php
$user = User::find(5);

// Current primary: HN01
echo $user->getPrimaryBranch()->code; // HN01

// Change primary to HCM01
$user->assignBranch(2, true);

// Verify
echo $user->getPrimaryBranch()->code; // HCM01

// HN01 vẫn còn nhưng không phải primary
$hn01 = $user->branches()->where('branches.id', 1)->first();
echo $hn01->pivot->is_primary; // 0 (false)
```

### Test 4: Remove User khỏi Branch
```php
$user = User::find(5);

// User có 3 branches
echo $user->branches()->count(); // 3

// Remove khỏi DN01
$user->removeBranch(3);

// Verify
echo $user->branches()->count(); // 2
```

### Test 5: Branch Access Filter
```php
// Login as manager.multi@example.com (có HCM01 và DN01)

// Middleware adds: user_branch_ids = [2, 3]

// Controller:
$students = Student::query();

if ($branchIds = $request->input('user_branch_ids')) {
    $students->whereIn('branch_id', $branchIds);
}

// Result: Chỉ thấy students của HCM01 và DN01
```

### Test 6: Get Users của Branch
```php
$branch = Branch::where('code', 'HCM01')->first();

// All users (primary + secondary)
$allUsers = $branch->users;
echo "Total users: " . $allUsers->count(); // 3

// Only primary users
$primaryUsers = $branch->primaryUsers()->get();
echo "Primary users: " . $primaryUsers->count(); // 2
```

---

## 🔄 Migration Flow

### Automatic Data Migration
```
1. Create branch_user pivot table
   ↓
2. Migrate existing branch_id data to pivot
   - Copy users.branch_id → branch_user
   - Set is_primary = true for all
   ↓
3. Drop old branch_id column from users
   ↓
4. Done! Many-to-many ready
```

**Rollback:**
```
1. Add back branch_id column to users
   ↓
2. Migrate data back from pivot (only primary)
   ↓
3. Drop pivot table
   ↓
4. Back to one-to-many
```

---

## 🎯 Use Cases

### Use Case 1: Manager Quản Lý Nhiều Chi Nhánh
```
Manager có thể:
- Xem data của HCM01
- Xem data của DN01
- Không xem được data của HN01

Middleware filter:
→ whereIn('branch_id', [2, 3])
```

### Use Case 2: Staff Chuyển Chi Nhánh
```
Staff ban đầu ở DN01
→ Chuyển sang HCM01
→ Vẫn giữ access DN01 (secondary)

$staff->assignBranch(2, true);  // HCM01 primary
// DN01 vẫn còn (secondary)
```

### Use Case 3: Temporary Access
```
User cần temporary access vào branch khác:
→ assignBranch(branch_id, false)

Sau khi xong:
→ removeBranch(branch_id)
```

### Use Case 4: Báo Cáo Cross-Branch
```
Manager có HCM01 và DN01
→ Có thể tạo báo cáo so sánh 2 chi nhánh
→ Middleware cho phép whereIn('branch_id', [2, 3])
```

---

## 📝 API Examples

### Get User's Branches
```bash
GET /api/user

Response:
{
  "id": 3,
  "name": "Manager Multi-Branch",
  "email": "manager.multi@example.com",
  "branches": [
    {
      "id": 2,
      "code": "HCM01",
      "name": "Chi Nhánh TP.HCM",
      "pivot": {
        "is_primary": true
      }
    },
    {
      "id": 3,
      "code": "DN01",
      "name": "Chi Nhánh Đà Nẵng",
      "pivot": {
        "is_primary": false
      }
    }
  ]
}
```

### Assign User to Branch
```bash
POST /api/users/5/assign-branch
{
  "branch_id": 2,
  "is_primary": true
}
```

### Remove User from Branch
```bash
POST /api/users/5/remove-branch
{
  "branch_id": 3
}
```

---

## ✅ Checklist

- [x] Pivot table `branch_user`
- [x] Migration data từ `branch_id` sang pivot
- [x] Drop old `branch_id` column
- [x] Update User Model relationships
- [x] Update Branch Model relationships
- [x] Update CheckBranchAccess middleware
- [x] Update DatabaseSeeder
- [x] Migrate fresh + seed
- [x] Test với sample data

---

## 🎉 Kết Quả

**1 User giờ có thể thuộc về NHIỀU chi nhánh!**

- ✅ Many-to-Many relationship
- ✅ Primary branch concept
- ✅ Helper methods: assignBranch(), removeBranch()
- ✅ Middleware filter by multiple branches
- ✅ Sample data với multi-branch users
- ✅ Backward compatible (có rollback)

**Test ngay với manager.multi@example.com - user có 2 branches!** 🔄


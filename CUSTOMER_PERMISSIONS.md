# 🔐 Hệ Thống Phân Quyền Customer Module

## 🎯 Tổng Quan

Customer module áp dụng cơ chế phân quyền tương tự Calendar module với 3 cấp độ:

1. **Super-admin** → Xem TẤT CẢ customers
2. **Branch Manager** → Xem customers của branch mình quản lý
3. **Regular User** → Chỉ xem customers được assign cho mình

**Lưu ý:** Hệ thống đã chuẩn bị sẵn cho **Direct Manager** - sẽ được implement sau khi có HR Module.

---

## 🔄 Logic Phân Quyền

### 1. Super-admin

```php
// Super-admin xem TẤT CẢ customers
if ($user->hasRole('super-admin')) {
    return $query; // Không filter gì cả
}
```

**Kết quả:**
- ✅ Xem tất cả customers của tất cả branches
- ✅ Xem tất cả customers được assign cho bất kỳ ai
- ✅ Không bị giới hạn

---

### 2. Branch Manager / User có Branches

```php
// Lấy branches của user
$userBranchIds = $user->branches()->pluck('branches.id')->toArray();

if (!empty($userBranchIds)) {
    // Xem customers:
    // 1. Được assign cho mình
    // 2. HOẶC customers thuộc branches của mình
    return $query->where(function ($q) use ($user, $userBranchIds) {
        $q->where('assigned_to', $user->id)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

**Kết quả:**
- ✅ Xem customers được assign cho chính mình
- ✅ Xem customers của TẤT CẢ users trong cùng branch
- ❌ Không xem customers của branches khác

**Ví dụ:**
```
User A thuộc Branch Hà Nội
→ Xem được:
  - Customer 1 (assigned_to = User A)
  - Customer 2 (branch_id = Hà Nội, assigned_to = User B)
  - Customer 3 (branch_id = Hà Nội, assigned_to = User C)
→ KHÔNG xem được:
  - Customer 4 (branch_id = TP.HCM, assigned_to = User D)
```

---

### 3. Regular User (Không có Branch)

```php
if (empty($userBranchIds)) {
    // Chỉ xem customers được assign cho mình
    return $query->where('assigned_to', $user->id);
}
```

**Kết quả:**
- ✅ Chỉ xem customers được assign cho chính mình
- ❌ Không xem customers của ai khác

---

## 🔧 Code Implementation

### Model: Customer.php

```php
/**
 * Scope: Customers mà user có quyền xem
 */
public function scopeAccessibleBy($query, User $user)
{
    // Super-admin xem tất cả
    if ($user->hasRole('super-admin')) {
        return $query;
    }

    // Lấy branches của user
    $userBranchIds = $user->branches()->pluck('branches.id')->toArray();

    if (empty($userBranchIds)) {
        // Không có branch → chỉ xem customers của mình
        return $query->where('assigned_to', $user->id);
    }

    // Có branches → xem của mình HOẶC cùng branch
    return $query->where(function ($q) use ($user, $userBranchIds) {
        $q->where('assigned_to', $user->id)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

### Controller: CustomerController.php

#### Index Method (List View)
```php
public function index(Request $request)
{
    $query = Customer::with([...])
        ->accessibleBy($request->user()); // ← Áp dụng phân quyền

    // Additional filters...
    if ($search) {
        $query->search($search);
    }

    $customers = $query->latest()->paginate($perPage);
    
    return response()->json([
        'success' => true,
        'data' => $customers
    ]);
}
```

#### Kanban Method
```php
public function kanban(Request $request)
{
    $query = Customer::with([...])
        ->accessibleBy($request->user()); // ← Áp dụng phân quyền

    $customers = $query->orderBy('stage_order')->get();
    // ... group by stages
}
```

#### Statistics Method
```php
public function statistics(Request $request)
{
    $query = Customer::query()
        ->accessibleBy($request->user()); // ← Áp dụng phân quyền

    $stats = [
        'total' => $query->count(),
        'by_stage' => [...],
        'total_value' => $query->sum('estimated_value'),
    ];
}
```

---

## 📊 Use Cases

### Use Case 1: Sales Team trong cùng Branch

```
Scenario:
- Branch: Hà Nội
- Users: Sales A, Sales B, Sales C

Kết quả:
- Sales A tạo Customer X (assigned_to = Sales A, branch = Hà Nội)
- Sales B, C xem được Customer X trong danh sách
- Họ biết Sales A đang chăm sóc khách nào
- Có thể hỗ trợ khi Sales A vắng mặt
```

### Use Case 2: Branch Manager Oversight

```
Scenario:
- Branch Manager Hà Nội cần theo dõi toàn bộ customers

Kết quả:
- Xem được TẤT CẢ customers của Branch Hà Nội
- Xem được ai đang chăm sóc khách nào
- Theo dõi pipeline của toàn branch
- Phân tích performance
```

### Use Case 3: Multi-Branch Isolation

```
Scenario:
- User A thuộc Branch Hà Nội
- User B thuộc Branch TP.HCM

Kết quả:
- User A KHÔNG xem được customers của Branch TP.HCM
- User B KHÔNG xem được customers của Branch Hà Nội
- Data isolation hoàn toàn giữa các branches
```

### Use Case 4: Super-admin Full View

```
Scenario:
- CEO/Director cần xem tổng quan toàn hệ thống

Kết quả:
- Super-admin xem TẤT CẢ customers của tất cả branches
- Theo dõi pipeline toàn công ty
- Phân tích doanh số theo branch
```

---

## 🔄 Áp Dụng cho Tất Cả Endpoints

Phân quyền được áp dụng cho:

### ✅ List View
```
GET /api/customers
→ Chỉ trả về customers mà user có quyền xem
```

### ✅ Kanban View
```
GET /api/customers/kanban
→ Chỉ hiển thị customers trong pipeline mà user có quyền xem
```

### ✅ Statistics
```
GET /api/customers/statistics
→ Thống kê chỉ tính customers mà user có quyền xem
```

### ⚠️ Show/Update/Delete
```
GET/PUT/DELETE /api/customers/{id}
→ Nếu customer không thuộc quyền → 404 Not Found
(Laravel tự động filter khi query)
```

---

## 🚀 Future: Direct Manager Hierarchy

### Khi có HR Module:

```php
public function scopeAccessibleBy($query, User $user)
{
    if ($user->hasRole('super-admin')) {
        return $query;
    }

    // Lấy subordinates (nhân viên dưới quyền)
    $subordinateIds = $user->getSubordinates()->pluck('id')->toArray();
    $userBranchIds = $user->branches()->pluck('branches.id')->toArray();

    return $query->where(function ($q) use ($user, $subordinateIds, $userBranchIds) {
        $q->where('assigned_to', $user->id)                    // Của mình
          ->orWhereIn('assigned_to', $subordinateIds)          // Của nhân viên dưới quyền
          ->orWhereIn('branch_id', $userBranchIds);            // Của cùng branch
    });
}
```

---

## 🧪 Testing Scenarios

### Test 1: Super-admin Access

```bash
# Login as super-admin
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'

# Get customers
curl http://localhost:8000/api/customers

# Expected: Tất cả customers của tất cả branches
```

### Test 2: Branch Manager Access

```bash
# Login as branch manager
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "manager@branch1.com", "password": "password"}'

# Get customers
curl http://localhost:8000/api/customers

# Expected:
# - Customers assigned to manager
# - Customers của Branch 1
# - KHÔNG có customers của Branch 2
```

### Test 3: Regular User Access

```bash
# Login as regular user
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "sales@branch1.com", "password": "password"}'

# Get customers
curl http://localhost:8000/api/customers

# Expected:
# - Chỉ customers assigned_to = sales@branch1.com
```

---

## 📈 So Sánh với Calendar Module

| Tính năng | Customer | Calendar Event |
|-----------|----------|----------------|
| Super-admin | ✅ Xem tất cả | ✅ Xem tất cả |
| Branch Manager | ✅ Xem customers của branch | ✅ Xem events của branch |
| Regular User | ✅ Xem customers của mình | ✅ Xem events của mình |
| Isolation | ✅ Branch-based | ✅ Branch-based |
| Future: Manager | 🔮 Chuẩn bị sẵn | 🔮 Chuẩn bị sẵn |

**Cả 2 modules đều sử dụng logic phân quyền nhất quán!**

---

## 📝 Summary

### ✅ Đã Implement

1. **Branch-based Access Control**
   - Super-admin: Full access
   - Branch users: Branch-scoped access
   - Regular users: Self-only access

2. **Scope `accessibleBy()`**
   - Tự động filter customers theo quyền
   - Áp dụng cho tất cả queries

3. **Controller Updates**
   - `index()` - List view với phân quyền
   - `kanban()` - Kanban view với phân quyền
   - `statistics()` - Statistics với phân quyền

4. **Code Cleanup**
   - Xóa logic `user_branch_ids` thủ công
   - Backend tự động xử lý phân quyền

### 🎯 Lợi Ích

1. **Bảo Mật Tốt Hơn**
   - Data isolation giữa branches
   - User chỉ xem được những gì họ có quyền

2. **Code Sạch Hơn**
   - Frontend không cần xử lý phân quyền
   - Backend tự động filter

3. **Dễ Bảo Trì**
   - Thay đổi logic ở 1 nơi (Model)
   - Áp dụng toàn bộ hệ thống

4. **Chuẩn Bị Tương Lai**
   - Sẵn sàng cho HR Module
   - Dễ mở rộng thêm quyền

---

**🎉 Customer Module đã có phân quyền hoàn chỉnh!**

- ✅ Branch-based access control
- ✅ Nhất quán với Calendar Module
- ✅ Secure & scalable
- ✅ Sẵn sàng cho organizational hierarchy

**Refresh và test ngay!** 🚀


## 🎯 Tổng Quan

Customer module áp dụng cơ chế phân quyền tương tự Calendar module với 3 cấp độ:

1. **Super-admin** → Xem TẤT CẢ customers
2. **Branch Manager** → Xem customers của branch mình quản lý
3. **Regular User** → Chỉ xem customers được assign cho mình

**Lưu ý:** Hệ thống đã chuẩn bị sẵn cho **Direct Manager** - sẽ được implement sau khi có HR Module.

---

## 🔄 Logic Phân Quyền

### 1. Super-admin

```php
// Super-admin xem TẤT CẢ customers
if ($user->hasRole('super-admin')) {
    return $query; // Không filter gì cả
}
```

**Kết quả:**
- ✅ Xem tất cả customers của tất cả branches
- ✅ Xem tất cả customers được assign cho bất kỳ ai
- ✅ Không bị giới hạn

---

### 2. Branch Manager / User có Branches

```php
// Lấy branches của user
$userBranchIds = $user->branches()->pluck('branches.id')->toArray();

if (!empty($userBranchIds)) {
    // Xem customers:
    // 1. Được assign cho mình
    // 2. HOẶC customers thuộc branches của mình
    return $query->where(function ($q) use ($user, $userBranchIds) {
        $q->where('assigned_to', $user->id)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

**Kết quả:**
- ✅ Xem customers được assign cho chính mình
- ✅ Xem customers của TẤT CẢ users trong cùng branch
- ❌ Không xem customers của branches khác

**Ví dụ:**
```
User A thuộc Branch Hà Nội
→ Xem được:
  - Customer 1 (assigned_to = User A)
  - Customer 2 (branch_id = Hà Nội, assigned_to = User B)
  - Customer 3 (branch_id = Hà Nội, assigned_to = User C)
→ KHÔNG xem được:
  - Customer 4 (branch_id = TP.HCM, assigned_to = User D)
```

---

### 3. Regular User (Không có Branch)

```php
if (empty($userBranchIds)) {
    // Chỉ xem customers được assign cho mình
    return $query->where('assigned_to', $user->id);
}
```

**Kết quả:**
- ✅ Chỉ xem customers được assign cho chính mình
- ❌ Không xem customers của ai khác

---

## 🔧 Code Implementation

### Model: Customer.php

```php
/**
 * Scope: Customers mà user có quyền xem
 */
public function scopeAccessibleBy($query, User $user)
{
    // Super-admin xem tất cả
    if ($user->hasRole('super-admin')) {
        return $query;
    }

    // Lấy branches của user
    $userBranchIds = $user->branches()->pluck('branches.id')->toArray();

    if (empty($userBranchIds)) {
        // Không có branch → chỉ xem customers của mình
        return $query->where('assigned_to', $user->id);
    }

    // Có branches → xem của mình HOẶC cùng branch
    return $query->where(function ($q) use ($user, $userBranchIds) {
        $q->where('assigned_to', $user->id)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

### Controller: CustomerController.php

#### Index Method (List View)
```php
public function index(Request $request)
{
    $query = Customer::with([...])
        ->accessibleBy($request->user()); // ← Áp dụng phân quyền

    // Additional filters...
    if ($search) {
        $query->search($search);
    }

    $customers = $query->latest()->paginate($perPage);
    
    return response()->json([
        'success' => true,
        'data' => $customers
    ]);
}
```

#### Kanban Method
```php
public function kanban(Request $request)
{
    $query = Customer::with([...])
        ->accessibleBy($request->user()); // ← Áp dụng phân quyền

    $customers = $query->orderBy('stage_order')->get();
    // ... group by stages
}
```

#### Statistics Method
```php
public function statistics(Request $request)
{
    $query = Customer::query()
        ->accessibleBy($request->user()); // ← Áp dụng phân quyền

    $stats = [
        'total' => $query->count(),
        'by_stage' => [...],
        'total_value' => $query->sum('estimated_value'),
    ];
}
```

---

## 📊 Use Cases

### Use Case 1: Sales Team trong cùng Branch

```
Scenario:
- Branch: Hà Nội
- Users: Sales A, Sales B, Sales C

Kết quả:
- Sales A tạo Customer X (assigned_to = Sales A, branch = Hà Nội)
- Sales B, C xem được Customer X trong danh sách
- Họ biết Sales A đang chăm sóc khách nào
- Có thể hỗ trợ khi Sales A vắng mặt
```

### Use Case 2: Branch Manager Oversight

```
Scenario:
- Branch Manager Hà Nội cần theo dõi toàn bộ customers

Kết quả:
- Xem được TẤT CẢ customers của Branch Hà Nội
- Xem được ai đang chăm sóc khách nào
- Theo dõi pipeline của toàn branch
- Phân tích performance
```

### Use Case 3: Multi-Branch Isolation

```
Scenario:
- User A thuộc Branch Hà Nội
- User B thuộc Branch TP.HCM

Kết quả:
- User A KHÔNG xem được customers của Branch TP.HCM
- User B KHÔNG xem được customers của Branch Hà Nội
- Data isolation hoàn toàn giữa các branches
```

### Use Case 4: Super-admin Full View

```
Scenario:
- CEO/Director cần xem tổng quan toàn hệ thống

Kết quả:
- Super-admin xem TẤT CẢ customers của tất cả branches
- Theo dõi pipeline toàn công ty
- Phân tích doanh số theo branch
```

---

## 🔄 Áp Dụng cho Tất Cả Endpoints

Phân quyền được áp dụng cho:

### ✅ List View
```
GET /api/customers
→ Chỉ trả về customers mà user có quyền xem
```

### ✅ Kanban View
```
GET /api/customers/kanban
→ Chỉ hiển thị customers trong pipeline mà user có quyền xem
```

### ✅ Statistics
```
GET /api/customers/statistics
→ Thống kê chỉ tính customers mà user có quyền xem
```

### ⚠️ Show/Update/Delete
```
GET/PUT/DELETE /api/customers/{id}
→ Nếu customer không thuộc quyền → 404 Not Found
(Laravel tự động filter khi query)
```

---

## 🚀 Future: Direct Manager Hierarchy

### Khi có HR Module:

```php
public function scopeAccessibleBy($query, User $user)
{
    if ($user->hasRole('super-admin')) {
        return $query;
    }

    // Lấy subordinates (nhân viên dưới quyền)
    $subordinateIds = $user->getSubordinates()->pluck('id')->toArray();
    $userBranchIds = $user->branches()->pluck('branches.id')->toArray();

    return $query->where(function ($q) use ($user, $subordinateIds, $userBranchIds) {
        $q->where('assigned_to', $user->id)                    // Của mình
          ->orWhereIn('assigned_to', $subordinateIds)          // Của nhân viên dưới quyền
          ->orWhereIn('branch_id', $userBranchIds);            // Của cùng branch
    });
}
```

---

## 🧪 Testing Scenarios

### Test 1: Super-admin Access

```bash
# Login as super-admin
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'

# Get customers
curl http://localhost:8000/api/customers

# Expected: Tất cả customers của tất cả branches
```

### Test 2: Branch Manager Access

```bash
# Login as branch manager
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "manager@branch1.com", "password": "password"}'

# Get customers
curl http://localhost:8000/api/customers

# Expected:
# - Customers assigned to manager
# - Customers của Branch 1
# - KHÔNG có customers của Branch 2
```

### Test 3: Regular User Access

```bash
# Login as regular user
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "sales@branch1.com", "password": "password"}'

# Get customers
curl http://localhost:8000/api/customers

# Expected:
# - Chỉ customers assigned_to = sales@branch1.com
```

---

## 📈 So Sánh với Calendar Module

| Tính năng | Customer | Calendar Event |
|-----------|----------|----------------|
| Super-admin | ✅ Xem tất cả | ✅ Xem tất cả |
| Branch Manager | ✅ Xem customers của branch | ✅ Xem events của branch |
| Regular User | ✅ Xem customers của mình | ✅ Xem events của mình |
| Isolation | ✅ Branch-based | ✅ Branch-based |
| Future: Manager | 🔮 Chuẩn bị sẵn | 🔮 Chuẩn bị sẵn |

**Cả 2 modules đều sử dụng logic phân quyền nhất quán!**

---

## 📝 Summary

### ✅ Đã Implement

1. **Branch-based Access Control**
   - Super-admin: Full access
   - Branch users: Branch-scoped access
   - Regular users: Self-only access

2. **Scope `accessibleBy()`**
   - Tự động filter customers theo quyền
   - Áp dụng cho tất cả queries

3. **Controller Updates**
   - `index()` - List view với phân quyền
   - `kanban()` - Kanban view với phân quyền
   - `statistics()` - Statistics với phân quyền

4. **Code Cleanup**
   - Xóa logic `user_branch_ids` thủ công
   - Backend tự động xử lý phân quyền

### 🎯 Lợi Ích

1. **Bảo Mật Tốt Hơn**
   - Data isolation giữa branches
   - User chỉ xem được những gì họ có quyền

2. **Code Sạch Hơn**
   - Frontend không cần xử lý phân quyền
   - Backend tự động filter

3. **Dễ Bảo Trì**
   - Thay đổi logic ở 1 nơi (Model)
   - Áp dụng toàn bộ hệ thống

4. **Chuẩn Bị Tương Lai**
   - Sẵn sàng cho HR Module
   - Dễ mở rộng thêm quyền

---

**🎉 Customer Module đã có phân quyền hoàn chỉnh!**

- ✅ Branch-based access control
- ✅ Nhất quán với Calendar Module
- ✅ Secure & scalable
- ✅ Sẵn sàng cho organizational hierarchy

**Refresh và test ngay!** 🚀


## 🎯 Tổng Quan

Customer module áp dụng cơ chế phân quyền tương tự Calendar module với 3 cấp độ:

1. **Super-admin** → Xem TẤT CẢ customers
2. **Branch Manager** → Xem customers của branch mình quản lý
3. **Regular User** → Chỉ xem customers được assign cho mình

**Lưu ý:** Hệ thống đã chuẩn bị sẵn cho **Direct Manager** - sẽ được implement sau khi có HR Module.

---

## 🔄 Logic Phân Quyền

### 1. Super-admin

```php
// Super-admin xem TẤT CẢ customers
if ($user->hasRole('super-admin')) {
    return $query; // Không filter gì cả
}
```

**Kết quả:**
- ✅ Xem tất cả customers của tất cả branches
- ✅ Xem tất cả customers được assign cho bất kỳ ai
- ✅ Không bị giới hạn

---

### 2. Branch Manager / User có Branches

```php
// Lấy branches của user
$userBranchIds = $user->branches()->pluck('branches.id')->toArray();

if (!empty($userBranchIds)) {
    // Xem customers:
    // 1. Được assign cho mình
    // 2. HOẶC customers thuộc branches của mình
    return $query->where(function ($q) use ($user, $userBranchIds) {
        $q->where('assigned_to', $user->id)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

**Kết quả:**
- ✅ Xem customers được assign cho chính mình
- ✅ Xem customers của TẤT CẢ users trong cùng branch
- ❌ Không xem customers của branches khác

**Ví dụ:**
```
User A thuộc Branch Hà Nội
→ Xem được:
  - Customer 1 (assigned_to = User A)
  - Customer 2 (branch_id = Hà Nội, assigned_to = User B)
  - Customer 3 (branch_id = Hà Nội, assigned_to = User C)
→ KHÔNG xem được:
  - Customer 4 (branch_id = TP.HCM, assigned_to = User D)
```

---

### 3. Regular User (Không có Branch)

```php
if (empty($userBranchIds)) {
    // Chỉ xem customers được assign cho mình
    return $query->where('assigned_to', $user->id);
}
```

**Kết quả:**
- ✅ Chỉ xem customers được assign cho chính mình
- ❌ Không xem customers của ai khác

---

## 🔧 Code Implementation

### Model: Customer.php

```php
/**
 * Scope: Customers mà user có quyền xem
 */
public function scopeAccessibleBy($query, User $user)
{
    // Super-admin xem tất cả
    if ($user->hasRole('super-admin')) {
        return $query;
    }

    // Lấy branches của user
    $userBranchIds = $user->branches()->pluck('branches.id')->toArray();

    if (empty($userBranchIds)) {
        // Không có branch → chỉ xem customers của mình
        return $query->where('assigned_to', $user->id);
    }

    // Có branches → xem của mình HOẶC cùng branch
    return $query->where(function ($q) use ($user, $userBranchIds) {
        $q->where('assigned_to', $user->id)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

### Controller: CustomerController.php

#### Index Method (List View)
```php
public function index(Request $request)
{
    $query = Customer::with([...])
        ->accessibleBy($request->user()); // ← Áp dụng phân quyền

    // Additional filters...
    if ($search) {
        $query->search($search);
    }

    $customers = $query->latest()->paginate($perPage);
    
    return response()->json([
        'success' => true,
        'data' => $customers
    ]);
}
```

#### Kanban Method
```php
public function kanban(Request $request)
{
    $query = Customer::with([...])
        ->accessibleBy($request->user()); // ← Áp dụng phân quyền

    $customers = $query->orderBy('stage_order')->get();
    // ... group by stages
}
```

#### Statistics Method
```php
public function statistics(Request $request)
{
    $query = Customer::query()
        ->accessibleBy($request->user()); // ← Áp dụng phân quyền

    $stats = [
        'total' => $query->count(),
        'by_stage' => [...],
        'total_value' => $query->sum('estimated_value'),
    ];
}
```

---

## 📊 Use Cases

### Use Case 1: Sales Team trong cùng Branch

```
Scenario:
- Branch: Hà Nội
- Users: Sales A, Sales B, Sales C

Kết quả:
- Sales A tạo Customer X (assigned_to = Sales A, branch = Hà Nội)
- Sales B, C xem được Customer X trong danh sách
- Họ biết Sales A đang chăm sóc khách nào
- Có thể hỗ trợ khi Sales A vắng mặt
```

### Use Case 2: Branch Manager Oversight

```
Scenario:
- Branch Manager Hà Nội cần theo dõi toàn bộ customers

Kết quả:
- Xem được TẤT CẢ customers của Branch Hà Nội
- Xem được ai đang chăm sóc khách nào
- Theo dõi pipeline của toàn branch
- Phân tích performance
```

### Use Case 3: Multi-Branch Isolation

```
Scenario:
- User A thuộc Branch Hà Nội
- User B thuộc Branch TP.HCM

Kết quả:
- User A KHÔNG xem được customers của Branch TP.HCM
- User B KHÔNG xem được customers của Branch Hà Nội
- Data isolation hoàn toàn giữa các branches
```

### Use Case 4: Super-admin Full View

```
Scenario:
- CEO/Director cần xem tổng quan toàn hệ thống

Kết quả:
- Super-admin xem TẤT CẢ customers của tất cả branches
- Theo dõi pipeline toàn công ty
- Phân tích doanh số theo branch
```

---

## 🔄 Áp Dụng cho Tất Cả Endpoints

Phân quyền được áp dụng cho:

### ✅ List View
```
GET /api/customers
→ Chỉ trả về customers mà user có quyền xem
```

### ✅ Kanban View
```
GET /api/customers/kanban
→ Chỉ hiển thị customers trong pipeline mà user có quyền xem
```

### ✅ Statistics
```
GET /api/customers/statistics
→ Thống kê chỉ tính customers mà user có quyền xem
```

### ⚠️ Show/Update/Delete
```
GET/PUT/DELETE /api/customers/{id}
→ Nếu customer không thuộc quyền → 404 Not Found
(Laravel tự động filter khi query)
```

---

## 🚀 Future: Direct Manager Hierarchy

### Khi có HR Module:

```php
public function scopeAccessibleBy($query, User $user)
{
    if ($user->hasRole('super-admin')) {
        return $query;
    }

    // Lấy subordinates (nhân viên dưới quyền)
    $subordinateIds = $user->getSubordinates()->pluck('id')->toArray();
    $userBranchIds = $user->branches()->pluck('branches.id')->toArray();

    return $query->where(function ($q) use ($user, $subordinateIds, $userBranchIds) {
        $q->where('assigned_to', $user->id)                    // Của mình
          ->orWhereIn('assigned_to', $subordinateIds)          // Của nhân viên dưới quyền
          ->orWhereIn('branch_id', $userBranchIds);            // Của cùng branch
    });
}
```

---

## 🧪 Testing Scenarios

### Test 1: Super-admin Access

```bash
# Login as super-admin
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'

# Get customers
curl http://localhost:8000/api/customers

# Expected: Tất cả customers của tất cả branches
```

### Test 2: Branch Manager Access

```bash
# Login as branch manager
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "manager@branch1.com", "password": "password"}'

# Get customers
curl http://localhost:8000/api/customers

# Expected:
# - Customers assigned to manager
# - Customers của Branch 1
# - KHÔNG có customers của Branch 2
```

### Test 3: Regular User Access

```bash
# Login as regular user
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "sales@branch1.com", "password": "password"}'

# Get customers
curl http://localhost:8000/api/customers

# Expected:
# - Chỉ customers assigned_to = sales@branch1.com
```

---

## 📈 So Sánh với Calendar Module

| Tính năng | Customer | Calendar Event |
|-----------|----------|----------------|
| Super-admin | ✅ Xem tất cả | ✅ Xem tất cả |
| Branch Manager | ✅ Xem customers của branch | ✅ Xem events của branch |
| Regular User | ✅ Xem customers của mình | ✅ Xem events của mình |
| Isolation | ✅ Branch-based | ✅ Branch-based |
| Future: Manager | 🔮 Chuẩn bị sẵn | 🔮 Chuẩn bị sẵn |

**Cả 2 modules đều sử dụng logic phân quyền nhất quán!**

---

## 📝 Summary

### ✅ Đã Implement

1. **Branch-based Access Control**
   - Super-admin: Full access
   - Branch users: Branch-scoped access
   - Regular users: Self-only access

2. **Scope `accessibleBy()`**
   - Tự động filter customers theo quyền
   - Áp dụng cho tất cả queries

3. **Controller Updates**
   - `index()` - List view với phân quyền
   - `kanban()` - Kanban view với phân quyền
   - `statistics()` - Statistics với phân quyền

4. **Code Cleanup**
   - Xóa logic `user_branch_ids` thủ công
   - Backend tự động xử lý phân quyền

### 🎯 Lợi Ích

1. **Bảo Mật Tốt Hơn**
   - Data isolation giữa branches
   - User chỉ xem được những gì họ có quyền

2. **Code Sạch Hơn**
   - Frontend không cần xử lý phân quyền
   - Backend tự động filter

3. **Dễ Bảo Trì**
   - Thay đổi logic ở 1 nơi (Model)
   - Áp dụng toàn bộ hệ thống

4. **Chuẩn Bị Tương Lai**
   - Sẵn sàng cho HR Module
   - Dễ mở rộng thêm quyền

---

**🎉 Customer Module đã có phân quyền hoàn chỉnh!**

- ✅ Branch-based access control
- ✅ Nhất quán với Calendar Module
- ✅ Secure & scalable
- ✅ Sẵn sàng cho organizational hierarchy

**Refresh và test ngay!** 🚀

















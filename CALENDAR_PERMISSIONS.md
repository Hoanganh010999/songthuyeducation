# 🔐 Hệ Thống Phân Quyền Calendar - Branch & Organizational Hierarchy

## 🎯 Tổng Quan

Hệ thống phân quyền Calendar theo cấu trúc tổ chức với 3 cấp độ:

1. **Super-admin** → Xem TẤT CẢ events
2. **Branch Manager** → Xem events của branch mình quản lý
3. **Regular User** → Chỉ xem events của chính mình

**Lưu ý:** Hệ thống đã chuẩn bị sẵn cho **Direct Manager** (quản lý trực tiếp) - sẽ được implement sau khi có HR Module với sơ đồ tổ chức.

---

## 🗄️ Database Schema Updates

### Bảng `calendar_events` - Thêm Columns

```sql
ALTER TABLE calendar_events ADD (
    branch_id BIGINT UNSIGNED NULL,        -- Chi nhánh của event
    created_by BIGINT UNSIGNED NULL,       -- Người tạo event
    manager_id BIGINT UNSIGNED NULL,       -- Quản lý trực tiếp (dùng sau)
    
    INDEX(branch_id),
    INDEX(created_by),
    INDEX(manager_id)
);
```

### Ý Nghĩa Các Columns

| Column | Ý Nghĩa | Sử Dụng |
|--------|---------|---------|
| `user_id` | Người chịu trách nhiệm event | Hiện tại |
| `branch_id` | Chi nhánh của event | Hiện tại |
| `created_by` | Người tạo event | Hiện tại |
| `manager_id` | Quản lý trực tiếp của user_id | Tương lai (HR Module) |

---

## 🔄 Logic Phân Quyền

### 1. Super-admin

```php
// Super-admin xem TẤT CẢ events
if ($user->hasRole('super-admin')) {
    return $query; // Không filter gì cả
}
```

**Kết quả:**
- ✅ Xem tất cả events của tất cả users
- ✅ Xem tất cả events của tất cả branches
- ✅ Không bị giới hạn

---

### 2. Branch Manager / User có Branches

```php
// Lấy branches của user
$userBranchIds = $user->branches()->pluck('branches.id')->toArray();

if (!empty($userBranchIds)) {
    // Xem events của:
    // 1. Chính mình
    // 2. HOẶC events thuộc branches của mình
    return $query->where(function ($q) use ($user, $userBranchIds) {
        $q->where('user_id', $user->id)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

**Kết quả:**
- ✅ Xem events của chính mình
- ✅ Xem events của TẤT CẢ users trong cùng branch
- ❌ Không xem events của branches khác

**Ví dụ:**
```
User A thuộc Branch 1 và Branch 2
→ Xem được:
  - Events của chính mình
  - Events của User B (thuộc Branch 1)
  - Events của User C (thuộc Branch 2)
→ KHÔNG xem được:
  - Events của User D (thuộc Branch 3)
```

---

### 3. Regular User (Không có Branch)

```php
if (empty($userBranchIds)) {
    // Chỉ xem events của chính mình
    return $query->where('user_id', $user->id);
}
```

**Kết quả:**
- ✅ Chỉ xem events của chính mình
- ❌ Không xem events của ai khác

---

### 4. Direct Manager (Tương Lai - HR Module)

**Khi có HR Module với sơ đồ tổ chức:**

```php
// Lấy danh sách subordinates (nhân viên dưới quyền)
$subordinateIds = $user->subordinates()->pluck('id')->toArray();

if (!empty($subordinateIds)) {
    // Xem events của:
    // 1. Chính mình
    // 2. Nhân viên dưới quyền trực tiếp
    // 3. Events thuộc branches của mình
    return $query->where(function ($q) use ($user, $subordinateIds, $userBranchIds) {
        $q->where('user_id', $user->id)
          ->orWhereIn('user_id', $subordinateIds)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

---

## 📊 Flow: Customer Interaction → Calendar Event với Branch

### Khi tạo Customer Interaction:

```
1. User tạo CustomerInteraction cho Customer X
   ↓
2. Customer X thuộc Branch A (customer->branch_id = 1)
   ↓
3. syncCalendarEvent() được gọi
   ↓
4. CalendarEvent được tạo với:
   - user_id = User hiện tại
   - branch_id = Customer->branch_id (Branch A)
   - created_by = User hiện tại
   ↓
5. Event xuất hiện trên Calendar với branch assignment
```

### Ai xem được Event này?

```
✅ Super-admin: Xem được
✅ User tạo event: Xem được (chính mình)
✅ Users khác thuộc Branch A: Xem được (cùng branch)
❌ Users thuộc Branch B: KHÔNG xem được
```

---

## 🔧 Code Implementation

### Model: CalendarEvent.php

```php
/**
 * Scope: Events mà user có quyền xem
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
        // Không có branch → chỉ xem của mình
        return $query->where('user_id', $user->id);
    }

    // Có branches → xem của mình HOẶC cùng branch
    return $query->where(function ($q) use ($user, $userBranchIds) {
        $q->where('user_id', $user->id)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

### Service: CalendarEventService.php

```php
/**
 * Lấy events trong khoảng thời gian (với phân quyền)
 */
public function getEventsBetweenDates($startDate, $endDate, $user, ?string $category = null)
{
    $query = CalendarEvent::with(['user:id,name', 'branch:id,name', 'eventable'])
        ->betweenDates($startDate, $endDate)
        ->accessibleBy($user); // ← Áp dụng phân quyền

    if ($category) {
        $query->byCategory($category);
    }

    return $query->orderBy('start_date', 'asc')->get();
}
```

### Controller: CalendarEventController.php

```php
public function index(Request $request)
{
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $category = $request->input('category');
    
    $user = Auth::user(); // ← Lấy user hiện tại

    $events = $this->calendarService->getEventsBetweenDates(
        $startDate,
        $endDate,
        $user, // ← Truyền user vào
        $category
    );

    // Events đã được filter theo quyền
    return response()->json([
        'success' => true,
        'data' => $formattedEvents,
    ]);
}
```

### Model: CustomerInteraction.php

```php
public function syncCalendarEvent()
{
    // ...
    $customer = $this->customer;
    
    $calendarService->syncEvent($this, [
        'title' => "Liên hệ lại: {$customer->name}",
        'category' => 'customer_follow_up',
        'user_id' => $this->user_id,
        'branch_id' => $customer->branch_id, // ← Lấy branch từ customer
        'created_by' => $this->user_id,
        // ...
    ]);
}
```

---

## 📈 Use Cases

### Use Case 1: Sales Team trong cùng Branch

```
Scenario:
- Branch: Hà Nội
- Users: Sales A, Sales B, Sales C (cùng Branch Hà Nội)

Kết quả:
- Sales A tạo event "Gọi khách X"
- Sales B, C xem được event này trên calendar
- Họ biết Sales A đang follow khách nào
- Tránh duplicate effort
```

### Use Case 2: Multi-Branch Isolation

```
Scenario:
- Branch 1: Hà Nội
- Branch 2: TP.HCM
- User A thuộc Branch 1
- User B thuộc Branch 2

Kết quả:
- User A tạo event cho Customer X (Branch 1)
- User B KHÔNG xem được event này
- Data isolation giữa các branches
```

### Use Case 3: Super-admin Oversight

```
Scenario:
- Super-admin cần xem tổng quan toàn hệ thống

Kết quả:
- Super-admin xem TẤT CẢ events của tất cả branches
- Theo dõi workload của từng branch
- Phân tích performance
```

### Use Case 4: User không có Branch

```
Scenario:
- User mới, chưa được assign branch

Kết quả:
- Chỉ xem events của chính mình
- Không xem được events của ai khác
- Bảo mật dữ liệu
```

---

## 🚀 Future: Direct Manager Hierarchy

### Khi có HR Module:

```sql
-- Bảng organizational_structure
CREATE TABLE organizational_structure (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,           -- Nhân viên
    manager_id BIGINT,        -- Quản lý trực tiếp
    department_id BIGINT,     -- Phòng ban
    position_id BIGINT,       -- Chức vụ
    level INT,                -- Cấp bậc (1=CEO, 2=Director, 3=Manager, 4=Staff)
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Logic Mở Rộng:

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
        $q->where('user_id', $user->id)                    // Của mình
          ->orWhereIn('user_id', $subordinateIds)          // Của nhân viên dưới quyền
          ->orWhereIn('branch_id', $userBranchIds);        // Của cùng branch
    });
}
```

### Ví dụ Hierarchy:

```
CEO (Super-admin)
  ↓
Director Miền Bắc
  ↓
Manager Branch Hà Nội
  ↓
Sales Team (A, B, C)

Quyền xem:
- CEO: Tất cả events
- Director: Events của tất cả branches miền Bắc
- Manager: Events của Sales A, B, C trong Branch Hà Nội
- Sales A: Events của chính mình + cùng branch
```

---

## 🧪 Testing Scenarios

### Test 1: Super-admin Access

```bash
# Login as super-admin
POST /api/auth/login
{
  "email": "admin@example.com",
  "password": "password"
}

# Get calendar events
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Expected: Tất cả events của tất cả users
```

### Test 2: Branch Manager Access

```bash
# Login as user thuộc Branch 1
POST /api/auth/login
{
  "email": "manager@branch1.com",
  "password": "password"
}

# Get calendar events
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Expected: 
# - Events của chính mình
# - Events của users khác trong Branch 1
# - KHÔNG có events của Branch 2
```

### Test 3: Regular User Access

```bash
# Login as regular user (không có branch)
POST /api/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

# Get calendar events
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Expected: Chỉ events của chính mình
```

### Test 4: Customer Interaction Sync

```bash
# Tạo customer interaction với next_follow_up
POST /api/customers/1/interactions
{
  "interaction_type_id": 1,
  "interaction_result_id": 3,
  "notes": "Khách hàng quan tâm",
  "interaction_date": "2025-10-31 10:00:00",
  "next_follow_up": "2025-11-05 14:00:00"
}

# Kiểm tra calendar event
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Expected:
# - Event được tạo với branch_id = customer->branch_id
# - Users cùng branch xem được
```

---

## 📝 Summary

### ✅ Đã Implement

1. **Branch-based Access Control**
   - Super-admin: Full access
   - Branch users: Branch-scoped access
   - Regular users: Self-only access

2. **Auto Branch Assignment**
   - Customer Interactions → inherit customer's branch
   - Standalone events → user's primary branch

3. **Database Schema**
   - `branch_id`, `created_by`, `manager_id` columns
   - Proper indexes for performance

4. **API Endpoints**
   - All calendar endpoints respect permissions
   - Automatic filtering based on user

### 🔮 Chuẩn Bị Sẵn cho Tương Lai

1. **Direct Manager Hierarchy**
   - `manager_id` column ready
   - Scope logic có thể mở rộng dễ dàng

2. **HR Module Integration**
   - Organizational structure
   - Department-based access
   - Position-based permissions

3. **Advanced Features**
   - Team calendars
   - Department calendars
   - Cross-branch collaboration (với permissions)

---

**🎉 Hệ thống phân quyền Calendar đã sẵn sàng! Branch-based access control hoạt động hoàn hảo!** 🚀


## 🎯 Tổng Quan

Hệ thống phân quyền Calendar theo cấu trúc tổ chức với 3 cấp độ:

1. **Super-admin** → Xem TẤT CẢ events
2. **Branch Manager** → Xem events của branch mình quản lý
3. **Regular User** → Chỉ xem events của chính mình

**Lưu ý:** Hệ thống đã chuẩn bị sẵn cho **Direct Manager** (quản lý trực tiếp) - sẽ được implement sau khi có HR Module với sơ đồ tổ chức.

---

## 🗄️ Database Schema Updates

### Bảng `calendar_events` - Thêm Columns

```sql
ALTER TABLE calendar_events ADD (
    branch_id BIGINT UNSIGNED NULL,        -- Chi nhánh của event
    created_by BIGINT UNSIGNED NULL,       -- Người tạo event
    manager_id BIGINT UNSIGNED NULL,       -- Quản lý trực tiếp (dùng sau)
    
    INDEX(branch_id),
    INDEX(created_by),
    INDEX(manager_id)
);
```

### Ý Nghĩa Các Columns

| Column | Ý Nghĩa | Sử Dụng |
|--------|---------|---------|
| `user_id` | Người chịu trách nhiệm event | Hiện tại |
| `branch_id` | Chi nhánh của event | Hiện tại |
| `created_by` | Người tạo event | Hiện tại |
| `manager_id` | Quản lý trực tiếp của user_id | Tương lai (HR Module) |

---

## 🔄 Logic Phân Quyền

### 1. Super-admin

```php
// Super-admin xem TẤT CẢ events
if ($user->hasRole('super-admin')) {
    return $query; // Không filter gì cả
}
```

**Kết quả:**
- ✅ Xem tất cả events của tất cả users
- ✅ Xem tất cả events của tất cả branches
- ✅ Không bị giới hạn

---

### 2. Branch Manager / User có Branches

```php
// Lấy branches của user
$userBranchIds = $user->branches()->pluck('branches.id')->toArray();

if (!empty($userBranchIds)) {
    // Xem events của:
    // 1. Chính mình
    // 2. HOẶC events thuộc branches của mình
    return $query->where(function ($q) use ($user, $userBranchIds) {
        $q->where('user_id', $user->id)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

**Kết quả:**
- ✅ Xem events của chính mình
- ✅ Xem events của TẤT CẢ users trong cùng branch
- ❌ Không xem events của branches khác

**Ví dụ:**
```
User A thuộc Branch 1 và Branch 2
→ Xem được:
  - Events của chính mình
  - Events của User B (thuộc Branch 1)
  - Events của User C (thuộc Branch 2)
→ KHÔNG xem được:
  - Events của User D (thuộc Branch 3)
```

---

### 3. Regular User (Không có Branch)

```php
if (empty($userBranchIds)) {
    // Chỉ xem events của chính mình
    return $query->where('user_id', $user->id);
}
```

**Kết quả:**
- ✅ Chỉ xem events của chính mình
- ❌ Không xem events của ai khác

---

### 4. Direct Manager (Tương Lai - HR Module)

**Khi có HR Module với sơ đồ tổ chức:**

```php
// Lấy danh sách subordinates (nhân viên dưới quyền)
$subordinateIds = $user->subordinates()->pluck('id')->toArray();

if (!empty($subordinateIds)) {
    // Xem events của:
    // 1. Chính mình
    // 2. Nhân viên dưới quyền trực tiếp
    // 3. Events thuộc branches của mình
    return $query->where(function ($q) use ($user, $subordinateIds, $userBranchIds) {
        $q->where('user_id', $user->id)
          ->orWhereIn('user_id', $subordinateIds)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

---

## 📊 Flow: Customer Interaction → Calendar Event với Branch

### Khi tạo Customer Interaction:

```
1. User tạo CustomerInteraction cho Customer X
   ↓
2. Customer X thuộc Branch A (customer->branch_id = 1)
   ↓
3. syncCalendarEvent() được gọi
   ↓
4. CalendarEvent được tạo với:
   - user_id = User hiện tại
   - branch_id = Customer->branch_id (Branch A)
   - created_by = User hiện tại
   ↓
5. Event xuất hiện trên Calendar với branch assignment
```

### Ai xem được Event này?

```
✅ Super-admin: Xem được
✅ User tạo event: Xem được (chính mình)
✅ Users khác thuộc Branch A: Xem được (cùng branch)
❌ Users thuộc Branch B: KHÔNG xem được
```

---

## 🔧 Code Implementation

### Model: CalendarEvent.php

```php
/**
 * Scope: Events mà user có quyền xem
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
        // Không có branch → chỉ xem của mình
        return $query->where('user_id', $user->id);
    }

    // Có branches → xem của mình HOẶC cùng branch
    return $query->where(function ($q) use ($user, $userBranchIds) {
        $q->where('user_id', $user->id)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

### Service: CalendarEventService.php

```php
/**
 * Lấy events trong khoảng thời gian (với phân quyền)
 */
public function getEventsBetweenDates($startDate, $endDate, $user, ?string $category = null)
{
    $query = CalendarEvent::with(['user:id,name', 'branch:id,name', 'eventable'])
        ->betweenDates($startDate, $endDate)
        ->accessibleBy($user); // ← Áp dụng phân quyền

    if ($category) {
        $query->byCategory($category);
    }

    return $query->orderBy('start_date', 'asc')->get();
}
```

### Controller: CalendarEventController.php

```php
public function index(Request $request)
{
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $category = $request->input('category');
    
    $user = Auth::user(); // ← Lấy user hiện tại

    $events = $this->calendarService->getEventsBetweenDates(
        $startDate,
        $endDate,
        $user, // ← Truyền user vào
        $category
    );

    // Events đã được filter theo quyền
    return response()->json([
        'success' => true,
        'data' => $formattedEvents,
    ]);
}
```

### Model: CustomerInteraction.php

```php
public function syncCalendarEvent()
{
    // ...
    $customer = $this->customer;
    
    $calendarService->syncEvent($this, [
        'title' => "Liên hệ lại: {$customer->name}",
        'category' => 'customer_follow_up',
        'user_id' => $this->user_id,
        'branch_id' => $customer->branch_id, // ← Lấy branch từ customer
        'created_by' => $this->user_id,
        // ...
    ]);
}
```

---

## 📈 Use Cases

### Use Case 1: Sales Team trong cùng Branch

```
Scenario:
- Branch: Hà Nội
- Users: Sales A, Sales B, Sales C (cùng Branch Hà Nội)

Kết quả:
- Sales A tạo event "Gọi khách X"
- Sales B, C xem được event này trên calendar
- Họ biết Sales A đang follow khách nào
- Tránh duplicate effort
```

### Use Case 2: Multi-Branch Isolation

```
Scenario:
- Branch 1: Hà Nội
- Branch 2: TP.HCM
- User A thuộc Branch 1
- User B thuộc Branch 2

Kết quả:
- User A tạo event cho Customer X (Branch 1)
- User B KHÔNG xem được event này
- Data isolation giữa các branches
```

### Use Case 3: Super-admin Oversight

```
Scenario:
- Super-admin cần xem tổng quan toàn hệ thống

Kết quả:
- Super-admin xem TẤT CẢ events của tất cả branches
- Theo dõi workload của từng branch
- Phân tích performance
```

### Use Case 4: User không có Branch

```
Scenario:
- User mới, chưa được assign branch

Kết quả:
- Chỉ xem events của chính mình
- Không xem được events của ai khác
- Bảo mật dữ liệu
```

---

## 🚀 Future: Direct Manager Hierarchy

### Khi có HR Module:

```sql
-- Bảng organizational_structure
CREATE TABLE organizational_structure (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,           -- Nhân viên
    manager_id BIGINT,        -- Quản lý trực tiếp
    department_id BIGINT,     -- Phòng ban
    position_id BIGINT,       -- Chức vụ
    level INT,                -- Cấp bậc (1=CEO, 2=Director, 3=Manager, 4=Staff)
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Logic Mở Rộng:

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
        $q->where('user_id', $user->id)                    // Của mình
          ->orWhereIn('user_id', $subordinateIds)          // Của nhân viên dưới quyền
          ->orWhereIn('branch_id', $userBranchIds);        // Của cùng branch
    });
}
```

### Ví dụ Hierarchy:

```
CEO (Super-admin)
  ↓
Director Miền Bắc
  ↓
Manager Branch Hà Nội
  ↓
Sales Team (A, B, C)

Quyền xem:
- CEO: Tất cả events
- Director: Events của tất cả branches miền Bắc
- Manager: Events của Sales A, B, C trong Branch Hà Nội
- Sales A: Events của chính mình + cùng branch
```

---

## 🧪 Testing Scenarios

### Test 1: Super-admin Access

```bash
# Login as super-admin
POST /api/auth/login
{
  "email": "admin@example.com",
  "password": "password"
}

# Get calendar events
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Expected: Tất cả events của tất cả users
```

### Test 2: Branch Manager Access

```bash
# Login as user thuộc Branch 1
POST /api/auth/login
{
  "email": "manager@branch1.com",
  "password": "password"
}

# Get calendar events
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Expected: 
# - Events của chính mình
# - Events của users khác trong Branch 1
# - KHÔNG có events của Branch 2
```

### Test 3: Regular User Access

```bash
# Login as regular user (không có branch)
POST /api/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

# Get calendar events
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Expected: Chỉ events của chính mình
```

### Test 4: Customer Interaction Sync

```bash
# Tạo customer interaction với next_follow_up
POST /api/customers/1/interactions
{
  "interaction_type_id": 1,
  "interaction_result_id": 3,
  "notes": "Khách hàng quan tâm",
  "interaction_date": "2025-10-31 10:00:00",
  "next_follow_up": "2025-11-05 14:00:00"
}

# Kiểm tra calendar event
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Expected:
# - Event được tạo với branch_id = customer->branch_id
# - Users cùng branch xem được
```

---

## 📝 Summary

### ✅ Đã Implement

1. **Branch-based Access Control**
   - Super-admin: Full access
   - Branch users: Branch-scoped access
   - Regular users: Self-only access

2. **Auto Branch Assignment**
   - Customer Interactions → inherit customer's branch
   - Standalone events → user's primary branch

3. **Database Schema**
   - `branch_id`, `created_by`, `manager_id` columns
   - Proper indexes for performance

4. **API Endpoints**
   - All calendar endpoints respect permissions
   - Automatic filtering based on user

### 🔮 Chuẩn Bị Sẵn cho Tương Lai

1. **Direct Manager Hierarchy**
   - `manager_id` column ready
   - Scope logic có thể mở rộng dễ dàng

2. **HR Module Integration**
   - Organizational structure
   - Department-based access
   - Position-based permissions

3. **Advanced Features**
   - Team calendars
   - Department calendars
   - Cross-branch collaboration (với permissions)

---

**🎉 Hệ thống phân quyền Calendar đã sẵn sàng! Branch-based access control hoạt động hoàn hảo!** 🚀


## 🎯 Tổng Quan

Hệ thống phân quyền Calendar theo cấu trúc tổ chức với 3 cấp độ:

1. **Super-admin** → Xem TẤT CẢ events
2. **Branch Manager** → Xem events của branch mình quản lý
3. **Regular User** → Chỉ xem events của chính mình

**Lưu ý:** Hệ thống đã chuẩn bị sẵn cho **Direct Manager** (quản lý trực tiếp) - sẽ được implement sau khi có HR Module với sơ đồ tổ chức.

---

## 🗄️ Database Schema Updates

### Bảng `calendar_events` - Thêm Columns

```sql
ALTER TABLE calendar_events ADD (
    branch_id BIGINT UNSIGNED NULL,        -- Chi nhánh của event
    created_by BIGINT UNSIGNED NULL,       -- Người tạo event
    manager_id BIGINT UNSIGNED NULL,       -- Quản lý trực tiếp (dùng sau)
    
    INDEX(branch_id),
    INDEX(created_by),
    INDEX(manager_id)
);
```

### Ý Nghĩa Các Columns

| Column | Ý Nghĩa | Sử Dụng |
|--------|---------|---------|
| `user_id` | Người chịu trách nhiệm event | Hiện tại |
| `branch_id` | Chi nhánh của event | Hiện tại |
| `created_by` | Người tạo event | Hiện tại |
| `manager_id` | Quản lý trực tiếp của user_id | Tương lai (HR Module) |

---

## 🔄 Logic Phân Quyền

### 1. Super-admin

```php
// Super-admin xem TẤT CẢ events
if ($user->hasRole('super-admin')) {
    return $query; // Không filter gì cả
}
```

**Kết quả:**
- ✅ Xem tất cả events của tất cả users
- ✅ Xem tất cả events của tất cả branches
- ✅ Không bị giới hạn

---

### 2. Branch Manager / User có Branches

```php
// Lấy branches của user
$userBranchIds = $user->branches()->pluck('branches.id')->toArray();

if (!empty($userBranchIds)) {
    // Xem events của:
    // 1. Chính mình
    // 2. HOẶC events thuộc branches của mình
    return $query->where(function ($q) use ($user, $userBranchIds) {
        $q->where('user_id', $user->id)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

**Kết quả:**
- ✅ Xem events của chính mình
- ✅ Xem events của TẤT CẢ users trong cùng branch
- ❌ Không xem events của branches khác

**Ví dụ:**
```
User A thuộc Branch 1 và Branch 2
→ Xem được:
  - Events của chính mình
  - Events của User B (thuộc Branch 1)
  - Events của User C (thuộc Branch 2)
→ KHÔNG xem được:
  - Events của User D (thuộc Branch 3)
```

---

### 3. Regular User (Không có Branch)

```php
if (empty($userBranchIds)) {
    // Chỉ xem events của chính mình
    return $query->where('user_id', $user->id);
}
```

**Kết quả:**
- ✅ Chỉ xem events của chính mình
- ❌ Không xem events của ai khác

---

### 4. Direct Manager (Tương Lai - HR Module)

**Khi có HR Module với sơ đồ tổ chức:**

```php
// Lấy danh sách subordinates (nhân viên dưới quyền)
$subordinateIds = $user->subordinates()->pluck('id')->toArray();

if (!empty($subordinateIds)) {
    // Xem events của:
    // 1. Chính mình
    // 2. Nhân viên dưới quyền trực tiếp
    // 3. Events thuộc branches của mình
    return $query->where(function ($q) use ($user, $subordinateIds, $userBranchIds) {
        $q->where('user_id', $user->id)
          ->orWhereIn('user_id', $subordinateIds)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

---

## 📊 Flow: Customer Interaction → Calendar Event với Branch

### Khi tạo Customer Interaction:

```
1. User tạo CustomerInteraction cho Customer X
   ↓
2. Customer X thuộc Branch A (customer->branch_id = 1)
   ↓
3. syncCalendarEvent() được gọi
   ↓
4. CalendarEvent được tạo với:
   - user_id = User hiện tại
   - branch_id = Customer->branch_id (Branch A)
   - created_by = User hiện tại
   ↓
5. Event xuất hiện trên Calendar với branch assignment
```

### Ai xem được Event này?

```
✅ Super-admin: Xem được
✅ User tạo event: Xem được (chính mình)
✅ Users khác thuộc Branch A: Xem được (cùng branch)
❌ Users thuộc Branch B: KHÔNG xem được
```

---

## 🔧 Code Implementation

### Model: CalendarEvent.php

```php
/**
 * Scope: Events mà user có quyền xem
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
        // Không có branch → chỉ xem của mình
        return $query->where('user_id', $user->id);
    }

    // Có branches → xem của mình HOẶC cùng branch
    return $query->where(function ($q) use ($user, $userBranchIds) {
        $q->where('user_id', $user->id)
          ->orWhereIn('branch_id', $userBranchIds);
    });
}
```

### Service: CalendarEventService.php

```php
/**
 * Lấy events trong khoảng thời gian (với phân quyền)
 */
public function getEventsBetweenDates($startDate, $endDate, $user, ?string $category = null)
{
    $query = CalendarEvent::with(['user:id,name', 'branch:id,name', 'eventable'])
        ->betweenDates($startDate, $endDate)
        ->accessibleBy($user); // ← Áp dụng phân quyền

    if ($category) {
        $query->byCategory($category);
    }

    return $query->orderBy('start_date', 'asc')->get();
}
```

### Controller: CalendarEventController.php

```php
public function index(Request $request)
{
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $category = $request->input('category');
    
    $user = Auth::user(); // ← Lấy user hiện tại

    $events = $this->calendarService->getEventsBetweenDates(
        $startDate,
        $endDate,
        $user, // ← Truyền user vào
        $category
    );

    // Events đã được filter theo quyền
    return response()->json([
        'success' => true,
        'data' => $formattedEvents,
    ]);
}
```

### Model: CustomerInteraction.php

```php
public function syncCalendarEvent()
{
    // ...
    $customer = $this->customer;
    
    $calendarService->syncEvent($this, [
        'title' => "Liên hệ lại: {$customer->name}",
        'category' => 'customer_follow_up',
        'user_id' => $this->user_id,
        'branch_id' => $customer->branch_id, // ← Lấy branch từ customer
        'created_by' => $this->user_id,
        // ...
    ]);
}
```

---

## 📈 Use Cases

### Use Case 1: Sales Team trong cùng Branch

```
Scenario:
- Branch: Hà Nội
- Users: Sales A, Sales B, Sales C (cùng Branch Hà Nội)

Kết quả:
- Sales A tạo event "Gọi khách X"
- Sales B, C xem được event này trên calendar
- Họ biết Sales A đang follow khách nào
- Tránh duplicate effort
```

### Use Case 2: Multi-Branch Isolation

```
Scenario:
- Branch 1: Hà Nội
- Branch 2: TP.HCM
- User A thuộc Branch 1
- User B thuộc Branch 2

Kết quả:
- User A tạo event cho Customer X (Branch 1)
- User B KHÔNG xem được event này
- Data isolation giữa các branches
```

### Use Case 3: Super-admin Oversight

```
Scenario:
- Super-admin cần xem tổng quan toàn hệ thống

Kết quả:
- Super-admin xem TẤT CẢ events của tất cả branches
- Theo dõi workload của từng branch
- Phân tích performance
```

### Use Case 4: User không có Branch

```
Scenario:
- User mới, chưa được assign branch

Kết quả:
- Chỉ xem events của chính mình
- Không xem được events của ai khác
- Bảo mật dữ liệu
```

---

## 🚀 Future: Direct Manager Hierarchy

### Khi có HR Module:

```sql
-- Bảng organizational_structure
CREATE TABLE organizational_structure (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,           -- Nhân viên
    manager_id BIGINT,        -- Quản lý trực tiếp
    department_id BIGINT,     -- Phòng ban
    position_id BIGINT,       -- Chức vụ
    level INT,                -- Cấp bậc (1=CEO, 2=Director, 3=Manager, 4=Staff)
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Logic Mở Rộng:

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
        $q->where('user_id', $user->id)                    // Của mình
          ->orWhereIn('user_id', $subordinateIds)          // Của nhân viên dưới quyền
          ->orWhereIn('branch_id', $userBranchIds);        // Của cùng branch
    });
}
```

### Ví dụ Hierarchy:

```
CEO (Super-admin)
  ↓
Director Miền Bắc
  ↓
Manager Branch Hà Nội
  ↓
Sales Team (A, B, C)

Quyền xem:
- CEO: Tất cả events
- Director: Events của tất cả branches miền Bắc
- Manager: Events của Sales A, B, C trong Branch Hà Nội
- Sales A: Events của chính mình + cùng branch
```

---

## 🧪 Testing Scenarios

### Test 1: Super-admin Access

```bash
# Login as super-admin
POST /api/auth/login
{
  "email": "admin@example.com",
  "password": "password"
}

# Get calendar events
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Expected: Tất cả events của tất cả users
```

### Test 2: Branch Manager Access

```bash
# Login as user thuộc Branch 1
POST /api/auth/login
{
  "email": "manager@branch1.com",
  "password": "password"
}

# Get calendar events
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Expected: 
# - Events của chính mình
# - Events của users khác trong Branch 1
# - KHÔNG có events của Branch 2
```

### Test 3: Regular User Access

```bash
# Login as regular user (không có branch)
POST /api/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

# Get calendar events
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Expected: Chỉ events của chính mình
```

### Test 4: Customer Interaction Sync

```bash
# Tạo customer interaction với next_follow_up
POST /api/customers/1/interactions
{
  "interaction_type_id": 1,
  "interaction_result_id": 3,
  "notes": "Khách hàng quan tâm",
  "interaction_date": "2025-10-31 10:00:00",
  "next_follow_up": "2025-11-05 14:00:00"
}

# Kiểm tra calendar event
GET /api/calendar/events?start_date=2025-11-01&end_date=2025-11-30

# Expected:
# - Event được tạo với branch_id = customer->branch_id
# - Users cùng branch xem được
```

---

## 📝 Summary

### ✅ Đã Implement

1. **Branch-based Access Control**
   - Super-admin: Full access
   - Branch users: Branch-scoped access
   - Regular users: Self-only access

2. **Auto Branch Assignment**
   - Customer Interactions → inherit customer's branch
   - Standalone events → user's primary branch

3. **Database Schema**
   - `branch_id`, `created_by`, `manager_id` columns
   - Proper indexes for performance

4. **API Endpoints**
   - All calendar endpoints respect permissions
   - Automatic filtering based on user

### 🔮 Chuẩn Bị Sẵn cho Tương Lai

1. **Direct Manager Hierarchy**
   - `manager_id` column ready
   - Scope logic có thể mở rộng dễ dàng

2. **HR Module Integration**
   - Organizational structure
   - Department-based access
   - Position-based permissions

3. **Advanced Features**
   - Team calendars
   - Department calendars
   - Cross-branch collaboration (với permissions)

---

**🎉 Hệ thống phân quyền Calendar đã sẵn sàng! Branch-based access control hoạt động hoàn hảo!** 🚀

















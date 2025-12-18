# 🎯 MODULE QUẢN LÝ KHÁCH HÀNG (CUSTOMERS)

## ✅ Backend Hoàn Tất 100%

Module Quản Lý Khách Hàng với Kanban board cho quá trình chăm sóc và chốt đơn.

---

## 🎯 Tính Năng Chính

### 1. Quản Lý Khách Hàng
- ✅ CRUD đầy đủ
- ✅ Tự động generate mã khách hàng (CUS + YYYYMMDD + 0001)
- ✅ Gán chi nhánh tự động theo logic:
  - **Super-admin:** PHẢI chọn chi nhánh
  - **User thường:** Tự động lấy primary branch
- ✅ Assign người phụ trách
- ✅ Soft delete

### 2. Sales Pipeline (Kanban)
7 giai đoạn:
1. **Lead** - Khách hàng tiềm năng
2. **Contacted** - Đã liên hệ
3. **Qualified** - Đủ điều kiện
4. **Proposal** - Đã gửi đề xuất
5. **Negotiation** - Đang đàm phán
6. **Closed Won** - Chốt thành công ✅
7. **Closed Lost** - Mất khách ❌

### 3. Branch Access Control
- ✅ Super-admin: Thấy tất cả customers
- ✅ User có branches: Chỉ thấy customers của branches mình
- ✅ Middleware `branch.access` tự động filter

---

## 📊 Database Schema

### Table: `customers`
```sql
id                  BIGINT UNSIGNED PRIMARY KEY
code                VARCHAR UNIQUE          -- Auto: CUS20251031000

1
name                VARCHAR                 -- Họ tên
phone               VARCHAR NULL
email               VARCHAR NULL
date_of_birth       DATE NULL
gender              ENUM(male,female,other)

address             TEXT NULL
city                VARCHAR NULL
district            VARCHAR NULL
ward                VARCHAR NULL

stage               ENUM(...)               -- Pipeline stage
stage_order         INTEGER                 -- Thứ tự trong stage

source              VARCHAR NULL            -- Nguồn KH
branch_id           FK branches             -- Chi nhánh
assigned_to         FK users NULL           -- Người phụ trách

notes               TEXT NULL
estimated_value     DECIMAL(15,2) NULL      -- Giá trị dự kiến
expected_close_date DATE NULL
closed_at           DATE NULL               -- Ngày chốt thực tế

is_active           BOOLEAN DEFAULT 1
metadata            JSON NULL

created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP NULL          -- Soft delete
```

---

## 🔧 Customer Model

### Constants
```php
const STAGE_LEAD = 'lead';
const STAGE_CONTACTED = 'contacted';
const STAGE_QUALIFIED = 'qualified';
const STAGE_PROPOSAL = 'proposal';
const STAGE_NEGOTIATION = 'negotiation';
const STAGE_CLOSED_WON = 'closed_won';
const STAGE_CLOSED_LOST = 'closed_lost';
```

### Relationships
```php
branch()        // BelongsTo Branch
assignedUser()  // BelongsTo User
```

### Scopes
```php
active()                    // WHERE is_active = 1
byStage($stage)             // WHERE stage = ?
byBranch($branchId)         // WHERE branch_id = ?
byBranches($branchIds)      // WHERE IN branch_id
assignedTo($userId)         // WHERE assigned_to = ?
search($search)             // LIKE name, code, phone, email
```

### Methods
```php
generateCode()              // Static: Tạo mã KH
moveToStage($stage, $order) // Chuyển stage
getStages()                 // Static: Lấy tất cả stages
```

---

## 🎮 CustomerController API

### Endpoints

#### 1. GET `/api/customers` - List View
```bash
GET /api/customers?page=1&per_page=15&search=&stage=&branch_id=&assigned_to=

Middleware: auth:sanctum, branch.access, permission:customers.view

Response:
{
  "success": true,
  "data": {
    "data": [...],
    "current_page": 1,
    "total": 20
  }
}
```

#### 2. GET `/api/customers/kanban` - Kanban View
```bash
GET /api/customers/kanban

Middleware: auth:sanctum, branch.access, permission:customers.view

Response:
{
  "success": true,
  "data": {
    "lead": {
      "label": "Khách Tiềm Năng",
      "customers": [...],
      "count": 5
    },
    "contacted": {...},
    ...
  }
}
```

#### 3. POST `/api/customers` - Create
```bash
POST /api/customers
{
  "name": "Nguyễn Văn A",
  "phone": "0901234567",
  "email": "a@example.com",
  "branch_id": 1,  // Required for super-admin, ignored for others
  "assigned_to": 2,
  "estimated_value": 5000000,
  ...
}

Logic:
- Super-admin: Dùng branch_id từ request
- User thường: Tự động lấy primary branch

Middleware: auth:sanctum, branch.access, permission:customers.create
```

#### 4. GET `/api/customers/{id}` - Detail
```bash
GET /api/customers/1

Middleware: auth:sanctum, permission:customers.view
```

#### 5. PUT `/api/customers/{id}` - Update
```bash
PUT /api/customers/1
{...}

Middleware: auth:sanctum, permission:customers.edit
```

#### 6. DELETE `/api/customers/{id}` - Delete
```bash
DELETE /api/customers/1

Middleware: auth:sanctum, permission:customers.delete
```

#### 7. POST `/api/customers/{id}/move-stage` - Move Stage (Kanban)
```bash
POST /api/customers/1/move-stage
{
  "stage": "contacted",
  "stage_order": 0
}

Middleware: auth:sanctum, permission:customers.edit
```

#### 8. GET `/api/customers/statistics` - Statistics
```bash
GET /api/customers/statistics

Middleware: auth:sanctum, branch.access, permission:customers.view

Response:
{
  "success": true,
  "data": {
    "total": 20,
    "by_stage": {
      "lead": {"label": "...", "count": 5},
      ...
    },
    "total_value": 150000000,
    "closed_won_value": 50000000
  }
}
```

---

## 🔐 Permissions

**Module:** `customers`

1. `customers.view` - Xem Khách Hàng
2. `customers.create` - Tạo Khách Hàng
3. `customers.edit` - Sửa Khách Hàng
4. `customers.delete` - Xóa Khách Hàng

**Tự động gán:**
- Super-admin: Tất cả
- Admin: Tất cả
- Manager: view, create, edit (không có delete)

---

## 📊 Sample Data

**20 customers mẫu:**
- Phân bố đều các stages
- Random branches (HN01, HCM01, DN01)
- Random assigned users
- Estimated value: 1-50 triệu
- Sources: Facebook, Google, Referral, Website, Walk-in, Phone Call

---

## 🎯 Logic Gán Branch

### Khi Tạo Customer Mới

**Super-Admin:**
```php
// PHẢI chọn branch trong form
// branch_id là required trong validation
// Sử dụng branch_id từ request
```

**User Thường:**
```php
// Tự động lấy primary branch
$primaryBranch = $user->getPrimaryBranch();
$validated['branch_id'] = $primaryBranch->id;

// Nếu không có primary branch → Error
```

### Khi Xem Danh Sách

**Super-Admin:**
```php
// Không có user_branch_ids trong request
// Thấy TẤT CẢ customers
```

**User Có Branches:**
```php
// Middleware adds: user_branch_ids = [2, 3]
// Controller: ->byBranches($branchIds)
// Chỉ thấy customers của branches mình
```

---

## 🧪 Test Scenarios

### Test 1: Create Customer as Super-Admin
```bash
POST /api/customers
Authorization: Bearer {super_admin_token}
{
  "name": "Test Customer",
  "phone": "0901234567",
  "branch_id": 2  // MUST provide
}

Expected: Success, customer.branch_id = 2
```

### Test 2: Create Customer as Regular User
```bash
POST /api/customers
Authorization: Bearer {user_token}  // User có primary branch = 1
{
  "name": "Test Customer",
  "phone": "0901234567"
  // NO branch_id provided
}

Expected: Success, customer.branch_id = 1 (auto from user's primary branch)
```

### Test 3: View Customers with Branch Filter
```bash
# Login as manager.multi@example.com (có HCM01 và DN01)
GET /api/customers

Expected: Chỉ thấy customers của HCM01 và DN01
```

### Test 4: Kanban View
```bash
GET /api/customers/kanban

Expected:
{
  "lead": {customers: [...], count: 5},
  "contacted": {customers: [...], count: 3},
  ...
}
```

### Test 5: Move Stage
```bash
POST /api/customers/1/move-stage
{
  "stage": "closed_won",
  "stage_order": 0
}

Expected:
- customer.stage = "closed_won"
- customer.closed_at = now()
```

---

## 📝 Next Steps - Frontend

### TODO:
1. ⏳ CustomersList.vue - List view với filters
2. ⏳ CustomerKanban.vue - Kanban board với drag-drop
3. ⏳ CustomerModal.vue - Form create/edit
   - Super-admin: Hiển thị dropdown branches
   - User thường: Ẩn dropdown, auto-assign
4. ⏳ Router config
5. ⏳ Sidebar navigation
6. ⏳ Build và test

---

## ✅ Backend Checklist

- [x] Migration customers table
- [x] Customer Model với relationships
- [x] CustomerController với 8 endpoints
- [x] Routes với permissions và branch.access
- [x] Permissions (4 permissions)
- [x] Seeder với 20 sample customers
- [x] Logic gán branch tự động
- [x] Kanban stages
- [x] Soft delete
- [x] Auto-generate customer code
- [x] Migrate fresh + seed thành công

---

**Backend sẵn sàng! API có thể test ngay bằng Postman/Thunder Client!** 🎯


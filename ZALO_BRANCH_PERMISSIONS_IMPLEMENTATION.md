# Zalo Module - Branch & Permissions Implementation

## 📋 Tổng Quan

Module Zalo đã được cập nhật để hỗ trợ:
1. **Phân cấp theo Branch**: Tài khoản Zalo có thể gán cho từng chi nhánh
2. **Gán cho Nhân sự**: Tài khoản Zalo có thể gán cho employee/user cụ thể
3. **Phân quyền**: Super-admin/Admin xem tất cả, user khác chỉ xem accounts được gán cho mình hoặc cùng branch

## 🗄️ Database Changes

### Migration: `2025_11_13_080000_add_branch_and_assigned_to_zalo_accounts.php`

**Thêm các cột:**
- `branch_id` (nullable, foreign key → `branches.id`)
- `assigned_to` (nullable, foreign key → `users.id`) - Employee/User được gán quản lý tài khoản Zalo này

**Indexes:**
- `branch_id`
- `assigned_to`

## 🔧 Model Changes

### `app/Models/ZaloAccount.php`

**Thêm vào `$fillable`:**
- `branch_id`
- `assigned_to`

**Thêm Relationships:**
```php
public function branch()
{
    return $this->belongsTo(Branch::class);
}

public function assignedUser()
{
    return $this->belongsTo(User::class, 'assigned_to');
}
```

**Thêm Scopes:**
- `scopeForBranch($query, $branchId)` - Filter theo branch (bao gồm cả null)
- `scopeAssignedTo($query, $userId)` - Filter theo user được gán
- `scopeAccessibleBy($query, $user)` - **QUAN TRỌNG**: Filter theo quyền của user

**Logic `scopeAccessibleBy()`:**
1. **Super-admin**: Xem tất cả accounts
2. **User có branches**: Xem accounts được gán cho mình HOẶC cùng branch
3. **User không có branch**: Chỉ xem accounts được gán cho mình

## 🎯 Controller Changes

### `app/Http/Controllers/Api/ZaloController.php`

**Tất cả methods đã được cập nhật:**

1. **`getAccounts()`**:
   - Sử dụng `->accessibleBy($user)` để filter theo quyền
   - Hỗ trợ filter theo `branch_id` và `assigned_to`
   - Trả về thông tin `branch` và `assigned_user`

2. **`getActiveAccount()`**:
   - Filter theo quyền và branch

3. **`getFriends()`**:
   - Chỉ lấy friends của accounts mà user có quyền

4. **`getGroups()`**:
   - Chỉ lấy groups của accounts mà user có quyền

5. **`saveAccount()`**:
   - **Permission check**: Chỉ admin/super-admin hoặc user có `zalo.manage_accounts` mới tạo được
   - Lưu `branch_id` từ request (từ header branch selector)
   - Lưu `assigned_to` (default: current user)
   - User chỉ có thể update account được gán cho mình (trừ admin/super-admin)
   - Admin/super-admin có thể update `branch_id` và `assigned_to`

6. **`setActiveAccount()`**:
   - Check quyền trước khi set active
   - Chỉ deactivate accounts trong cùng branch

7. **`reloginAccount()`**:
   - Check quyền: user chỉ có thể re-login account được gán cho mình

8. **`refreshAccountInfo()`**:
   - Check quyền: user chỉ có thể refresh account được gán cho mình

## 🔐 Permissions

### Seeder: `database/seeders/ZaloPermissionsSeeder.php`

**Permissions được tạo:**
- `zalo.view` - Xem module Zalo (danh sách accounts, friends, groups, lịch sử)
- `zalo.send` - Gửi tin nhắn Zalo đơn lẻ
- `zalo.send_bulk` - Gửi tin nhắn hàng loạt
- `zalo.manage_accounts` - Quản lý tài khoản Zalo (tạo, chỉnh sửa, xóa, đăng nhập lại)
- `zalo.manage_settings` - Quản lý cài đặt Zalo

## 🛣️ Routes Changes

### `routes/api.php`

**Middleware được thêm:**
- `branch.access` - Kiểm tra quyền truy cập theo branch
- `permission:zalo.*` - Kiểm tra permissions cho từng endpoint

**Routes với permissions:**
- `GET /api/zalo/status` → `zalo.view`
- `POST /api/zalo/initialize` → `zalo.manage_accounts`
- `GET /api/zalo/stats` → `zalo.view`
- `GET /api/zalo/friends` → `zalo.view`
- `GET /api/zalo/groups` → `zalo.view`
- `GET /api/zalo/history` → `zalo.view`
- `GET /api/zalo/settings` → `zalo.view`
- `POST /api/zalo/settings` → `zalo.manage_settings`
- `GET /api/zalo/accounts` → `zalo.view`
- `GET /api/zalo/accounts/active` → `zalo.view`
- `POST /api/zalo/accounts/active` → `zalo.manage_accounts`
- `POST /api/zalo/accounts/save` → `zalo.manage_accounts`
- `POST /api/zalo/accounts/relogin` → `zalo.manage_accounts`
- `POST /api/zalo/accounts/refresh` → `zalo.manage_accounts`

## 📊 Phân Quyền Chi Tiết

### Scenario 1: Super-Admin
```
Super-Admin login
  ↓
Xem TẤT CẢ Zalo accounts (không filter)
  ↓
Có thể tạo, chỉnh sửa, xóa bất kỳ account nào
```

### Scenario 2: Admin (có branch)
```
Admin HCM login (branch_id = 2)
  ↓
Xem accounts:
  - Được gán cho mình (assigned_to = admin.id)
  - Cùng branch (branch_id = 2)
  - Không có branch (branch_id = null)
  ↓
Có thể tạo, chỉnh sửa accounts trong branch của mình
```

### Scenario 3: Regular User (có branch)
```
User A thuộc Branch Hà Nội
  ↓
Xem accounts:
  - Được gán cho mình (assigned_to = userA.id)
  - Cùng branch (branch_id = Hà Nội)
  ↓
CHỈ có thể update/refresh accounts được gán cho mình
```

### Scenario 4: Regular User (không có branch)
```
User B không có branch
  ↓
Xem accounts:
  - CHỈ accounts được gán cho mình (assigned_to = userB.id)
  ↓
CHỈ có thể update/refresh accounts được gán cho mình
```

## 🔄 Frontend Integration

### Cần cập nhật Frontend:

1. **Header Branch Selector**:
   - Khi user chọn branch, gửi `branch_id` trong request header hoặc query param
   - Frontend nên tự động filter accounts theo branch được chọn

2. **Account List**:
   - Hiển thị thông tin `branch` và `assigned_user` cho mỗi account
   - Filter dropdown: theo branch, theo assigned user

3. **Add Account Modal**:
   - Thêm dropdown chọn `branch_id` (từ branch selector hoặc manual)
   - Thêm dropdown chọn `assigned_to` (danh sách users)
   - Default: `branch_id` = branch hiện tại, `assigned_to` = current user

4. **Permission Checks**:
   - Ẩn/hiện buttons dựa trên permissions:
     - "Add Account" → cần `zalo.manage_accounts`
     - "Send Message" → cần `zalo.send`
     - "Send Bulk" → cần `zalo.send_bulk`
     - "Settings" → cần `zalo.manage_settings`

## ✅ Đã Hoàn Thành

- [x] Migration thêm `branch_id` và `assigned_to`
- [x] Model `ZaloAccount` với relationships và scopes
- [x] Controller methods với phân quyền
- [x] Permissions seeder
- [x] Routes với middleware `branch.access` và permissions
- [x] Logic filter theo quyền (super-admin, admin, user)

## 📝 Notes

- **ZaloFriend, ZaloGroup, ZaloMessage** KHÔNG cần thêm `branch_id` vì đã có `zalo_account_id`. Filter được thực hiện qua account.
- Khi user chọn branch trên header, frontend nên tự động filter accounts theo branch đó.
- Admin/super-admin có thể gán account cho bất kỳ user nào, user thường chỉ thấy accounts được gán cho mình.


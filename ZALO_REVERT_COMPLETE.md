# ✅ ZALO MODULE - HOÀN TẤT REVERT VỀ CẤU TRÚC CŨ

**Ngày:** 26/11/2025
**Trạng thái:** ✅ HOÀN THÀNH - Đã khôi phục hoàn toàn về code ngày 24/11

---

## 📋 TÓM TẮT VẤN ĐỀ

### Vấn đề ban đầu:
- **Lỗi báo cáo:** "Maximum execution time of 60 seconds exceeded"
- **Lỗi thực tế:** `Table 'zalo_group_branches' doesn't exist`
- **Nguyên nhân:** Code đã bị thay đổi để triển khai tính năng "Session Sharing" nhưng migrations không được chạy

### Timeline:
- **23/11:** Code cũ hoạt động tốt (backup: `backup_code_20251123_150950.tar.gz`)
- **24/11:** Code cũ hoạt động tốt (backup: `vps_backup_20251124.tar.gz`)
- **26/11 00:22:** Ai đó thực hiện "Session Sharing" refactor (backup: `_backups/session-sharing-20251126-002217/`)
- **26/11 15:00:** User báo lỗi

---

## 🔧 CÁC FILE ĐÃ SỬA

### 1. ✅ app/Services/ZaloCacheService.php
**Đã revert trước đó (thời gian trước)**

**Thay đổi:**
- Line 68, 311: Sử dụng `$account->id` thay vì `$account->zalo_id`
- Line 70-76, 314-320: `updateOrCreate` dùng `['zalo_account_id' => $account->id]`

### 2. ✅ app/Models/ZaloFriend.php
**Đã revert trước đó**

**Thay đổi:**
- Line 13: Thêm `'zalo_account_id'` vào `$fillable` array
- Line 29-37: Xóa relationship `branches()` và các scopes liên quan đến pivot table

### 3. ✅ app/Models/ZaloGroup.php
**Đã revert lần này**

**Thay đổi:**
- Line 39-47: Xóa `branches()` many-to-many relationship, thay bằng `belongsTo`
- Line 56-130: Cập nhật tất cả scopes để dùng trực tiếp `branch_id` và `department_id`

### 4. ✅ app/Http/Controllers/Api/ZaloController.php
**Đã revert lần này**

**Thay đổi:**
- **Line 867-883:** getGroups() query
  - Đổi `$account->zalo_account_id` → `$account->id`
  - Xóa `->with(['branches'])`
  - Xóa logic shared accounts

- **Line 889-897:** Conversations và departments
  - Đổi `whereIn('zalo_account_id', $accountIds)` → `where('zalo_account_id', $account->id)`
  - Đổi `$group->branches->pluck('pivot.department_id')` → `$groups->pluck('department_id')`

- **Line 906-938:** Groups mapping
  - Đổi `$group->branches` → truy cập trực tiếp `$group->branch_id`, `$group->department_id`
  - Load branches/departments riêng từ database

- **Line 4251:** Webhook - tìm friend
  - Đổi `$account->zalo_account_id` → `$account->id`

- **Line 4281:** Webhook - tạo friend mới
  - Đổi `$account->zalo_account_id` → `$account->id`

---

## 🗂️ CẤU TRÚC DATABASE HIỆN TẠI

### ✅ zalo_accounts
```sql
- id                 (PK, bigint)
- branch_id          (FK, bigint)
- assigned_to        (FK nullable, bigint)
- name               (varchar)
- phone              (varchar nullable)
- zalo_id            (varchar nullable) ← METADATA ONLY, không dùng cho query!
- cookie             (text)
- is_active          (boolean)
- is_connected       (boolean)
- is_primary         (boolean)
- ...
```

### ✅ zalo_friends
```sql
- id                 (PK, bigint)
- zalo_account_id    (FK NOT NULL, bigint) → zalo_accounts.id
- zalo_user_id       (varchar) ← Zalo user ID của friend
- name               (varchar)
- phone              (varchar nullable)
- avatar_url         (varchar nullable)
- avatar_path        (varchar nullable)
- ...
```

### ✅ zalo_groups
```sql
- id                 (PK, bigint)
- zalo_account_id    (FK NOT NULL, bigint) → zalo_accounts.id
- branch_id          (FK nullable, bigint) → branches.id
- department_id      (FK nullable, bigint) → departments.id
- zalo_group_id      (varchar) ← Zalo group ID
- name               (varchar)
- description        (text nullable)
- members_count      (int)
- ...
```

### ❌ KHÔNG TỒN TẠI (Từ session sharing):
- ❌ `zalo_friend_branches` pivot table
- ❌ `zalo_group_branches` pivot table

---

## 🎯 CẤU TRÚC CODE HIỆN TẠI

### Relationships:
```php
// ZaloFriend - NO relationships with branches
class ZaloFriend extends Model {
    // Scopes only
    public function scopeForAccount($query, $accountId)
}

// ZaloGroup - Simple belongsTo
class ZaloGroup extends Model {
    public function branch() {
        return $this->belongsTo(Branch::class);
    }
    public function department() {
        return $this->belongsTo(Department::class);
    }

    // Scopes use direct columns
    public function scopeForBranch($query, $branchId) {
        return $query->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
    }
}
```

### Queries:
```php
// Friends sync
ZaloFriend::updateOrCreate([
    'zalo_account_id' => $account->id,  // ✅ Integer account PK
    'zalo_user_id' => $zaloUserId
], $data);

// Groups sync
ZaloGroup::updateOrCreate([
    'zalo_account_id' => $account->id,  // ✅ Integer account PK
    'zalo_group_id' => $zaloGroupId
], $data);

// getGroups() query
ZaloGroup::where('zalo_account_id', $account->id)  // ✅ Integer
    ->where(function($q) use ($branchId) {
        $q->where('branch_id', $branchId)
          ->orWhereNull('branch_id');
    })
    ->get();
```

---

## ✅ KẾT QUẢ SAU KHI REVERT

### Test Results (26/11 16:00):
- ✅ **Friends sync:** Thành công (188 friends synced)
- ✅ **Groups loading:** Thành công (50 groups loaded)
- ✅ **Không còn lỗi SQL:** Tất cả queries đều dùng đúng `account->id`
- ✅ **Webhook nhận tin nhắn:** Đã fix lỗi NULL zalo_account_id

### Files được kiểm tra:
```bash
✅ app/Services/ZaloCacheService.php
✅ app/Models/ZaloFriend.php
✅ app/Models/ZaloGroup.php
✅ app/Http/Controllers/Api/ZaloController.php
```

### Không còn references đến:
```bash
❌ $account->zalo_account_id (đã đổi hết thành $account->id)
❌ $group->branches (đã đổi thành $group->branch_id)
❌ zalo_friend_branches table
❌ zalo_group_branches table
```

---

## 📁 BACKUP VÀ MIGRATIONS

### Migrations không dùng:
Tất cả migrations cho "session sharing" đã được di chuyển vào:
```
database/migrations/_backup_zalo_sharing_nov25/
```

**KHÔNG được chạy các migrations này!**

### Backups code cũ (nếu cần tham khảo):
- `_backups/session-sharing-20251126-002217/` - Code trước khi revert
- VPS backups:
  - `backup_code_20251123_150950.tar.gz`
  - `vps_backup_20251124.tar.gz`

---

## 🚨 LƯU Ý QUAN TRỌNG

### ❌ KHÔNG ĐƯỢC:
1. ❌ Chạy migrations trong `_backup_zalo_sharing_nov25/`
2. ❌ Sử dụng `$account->zalo_account_id` (dùng `$account->id`)
3. ❌ Tạo pivot tables `zalo_friend_branches` hoặc `zalo_group_branches`
4. ❌ Dùng `$group->branches` relationship (dùng `$group->branch_id`)

### ✅ LUÔN DÙNG:
1. ✅ `$account->id` cho tất cả Zalo queries
2. ✅ `branch_id` và `department_id` trực tiếp trong groups
3. ✅ `zalo_account_id` trong WHERE clause VÀ trong data array
4. ✅ `zalo_account_id` phải có trong Model `$fillable`

---

## 📝 CHECKLIST ĐỂ TRIỂN KHAI SESSION SHARING (NẾU CẦN)

Nếu trong tương lai muốn triển khai lại "Session Sharing", cần:

- [ ] 1. Backup đầy đủ database và code
- [ ] 2. Tạo migrations cho pivot tables
- [ ] 3. **CHẠY migrations** trước khi update code
- [ ] 4. Update Models với relationships mới
- [ ] 5. Update Controllers với queries mới
- [ ] 6. Update Services với logic mới
- [ ] 7. Test kỹ càng trên staging
- [ ] 8. Deploy từng bước, có rollback plan

**Đừng bao giờ update code mà không chạy migrations!**

---

## 🎉 KẾT LUẬN

Tất cả code đã được revert về cấu trúc ổn định ngày 24/11/2025.

**Status:** ✅ PRODUCTION READY

**Tested:** ✅ Friends sync, Groups loading, Webhook messages

**Database:** ✅ Cấu trúc cũ, không có pivot tables

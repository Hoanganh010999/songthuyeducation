# 🎯 ZALO SHARED ACCESS - THIẾT KẾ CHI TIẾT

**Ngày:** 27/11/2025
**Trạng thái:** 📝 THIẾT KẾ - Chờ phê duyệt

---

## 📋 YÊU CẦU

### Mô tả:
Khi 1 tài khoản Zalo được đăng nhập ở Branch A:
- **Branch A (Owner):** Quyền đầy đủ - xem dữ liệu VÀ gửi tin nhắn
- **Branch B, C, D (Shared):** Chỉ được xem dữ liệu (friends, groups, conversations)
- **Branch được chia sẻ KHÔNG được phép:**
  - Gửi tin nhắn cho customer (Customer module)
  - Gửi tin nhắn cho teacher (Teacher module)
  - Gửi tin nhắn vào class group (ClassManagement module)

---

## 🔍 PHÂN TÍCH HỆ THỐNG HIỆN TẠI

### 1. Database Structure

**zalo_accounts table:**
```sql
id                  bigint PK
branch_id           bigint FK         ← Account thuộc branch nào
is_active           boolean
is_primary          boolean           ← Primary account cho toàn hệ thống
name                varchar
...
```

**classes table:**
```sql
id                  bigint PK
branch_id           bigint FK
zalo_account_id     bigint FK NULL    ← Class có thể chọn account riêng
zalo_group_id       varchar           ← Group ID của Zalo
...
```

**zalo_friends, zalo_groups, zalo_conversations:**
```sql
id                  bigint PK
zalo_account_id     bigint FK         ← FK to zalo_accounts.id
branch_id           bigint FK         ← Đã có từ implementation trước
...
```

### 2. Integration Points

#### A. Customer Module
**File:** `app/Services/CustomerZaloNotificationService.php`

**Hiện tại:**
```php
// Line 555-560
public function getPrimaryZaloAccount(): ?ZaloAccount
{
    return ZaloAccount::where('is_active', true)
        ->where('is_primary', true)
        ->first();
}
```

**Vấn đề:**
- Chỉ lấy primary account
- KHÔNG filter theo branch
- KHÔNG check quyền gửi tin

**Sử dụng:**
- `sendPlacementTestNotification()` - Gửi thông báo placement test
- `sendTrialClassNotification()` - Gửi thông báo trial class
- `sendReminderNotification()` - Gửi nhắc nhở
- `sendResultNotification()` - Gửi kết quả

#### B. Teacher Module
**File:** `app/Services/TeacherZaloNotificationService.php`

**Hiện tại:**
```php
// Line 22
public function getPrimaryZaloAccount(): ?ZaloAccount
{
    // Same - chỉ lấy primary account
}
```

**Sử dụng:**
- `sendTeacherAssignmentNotification()` - Gửi thông báo phân công

#### C. ClassManagement Module
**File:** `app/Services/ZaloGroupNotificationService.php`

**Hiện tại:**
```php
// Line 165-179
protected function getClassZaloAccount(ClassModel $class): ?ZaloAccount
{
    // Try class-specific account first
    if ($class->zalo_account_id) {
        $account = ZaloAccount::find($class->zalo_account_id);
        if ($account && $account->is_active) {
            return $account;
        }
    }

    // Fallback to primary account
    return ZaloAccount::where('is_active', true)
        ->where('is_primary', true)
        ->first();
}
```

**Ưu điểm:**
- Có logic ưu tiên: Class-specific → Primary
- Linh hoạt hơn

**Vấn đề:**
- KHÔNG check quyền gửi tin theo branch

**Sử dụng:**
- `sendSessionCancellationNotification()` - Thông báo hủy buổi học
- `sendTeacherChangeNotification()` - Thông báo thay giáo viên

---

## 💡 THIẾT KẾ ĐỀ XUẤT

### Option 1: Shared Access Table (RECOMMENDED ⭐)

#### A. Database Schema

**Tạo bảng mới: `zalo_account_shares`**

```sql
CREATE TABLE zalo_account_shares (
    id                      BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    zalo_account_id         BIGINT UNSIGNED NOT NULL,    -- Account được share
    shared_with_branch_id   BIGINT UNSIGNED NOT NULL,    -- Branch được share đến
    can_send_messages       BOOLEAN DEFAULT FALSE,       -- Quyền gửi tin
    can_manage_groups       BOOLEAN DEFAULT FALSE,       -- Quyền quản lý groups
    notes                   TEXT NULL,                   -- Ghi chú
    shared_by               BIGINT UNSIGNED NULL,        -- User tạo share
    shared_at               TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at              TIMESTAMP NULL,              -- Hết hạn (optional)
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,

    FOREIGN KEY (zalo_account_id) REFERENCES zalo_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_with_branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_by) REFERENCES users(id) ON DELETE SET NULL,

    UNIQUE KEY unique_share (zalo_account_id, shared_with_branch_id)
);
```

**Ưu điểm:**
✅ Flexible - Có thể share với nhiều branches
✅ Granular permissions - Quyền chi tiết cho từng branch
✅ Auditable - Biết ai share, khi nào
✅ Revocable - Có thể thu hồi quyền dễ dàng
✅ Expirable - Có thể set thời hạn share

**Nhược điểm:**
⚠️ Phức tạp hơn một chút
⚠️ Cần thêm UI để quản lý shares

---

### Option 2: Simple Flag on Account (KHÔNG KHUYẾN NGHỊ)

```sql
ALTER TABLE zalo_accounts
ADD COLUMN is_shared BOOLEAN DEFAULT FALSE,
ADD COLUMN shared_with_branches JSON NULL;  -- [2, 3, 4]
```

**Ưu điểm:**
✅ Đơn giản
✅ Không cần bảng mới

**Nhược điểm:**
❌ Không linh hoạt
❌ Không có permissions chi tiết
❌ Khó audit
❌ JSON không tối ưu cho queries

---

## 🎯 GIẢI PHÁP ĐỀ XUẤT (Option 1)

### 1. Database Changes

#### Migration 1: Create Shares Table

```php
Schema::create('zalo_account_shares', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('zalo_account_id');
    $table->unsignedBigInteger('shared_with_branch_id');
    $table->boolean('can_send_messages')->default(false);
    $table->boolean('can_manage_groups')->default(false);
    $table->text('notes')->nullable();
    $table->unsignedBigInteger('shared_by')->nullable();
    $table->timestamp('shared_at')->useCurrent();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();

    $table->foreign('zalo_account_id')
        ->references('id')->on('zalo_accounts')
        ->onDelete('cascade');
    $table->foreign('shared_with_branch_id')
        ->references('id')->on('branches')
        ->onDelete('cascade');
    $table->foreign('shared_by')
        ->references('id')->on('users')
        ->onDelete('set null');

    $table->unique(['zalo_account_id', 'shared_with_branch_id'], 'unique_share');
});
```

---

### 2. Model Changes

#### A. ZaloAccount Model

**Thêm relationships:**

```php
class ZaloAccount extends Model
{
    // ... existing code ...

    /**
     * Branches that this account is shared with
     */
    public function sharedWithBranches(): BelongsToMany
    {
        return $this->belongsToMany(
            Branch::class,
            'zalo_account_shares',
            'zalo_account_id',
            'shared_with_branch_id'
        )->withPivot([
            'can_send_messages',
            'can_manage_groups',
            'notes',
            'shared_by',
            'shared_at',
            'expires_at'
        ])->withTimestamps();
    }

    /**
     * Check if account is accessible by branch
     */
    public function isAccessibleByBranch(int $branchId): bool
    {
        // Owner branch always has access
        if ($this->branch_id === $branchId) {
            return true;
        }

        // Check if shared with branch
        return $this->sharedWithBranches()
            ->where('shared_with_branch_id', $branchId)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Check if branch can send messages using this account
     */
    public function canSendMessages(int $branchId): bool
    {
        // Owner branch always can send
        if ($this->branch_id === $branchId) {
            return true;
        }

        // Check share permission
        $share = $this->sharedWithBranches()
            ->where('shared_with_branch_id', $branchId)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();

        return $share && $share->pivot->can_send_messages;
    }

    /**
     * Scope: Accounts accessible by branch (owner + shared)
     */
    public function scopeAccessibleByBranch($query, int $branchId)
    {
        return $query->where(function ($q) use ($branchId) {
            // Owner branch
            $q->where('branch_id', $branchId)
              // OR shared with branch (not expired)
              ->orWhereHas('sharedWithBranches', function ($q2) use ($branchId) {
                  $q2->where('shared_with_branch_id', $branchId)
                     ->where(function ($q3) {
                         $q3->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                     });
              });
        });
    }

    /**
     * Scope: Accounts that can send messages for branch
     */
    public function scopeCanSendForBranch($query, int $branchId)
    {
        return $query->where(function ($q) use ($branchId) {
            // Owner branch
            $q->where('branch_id', $branchId)
              // OR shared with can_send_messages permission
              ->orWhereHas('sharedWithBranches', function ($q2) use ($branchId) {
                  $q2->where('shared_with_branch_id', $branchId)
                     ->where('can_send_messages', true)
                     ->where(function ($q3) {
                         $q3->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                     });
              });
        });
    }
}
```

#### B. ZaloAccountShare Model (New)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZaloAccountShare extends Model
{
    protected $fillable = [
        'zalo_account_id',
        'shared_with_branch_id',
        'can_send_messages',
        'can_manage_groups',
        'notes',
        'shared_by',
        'shared_at',
        'expires_at',
    ];

    protected $casts = [
        'can_send_messages' => 'boolean',
        'can_manage_groups' => 'boolean',
        'shared_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(ZaloAccount::class, 'zalo_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'shared_with_branch_id');
    }

    public function sharedBy()
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    /**
     * Check if share is still valid (not expired)
     */
    public function isValid(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
```

---

### 3. Service Changes

#### A. Update CustomerZaloNotificationService

```php
class CustomerZaloNotificationService
{
    /**
     * Get Zalo account for sending messages to customer
     * Must have send permission for current branch
     */
    public function getZaloAccountForBranch(?int $branchId = null): ?ZaloAccount
    {
        $branchId = $branchId ?? auth()->user()->branch_id ?? null;

        if (!$branchId) {
            Log::warning('[CustomerZaloNotification] No branch context');
            return null;
        }

        // Get accounts that current branch can use to send messages
        return ZaloAccount::where('is_active', true)
            ->canSendForBranch($branchId)
            ->orderByRaw('CASE WHEN branch_id = ? THEN 0 ELSE 1 END', [$branchId]) // Owner first
            ->orderBy('is_primary', 'desc') // Then primary
            ->first();
    }

    /**
     * OLD METHOD - Keep for backward compatibility but deprecated
     * @deprecated Use getZaloAccountForBranch() instead
     */
    public function getPrimaryZaloAccount(): ?ZaloAccount
    {
        return $this->getZaloAccountForBranch();
    }

    // ... rest of the methods use getZaloAccountForBranch() ...
}
```

#### B. Update ZaloGroupNotificationService

```php
class ZaloGroupNotificationService
{
    protected function getClassZaloAccount(ClassModel $class): ?ZaloAccount
    {
        $branchId = $class->branch_id;

        // Try class-specific account first (if can send)
        if ($class->zalo_account_id) {
            $account = ZaloAccount::find($class->zalo_account_id);
            if ($account && $account->is_active && $account->canSendMessages($branchId)) {
                return $account;
            }
        }

        // Fallback to any account that branch can use
        return ZaloAccount::where('is_active', true)
            ->canSendForBranch($branchId)
            ->orderByRaw('CASE WHEN branch_id = ? THEN 0 ELSE 1 END', [$branchId])
            ->orderBy('is_primary', 'desc')
            ->first();
    }
}
```

#### C. Update TeacherZaloNotificationService

```php
class TeacherZaloNotificationService
{
    public function getZaloAccountForBranch(?int $branchId = null): ?ZaloAccount
    {
        $branchId = $branchId ?? auth()->user()->branch_id ?? null;

        if (!$branchId) {
            return null;
        }

        return ZaloAccount::where('is_active', true)
            ->canSendForBranch($branchId)
            ->orderByRaw('CASE WHEN branch_id = ? THEN 0 ELSE 1 END', [$branchId])
            ->orderBy('is_primary', 'desc')
            ->first();
    }
}
```

---

### 4. Query Modifications

#### A. Friends/Groups/Conversations Lists

**ZaloController - getFriends():**

```php
public function getFriends(Request $request)
{
    $user = auth()->user();
    $branchId = $user->branch_id;

    // Get account from request
    $accountId = $request->input('account_id');

    // Verify access
    $account = ZaloAccount::find($accountId);
    if (!$account || !$account->isAccessibleByBranch($branchId)) {
        return response()->json([
            'success' => false,
            'message' => 'Không có quyền truy cập tài khoản này',
        ], 403);
    }

    // Get friends
    $friends = ZaloFriend::where('zalo_account_id', $accountId)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $friends,
        'permissions' => [
            'can_send_messages' => $account->canSendMessages($branchId),
            'is_owner' => $account->branch_id === $branchId,
        ],
    ]);
}
```

**Similar for getGroups(), getConversations()...**

---

### 5. UI Changes

#### A. Account Selector

**Hiển thị accounts accessible:**

```vue
<template>
  <select v-model="selectedAccount">
    <option
      v-for="account in accounts"
      :key="account.id"
      :value="account.id"
    >
      {{ account.name }}
      <span v-if="account.is_owner">(Của bạn)</span>
      <span v-else-if="account.is_shared">
        (Được chia sẻ từ {{ account.owner_branch_name }})
      </span>
      <span v-if="!account.can_send_messages" class="text-warning">
        - Chỉ xem
      </span>
    </option>
  </select>
</template>
```

#### B. Send Message Button

```vue
<button
  :disabled="!currentAccount?.can_send_messages"
  @click="sendMessage"
>
  <span v-if="currentAccount?.can_send_messages">
    Gửi tin nhắn
  </span>
  <span v-else class="text-muted">
    <i class="bi bi-lock"></i>
    Không có quyền gửi (Tài khoản được chia sẻ)
  </span>
</button>
```

#### C. Account Management UI

**Thêm tab "Chia sẻ" trong account settings:**

```vue
<div class="card">
  <div class="card-header">
    <h5>Chia sẻ tài khoản Zalo</h5>
  </div>
  <div class="card-body">
    <p class="text-muted">
      Cho phép các chi nhánh khác xem dữ liệu Zalo của bạn
    </p>

    <div class="mb-3">
      <label>Chi nhánh được chia sẻ</label>
      <select v-model="newShare.branch_id">
        <option v-for="branch in availableBranches" :value="branch.id">
          {{ branch.name }}
        </option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-check">
        <input type="checkbox" v-model="newShare.can_send_messages">
        Cho phép gửi tin nhắn
      </label>
    </div>

    <button @click="shareAccount">Chia sẻ</button>

    <hr>

    <h6>Đang chia sẻ với:</h6>
    <ul>
      <li v-for="share in shares" :key="share.id">
        {{ share.branch_name }}
        <span v-if="share.can_send_messages" class="badge bg-warning">
          Có quyền gửi tin
        </span>
        <span v-else class="badge bg-secondary">Chỉ xem</span>
        <button @click="revokeShare(share.id)" class="btn btn-sm btn-danger">
          Thu hồi
        </button>
      </li>
    </ul>
  </div>
</div>
```

---

## 📊 FLOW DIAGRAM

### Current Flow (Before)
```
Branch A login Zalo → Account stored with branch_id = A
Branch B wants to use → ❌ Cannot access
Branch C wants to use → ❌ Cannot access

Customer notification → Uses is_primary account (không check branch)
```

### Proposed Flow (After)
```
Branch A login Zalo → Account stored with branch_id = A (owner)
Branch A shares to B, C (view only) → zalo_account_shares table

Branch A:
  - View data ✅
  - Send messages ✅ (owner)

Branch B (shared, view only):
  - View data ✅ (through isAccessibleByBranch)
  - Send messages ❌ (can_send_messages = false)

Branch C (shared, view only):
  - View data ✅
  - Send messages ❌

Customer notification → Uses canSendForBranch(current_branch)
  → Only owner or branches with permission can send
```

---

## 🧪 TESTING SCENARIOS

### Scenario 1: Owner Branch
```
Given: Branch A owns Zalo account (branch_id = A)
When: Branch A user tries to send customer notification
Then: ✅ Should succeed
```

### Scenario 2: Shared Branch (View Only)
```
Given: Branch A owns account, shared to Branch B (can_send_messages = false)
When: Branch B user tries to:
  1. View friends list → ✅ Should succeed
  2. View conversations → ✅ Should succeed
  3. Send customer notification → ❌ Should fail with permission error
Then: Data visible, actions blocked
```

### Scenario 3: Shared Branch (Can Send)
```
Given: Branch A owns account, shared to Branch B (can_send_messages = true)
When: Branch B user tries to send customer notification
Then: ✅ Should succeed
```

### Scenario 4: Expired Share
```
Given: Branch A shared to B with expires_at = yesterday
When: Branch B user tries to access
Then: ❌ Should fail (share expired)
```

### Scenario 5: Revoked Share
```
Given: Branch A shared to B, then deleted the share
When: Branch B user tries to access
Then: ❌ Should fail (no longer shared)
```

---

## 💼 MIGRATION PLAN

### Phase 1: Database (Week 1)
1. ✅ Create `zalo_account_shares` table
2. ✅ Add foreign keys and indexes
3. ✅ Create ZaloAccountShare model

### Phase 2: Backend Logic (Week 1-2)
1. ✅ Update ZaloAccount model with scopes
2. ✅ Update service classes
3. ✅ Add permission checks in controllers
4. ✅ Update API responses with permissions

### Phase 3: API & Testing (Week 2)
1. ✅ Add share management endpoints
2. ✅ Write unit tests
3. ✅ Write integration tests
4. ✅ Test permission enforcement

### Phase 4: Frontend (Week 3)
1. ✅ Update account selector
2. ✅ Add share management UI
3. ✅ Show permission badges
4. ✅ Disable actions for read-only

### Phase 5: Deployment (Week 3-4)
1. ✅ Staging deployment
2. ✅ UAT testing
3. ✅ Production deployment
4. ✅ Monitor & fix issues

---

## ⚠️ CONSIDERATIONS

### 1. Security
- ✅ Always check permissions before sending messages
- ✅ Validate branch_id from authenticated user
- ✅ Log all share actions for audit
- ✅ Expire shares after period if needed

### 2. Performance
- ✅ Index on (zalo_account_id, shared_with_branch_id)
- ✅ Cache accessible accounts per branch
- ✅ Eager load shares when needed

### 3. UX
- ✅ Clear indicators for shared accounts
- ✅ Disable buttons with tooltip explanation
- ✅ Show owner branch name
- ✅ Easy share management

### 4. Backward Compatibility
- ✅ Keep `is_primary` flag working
- ✅ Old code still works (uses owner accounts only)
- ✅ Gradual migration

---

## 📈 BENEFITS

### For Branch A (Owner):
✅ Can share data without losing control
✅ Can revoke access anytime
✅ Audit who accessed what
✅ Set expiration dates

### For Branch B, C (Shared):
✅ Access to more Zalo accounts
✅ View customer interactions
✅ Better coordination
✅ No need to duplicate logins

### For System:
✅ Better multi-tenancy
✅ Clear permission model
✅ Auditable
✅ Scalable

---

## 🎯 RECOMMENDATION

**Đề xuất sử dụng Option 1 (Shared Access Table)** vì:

1. ✅ **Linh hoạt:** Có thể share với nhiều branches, set quyền khác nhau
2. ✅ **Bảo mật:** Permissions rõ ràng, có thể thu hồi
3. ✅ **Audit:** Track được ai share, khi nào, cho ai
4. ✅ **Scalable:** Dễ mở rộng thêm permissions sau này
5. ✅ **UX tốt:** Rõ ràng cho user về quyền hạn

**Thời gian ước tính:** 3-4 tuần cho full implementation

---

## 📚 NEXT STEPS

Sau khi thiết kế được phê duyệt:

1. Review với team về database schema
2. Review về UI/UX flow
3. Estimate effort chi tiết cho từng phase
4. Bắt đầu implementation theo plan

---

**Prepared by:** Claude Code
**Date:** 27/11/2025
**Status:** 📝 AWAITING APPROVAL

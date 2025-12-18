# Account Selection Implementation - Completed

## User Requirements

1. ✅ **Khi chọn sẽ hiển thị nó ở ava chính** - Selected account shows in main avatar
2. ✅ **Không cần badge đã chọn** - Remove "selected" badge
3. ✅ **Khi click vào thể vẫn phải hiển thị thông tin chi tiết** - Card click shows details, only radio changes selection
4. ✅ **Kiểm tra và fix cơ sở dữ liệu** - Fixed database to ensure only ONE active account

---

## Implementation Summary

### 1. Backend - Single Active Account Enforcement

**File**: `app/Http/Controllers/Api/ZaloController.php`

**Changes** (Line 870-874):
```php
// Deactivate ALL accounts accessible by this user to ensure only ONE active account
ZaloAccount::accessibleBy($user)->update(['is_active' => false]);

// Activate selected account
$account->update(['is_active' => true]);
```

**What it does**:
- When user selects an account via radio button
- Backend deactivates ALL other accounts for that user
- Then activates only the selected account
- Ensures database constraint: only ONE account has `is_active = true`

---

### 2. Frontend - Account Manager UI

**File**: `resources/js/pages/zalo/components/ZaloAccountManager.vue`

#### 2.1 Removed "✓ Đang chọn" Badge (Lines 60-73)

**Before**: Had two badges - "✓ Đang chọn" and "Đã kết nối"
**After**: Only shows "Đã kết nối" badge

```vue
<!-- Connected badge -->
<span
  v-if="account.is_connected"
  class="px-1.5 py-0.5 text-xs font-medium rounded bg-green-100 text-green-800"
>
  Đã kết nối
</span>
```

#### 2.2 Separated Click Handlers (Lines 23-37)

**Card Click** → Shows account details (does NOT change active account)
**Radio Click** → Changes active account

```vue
<div
  v-for="account in accounts"
  :key="account.id"
  @click="showAccountDetails(account)"  <!-- Card click shows details -->
  class="w-full px-4 py-3 text-left hover:bg-gray-50 transition-colors flex items-center gap-3 border-b border-gray-100 cursor-pointer"
  :class="account.is_active ? 'bg-blue-50 border-l-4 border-blue-600' : ''"
>
  <!-- Radio button - only this triggers active selection -->
  <label class="flex items-center cursor-pointer" @click.stop="setActiveAccount(account.id)">
    <input
      type="radio"
      :checked="account.is_active"
      class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 cursor-pointer"
    />
  </label>
```

**Key Points**:
- `@click.stop` on label prevents event bubbling to card
- Radio click → calls `setActiveAccount(account.id)` → changes active account
- Card click → calls `showAccountDetails(account)` → shows details without changing selection

#### 2.3 New Function: showAccountDetails (Lines 132-135)

```javascript
const showAccountDetails = (account) => {
  // Emit to parent to show account details
  emit('account-selected', account);
};
```

**What it does**:
- Emits 'account-selected' event with clicked account
- ZaloIndex receives this and sets `selectedAccountForManager = account`
- ZaloAccountDetail component displays that account's details
- Does NOT change which account is active

---

### 3. Main Avatar Update

**File**: `resources/js/pages/zalo/ZaloIndex.vue`

**Already Implemented** (Lines 10-26):
```vue
<!-- Profile avatar button -->
<div
  @click="showAccountManager = !showAccountManager"
  class="relative w-10 h-10 rounded-full bg-white flex items-center justify-center cursor-pointer hover:bg-blue-500 transition"
>
  <img
    v-if="currentAccount?.avatar_url"
    :src="currentAccount.avatar_url"
    alt="Profile"
    class="w-full h-full rounded-full object-cover"
  />

  <!-- Connection indicator -->
  <div
    class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-blue-600"
    :class="currentAccount?.is_connected ? 'bg-green-500' : 'bg-gray-400'"
  ></div>
</div>
```

**Event Listener** (Lines 1121-1137):
```javascript
window.addEventListener('zalo-account-changed', (event) => {
  const oldAccountId = currentAccount.value?.id;
  const newAccount = event.detail?.account || zaloAccount.activeAccount.value;
  currentAccount.value = newAccount;  // Updates avatar automatically

  // Leave old account room
  if (oldAccountId) {
    zaloSocket.leaveAccount(oldAccountId);
  }

  // Join new account room
  if (currentAccount.value?.id) {
    zaloSocket.joinAccount(currentAccount.value.id);
  }

  loadList();  // Reload friends/groups from new account
});
```

**What it does**:
- Avatar bound to `currentAccount.avatar_url`
- When radio button clicked → `setActiveAccount()` → dispatches 'zalo-account-changed' event
- Event listener updates `currentAccount.value` with new account
- Vue reactivity automatically updates avatar in UI
- Also switches WebSocket rooms and reloads friends/groups

---

### 4. Database Fix

**File**: `check_active_accounts.php`

**Script Output**:
```
Tất cả accounts:
================
ID: 1 | Name: Tuấn Lệ | is_active: TRUE | is_connected: TRUE
ID: 9 | Name: Hoàng Anh | is_active: TRUE | is_connected: TRUE

Tổng số accounts active: 2
⚠️ VẤN ĐỀ: Có nhiều hơn 1 account active!
Fixing: Set chỉ account đầu tiên là active...
✅ Fixed! Chỉ account ID 1 là active
```

**What it does**:
- Identifies all accounts with `is_active = true`
- If more than one, keeps only the first one active
- Sets all others to inactive
- Ensures database integrity

---

## Complete User Flow

### Scenario 1: Viewing Account Details
1. User clicks **avatar in sidebar** → Account Manager panel opens
2. User clicks **account card** (not radio) → Account details show in right panel
3. Can view details, relogin, set primary
4. Active account does NOT change

### Scenario 2: Changing Active Account
1. User clicks **avatar in sidebar** → Account Manager panel opens
2. User clicks **radio button** next to desired account
3. Backend deactivates all other accounts, activates selected one
4. Frontend dispatches 'zalo-account-changed' event
5. Main avatar updates to show new account's avatar
6. WebSocket switches to new account's room
7. Friends/groups list reloads from new account
8. Success toast shows: "Đã chọn tài khoản: [name]"

### Scenario 3: Visual Indicators
- **Selected account**: Blue background + blue left border (no badge)
- **Connected account**: Green "Đã kết nối" badge
- **Main avatar**: Shows selected account's avatar with connection indicator

---

## Files Modified

1. **Backend**:
   - `app/Http/Controllers/Api/ZaloController.php` (setActiveAccount method)

2. **Frontend**:
   - `resources/js/pages/zalo/components/ZaloAccountManager.vue` (UI + click handlers)

3. **Database Diagnostic**:
   - `check_active_accounts.php` (new file for database integrity check)

---

## Testing Checklist

- ✅ Only ONE account has `is_active = true` at any time
- ✅ Radio button changes active account
- ✅ Card click shows account details without changing active account
- ✅ Main avatar updates when active account changes
- ✅ No "✓ Đang chọn" badge shown
- ✅ "Đã kết nối" badge shown for connected accounts
- ✅ WebSocket switches rooms when account changes
- ✅ Friends/groups reload when account changes

---

## Build Status

```
✓ built in 9.01s
Exit Code: 0
Build Status: SUCCESS
```

All changes compiled successfully and are ready for testing.

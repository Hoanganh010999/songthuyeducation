# Fix: Dữ liệu không load theo account được chọn

## 🔴 Vấn đề

Sau khi chọn tài khoản Zalo qua radio button, các dữ liệu sau KHÔNG được cập nhật:
- ❌ Lịch sử chat (conversations)
- ❌ Danh sách bạn bè (friends)
- ❌ Danh sách nhóm (groups)
- ❌ Gửi tin nhắn, tìm kiếm, tạo nhóm, sync history

## 🔍 Phân tích root cause

### Vấn đề: 2 nguồn state khác nhau cho account ID

Có **2 nơi lưu trữ active account**:

1. **`useZaloAccount` composable** (global state):
   ```javascript
   const activeAccountId = ref(null);
   const activeAccount = ref(null);
   ```

2. **`ZaloIndex.vue` local state**:
   ```javascript
   const currentAccount = ref(null);
   ```

### Flow hiện tại (BỊ LỖI):

```
User click radio button
  ↓
ZaloAccountManager.setActiveAccount(accountId)
  ↓
API: POST /api/zalo/accounts/active
  ↓
Backend: Deactivate all, activate selected ✅
  ↓
Dispatch event: 'zalo-account-changed'
  ↓
ZaloIndex event listener: currentAccount.value = newAccount ✅
  ↓
zaloAccount.activeAccountId = (KHÔNG CẬP NHẬT!) ❌
  ↓
loadList() calls API with zaloAccount.activeAccountId ❌
  ↓
Load data from WRONG account! ❌
```

### Ví dụ cụ thể:

**File: `ZaloIndex.vue` (lines 656-658)**
```javascript
if (activeNav.value === 'history') {
  const accountId = zaloAccount?.activeAccountId.value;  // ❌ WRONG!
  if (accountId) {
    params.account_id = accountId;
  }
}
```

**Event listener (lines 1121-1136)**
```javascript
window.addEventListener('zalo-account-changed', (event) => {
  currentAccount.value = newAccount;  // ✅ Updated
  loadList();  // ❌ But loadList() uses zaloAccount.activeAccountId!
});
```

## ✅ Giải pháp

### Solution: Sử dụng `currentAccount.value.id` thay vì `zaloAccount.activeAccountId.value`

## 📝 Các file đã sửa

### 1. ZaloIndex.vue - Load List Functions

**File**: `resources/js/pages/zalo/ZaloIndex.vue`

#### 1.1 loadList() - Lines 642-710

**Trước:**
```javascript
if (activeNav.value === 'history') {
  const accountId = zaloAccount?.activeAccountId.value;  // ❌
  if (accountId) {
    params.account_id = accountId;
  }
}
```

**Sau:**
```javascript
// 🔥 FIX: Use currentAccount.value.id instead of zaloAccount.activeAccountId.value
const accountId = currentAccount.value?.id || zaloAccount?.activeAccountId.value;

if (activeNav.value === 'history') {
  if (accountId) {
    params.account_id = accountId;
  }
  console.log('📥 [ZaloIndex] Loading conversations for account:', accountId);
}
```

#### 1.2 handleSyncHistory() - Line 726

```javascript
// 🔥 FIX: Use currentAccount instead of zaloAccount
const accountId = currentAccount.value?.id || zaloAccount?.activeAccountId.value;
```

#### 1.3 searchUser() - Line 861

```javascript
// 🔥 FIX: Use currentAccount instead of zaloAccount
const accountId = currentAccount.value?.id || zaloAccount?.activeAccountId.value;
```

#### 1.4 handleSendFriendRequest() - Line 919

```javascript
// 🔥 FIX: Use currentAccount instead of zaloAccount
const accountId = currentAccount.value?.id || zaloAccount?.activeAccountId.value;
```

#### 1.5 handleCreateGroup() - Line 981

```javascript
// 🔥 FIX: Use currentAccount instead of zaloAccount
const accountId = currentAccount.value?.id || zaloAccount?.activeAccountId.value;
```

#### 1.6 loadFriendsForGroup() - Line 1052

```javascript
// 🔥 FIX: Use currentAccount instead of zaloAccount
const accountId = currentAccount.value?.id || zaloAccount?.activeAccountId.value;
```

---

### 2. ZaloChatView.vue - Send Messages

**File**: `resources/js/pages/zalo/components/ZaloChatView.vue`

#### 2.1 Thêm accountId prop (Lines 448-451)

```javascript
const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  accountId: {
    type: Number,
    default: null,
  },
  itemType: {
    type: String,
    required: true,
  },
});
```

#### 2.2 Tạo computed property (Lines 464-468)

```javascript
// 🔥 FIX: Get account ID from props or fallback to zaloAccount
// This ensures we use the currently selected account
const currentAccountId = computed(() => {
  return props.accountId || zaloAccount?.activeAccountId?.value || null;
});
```

#### 2.3 Replace ALL occurrences

Thay thế TẤT CẢ `zaloAccount?.activeAccountId.value` → `currentAccountId.value`

Bao gồm:
- loadMessages() - Line 557
- uploadFile() - Line 639
- uploadImage() - Line 723
- sendMessage() - Line 969
- checkMessageExists() - Line 1119
- sendReply() - Line 1230
- addReaction() - Line 1302
- loadReactions() - Line 1356
- WebSocket listeners - Lines 1430, 1438, 1462, 1469, etc.

---

### 3. ZaloIndex.vue - Pass accountId to ZaloChatView

**File**: `resources/js/pages/zalo/ZaloIndex.vue` (Line 207)

**Trước:**
```vue
<ZaloChatView
  v-if="selectedItem && activeNav !== 'settings'"
  :item="selectedItem"
  :item-type="selectedItem.itemType || activeNav"
  @message-sent="handleMessageSent"
/>
```

**Sau:**
```vue
<ZaloChatView
  v-if="selectedItem && activeNav !== 'settings'"
  :item="selectedItem"
  :account-id="currentAccount?.id"
  :item-type="selectedItem.itemType || activeNav"
  @message-sent="handleMessageSent"
/>
```

---

## 🎯 Flow sau khi fix (ĐÚNG)

```
User click radio button
  ↓
ZaloAccountManager.setActiveAccount(accountId)
  ↓
API: POST /api/zalo/accounts/active
  ↓
Backend: Deactivate all, activate selected ✅
  ↓
Dispatch event: 'zalo-account-changed'
  ↓
ZaloIndex event listener:
  - currentAccount.value = newAccount ✅
  - Switch WebSocket rooms ✅
  - loadList() ✅
  ↓
loadList() sử dụng currentAccount.value.id ✅
  ↓
GET /api/zalo/messages/conversations?account_id=9 ✅
GET /api/zalo/friends?account_id=9 ✅
GET /api/zalo/groups?account_id=9 ✅
  ↓
Load data from CORRECT account! ✅
```

---

## 🧪 Test Cases

### Test 1: Load conversations
1. Chọn account 1 (Tuấn Lệ) qua radio button
2. Click icon "History" ở sidebar
3. ✅ Verify: Browser console log `📥 [ZaloIndex] Loading conversations for account: 1`
4. ✅ Verify: Danh sách conversations của account 1 được hiển thị

5. Chọn account 9 (Hoàng Anh) qua radio button
6. ✅ Verify: Browser console log `📥 [ZaloIndex] Loading conversations for account: 9`
7. ✅ Verify: Danh sách conversations TỰ ĐỘNG thay đổi sang account 9

### Test 2: Load friends
1. Chọn account 1, click icon "Friends"
2. ✅ Verify: Log `📥 [ZaloIndex] Loading friends for account: 1`
3. ✅ Verify: Danh sách bạn của account 1

4. Chọn account 9
5. ✅ Verify: Log `📥 [ZaloIndex] Loading friends for account: 9`
6. ✅ Verify: Danh sách bạn TỰ ĐỘNG thay đổi sang account 9

### Test 3: Load groups
1. Chọn account 1, click icon "Groups"
2. ✅ Verify: Log `📥 [ZaloIndex] Loading groups for account: 1`
3. ✅ Verify: Danh sách nhóm của account 1

4. Chọn account 9
5. ✅ Verify: Log `📥 [ZaloIndex] Loading groups for account: 9`
6. ✅ Verify: Danh sách nhóm TỰ ĐỘNG thay đổi sang account 9

### Test 4: Send message
1. Chọn account 9
2. Click vào một conversation
3. Gửi tin nhắn "Hello from account 9"
4. ✅ Verify: Tin nhắn được gửi từ account 9
5. ✅ Verify: API call `POST /api/zalo/messages` với `account_id: 9`

### Test 5: Sync history
1. Chọn account 9
2. Click button "Sync History"
3. ✅ Verify: API call `POST /api/zalo/messages/sync` với `account_id: 9`
4. ✅ Verify: Sync dữ liệu từ đúng account 9

### Test 6: Search user
1. Chọn account 9
2. Click "Add Friend", nhập số điện thoại
3. ✅ Verify: API call `GET /api/zalo/search-user` với `account_id: 9`

### Test 7: Create group
1. Chọn account 9
2. Click "Create Group"
3. ✅ Verify: Danh sách friends để chọn là của account 9
4. Tạo nhóm
5. ✅ Verify: API call `POST /api/zalo/groups` với `account_id: 9`

---

## 📊 Summary

| Function | Before (BUG) | After (FIXED) |
|----------|-------------|---------------|
| Load conversations | ❌ Account 1 | ✅ Account 9 |
| Load friends | ❌ Account 1 | ✅ Account 9 |
| Load groups | ❌ Account 1 | ✅ Account 9 |
| Send message | ❌ Account 1 | ✅ Account 9 |
| Sync history | ❌ Account 1 | ✅ Account 9 |
| Search user | ❌ Account 1 | ✅ Account 9 |
| Create group | ❌ Account 1 | ✅ Account 9 |

---

## 📦 Build Status

```
✓ built in 9.04s
Exit code: 0
Status: SUCCESS ✅
```

Tất cả thay đổi đã được compile thành công và sẵn sàng để test!

---

## 🔧 Debug Tips

Nếu vẫn gặp vấn đề, kiểm tra browser console:

1. **Check account selection**:
   ```
   ✅ Đã chọn tài khoản: Hoàng Anh
   ```

2. **Check data loading**:
   ```
   📥 [ZaloIndex] Loading conversations for account: 9
   📥 [ZaloIndex] Loading friends for account: 9
   📥 [ZaloIndex] Loading groups for account: 9
   ```

3. **Check API calls** (F12 > Network tab):
   ```
   GET /api/zalo/messages/conversations?account_id=9
   GET /api/zalo/friends?account_id=9
   GET /api/zalo/groups?account_id=9
   ```

4. **Check WebSocket rooms**:
   ```
   👋 [ZaloIndex] Leaving old conversation: 1
   👋 [ZaloIndex] Joining new conversation: 9
   ```

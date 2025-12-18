# Fix: Sync Friends & Groups không hoạt động đúng

## 🔴 Vấn đề phát hiện

### 1. Sync Friends KHÔNG hoạt động
**Root cause**: Endpoint `/api/user/friends` KHÔNG TỒN TẠI trong zalo-service!

Laravel gọi: `GET {baseUrl}/api/user/friends`
→ **404 Not Found** ❌

### 2. Sync Groups sync SAI account
**Root cause**: Endpoint `/api/group/list` KHÔNG ĐỌC account_id!

```javascript
// ❌ BEFORE
const zalo = getZaloClient();  // No account ID!
groups = await zalo.getAllGroups();  // Wrong account!
```

## ✅ Giải pháp đã implement

### 1. TẠO endpoint `/api/user/friends` mới

**File**: `zalo-service/routes/user.js`

```javascript
/**
 * GET /api/user/friends
 * Get friends list for specific account
 */
router.get('/friends', verifyApiKey, async (req, res) => {
  try {
    console.log('📋 [GET /api/user/friends] Getting friends list...');

    // 🔥 FIX: Get account ID from header or query
    const accountId = req.headers['x-account-id'] || req.query.account_id;

    if (!accountId) {
      return res.status(400).json({
        success: false,
        message: 'account_id is required (header X-Account-Id or query param)'
      });
    }

    console.log('   Account ID:', accountId);

    // 🔥 FIX: Get session for specific account
    const { getSession } = require('../services/zaloClient');
    const zalo = getSession(parseInt(accountId));

    if (!zalo) {
      return res.status(400).json({
        success: false,
        message: `Zalo session not found for account ${accountId}. Please login first.`
      });
    }

    // Try different method names for getting friends
    const methodNames = ['getAllFriends', 'getFriends', 'listFriends', 'getFriendList'];
    let friends = null;

    for (const methodName of methodNames) {
      if (typeof zalo[methodName] === 'function') {
        friends = await zalo[methodName]();
        break;
      }
    }

    if (!friends || !Array.isArray(friends)) {
      friends = [];
    }

    res.json({
      success: true,
      data: friends,
      count: friends.length
    });
  } catch (error) {
    console.error('❌ Get friends error:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'Failed to get friends'
    });
  }
});
```

**Tính năng**:
- ✅ Đọc account_id từ `X-Account-Id` header hoặc query param
- ✅ Lấy session đúng account với `getSession(accountId)`
- ✅ Hỗ trợ nhiều method names: getAllFriends, getFriends, listFriends, getFriendList
- ✅ Error handling đầy đủ
- ✅ Logging chi tiết

---

### 2. SỬA endpoint `/api/group/list` để đọc account_id

**File**: `zalo-service/routes/group.js`

**BEFORE**:
```javascript
router.get('/list', verifyApiKey, async (req, res) => {
  const zalo = getZaloClient();  // ❌ No account ID
  groups = await zalo.getAllGroups();  // ❌ Wrong account!
});
```

**AFTER**:
```javascript
router.get('/list', verifyApiKey, async (req, res) => {
  // 🔥 FIX: Get account ID from header or query
  const accountId = req.headers['x-account-id'] || req.query.account_id;

  if (!accountId) {
    return res.status(400).json({
      success: false,
      message: 'account_id is required (header X-Account-Id or query param)'
    });
  }

  console.log('   Account ID:', accountId);

  // 🔥 FIX: Get session for specific account
  const { getSession } = require('../services/zaloClient');
  const zalo = getSession(parseInt(accountId));

  if (!zalo) {
    return res.status(400).json({
      success: false,
      message: `Zalo session not found for account ${accountId}. Please login first.`
    });
  }

  // Try different method names
  const methodNames = ['getAllGroups', 'getGroups', 'listGroups', 'getGroupList'];
  for (const methodName of methodNames) {
    if (typeof zalo[methodName] === 'function') {
      groups = await zalo[methodName]();
      break;
    }
  }
  // ... rest of logic
});
```

**Thay đổi**:
- ✅ Đọc account_id từ header/query
- ✅ Sử dụng `getSession(accountId)` thay vì `getZaloClient()`
- ✅ Validate account_id và session tồn tại
- ✅ Logging với account_id

---

## 🔧 Cách restart zalo-service

### Windows:

1. **Kill process đang chạy trên port 3001**:
   ```powershell
   # Tìm process
   netstat -ano | findstr :3001

   # Kill process (thay PID bằng số từ câu lệnh trên)
   taskkill /PID <PID> /F
   ```

2. **Start lại zalo-service**:
   ```bash
   cd c:/xampp/htdocs/school/zalo-service
   npm start
   ```

### Hoặc đơn giản:

1. Mở Task Manager (Ctrl + Shift + Esc)
2. Tìm process "node.exe" (zalo-service)
3. End task
4. Start lại: `cd zalo-service && npm start`

---

## 🎯 Flow sau khi fix (ĐÚNG)

### Sync Friends:
```
User click "Resync" button
  ↓
Frontend: loadList(true) with account_id=9
  ↓
Laravel: $this->zalo->getFriends(9)
  ↓
ZaloNotificationService: buildHeaders(9) → X-Account-Id: 9
  ↓
GET {baseUrl}/api/user/friends với header X-Account-Id: 9
  ↓
zalo-service: getSession(9) → Get correct Zalo client
  ↓
zalo.getAllFriends() → Friends từ ĐÚNG account 9 ✅
  ↓
Return friends data
  ↓
Laravel: syncFriends(account 9, friendsData)
  ↓
Database: Lưu friends vào zalo_friends với account_id=9 ✅
```

### Sync Groups:
```
User click "Resync" button
  ↓
Frontend: loadList(true) with account_id=9
  ↓
Laravel: $this->zalo->getGroups(9)
  ↓
ZaloNotificationService: buildHeaders(9) → X-Account-Id: 9
  ↓
GET {baseUrl}/api/group/list với header X-Account-Id: 9
  ↓
zalo-service: getSession(9) → Get correct Zalo client
  ↓
zalo.getAllGroups() → Groups từ ĐÚNG account 9 ✅
  ↓
Return groups data
  ↓
Laravel: syncGroups(account 9, groupsData)
  ↓
Database: Lưu groups vào zalo_groups với account_id=9 ✅
```

---

## 🧪 Test Cases

### Test 1: Sync Friends
1. Chọn account 9 (Hoàng Anh) qua radio button
2. Click icon "Friends" ở sidebar
3. Click button "Resync" (refresh icon)
4. **Check zalo-service console**:
   ```
   📋 [GET /api/user/friends] Getting friends list...
      Account ID: 9
      ✅ Zalo session found
      ✅ Found method: getAllFriends()
      ✅ getAllFriends() returned 25 friends
      ✅ Returning 25 friends
   ```
5. **Check Laravel log** (`storage/logs/laravel.log`):
   ```
   [Zalo] Getting friends
   url: http://localhost:3001/api/user/friends
   accountId: 9

   [Zalo] Friends retrieved
   count: 25

   [ZaloController] Syncing friends...
   account_id: 9
   ```
6. **Verify frontend**: Danh sách bạn của account 9 hiển thị

### Test 2: Sync Groups
1. Chọn account 9
2. Click icon "Groups"
3. Click "Resync"
4. **Check zalo-service console**:
   ```
   📋 [GET /api/group/list] Getting groups list...
      Account ID: 9
      ✅ Zalo session found
      ✅ Found method: getAllGroups()
      ✅ getAllGroups() success!
      ✅ Returning 12 groups
   ```
5. **Check Laravel log**:
   ```
   [ZaloController] Syncing groups from API
   account_id: 9

   [ZaloController] Groups from API received
   count: 12

   [ZaloController] Groups sync completed
   ```
6. **Verify frontend**: Danh sách nhóm của account 9 hiển thị

### Test 3: Switch Account và Sync
1. Chọn account 1 (Tuấn Lệ)
2. Click "Friends" → Resync
3. ✅ Verify: Sync friends của account 1
4. Chọn account 9 (Hoàng Anh)
5. Click "Friends" → Resync
6. ✅ Verify: Sync friends của account 9 (KHÁC với account 1!)

---

## 📊 Summary

| Issue | Before | After |
|-------|--------|-------|
| Sync Friends | ❌ 404 Not Found | ✅ Works with account_id |
| Sync Groups | ❌ Wrong account | ✅ Correct account |
| API Endpoint | ❌ Missing | ✅ Created |
| Account Detection | ❌ None | ✅ From header/query |
| Session Selection | ❌ Global only | ✅ Per account |

---

## 🔍 Debug Tips

### Nếu sync friends vẫn lỗi:

1. **Check endpoint tồn tại**:
   ```bash
   curl -H "Authorization: Bearer your-api-key" \
        -H "X-Account-Id: 9" \
        http://localhost:3001/api/user/friends
   ```

2. **Check zalo-service logs**:
   - Phải thấy: `📋 [GET /api/user/friends] Getting friends list...`
   - Phải thấy: `Account ID: 9`
   - Phải thấy: `✅ Zalo session found`

3. **Check Laravel logs** (`storage/logs/laravel.log`):
   - Phải thấy: `[Zalo] Getting friends`
   - Phải thấy: `accountId: 9`

### Nếu sync groups sync sai account:

1. **Check account_id được pass**:
   - Laravel log phải có: `account_id: 9`
   - zalo-service log phải có: `Account ID: 9`

2. **Check session**:
   - zalo-service phải log: `✅ Zalo session found`
   - Không được: `❌ Zalo session not found`

3. **Verify database**:
   ```sql
   -- Friends phải có account_id đúng
   SELECT account_id, COUNT(*) FROM zalo_friends GROUP BY account_id;

   -- Groups phải có account_id đúng
   SELECT account_id, COUNT(*) FROM zalo_groups GROUP BY account_id;
   ```

---

## ⚠️ QUAN TRỌNG

**BẮT BUỘC RESTART zalo-service** sau khi sửa code!

Nếu không restart:
- ❌ Endpoint mới `/api/user/friends` sẽ KHÔNG có
- ❌ Endpoint `/api/group/list` vẫn dùng code CŨ (không đọc account_id)
- ❌ Sync sẽ VẪN LỖI!

**Cách kiểm tra đã restart thành công**:
```bash
# Check zalo-service đang chạy
curl http://localhost:3001/health

# Test endpoint mới
curl -H "Authorization: Bearer test-key-123" \
     -H "X-Account-Id: 1" \
     http://localhost:3001/api/user/friends
```

Nếu thấy response (không phải 404) → Thành công! ✅

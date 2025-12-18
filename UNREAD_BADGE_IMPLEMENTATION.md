# Unread Message Badge System Implementation

## Yêu cầu từ người dùng

Tạo hệ thống thông báo số lượng tin nhắn chưa đọc với 3 cấp độ:

1. **Badge trên icon Zalo trong sidebar** - Tổng tin nhắn chưa đọc của tất cả tài khoản
2. **Badge trên từng account** - Tổng tin nhắn chưa đọc của từng tài khoản
3. **Badge trên từng cuộc hội thoại** - Số tin nhắn chưa đọc của cuộc hội thoại đó

---

## ✅ Đã implement

### 1. Backend - Database Structure

**File**: `database/migrations/2025_11_13_015745_create_zalo_messages_table.php`

Bảng `zalo_messages` đã có sẵn các trường cần thiết:
- `type` enum('sent', 'received') - Phân biệt tin nhắn đi/đến
- `read_at` timestamp - Thời điểm đọc tin nhắn (NULL = chưa đọc)
- `status` enum('pending', 'sent', 'delivered', 'read', 'failed')
- `recipient_id` - ID người nhận/nhóm
- `zalo_account_id` - Tài khoản Zalo

**Logic unread**: Tin nhắn chưa đọc = `type = 'received'` AND `read_at IS NULL`

---

### 2. Backend - API Endpoints

**File**: [app/Http/Controllers/Api/ZaloController.php](app/Http/Controllers/Api/ZaloController.php)

#### 2.1. GET /api/zalo/unread-counts

Endpoint linh hoạt hỗ trợ 3 cấp độ:

**Level 1: Tổng unread (no params)**
```bash
GET /api/zalo/unread-counts
```

Response:
```json
{
  "success": true,
  "data": {
    "total_unread": 25,
    "by_account": [
      { "zalo_account_id": 1, "unread_count": 10 },
      { "zalo_account_id": 9, "unread_count": 15 }
    ]
  }
}
```

**Level 2: Unread per account**
```bash
GET /api/zalo/unread-counts?account_id=9
```

Response:
```json
{
  "success": true,
  "data": {
    "total_unread": 15,
    "account_id": 9,
    "by_conversation": [
      {
        "recipient_id": "123456",
        "recipient_name": "Nguyễn Văn A",
        "recipient_type": "user",
        "unread_count": 5
      },
      {
        "recipient_id": "789",
        "recipient_name": "Nhóm ABC",
        "recipient_type": "group",
        "unread_count": 10
      }
    ]
  }
}
```

**Level 3: Unread per conversation**
```bash
GET /api/zalo/unread-counts?account_id=9&recipient_id=123456
```

Response:
```json
{
  "success": true,
  "data": {
    "unread_count": 5,
    "account_id": 9,
    "recipient_id": "123456"
  }
}
```

#### 2.2. POST /api/zalo/mark-as-read

Đánh dấu tin nhắn đã đọc:

```bash
POST /api/zalo/mark-as-read
Body:
{
  "account_id": 9,
  "recipient_id": "123456",
  "message_ids": [1, 2, 3] // Optional: specific messages
}
```

Response:
```json
{
  "success": true,
  "data": {
    "marked_count": 5
  }
}
```

**Logic**:
- Update `read_at = now()` và `status = 'read'`
- Nếu không có `message_ids`: Đánh dấu TẤT CẢ tin nhắn chưa đọc của cuộc hội thoại

---

### 3. Backend - Routes

**File**: [routes/api.php](routes/api.php:1203-1204)

```php
Route::get('/unread-counts', [ZaloController::class, 'getUnreadCounts'])
  ->middleware('permission:zalo.view');

Route::post('/mark-as-read', [ZaloController::class, 'markAsRead'])
  ->middleware('permission:zalo.send');
```

---

### 4. Frontend - Badge System

**File**: [resources/js/pages/zalo/ZaloIndex.vue](resources/js/pages/zalo/ZaloIndex.vue)

#### 4.1. State Management (Lines 494-497)

```javascript
// Unread counts
const totalUnreadCount = ref(0); // Total across all accounts
const accountUnreadCounts = ref({}); // Per account: { accountId: count }
const unreadPollingInterval = ref(null);
```

#### 4.2. Fetch Functions (Lines 727-826)

**fetchUnreadCounts()** - Lines 728-758:
- Lấy tổng unread cho tất cả accounts
- Build map `accountUnreadCounts` cho từng account
- Nếu đang ở History tab, cũng fetch conversation-level unread

**fetchConversationUnreadCounts()** - Lines 761-787:
- Lấy unread counts cho các conversation của account hiện tại
- Update `unread_count` trong `listItems`

**markConversationAsRead()** - Lines 790-806:
- Gọi API mark-as-read
- Refresh unread counts sau khi đánh dấu

**Polling** - Lines 809-826:
- `startUnreadPolling()`: Poll mỗi 30 giây
- `stopUnreadPolling()`: Cleanup khi unmount

#### 4.3. Badge UI Components

**Badge 1: Zalo Icon Sidebar** - Lines 41-48:

```vue
<span
  v-if="nav.key === 'history' && totalUnreadCount > 0"
  class="absolute top-0 right-0 flex items-center justify-center
         min-w-[18px] h-[18px] px-1 text-xs font-bold
         text-white bg-red-500 rounded-full border-2 border-blue-600"
>
  {{ totalUnreadCount > 99 ? '99+' : totalUnreadCount }}
</span>
```

Hiển thị: **Tổng tin nhắn chưa đọc của TẤT CẢ tài khoản**

**Badge 2: Account List** - [ZaloAccountManager.vue](resources/js/pages/zalo/components/ZaloAccountManager.vue:64-70):

```vue
<span
  v-if="unreadCounts[account.id] > 0"
  class="min-w-[20px] h-5 px-1.5 flex items-center justify-center
         text-xs font-bold text-white bg-red-500 rounded-full"
>
  {{ unreadCounts[account.id] > 99 ? '99+' : unreadCounts[account.id] }}
</span>
```

Props passed from parent: `:unread-counts="accountUnreadCounts"` (ZaloIndex.vue:55)

Hiển thị: **Tổng tin nhắn chưa đọc của TỪNG tài khoản**

**Badge 3: Conversation List** - Existing feature:

Mỗi conversation đã có sẵn thuộc tính `unread_count` từ API `/api/zalo/history`.
Chúng ta chỉ cần cập nhật giá trị này qua `fetchConversationUnreadCounts()`.

#### 4.4. Lifecycle Hooks

**onMounted** - Lines 1244-1245:
```javascript
// Start polling for unread counts
startUnreadPolling();
```

**onUnmounted** - Lines 1349-1350:
```javascript
// Stop unread polling
stopUnreadPolling();
```

#### 4.5. Real-time Updates

**WebSocket Conversation Update** - Lines 1324-1325:
```javascript
// Refresh total unread count when conversation updates
fetchUnreadCounts();
```

Khi có tin nhắn mới đến qua WebSocket → Tự động refresh unread counts.

**Mark as Read on Click** - Lines 892-901:
```javascript
const selectItem = (item) => {
  selectedItem.value = item;

  // Mark conversation as read when opened (only for history)
  if (activeNav.value === 'history' && item.unread_count > 0) {
    const recipientId = item.recipient_id || item.zalo_user_id || item.zalo_group_id;
    if (recipientId) {
      markConversationAsRead(recipientId);
      item.unread_count = 0; // Optimistic UI update
    }
  }
};
```

Khi user click vào conversation → Tự động đánh dấu đã đọc.

---

## 🔄 Complete Flow

### User Journey:

```
1. User mở Zalo page
   ↓
2. onMounted() → startUnreadPolling()
   ↓
3. Poll API /api/zalo/unread-counts mỗi 30 giây
   ↓
4. Response:
   - totalUnreadCount = 25
   - accountUnreadCounts = { 1: 10, 9: 15 }
   ↓
5. 🔔 Badge hiển thị:
   ┌─────────────────────────────────────┐
   │ SIDEBAR ICON                        │
   │ ┌──────┐                           │
   │ │ 📱   │ [25] ← Total unread       │
   │ └──────┘                           │
   └─────────────────────────────────────┘

6. User click avatar → Mở Account Manager
   ↓
7. 🔔 Badge per account:
   ┌─────────────────────────────────────┐
   │ Account 1 (Tuấn Lệ)      [10] ←    │
   │ Account 9 (Hoàng Anh)    [15] ←    │
   └─────────────────────────────────────┘

8. User chọn Account 9 → Navigate to History tab
   ↓
9. fetchConversationUnreadCounts(9)
   ↓
10. 🔔 Badge per conversation:
   ┌─────────────────────────────────────┐
   │ Nguyễn Văn A            [5] ←      │
   │ Nhóm ABC               [10] ←      │
   │ Trần Thị B              [0]        │
   └─────────────────────────────────────┘

11. User click "Nguyễn Văn A"
   ↓
12. markConversationAsRead("123456")
   ↓
13. POST /api/zalo/mark-as-read
   ↓
14. Database: Update read_at = now()
   ↓
15. Badge biến mất: [5] → [0] ✅
   ↓
16. 📡 WebSocket nhận tin nhắn mới từ "Trần Thị B"
   ↓
17. onConversationUpdate() → fetchUnreadCounts()
   ↓
18. Badge cập nhật real-time:
   - Trần Thị B: [0] → [1]
   - Total: [25] → [21]
```

---

## 📊 Summary Table

| Badge Level | Location | Display | API Endpoint | Update Trigger |
|-------------|----------|---------|--------------|----------------|
| **Total** | Zalo icon in sidebar | Total unread across ALL accounts | GET /api/zalo/unread-counts | Poll 30s + WebSocket |
| **Per Account** | Account Manager list | Unread for EACH account | GET /api/zalo/unread-counts | Poll 30s + WebSocket |
| **Per Conversation** | History conversation list | Unread for EACH conversation | GET /api/zalo/unread-counts?account_id=X | Poll 30s + WebSocket + On click |

---

## 🧪 Test Cases

### Test 1: Badge hiển thị đúng

**Setup**: Tạo 5 tin nhắn chưa đọc cho Account 9

**Steps**:
1. Mở Zalo page
2. Quan sát sidebar icon
3. Click avatar → Mở Account Manager
4. Chọn Account 9 → Mở History tab

**Expected**:
- ✅ Sidebar icon có badge [5]
- ✅ Account 9 có badge [5]
- ✅ Conversation có badge với số tin nhắn chưa đọc tương ứng

### Test 2: Mark as read

**Steps**:
1. Click vào conversation có 3 tin nhắn chưa đọc
2. Quan sát badge

**Expected**:
- ✅ Badge conversation: [3] → [0]
- ✅ Badge account: [5] → [2]
- ✅ Badge total giảm tương ứng
- ✅ Database: `zalo_messages` có `read_at` được update

**SQL Check**:
```sql
SELECT id, content, read_at, status
FROM zalo_messages
WHERE zalo_account_id = 9
  AND recipient_id = '123456'
  AND type = 'received';

-- Expected: Tất cả messages có read_at != NULL và status = 'read'
```

### Test 3: Real-time update qua WebSocket

**Steps**:
1. Gửi tin nhắn từ Zalo app đến Account 9
2. Quan sát badge (KHÔNG refresh page)

**Expected**:
- ✅ Badge conversation tăng: [0] → [1]
- ✅ Badge account 9 tăng: [2] → [3]
- ✅ Badge total tăng tương ứng
- ✅ Update trong vòng 1-2 giây (WebSocket real-time)

### Test 4: Polling updates

**Steps**:
1. Note badge count hiện tại
2. Wait 30 seconds
3. Quan sát console logs

**Expected**:
- ✅ Console log: `[ZaloIndex] Error fetching unread counts:` (if any)
- ✅ Badge vẫn hiển thị đúng
- ✅ Polling interval hoạt động (check mỗi 30s)

### Test 5: Multiple accounts

**Setup**: 2 accounts với unread khác nhau

**Expected**:
- Account 1: [10]
- Account 9: [15]
- Total: [25]

Switch giữa accounts → Badge update đúng cho từng account.

---

## 🔍 Debug Tips

### Check unread counts API

```bash
# Total unread
curl -H "Authorization: Bearer {token}" \
     "http://localhost/api/zalo/unread-counts"

# Per account
curl -H "Authorization: Bearer {token}" \
     "http://localhost/api/zalo/unread-counts?account_id=9"

# Per conversation
curl -H "Authorization: Bearer {token}" \
     "http://localhost/api/zalo/unread-counts?account_id=9&recipient_id=123456"
```

### Check database

```sql
-- Count unread messages for account 9
SELECT COUNT(*)
FROM zalo_messages
WHERE zalo_account_id = 9
  AND type = 'received'
  AND read_at IS NULL;

-- Count unread per conversation
SELECT recipient_id, recipient_name, COUNT(*) as unread_count
FROM zalo_messages
WHERE zalo_account_id = 9
  AND type = 'received'
  AND read_at IS NULL
GROUP BY recipient_id, recipient_name;

-- Total unread across all accounts
SELECT COUNT(*)
FROM zalo_messages
WHERE type = 'received'
  AND read_at IS NULL;
```

### Browser Console

```javascript
// Check if polling is running
// Should see logs every 30 seconds:
"[ZaloIndex] Error fetching unread counts:" // or success

// Check state
// In Vue DevTools:
totalUnreadCount // Should show number
accountUnreadCounts // Should show { 1: 10, 9: 15 }
```

### Laravel Logs

```bash
tail -f storage/logs/laravel.log | grep -E "unread|mark.*read"
```

Expected output:
```
[ZaloController] Marked messages as read
account_id: 9
recipient_id: 123456
updated_count: 5
```

---

## 🎨 UI/UX Notes

### Badge Colors:
- **Red (#EF4444)**: Unread count
- **Border**: Blue (#2563EB) for sidebar icon (matches background)

### Badge Sizes:
- **Sidebar icon**: min-w-[18px] h-[18px]
- **Account list**: min-w-[20px] h-5
- **Conversation list**: Existing design

### Badge Display Rules:
- Show badge ONLY if count > 0
- Show "99+" if count > 99
- Badge disappears when count = 0

### Performance:
- Polling interval: 30 seconds (không quá nhanh để tránh spam API)
- WebSocket: Real-time updates (ngay lập tức)
- Optimistic UI: Mark as read instantly, sau đó confirm với server

---

## 📝 Files Modified

### Backend:
1. ✅ [app/Http/Controllers/Api/ZaloController.php](app/Http/Controllers/Api/ZaloController.php:109-245)
   - Added `getUnreadCounts()` method
   - Added `markAsRead()` method

2. ✅ [routes/api.php](routes/api.php:1203-1204)
   - Added unread-counts route
   - Added mark-as-read route

### Frontend:
3. ✅ [resources/js/pages/zalo/ZaloIndex.vue](resources/js/pages/zalo/ZaloIndex.vue)
   - Added unread count state (lines 494-497)
   - Added fetch functions (lines 727-826)
   - Added badge to sidebar icon (lines 41-48)
   - Added mark-as-read on click (lines 892-901)
   - Added polling lifecycle (lines 1244-1245, 1349-1350)
   - Added WebSocket refresh (line 1325)

4. ✅ [resources/js/pages/zalo/components/ZaloAccountManager.vue](resources/js/pages/zalo/components/ZaloAccountManager.vue)
   - Added unreadCounts prop (lines 107-110)
   - Added badge per account (lines 64-70)

### Documentation:
5. ✅ [UNREAD_BADGE_IMPLEMENTATION.md](UNREAD_BADGE_IMPLEMENTATION.md) - This file

---

## ✅ Build Status

```bash
npm run build
```

**Result**: ✓ built in 8.89s

---

**Status**: ✅ HOÀN THÀNH - Ready for testing!

All 3 badge levels implemented:
1. ✅ Total unread badge on Zalo sidebar icon
2. ✅ Per-account unread badge in Account Manager
3. ✅ Per-conversation unread badge in History list (existing + updated)

Next step: Test with real Zalo messages!

# 🔍 Zalo Debug Guide - Messages Lẫn Lộn (Mixed Messages)

## 🚨 Vấn đề: Messages bị lẫn lộn giữa các conversation

Khi chuyển đổi giữa các conversation, messages của conversation cũ vẫn hiển thị hoặc bị lẫn với messages mới.

---

## ✅ Các fix đã implement

### 1. **Clear messages ngay lập tức khi chuyển conversation**
- File: [ZaloChatView.vue:1738-1741](c:\xampp\htdocs\school\resources\js\pages\zalo\components\ZaloChatView.vue#L1738-L1741)
- `messages.value = []` ngay khi detect conversation thay đổi
- Hiển thị loading spinner ngay lập tức

### 2. **Watch item.id riêng biệt**
- File: [ZaloChatView.vue:1727-1740](c:\xampp\htdocs\school\resources\js\pages\zalo\components\ZaloChatView.vue#L1727-L1740)
- Watch trực tiếp `props.item?.id` để catch mọi thay đổi
- Reliable hơn việc watch entire object

### 3. **Cache validation**
- File: [ZaloChatView.vue:670-681](c:\xampp\htdocs\school\resources\js\pages\zalo\components\ZaloChatView.vue#L670-L681)
- Validate cached data là array hợp lệ
- Auto-delete cache bị corrupt

### 4. **Response validation**
- File: [ZaloChatView.vue:724-728](c:\xampp\htdocs\school\resources\js\pages\zalo\components\ZaloChatView.vue#L724-L728)
- Ensure API response data là array
- Prevent caching invalid data

---

## 🧪 Cách test và debug

### Test 1: Kiểm tra Console Logs

Mở DevTools Console và tìm các logs sau khi click conversation:

```javascript
// ✅ EXPECTED LOGS (đúng):
🔍 [ZaloChatView] Item ID changed: { oldId: 123, newId: 456, itemName: "User B" }
🧹 [ZaloChatView] Clearing messages before switching conversation
👋 [ZaloChatView] Leaving old conversation: 123
👋 [ZaloChatView] Joining new conversation: 456
🔄 [ZaloChatView] Loading messages: { conversationId: 456, requestTimestamp: 1234567890 }
💾 [ZaloChatView] Loading from cache: 456 messages: 10
// HOẶC
📡 [ZaloChatView] Fetching messages for: 456
✅ [ZaloChatView] Messages loaded and cached: { conversationId: 456, messageCount: 10, isCurrentConv: true }
```

```javascript
// ❌ WARNING LOGS (cần chú ý):
⚠️ [ZaloChatView] Invalid cache data, removing: 456 [...]
⚠️ [ZaloChatView] Ignoring stale response: { requestConvId: 123, currentConvId: 456, isStale: true }
⏭️ [ZaloChatView] Skipping cleanup for stale request: 123
```

```javascript
// 🚫 ERROR LOGS (cần fix ngay):
❌ [ZaloChatView] Invalid response data (not array): {...}
```

### Test 2: Verify Messages Content

1. Click conversation A
2. Nhớ 1-2 tin nhắn cuối của A
3. Click conversation B
4. **Kiểm tra:** Messages hiển thị phải là của B, KHÔNG có tin nhắn của A
5. Click lại A
6. **Kiểm tra:** Messages hiển thị đúng của A

### Test 3: Test Cache

```javascript
// Mở Console và chạy:
// 1. Load conversation A
// Click vào conversation A
// 2. Check cache
console.log('Cache size:', window.messagesCache?.size || 'N/A');

// 3. Load conversation B
// Click vào conversation B

// 4. Check cache again
console.log('Cache size:', window.messagesCache?.size || 'N/A');

// 5. Click lại A → Should load from cache (very fast)
```

### Test 4: Force Clear Cache

Click nút **Refresh** (icon ↻) trên header của chat view để:
- Clear cache cho conversation hiện tại
- Force reload từ server
- Verify data is fresh

---

## 🔍 Debug Common Issues

### Issue 1: Messages vẫn bị lẫn sau khi fix

**Triệu chứng:**
- Click A → Thấy messages của A
- Click B → Vẫn thấy messages của A (hoặc lẫn A + B)

**Cách debug:**

1. Mở Console, filter logs với keyword: `ZaloChatView`
2. Click conversation B
3. Tìm log `🧹 Clearing messages before switching conversation`
   - ✅ Có log này → messages đã được clear
   - ❌ Không có → watch() không trigger

4. Tìm log `🔍 Item ID changed`
   - ✅ Có log với `newId: B` → item.id đã thay đổi
   - ❌ Không có → props.item.id không thay đổi (bug ở parent component)

5. Tìm log `✅ Messages loaded and cached`
   - Check `conversationId` có đúng là B không
   - Check `isCurrentConv: true` hay `false`
   - Nếu `false` → Response bị ignore vì không phải conversation hiện tại

**Giải pháp:**

```javascript
// Nếu watch() không trigger, check ZaloIndex.vue:
// Ensure selectItem() đang set object mới, không phải mutate object cũ
const selectItem = (item) => {
  // ✅ CORRECT: Create new reference
  selectedItem.value = { ...item };

  // ❌ WRONG: Mutate existing object
  // selectedItem.value.id = item.id; // Don't do this!
};
```

### Issue 2: Cache bị corrupt

**Triệu chứng:**
- Logs hiển thị: `⚠️ Invalid cache data, removing`
- Messages load chậm hơn bình thường

**Cách debug:**

1. Mở Console
2. Run: `localStorage.clear(); location.reload();`
3. Clear cache bằng tay:
   - Click Refresh button (↻) trên mỗi conversation
   - Hoặc reload trang (F5)

**Giải pháp:**

Cache sẽ tự động được xóa nếu invalid. Nếu vấn đề lặp lại:
- Check API response format
- Verify `/api/zalo/messages` trả về array

### Issue 3: Race condition vẫn xảy ra

**Triệu chứng:**
- Click nhanh A → B → C
- Thấy messages của A hoặc B thay vì C

**Cách debug:**

1. Mở Console
2. Click nhanh A → B → C
3. Check logs:

```javascript
// ✅ EXPECTED với debounce:
🎯 [ZaloIndex] Selecting conversation: C <name>
// Chỉ có 1 log này, không có A và B

// ❌ WRONG:
🎯 [ZaloIndex] Selecting conversation: A <name>
🎯 [ZaloIndex] Selecting conversation: B <name>
🎯 [ZaloIndex] Selecting conversation: C <name>
// 3 logs → debounce không hoạt động
```

4. Check `⚠️ Ignoring stale response` logs:

```javascript
⚠️ [ZaloChatView] Ignoring stale response: {
  requestConvId: "A",
  currentConvId: "C",
  requestTime: 1000,
  currentTime: 1300,
  isStale: true
}
// ✅ Stale responses bị ignore → Good!
```

**Giải pháp:**

Nếu debounce không hoạt động:
- Check [ZaloIndex.vue:951-969](c:\xampp\htdocs\school\resources\js\pages\zalo\ZaloIndex.vue#L951-L969)
- Ensure `DEBOUNCE_DELAY = 150` (ms)
- Verify `selectItemDebounceTimer` được clear đúng cách

### Issue 4: Loading spinner không hiển thị

**Triệu chứng:**
- Click conversation → Không thấy spinner
- Messages xuất hiện đột ngột

**Cách debug:**

1. Check template có loading overlay:
   - File: [ZaloChatView.vue:65-72](c:\xampp\htdocs\school\resources\js\pages\zalo\components\ZaloChatView.vue#L65-L72)
   - Ensure `v-if="loadingMessages"` exists

2. Check `loadingMessages` value:
   - Mở Vue DevTools
   - Find ZaloChatView component
   - Check `loadingMessages` state
   - Should be `true` when switching conversation

**Giải pháp:**

```javascript
// Ensure loadingMessages is set immediately:
messages.value = [];
loadingMessages.value = true; // ✅ Set before API call
```

---

## 📊 Performance Metrics

### Expected Behavior

| Action | Time | Cache Hit | API Call |
|--------|------|-----------|----------|
| First load conversation | ~100-300ms | ❌ No | ✅ Yes |
| Switch to new conversation | ~100-300ms | ❌ No | ✅ Yes |
| Switch back to cached conversation | ~10-50ms | ✅ Yes | ❌ No |
| Click same conversation | ~0ms | ✅ Skip | ❌ No |

### Logs Timeline

```
T+0ms:    User clicks conversation B
T+5ms:    🔍 Item ID changed (watch triggered)
T+6ms:    🧹 Clearing messages
T+7ms:    loadingMessages.value = true (spinner shows)
T+10ms:   👋 Leaving old conversation
T+15ms:   👋 Joining new conversation
T+20ms:   🔄 Loading messages (check cache)
T+25ms:   💾 Loading from cache (if cached)
          OR
          📡 Fetching messages (if not cached)
T+100ms:  ✅ Messages loaded (if from cache)
T+250ms:  ✅ Messages loaded (if from API)
T+260ms:  Spinner hides, messages display
```

---

## 🛠️ Manual Fixes (If Needed)

### Fix 1: Force Clear All Cache

```javascript
// Mở Console, run:
if (window.messagesCache) {
  window.messagesCache.clear();
  console.log('✅ Cache cleared');
}
location.reload();
```

### Fix 2: Reset Component State

```javascript
// F5 (reload page) should reset everything
// If not working, try:
localStorage.clear();
sessionStorage.clear();
location.reload();
```

### Fix 3: Check API Response

```javascript
// Test API directly:
const accountId = 1; // Your account ID
const recipientId = 123; // Conversation ID

fetch(`/api/zalo/messages?account_id=${accountId}&recipient_id=${recipientId}&recipient_type=user`)
  .then(r => r.json())
  .then(data => {
    console.log('API Response:', data);
    console.log('Is Array?', Array.isArray(data.data));
    console.log('Count:', data.data?.length);
  });
```

---

## 📝 Common Console Errors

### Error 1: `Cannot read property 'id' of undefined`

```
TypeError: Cannot read property 'id' of undefined
  at watch (...ZaloChatView.vue:1729)
```

**Nguyên nhân:** `props.item` là `undefined` hoặc `null`

**Fix:** Ensure parent component always passes valid `item` prop

### Error 2: `messages.value.push is not a function`

```
TypeError: messages.value.push is not a function
```

**Nguyên nhân:** `messages.value` không phải là array

**Fix:** Check cache validation (should be fixed in latest version)

### Error 3: `AbortError: The user aborted a request`

```
AbortError: The user aborted a request
```

**Nguyên nhân:** Request bị abort (đây là hành vi mong muốn khi click nhanh)

**Fix:** Không cần fix, đây là cơ chế race condition prevention

---

## ✅ Verification Checklist

Sau khi apply fixes, verify:

- [ ] Click conversation A → Messages hiển thị đúng của A
- [ ] Click conversation B → Messages hiển thị đúng của B (KHÔNG có messages của A)
- [ ] Click lại A → Messages hiển thị đúng của A (load từ cache, nhanh)
- [ ] Click nhanh A → B → C → Chỉ C được load
- [ ] Loading spinner hiển thị khi chuyển conversation
- [ ] Conversation được highlight rõ ràng (background xanh)
- [ ] Console không có error logs (chỉ có info/warning logs bình thường)
- [ ] Click Refresh button → Messages được reload từ server

---

## 🆘 Vẫn gặp vấn đề?

Nếu sau khi apply tất cả fixes vẫn gặp vấn đề:

1. **Copy toàn bộ console logs** và gửi để phân tích
2. **Screenshot** màn hình khi messages bị lẫn
3. **Mô tả chi tiết** các bước tái hiện vấn đề:
   - Conversation A ID: ?
   - Conversation B ID: ?
   - Click sequence: A → B → ? → ?
   - Messages seen: ?

---

## 📚 Related Files

- [ZaloChatView.vue](c:\xampp\htdocs\school\resources\js\pages\zalo\components\ZaloChatView.vue) - Main chat component
- [ZaloIndex.vue](c:\xampp\htdocs\school\resources\js\pages\zalo\ZaloIndex.vue) - Parent component
- [ZALO_RACE_CONDITION_FIX.md](c:\xampp\htdocs\school\ZALO_RACE_CONDITION_FIX.md) - Race condition fix documentation

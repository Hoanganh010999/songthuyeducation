# 🔍 Check Frontend Duplicate - Step by Step

## ✅ **Database: OK (chỉ 1 record)**
## ✅ **Backend: OK (broadcast 1 lần)**
## ❌ **Frontend: CÓ VẤN ĐỀ!**

---

## 📋 **Hãy làm theo các bước sau:**

### 1. **Mở Chrome DevTools Console (F12)**

### 2. **Tìm các logs sau trong Console:**

#### A. Component lifecycle:
```
🔵 [ZaloChatView] Component mounted for: <id>
```
**❗ NẾU THẤY LOG NÀY 2 LẦN → Component được mount 2 lần!**

#### B. WebSocket message:
```
📨 [ZaloChatView] onMessage triggered
```
**❗ NẾU THẤY LOG NÀY 2 LẦN CHO CÙNG 1 MESSAGE_ID → Listener bị duplicate!**

#### C. Message được thêm vào UI:
```
✅ [ZaloChatView] Adding new message to UI: <id>
```
**❗ NẾU THẤY LOG NÀY 2 LẦN → Message được push 2 lần!**

---

### 3. **Screenshot Console logs**
Hãy chụp màn hình Console logs sau khi gửi ảnh và share với tôi!

---

## 🎯 **Expected Logs (ĐÚNG):**

```
🔵 [ZaloChatView] Component mounted for: 2269883545780343929
📨 [ZaloChatView] onMessage triggered {
  account_match: true,
  recipient_match: true,
  message_id: 134,
  already_exists: null
}
✅ [ZaloChatView] Adding new message to UI: 134
```

**→ Mỗi log chỉ xuất hiện 1 LẦN DUY NHẤT!**

---

## ❌ **Wrong Logs (LỖI - duplicate):**

### Trường hợp 1: Component mount 2 lần
```
🔵 [ZaloChatView] Component mounted for: 2269883545780343929
🔵 [ZaloChatView] Component mounted for: 2269883545780343929  ← DUPLICATE!
```

### Trường hợp 2: Listener trigger 2 lần
```
📨 [ZaloChatView] onMessage triggered { message_id: 134 }
📨 [ZaloChatView] onMessage triggered { message_id: 134 }  ← DUPLICATE!
```

### Trường hợp 3: Message push 2 lần
```
✅ [ZaloChatView] Adding new message to UI: 134
✅ [ZaloChatView] Adding new message to UI: 134  ← DUPLICATE!
```

---

## 🔧 **Nếu thấy duplicate logs:**

### A. Component mount 2 lần:
**Nguyên nhân:** Parent component render ZaloChatView nhiều lần

**Fix:**
- Check `ZaloIndex.vue` xem có điều kiện `v-if` hoặc `v-show` không đúng
- Check route hoặc tab switching logic

### B. Listener trigger 2 lần:
**Nguyên nhân:** WebSocket listener không được cleanup đúng cách

**Fix:**
- Ensure `onUnmounted()` được gọi đúng
- Check console có thấy "🔴 Component unmounted" không?

### C. Message push 2 lần (không có duplicate logs):
**Nguyên nhân:** Message đã tồn tại trong `messages.value` nhưng check duplicate không hoạt động

**Fix:**
- Check `messages.value.find(m => m.id === newMessage.id)` có return đúng không
- Check `newMessage.id` có đúng type không (number vs string)

---

## 🧪 **Debug Commands:**

### Check trong Console:
```javascript
// 1. Check có bao nhiêu ZaloChatView instances
document.querySelectorAll('[data-zalo-chat-view]').length

// 2. Check messages array
// (Trong Vue DevTools → Components → ZaloChatView → messages)

// 3. Check WebSocket connection
// (Trong Console → Application → WebSocket)
```

---

## ⚡ **Quick Fixes to Try:**

### Fix 1: Hard Refresh
```
Ctrl + Shift + R
hoặc
Ctrl + F5
```

### Fix 2: Clear Site Data
```
1. F12 → Application tab
2. Click "Clear site data"
3. Reload page
```

### Fix 3: Close All Tabs
```
1. Đóng TẤT CẢ các tabs của app
2. Mở 1 tab MỚI DUY NHẤT
3. Test lại
```

---

## 📸 **Hãy share screenshots:**

1. **Console logs** sau khi gửi ảnh
2. **UI hiển thị** (có bao nhiêu ảnh)
3. **Network tab** (POST /api/zalo/messages/send - có bao nhiêu requests?)

---

**Với thông tin này, tôi sẽ xác định chính xác nguyên nhân và fix ngay!**


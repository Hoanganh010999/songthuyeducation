# 🔧 Fix: Ảnh hiển thị 2 lần (Duplicate Messages)

## ❌ **Vấn đề:**

Sau khi gửi ảnh, có **2 ảnh xuất hiện** trong chat:
1. Ảnh không có CDN URL (content rỗng)
2. Ảnh có CDN URL từ Zalo

**Database:**
```json
[
  {"id":131, "message_id":"7224727464602", "content":"https://f25-zpc.zdn.vn/...", "type":"sent"},
  {"id":130, "message_id":"1763129601975", "content":"", "type":"sent"},  ← DUPLICATE!
]
```

---

## 🔍 **Nguyên nhân:**

### Message được lưu & broadcast **2 LẦN:**

```
1. User click "Gửi ảnh"
   ├─ Frontend: uploadImage() → messages.push() (optimistic update)
   │
2. Laravel: sendMessage() gửi ảnh thành công
   ├─ ZaloController: saveSentMessage() → DB record #130
   ├─ Socket.IO: broadcast message #130 → Frontend nhận
   │
3. WebSocket listener: Nhận self-sent message từ Zalo
   ├─ ZaloController: receiveMessage(isSelf=true)
   ├─ ZaloController: saveSentMessage() → DB record #131 (với CDN URL)
   └─ Socket.IO: broadcast message #131 → Frontend nhận

→ Frontend có 3 messages:
  1. Optimistic update (không có DB ID)
  2. Message từ sendMessage broadcast (không có CDN URL)
  3. Message từ WebSocket (có CDN URL)
```

---

## ✅ **Giải pháp:**

### 1. ❌ **Xóa `saveSentMessage` trong `sendMessage` controller**

Không lưu message trong `sendMessage` nữa, để WebSocket xử lý toàn bộ!

```php
// app/Http/Controllers/Api/ZaloController.php

// TRƯỚC (SAI):
$savedMessage = $messageService->saveSentMessage(...);
// → Lưu ngay sau khi gửi → duplicate!

// SAU (ĐÚNG):
// Do NOT save message here!
// Let WebSocket listener handle it to avoid duplicate messages
Log::info('[ZaloController] Message sent successfully, WebSocket will save it');

// Create temporary object for response (not saved to DB)
$savedMessage = (object)[
    'id' => null, // Will be set by WebSocket
    'message_id' => $messageId,
    // ...
];
```

### 2. ❌ **Xóa optimistic update trong frontend**

Không push message vào `messages.value` ngay sau khi gửi!

```javascript
// resources/js/pages/zalo/components/ZaloChatView.vue

// TRƯỚC (SAI):
if (response.data.success) {
  messages.value.push({ ... }); // Optimistic update
  // → Duplicate!
}

// SAU (ĐÚNG):
if (response.data.success) {
  // Do NOT push message here!
  // WebSocket will receive and push it automatically
  console.log('✅ Image sent, waiting for WebSocket');
  
  // Just clear form and show success
  clearImage();
  useSwal.fire({ icon: 'success', ... });
}
```

---

## 🎯 **Flow mới (Correct):**

```
1. User click "Gửi ảnh"
   ├─ Frontend: uploadImage() → gửi request
   ├─ Frontend: Hiển thị success toast
   └─ Frontend: KHÔNG push message (wait for WebSocket)
   
2. Laravel: sendMessage() gửi ảnh thành công
   ├─ ZaloNotificationService: sendImage()
   ├─ zalo-service: Upload & send
   ├─ Laravel: Return success (KHÔNG save to DB)
   └─ Frontend: Nhận response → show success
   
3. WebSocket listener: Nhận self-sent message từ Zalo
   ├─ zalo-service: listener.on('message')
   ├─ zalo-service → Laravel: POST /api/zalo/messages/receive (isSelf=true)
   ├─ ZaloController: receiveMessage(isSelf=true)
   ├─ ZaloController: saveSentMessage() → DB record (với CDN URL)
   ├─ Socket.IO: broadcast message → Frontend
   └─ Frontend: onMessage() → messages.push() → Hiển thị 1 ảnh duy nhất!

→ Chỉ 1 message trong DB
→ Chỉ 1 ảnh hiển thị trong chat!
```

---

## 🧪 **Test:**

**Hard refresh (Ctrl + Shift + R) → Gửi ảnh → Kiểm tra:**

### 1. ✅ Database (chỉ 1 record):
```sql
SELECT * FROM zalo_messages 
WHERE recipient_id = '2269883545780343929' 
ORDER BY id DESC LIMIT 5;

-- Kỳ vọng: Mỗi ảnh CHỈ 1 record với CDN URL!
```

### 2. ✅ Frontend (chỉ 1 ảnh):
- Chọn ảnh → Click gửi
- Thấy "Success" toast
- **Đợi 1-2 giây**
- Ảnh xuất hiện **1 LẦN DUY NHẤT** với CDN URL

### 3. ✅ Logs:
```
[ZaloController] Message sent successfully, WebSocket will save it
  will_be_saved_by_websocket: true

[WebSocket] Received Zalo message:
  isSelf: true
  content: https://f25-zpc.zdn.vn/...

✅ Message saved to database: 132

📡 Message broadcasted via Socket.IO
```

---

## 🎯 **Kỳ vọng sau fix:**

| Metric | Trước | Sau |
|--------|-------|-----|
| DB records | 2 (duplicate) | 1 ✅ |
| Frontend display | 2 ảnh | 1 ảnh ✅ |
| CDN URL | 1 có, 1 không | Tất cả có ✅ |
| Message count | Double | Correct ✅ |

---

## ⚠️ **Lưu ý:**

### Độ trễ nhỏ (~1-2 giây):
- User click "Gửi" → thấy success toast
- Đợi 1-2 giây → ảnh xuất hiện (từ WebSocket)
- **Điều này là BÌNH THƯỜNG và tốt hơn duplicate!**

### Nếu WebSocket không hoạt động:
- Message vẫn được gửi thành công
- Nhưng không xuất hiện trong chat (cần reload)
- Check WebSocket connection status

---

## 🚀 READY TO TEST!
**Hard refresh → Gửi ảnh → Chỉ thấy 1 ảnh xuất hiện!**


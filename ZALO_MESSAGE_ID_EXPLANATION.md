# Giải thích về Message ID trong Zalo System

## 📋 Tổng quan

Hệ thống Zalo sử dụng nhiều loại Message ID khác nhau. Tài liệu này giải thích cách chúng hoạt động và tại sao có thể xảy ra vấn đề khi tìm message cho reaction.

## 🔑 Các loại Message ID

### 1. **msgId** (Message ID chính)
- **Nguồn:** Zalo server tạo ra khi message được gửi
- **Đặc điểm:** 
  - ID duy nhất toàn cục (global unique ID)
  - Không bao giờ thay đổi
  - Được Zalo server quản lý
- **Ví dụ:** `7222313449284`

### 2. **cliMsgId** (Client Message ID)
- **Nguồn:** Client (ứng dụng) tạo ra trước khi gửi
- **Đặc điểm:**
  - ID do client tự tạo
  - Có thể trùng lặp nếu nhiều client cùng tạo
  - Thường là timestamp hoặc random number
- **Ví dụ:** `1763090324075`

### 3. **globalMsgId** và **realMsgId**
- **Nguồn:** Alias của `msgId`
- **Đặc điểm:** Thường bằng `msgId` hoặc là alias

## 🔄 Flow Message ID khi gửi message

### Bước 1: Gửi message qua zalo-api-final
```javascript
// zalo-service/routes/message.js
const result = await zalo.sendMessage(message, threadId, threadType);

// zalo-api-final trả về:
result = {
  message: {
    msgId: "7222313449284",      // ✅ ID từ Zalo server
    cliMsgId: "1763090324075",   // ID từ client (nếu có)
    globalMsgId: "7222313449284",
    realMsgId: "7222313449284",
    // ... other fields
  },
  attachment: { ... }
}
```

### Bước 2: zalo-service extract và trả về Laravel
```javascript
// zalo-service/routes/message.js
const msgId = result?.message?.msgId?.toString();  // Extract msgId
const cliMsgId = result?.message?.cliMsgId?.toString();

res.json({
  success: true,
  data: {
    message_id: msgId,              // ✅ msgId từ Zalo server
    cli_msg_id: cliMsgId,
    all_message_ids: {
      msgId: msgId,
      cliMsgId: cliMsgId,
      globalMsgId: msgId,
    }
  }
});
```

### Bước 3: Laravel lưu vào Database
```php
// app/Services/ZaloMessageService.php
$message = ZaloMessage::updateOrCreate(
    [
        'zalo_account_id' => $account->id,
        'message_id' => $messageId,  // ✅ Lưu msgId vào column message_id
    ],
    [
        'metadata' => [
            'msgId' => $messageId,        // ✅ Lưu msgId vào metadata
            'cliMsgId' => $cliMsgId,
            'globalMsgId' => $messageId,
            'realMsgId' => $messageId,
        ]
    ]
);
```

### Kết quả trong Database:
```
message_id (column): 7222313449284  ✅
metadata[msgId]: 7222313449284      ✅
metadata[cliMsgId]: 1763090324075
```

**✅ KẾT LUẬN:** Message ID trong Database và từ Zalo server **TRÙNG NHAU**

## ⚠️ Vấn đề với Reaction

### Khi nhận Reaction event từ WebSocket:

```javascript
// zalo-service/services/zaloClient.js
listener.on('reaction', (reaction) => {
  // ❌ VẤN ĐỀ: reaction.data.msgId có thể là ID của chính reaction, không phải message!
  const msgId = reaction.data?.msgId?.toString();  // Có thể là reaction ID
  const cliMsgId = reaction.data?.cliMsgId?.toString();
  
  // ✅ GIẢI PHÁP: Extract từ content.rMsg[0]
  if (reaction.data?.content?.rMsg && Array.isArray(reaction.data.content.rMsg)) {
    const rMsg = reaction.data.content.rMsg[0];
    // Message ID thực sự có thể ở đây:
    if (rMsg.cMsg) {
      msgId = rMsg.cMsg.toString();  // ✅ Message ID thực sự
    }
    if (rMsg.msgId) {
      msgId = rMsg.msgId.toString();  // ✅ Hoặc ở đây
    }
  }
});
```

### Cấu trúc Reaction Event:
```json
{
  "actionId": "1379386117296",
  "msgId": "7222314697762",        // ❌ Đây là ID của reaction, không phải message!
  "cliMsgId": "1763090324075",
  "msgType": "chat.reaction",
  "uidFrom": "2269883545780343929",
  "idTo": "422130881766855970",
  "content": {
    "rType": 5,
    "rMsg": [                       // ✅ Message ID thực sự ở đây!
      {
        "cMsg": "7222313449284",    // ✅ Đây mới là message ID thực sự!
        "msgId": "7222313449284",   // ✅ Hoặc ở đây
        "cliMsgId": "1763090324075"
      }
    ]
  }
}
```

## 🔍 So sánh Message ID

### Message được gửi:
- **msgId từ sendMessage:** `7222313449284`
- **Lưu vào Database:** `message_id = 7222313449284` ✅

### Reaction tìm message:
- **reaction.data.msgId:** `7222314697762` ❌ (Đây là ID của reaction, không phải message!)
- **reaction.data.content.rMsg[0].cMsg:** `7222313449284` ✅ (Đây mới là message ID thực sự!)

## ✅ Giải pháp đã triển khai

### 1. Extract Message ID từ content.rMsg
```javascript
// zalo-service/services/zaloClient.js
if (reaction.data?.content?.rMsg && Array.isArray(reaction.data.content.rMsg)) {
  const rMsg = reaction.data.content.rMsg[0];
  if (rMsg.cMsg && !msgId) {
    msgId = rMsg.cMsg.toString();  // ✅ Extract từ rMsg
  }
  if (rMsg.msgId && !msgId) {
    msgId = rMsg.msgId.toString();
  }
  if (rMsg.cliMsgId && !cliMsgId) {
    cliMsgId = rMsg.cliMsgId.toString();
  }
}
```

### 2. Tìm message với nhiều strategies
```php
// app/Services/ZaloMessageFinderService.php
// Strategy 1: Tìm bằng message_id column
// Strategy 2: Tìm bằng metadata[msgId]
// Strategy 3: Tìm bằng metadata[cliMsgId]
// Strategy 4: Tìm trong conversation
// Strategy 5: Tìm bằng cliMsgId như message_id
// Strategy 6: Tìm linh hoạt trong conversation
// Strategy 7: Tìm theo time proximity (10 phút gần nhất)
```

## 📊 Kết luận

1. **Message ID trong Database và từ Zalo server TRÙNG NHAU** ✅
   - `message_id` column = `msgId` từ Zalo server
   - `metadata[msgId]` = `msgId` từ Zalo server

2. **zalo-service ĐANG trả về ID từ server** ✅
   - Extract từ `result.message.msgId`
   - Trả về trong `data.message_id`

3. **Vấn đề với Reaction:**
   - `reaction.data.msgId` là ID của reaction, không phải message
   - Cần extract từ `content.rMsg[0].cMsg` hoặc `content.rMsg[0].msgId`
   - Đã triển khai logic extract này

4. **Giải pháp:**
   - ✅ Extract message ID từ `content.rMsg[0]` trong reaction event
   - ✅ Tìm message với nhiều strategies (7 strategies)
   - ✅ Tìm theo time proximity nếu không tìm thấy


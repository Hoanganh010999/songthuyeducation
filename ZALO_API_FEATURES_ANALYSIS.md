# Phân tích tính năng Reply và Reaction trong zalo-api-final

## 📋 Tổng quan

Sau khi kiểm tra nội dung gốc của [zalo-api-final](https://github.com/hiennguyen270995/zalo-api-final) và codebase hiện tại, đây là báo cáo về các tính năng reply và tương tác tin nhắn.

## ✅ Tính năng có sẵn trong zalo-api-final

### 1. **Reply/Quote Message** ✉️

**Có hỗ trợ:** ✅ CÓ

**Cách sử dụng:**
- `sendMessage` hỗ trợ parameter `quote` trong `MessageContent`
- Cấu trúc `SendMessageQuote`:
  ```typescript
  {
    content: TMessage["content"];
    msgType: TMessage["msgType"];
    propertyExt: TMessage["propertyExt"];
    uidFrom: TMessage["uidFrom"];
    msgId: TMessage["msgId"];
    cliMsgId: TMessage["cliMsgId"];
    ts: TMessage["ts"];
    ttl: TMessage["ttl"];
  }
  ```

**Ví dụ sử dụng:**
```javascript
const zalo = getZaloClient();

// Reply to a message
await zalo.sendMessage({
  msg: "Đây là câu trả lời",
  quote: {
    content: originalMessage.data.content,
    msgType: originalMessage.data.msgType,
    propertyExt: originalMessage.data.propertyExt,
    uidFrom: originalMessage.data.uidFrom,
    msgId: originalMessage.data.msgId,
    cliMsgId: originalMessage.data.cliMsgId,
    ts: originalMessage.data.ts,
    ttl: originalMessage.data.ttl
  }
}, threadId, threadType);
```

### 2. **Reaction (Thêm cảm xúc)** 😊

**Có hỗ trợ:** ✅ CÓ

**Cách sử dụng:**
- Method: `addReaction(icon, destination)`
- Enum `Reactions` có nhiều loại:
  - HEART, LIKE, HAHA, WOW, CRY, ANGRY, KISS
  - TEARS_OF_JOY, ROSE, BROKEN_HEART, DISLIKE, LOVE
  - Và nhiều loại khác (tổng cộng ~50+ reactions)

**Ví dụ sử dụng:**
```javascript
const { Reactions, ThreadType } = require('zalo-api-final');
const zalo = getZaloClient();

// Add reaction to a message
await zalo.addReaction(
  Reactions.HEART, // hoặc Reactions.LIKE, Reactions.HAHA, etc.
  {
    data: {
      msgId: messageId,
      cliMsgId: cliMsgId
    },
    threadId: threadId,
    type: ThreadType.User // hoặc ThreadType.Group
  }
);
```

**Listener events:**
- `reaction` - Khi có reaction mới
- `old_reactions` - Lấy danh sách reactions cũ

### 3. **Auto Reply (Tự động trả lời)** 🤖

**Có hỗ trợ:** ✅ CÓ

**Methods:**
- `createAutoReply(payload)` - Tạo auto reply
- `updateAutoReply(id, payload)` - Cập nhật auto reply
- `deleteAutoReply(id)` - Xóa auto reply
- `getAutoReplyList()` - Lấy danh sách auto reply

**Lưu ý:** Đây là tính năng tự động trả lời dựa trên keyword/pattern, không phải reply một tin nhắn cụ thể.

## 📊 Trạng thái hiện tại trong codebase

### ✅ Đã triển khai:
1. **Gửi tin nhắn cơ bản** - `POST /api/message/send`
2. **Gửi hình ảnh** - `POST /api/message/send-image`
3. **Gửi bulk** - `POST /api/message/send-bulk`
4. **Nhận tin nhắn real-time** - WebSocket listener
5. **Lưu lịch sử chat** - Database persistence

### ❌ Chưa triển khai:
1. **Reply/Quote message** - Chưa có endpoint
2. **Add reaction** - Chưa có endpoint
3. **Get reactions** - Chưa có endpoint
4. **Auto reply management** - Chưa có endpoint

## 🔍 Chi tiết kỹ thuật

### Reply Message Structure

Khi nhận tin nhắn từ WebSocket listener, message object có cấu trúc:
```javascript
{
  type: ThreadType.User | ThreadType.Group,
  threadId: string,
  isSelf: boolean,
  data: {
    msgId: string,
    cliMsgId: string,
    content: string | object,
    msgType: string,
    uidFrom: string,
    idTo: string,
    ts: string,
    ttl: number,
    propertyExt: object,
    quote: TQuote | undefined // Nếu tin nhắn này là reply
  }
}
```

### Reaction Structure

Reaction object từ listener:
```javascript
{
  actionId: string,
  msgId: string,
  cliMsgId: string,
  msgType: string,
  uidFrom: string,
  idTo: string,
  content: {
    rMsg: Array<{ gMsgID, cMsgID, msgType }>,
    rIcon: Reactions,
    rType: number,
    source: number
  },
  ts: string,
  ttl: number
}
```

## 💡 Khuyến nghị triển khai

### 1. Reply Message Endpoint
- **Route:** `POST /api/zalo/messages/reply`
- **Body:** 
  ```json
  {
    "account_id": 1,
    "recipient_id": "user_id",
    "recipient_type": "user",
    "message": "Reply text",
    "reply_to_message_id": "original_msg_id",
    "reply_to_cli_msg_id": "original_cli_msg_id"
  }
  ```
- **Logic:** Lấy thông tin tin nhắn gốc từ database, tạo `SendMessageQuote` object, gọi `sendMessage` với `quote` parameter

### 2. Add Reaction Endpoint
- **Route:** `POST /api/zalo/messages/reaction`
- **Body:**
  ```json
  {
    "account_id": 1,
    "recipient_id": "user_id",
    "recipient_type": "user",
    "message_id": "msg_id",
    "cli_msg_id": "cli_msg_id",
    "reaction": "HEART" // hoặc Reactions enum value
  }
  ```
- **Logic:** Gọi `addReaction` với `Reactions` enum và `AddReactionDestination`

### 3. Get Reactions Endpoint
- **Route:** `GET /api/zalo/messages/{message_id}/reactions`
- **Logic:** Lưu reactions từ WebSocket listener vào database, query và trả về

### 4. Frontend UI
- Thêm nút "Reply" bên cạnh mỗi tin nhắn
- Thêm nút "React" với dropdown các emoji
- Hiển thị quoted message khi có
- Hiển thị reactions dưới mỗi tin nhắn

## 📚 Tài liệu tham khảo

- [zalo-api-final GitHub](https://github.com/hiennguyen270995/zalo-api-final)
- Type definitions: `zalo-service/node_modules/zalo-api-final/dist/`
- API Documentation: `https://hiennguyen270995.github.io/zalo-api-final/`

## ⚠️ Lưu ý

1. **Bảo mật:** Việc sử dụng API không chính thức có thể vi phạm điều khoản dịch vụ của Zalo
2. **Rate Limiting:** Cần implement rate limiting để tránh spam
3. **Error Handling:** Cần xử lý lỗi khi reply/reaction thất bại
4. **Database:** Cần lưu thông tin quote và reactions vào database để hiển thị trong UI


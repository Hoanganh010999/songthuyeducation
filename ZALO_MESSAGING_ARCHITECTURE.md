# Kiến trúc hệ thống nhắn tin Zalo

## Tổng quan

Hệ thống nhắn tin Zalo hiện tại sử dụng kiến trúc **3 tầng** với giao tiếp qua **REST API** và **WebSocket (Socket.IO)**. Không có giao diện chat từ zalo-service, chỉ có API endpoints.

## Kiến trúc tổng thể

```
┌─────────────────────────────────────────────────────────────────┐
│                        FRONTEND (Vue.js)                        │
│  - ZaloChatView.vue                                             │
│  - useZaloSocket.js (Socket.IO client)                          │
│  - REST API calls (axios)                                       │
└──────────────┬──────────────────────────────┬───────────────────┘
               │                              │
               │ REST API (HTTP)               │ WebSocket (Socket.IO)
               │                              │
┌──────────────▼──────────────────────────────▼───────────────────┐
│                    LARAVEL BACKEND (PHP)                         │
│  - ZaloController.php                                            │
│  - ZaloNotificationService.php                                   │
│  - ZaloMessageService.php                                        │
│  - Database (MySQL)                                             │
└──────────────┬──────────────────────────────┬───────────────────┘
               │                              │
               │ REST API (HTTP)               │ WebSocket (Socket.IO)
               │                              │
┌──────────────▼──────────────────────────────▼───────────────────┐
│                  ZALO-SERVICE (Node.js)                         │
│  - routes/message.js (REST API endpoints)                       │
│  - services/zaloClient.js (Zalo WebSocket listener)             │
│  - services/realtimeServer.js (Socket.IO server)                │
│  - zalo-api-final library                                        │
└──────────────┬──────────────────────────────────────────────────┘
               │
               │ Zalo WebSocket (official)
               │
┌──────────────▼──────────────────────────────────────────────────┐
│                      ZALO PLATFORM                               │
└──────────────────────────────────────────────────────────────────┘
```

## Luồng gửi tin nhắn (Outgoing Messages)

### 1. Frontend → Laravel
```
User types message → ZaloChatView.vue
  ↓
POST /api/zalo/messages/send
{
  account_id: 1,
  recipient_id: "user_id",
  recipient_type: "user",
  message: "Hello",
  content_type: "text"
}
```

**File:** `resources/js/pages/zalo/components/ZaloChatView.vue`
- Function: `sendMessage()`
- Line: ~558-638

### 2. Laravel → zalo-service
```
ZaloController::sendMessage()
  ↓
ZaloNotificationService::sendMessage()
  ↓
POST http://localhost:3001/api/message/send
Headers: X-API-Key: school-zalo-2024-secret-key
Body: {
  to: "user_id",
  message: "Hello",
  type: "user"
}
```

**Files:**
- `app/Http/Controllers/Api/ZaloController.php` (line ~1350-1550)
- `app/Services/ZaloNotificationService.php` (line ~200-300)

### 3. zalo-service → Zalo Platform
```
routes/message.js → /send endpoint
  ↓
zalo.sendMessage(message, threadId, threadType)
  ↓
zalo-api-final library
  ↓
Zalo WebSocket (official)
```

**File:** `zalo-service/routes/message.js` (line ~12-100)

### 4. Response flow (ngược lại)
```
Zalo Platform → zalo-service
  ↓
Returns: { messageId: "123456", ... }
  ↓
zalo-service → Laravel
  ↓
Laravel saves to database (ZaloMessage)
  ↓
Laravel → Frontend
  ↓
Frontend adds message to UI
```

## Luồng nhận tin nhắn (Incoming Messages)

### 1. Zalo Platform → zalo-service
```
Zalo WebSocket (official)
  ↓
zalo-api-final listener.on('message')
  ↓
services/zaloClient.js → handleIncomingMessage()
```

**File:** `zalo-service/services/zaloClient.js` (line ~955-1042)

### 2. zalo-service → Laravel
```
handleIncomingMessage()
  ↓
POST http://127.0.0.1:8000/api/zalo/messages/receive
Headers: X-API-Key: school-zalo-2024-secret-key
Body: {
  zalo_id: "account_zalo_id",
  recipient_id: "sender_id",
  recipient_type: "user",
  message_id: "msg_id",
  cli_msg_id: "cli_msg_id",
  content: "Hello",
  content_type: "text",
  sent_at: "2025-11-14T01:00:00Z"
}
```

**File:** `zalo-service/services/zaloClient.js` (line ~1278-1386)

### 3. Laravel xử lý và lưu
```
ZaloController::receiveMessage()
  ↓
ZaloMessageService::saveReceivedMessage()
  ↓
Database: zalo_messages table
  ↓
Response: { success: true, data: {...} }
```

**Files:**
- `app/Http/Controllers/Api/ZaloController.php` (line ~1877-1970)
- `app/Services/ZaloMessageService.php` (line ~73-162)

### 4. zalo-service → Frontend (Real-time)
```
After saving to Laravel
  ↓
services/realtimeServer.js → sendToZaloConversation()
  ↓
Socket.IO server broadcasts
  ↓
Event: 'zalo:message:new'
Room: 'zalo:{account_id}:{recipient_id}'
Data: {
  account_id: 1,
  recipient_id: "user_id",
  recipient_type: "user",
  message: {...}
}
```

**File:** `zalo-service/services/realtimeServer.js` (line ~200-300)

### 5. Frontend nhận real-time
```
useZaloSocket.js → onMessage()
  ↓
ZaloChatView.vue → messages.value.push(newMessage)
  ↓
UI updates automatically
```

**Files:**
- `resources/js/composables/useZaloSocket.js`
- `resources/js/pages/zalo/components/ZaloChatView.vue` (line ~973-985)

## Các loại tin nhắn được hỗ trợ

### 1. Text Message
- **Frontend:** `POST /api/zalo/messages/send` với `message: "text"`
- **zalo-service:** `zalo.sendMessage(text, threadId, threadType)`

### 2. Image Message
- **Frontend:** Upload image → `POST /api/zalo/messages/upload-image` → `POST /api/zalo/messages/send` với `content_type: "image"`
- **zalo-service:** `zalo.sendImage(threadId, imageUrl, threadType)`

### 3. File Message
- **Frontend:** Upload file → `POST /api/zalo/messages/upload-file` → `POST /api/zalo/messages/send` với `content_type: "file"`
- **zalo-service:** `zalo.sendMessage()` với attachment

### 4. Link Message
- **Frontend:** Auto-detect URL → `POST /api/zalo/messages/send` với `content_type: "link"`
- **zalo-service:** `zalo.sendMessage()` với link

### 5. Reply Message (Quote)
- **Frontend:** `POST /api/zalo/messages/reply` với `reply_to_message_id`
- **zalo-service:** `zalo.sendMessage()` với quote data

### 6. Reaction
- **Frontend:** `POST /api/zalo/messages/reaction` với `reaction_icon`
- **zalo-service:** `zalo.sendReaction(threadId, messageId, reactionIcon, threadType)`

## WebSocket Architecture

### Socket.IO Server (trong zalo-service)
- **Port:** 3001 (cùng với REST API)
- **File:** `zalo-service/services/realtimeServer.js`
- **Rooms:**
  - `zalo:account:{account_id}` - Account room (conversation updates)
  - `zalo:{account_id}:{recipient_id}` - Conversation room (messages)

### Socket.IO Client (trong frontend)
- **File:** `resources/js/composables/useZaloSocket.js`
- **Connection:** `http://localhost:3001` (hoặc từ config)
- **Events:**
  - `zalo:message:new` - New message received
  - `zalo:reaction:new` - New reaction received
  - `zalo:conversation:updated` - Conversation list updated

## Database Schema

### zalo_messages
- `id` - Primary key
- `zalo_account_id` - Foreign key to zalo_accounts
- `message_id` - Zalo message ID (unique per account)
- `type` - 'sent' or 'received'
- `recipient_id` - User ID or Group ID
- `recipient_type` - 'user' or 'group'
- `content` - Message text
- `content_type` - 'text', 'image', 'file', 'link'
- `media_url` - URL to media file
- `metadata` - JSON (contains cliMsgId, quote data, etc.)
- `sent_at` - Timestamp
- `created_at`, `updated_at`

### zalo_message_reactions
- `id` - Primary key
- `zalo_message_id` - Foreign key to zalo_messages
- `zalo_user_id` - User who reacted
- `reaction` - Reaction icon (e.g., '/-heart')
- `created_at`, `updated_at`

## API Endpoints

### Laravel API (Frontend → Laravel)

#### Gửi tin nhắn
```
POST /api/zalo/messages/send
Body: {
  account_id: number,
  recipient_id: string,
  recipient_type: 'user' | 'group',
  message: string,
  content_type?: 'text' | 'image' | 'file' | 'link',
  media_url?: string
}
```

#### Reply tin nhắn
```
POST /api/zalo/messages/reply
Body: {
  account_id: number,
  recipient_id: string,
  recipient_type: 'user' | 'group',
  message: string,
  reply_to_message_id: number,
  reply_to_zalo_message_id: string
}
```

#### Thêm reaction
```
POST /api/zalo/messages/reaction
Body: {
  account_id: number,
  message_id: number,
  reaction_icon: string
}
```

#### Load tin nhắn
```
GET /api/zalo/messages
Params: {
  account_id: number,
  recipient_id: string,
  recipient_type: 'user' | 'group',
  before_date?: string,
  limit?: number
}
```

### zalo-service API (Laravel → zalo-service)

#### Gửi tin nhắn
```
POST /api/message/send
Headers: X-API-Key: school-zalo-2024-secret-key
Body: {
  to: string,
  message: string,
  type: 'user' | 'group'
}
```

#### Gửi hình ảnh
```
POST /api/message/send-image
Headers: X-API-Key: school-zalo-2024-secret-key
Body: {
  to: string,
  imageUrl: string,
  type: 'user' | 'group'
}
```

#### Reply với quote
```
POST /api/message/reply
Headers: X-API-Key: school-zalo-2024-secret-key
Body: {
  to: string,
  message: string,
  type: 'user' | 'group',
  quote: {
    cliMsgId: string,
    globalMsgId: string,
    msg: string,
    cliMsgType: string,
    ts: number,
    ownerId: string,
    ttl: number
  }
}
```

#### Thêm reaction
```
POST /api/message/reaction
Headers: X-API-Key: school-zalo-2024-secret-key
Body: {
  to: string,
  messageId: string,
  reactionIcon: string,
  type: 'user' | 'group'
}
```

### Laravel API (zalo-service → Laravel)

#### Nhận tin nhắn từ Zalo
```
POST /api/zalo/messages/receive
Headers: X-API-Key: school-zalo-2024-secret-key
Body: {
  zalo_id: string,
  recipient_id: string,
  recipient_type: 'user' | 'group',
  message_id: string,
  cli_msg_id: string,
  content: string,
  content_type: string,
  media_url?: string,
  quote?: object,
  sent_at: string
}
```

#### Nhận reaction từ Zalo
```
POST /api/zalo/messages/receive-reaction
Headers: X-API-Key: school-zalo-2024-secret-key
Body: {
  zalo_id: string,
  message_id: string,
  cli_msg_id: string,
  recipient_id: string,
  recipient_type: 'user' | 'group',
  user_id: string,
  reaction_icon: string,
  reaction_type: number,
  reaction_source: number,
  reaction_data: object,
  reacted_at: string
}
```

## Kết luận

### Hệ thống hiện tại:
✅ **Sử dụng Frontend Laravel (Vue.js) + REST API + WebSocket (Socket.IO)**
- Frontend gọi REST API đến Laravel
- Laravel gọi REST API đến zalo-service
- zalo-service gọi Zalo API và lắng nghe WebSocket
- Real-time updates qua Socket.IO

### Không sử dụng:
❌ **Giao diện chat từ zalo-service**
- zalo-service chỉ cung cấp API endpoints
- Không có UI/interface từ zalo-service
- Tất cả UI đều từ Laravel frontend (Vue.js)

### Ưu điểm của kiến trúc hiện tại:
1. **Tách biệt rõ ràng:** Frontend, Backend, và Zalo service độc lập
2. **Scalable:** Có thể scale từng component riêng biệt
3. **Real-time:** Socket.IO đảm bảo tin nhắn real-time
4. **Database caching:** Lưu tin nhắn vào database để tải nhanh
5. **Multi-account:** Hỗ trợ nhiều tài khoản Zalo

### Cải thiện có thể:
1. **Message queue:** Sử dụng Redis/RabbitMQ cho message queuing
2. **Load balancing:** Nếu có nhiều zalo-service instances
3. **Caching:** Redis cache cho tin nhắn thường dùng
4. **Rate limiting:** Giới hạn số tin nhắn gửi/phút


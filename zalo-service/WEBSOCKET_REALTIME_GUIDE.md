# WebSocket Realtime Guide

## 📋 Tổng quan

### WebSocket hiện tại (Zalo API)
- **Mục đích**: Duy trì kết nối với Zalo servers để tránh logout
- **Không thể dùng cho**: Realtime comment, chat, notifications
- **Lý do**: Đây là WebSocket client kết nối tới Zalo, không phải server

### WebSocket Server cần thiết
- **Mục đích**: Tạo WebSocket server riêng cho ứng dụng
- **Có thể dùng cho**: 
  - ✅ Realtime comments
  - ✅ Chat messages
  - ✅ Notifications
  - ✅ Live updates
  - ✅ Typing indicators

## 🏗️ Kiến trúc

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│   Browser   │◄───────►│ WebSocket    │◄───────►│   Laravel   │
│   (Vue.js)  │  WS     │   Server     │  HTTP   │   Backend   │
└─────────────┘         └──────────────┘         └─────────────┘
                              │
                              │ (Broadcast events)
                              ▼
                        ┌──────────────┐
                        │   Database   │
                        └──────────────┘
```

## 🚀 Triển khai

### Option 1: Tích hợp vào zalo-service (Đơn giản)

**Ưu điểm**: 
- Chỉ cần 1 service
- Dễ quản lý
- Chia sẻ authentication

**Nhược điểm**:
- Coupling với Zalo service
- Khó scale riêng

### Option 2: Service riêng (Khuyến nghị)

**Ưu điểm**:
- Tách biệt concerns
- Dễ scale độc lập
- Có thể tắt Zalo service mà không ảnh hưởng realtime

**Nhược điểm**:
- Cần quản lý 2 services
- Cần sync authentication

## 🌐 Triển khai trên Hosting

### Với SSH/VPN

✅ **Hoàn toàn được!** SSH/VPN không ảnh hưởng WebSocket.

**Yêu cầu**:
1. **Port forwarding**: Mở port WebSocket (ví dụ: 3002)
2. **Reverse proxy**: Nginx/Apache để route WebSocket
3. **Firewall**: Cho phép WebSocket connections
4. **Process manager**: PM2 để keep service alive

### Cấu hình Nginx

```nginx
# WebSocket Server
upstream websocket {
    server 127.0.0.1:3002;
}

server {
    listen 80;
    server_name yourdomain.com;

    # WebSocket endpoint
    location /socket.io/ {
        proxy_pass http://websocket;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400; # 24 hours
    }

    # Laravel API
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### Cấu hình PM2

```json
{
  "apps": [
    {
      "name": "zalo-service",
      "script": "server.js",
      "cwd": "/path/to/zalo-service",
      "instances": 1,
      "exec_mode": "fork",
      "env": {
        "NODE_ENV": "production",
        "PORT": 3001
      }
    },
    {
      "name": "realtime-service",
      "script": "server.js",
      "cwd": "/path/to/realtime-service",
      "instances": 1,
      "exec_mode": "fork",
      "env": {
        "NODE_ENV": "production",
        "PORT": 3002
      }
    }
  ]
}
```

## 🔒 Security

### Authentication
- Sử dụng JWT token từ Laravel
- Verify token trên WebSocket server
- Disconnect nếu token invalid

### Rate Limiting
- Giới hạn messages per second
- Prevent spam/abuse

### CORS
- Chỉ cho phép domain của bạn
- Validate origin

## 📊 Monitoring

### Health Check
```javascript
// Health check endpoint
app.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    connections: io.engine.clientsCount,
    uptime: process.uptime()
  });
});
```

### Logging
- Log connections/disconnections
- Log errors
- Monitor performance

## 🎯 Use Cases

### 1. Realtime Comments
```javascript
// Client sends comment
socket.emit('comment:create', {
  postId: 123,
  content: 'Great post!'
});

// Server broadcasts to all clients viewing that post
io.to(`post:${postId}`).emit('comment:new', commentData);
```

### 2. Chat Messages
```javascript
// Send message
socket.emit('message:send', {
  to: userId,
  message: 'Hello!'
});

// Receive message
socket.on('message:receive', (data) => {
  // Display message
});
```

### 3. Typing Indicators
```javascript
// User is typing
socket.emit('typing:start', { chatId: 123 });

// Broadcast to other users
socket.to(`chat:${chatId}`).emit('typing:user', { userId });
```

## ⚠️ Lưu ý

1. **WebSocket không phải HTTP**: Cần reverse proxy đúng cách
2. **Connection limits**: Mỗi server có giới hạn connections
3. **Memory usage**: Mỗi connection tốn memory
4. **Reconnection**: Client cần handle reconnection
5. **Load balancing**: Cần sticky sessions hoặc Redis adapter

## 🔄 Next Steps

1. ✅ Cài đặt Socket.io
2. ✅ Tạo WebSocket server
3. ✅ Tích hợp authentication
4. ✅ Implement realtime features
5. ✅ Deploy và test


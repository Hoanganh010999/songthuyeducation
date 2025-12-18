# Client-side WebSocket Usage

## 📦 Cài đặt

```bash
npm install socket.io-client
```

## 🔌 Kết nối

```javascript
import { io } from 'socket.io-client';

// Lấy JWT token từ Laravel (sau khi login)
const token = localStorage.getItem('auth_token'); // hoặc từ auth store

// Kết nối tới WebSocket server
const socket = io('http://localhost:3001', {
  auth: {
    token: token
  },
  transports: ['websocket', 'polling'],
  reconnection: true,
  reconnectionDelay: 1000,
  reconnectionAttempts: 5
});

// Xử lý kết nối
socket.on('connect', () => {
  console.log('✅ Connected to WebSocket server');
  console.log('Socket ID:', socket.id);
});

socket.on('connected', (data) => {
  console.log('Connection confirmed:', data);
});

socket.on('disconnect', (reason) => {
  console.log('❌ Disconnected:', reason);
  if (reason === 'io server disconnect') {
    // Server disconnected, need to reconnect manually
    socket.connect();
  }
});

socket.on('error', (error) => {
  console.error('WebSocket error:', error);
});
```

## 💬 Realtime Comments

### Join post room để nhận updates

```javascript
// Khi vào trang post
const postId = 123;
socket.emit('post:join', { postId });

socket.on('post:joined', (data) => {
  console.log('Joined post room:', data.postId);
});

// Nhận comment mới
socket.on('comment:new', (data) => {
  console.log('New comment:', data);
  // Update UI với comment mới
  addCommentToUI(data.comment);
});

// Nhận comment updated
socket.on('comment:updated', (data) => {
  updateCommentInUI(data.commentId, data.comment);
});

// Nhận comment deleted
socket.on('comment:deleted', (data) => {
  removeCommentFromUI(data.commentId);
});

// Rời post room khi rời trang
socket.emit('post:leave', { postId });
```

### Gửi comment mới (từ Laravel API, sau đó broadcast)

```javascript
// 1. Gửi comment qua Laravel API
const response = await axios.post(`/api/course/classes/${classId}/posts/${postId}/comments`, {
  content: 'Great post!'
});

// 2. Laravel sẽ broadcast qua WebSocket
// Client sẽ nhận qua event 'comment:new'
```

### Typing indicator

```javascript
let typingTimeout;

// Khi user đang gõ
const handleTyping = () => {
  socket.emit('typing:start', { postId: 123 });
  
  // Clear timeout
  clearTimeout(typingTimeout);
  
  // Stop typing sau 3 giây không gõ
  typingTimeout = setTimeout(() => {
    socket.emit('typing:stop', { postId: 123 });
  }, 3000);
};

// Nhận typing events từ users khác
socket.on('typing:user', (data) => {
  if (data.isTyping) {
    showTypingIndicator(data.userId, data.userName);
  } else {
    hideTypingIndicator(data.userId);
  }
});
```

## 📨 Chat Messages

### Join chat room

```javascript
const chatId = 456;
socket.emit('chat:join', { chatId });

socket.on('chat:joined', (data) => {
  console.log('Joined chat:', data.chatId);
});
```

### Gửi message

```javascript
socket.emit('message:send', {
  chatId: 456,
  message: 'Hello!'
});

// Hoặc private message
socket.emit('message:send', {
  toUserId: 789,
  message: 'Hi there!'
});
```

### Nhận message

```javascript
socket.on('message:receive', (data) => {
  console.log('New message:', data);
  displayMessage(data);
});
```

### Typing trong chat

```javascript
socket.emit('typing:start', { chatId: 456 });

socket.on('typing:user', (data) => {
  if (data.isTyping) {
    showTypingInChat(data.userId, data.userName);
  }
});
```

## 🔔 Notifications

```javascript
socket.on('notification:receive', (notification) => {
  console.log('New notification:', notification);
  showNotification(notification);
});
```

## 👥 User Status

```javascript
// User online
socket.on('user:online', (data) => {
  updateUserStatus(data.userId, 'online');
});

// User offline
socket.on('user:offline', (data) => {
  updateUserStatus(data.userId, 'offline');
});
```

## 🎯 Vue.js Integration Example

```vue
<template>
  <div>
    <!-- Comments section -->
    <div v-for="comment in comments" :key="comment.id">
      {{ comment.content }}
    </div>
    
    <!-- Typing indicator -->
    <div v-if="typingUsers.length > 0">
      {{ typingUsers.join(', ') }} đang gõ...
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { io } from 'socket.io-client';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const comments = ref([]);
const typingUsers = ref([]);

let socket = null;

onMounted(() => {
  // Kết nối
  socket = io('http://localhost:3001', {
    auth: {
      token: authStore.token
    }
  });

  // Join post room
  const postId = 123;
  socket.emit('post:join', { postId });

  // Listen events
  socket.on('comment:new', (data) => {
    comments.value.push(data.comment);
  });

  socket.on('typing:user', (data) => {
    if (data.isTyping) {
      if (!typingUsers.value.includes(data.userName)) {
        typingUsers.value.push(data.userName);
      }
    } else {
      typingUsers.value = typingUsers.value.filter(u => u !== data.userName);
    }
  });
});

onUnmounted(() => {
  if (socket) {
    socket.emit('post:leave', { postId: 123 });
    socket.disconnect();
  }
});
</script>
```

## 🔄 Reconnection Handling

```javascript
socket.on('reconnect', (attemptNumber) => {
  console.log('Reconnected after', attemptNumber, 'attempts');
  // Rejoin rooms
  socket.emit('post:join', { postId: 123 });
});

socket.on('reconnect_attempt', (attemptNumber) => {
  console.log('Reconnection attempt', attemptNumber);
});

socket.on('reconnect_error', (error) => {
  console.error('Reconnection error:', error);
});

socket.on('reconnect_failed', () => {
  console.error('Failed to reconnect');
  // Show error message to user
});
```

## 🛠️ Helper Functions

```javascript
// Tạo socket instance reusable
export function createSocket(token) {
  return io('http://localhost:3001', {
    auth: { token },
    transports: ['websocket', 'polling'],
    reconnection: true
  });
}

// Join post room helper
export function joinPostRoom(socket, postId) {
  socket.emit('post:join', { postId });
}

// Leave post room helper
export function leavePostRoom(socket, postId) {
  socket.emit('post:leave', { postId });
}
```

## 📝 Notes

1. **Token Authentication**: Luôn gửi JWT token khi kết nối
2. **Room Management**: Join/leave rooms khi vào/rời trang
3. **Error Handling**: Xử lý lỗi và reconnection
4. **Memory Leaks**: Disconnect socket khi component unmount
5. **Production URL**: Thay `localhost:3001` bằng production URL


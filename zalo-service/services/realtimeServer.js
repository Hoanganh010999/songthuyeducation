const { Server } = require('socket.io');
const jwt = require('jsonwebtoken');

let io = null;
const connectedUsers = new Map(); // userId -> socketId[]
const userRooms = new Map(); // socketId -> Set of rooms

/**
 * Initialize WebSocket server for realtime features
 * @param {http.Server} httpServer - HTTP server instance
 * @param {string} laravelUrl - Laravel backend URL for CORS
 */
function initializeRealtimeServer(httpServer, laravelUrl = 'http://127.0.0.1:8000') {
  if (io) {
    console.log('⚠️  Realtime server already initialized');
    return io;
  }

  console.log('🔌 Initializing WebSocket server for realtime features...');

  // Create Socket.IO server
  io = new Server(httpServer, {
    cors: {
      origin: laravelUrl,
      methods: ['GET', 'POST'],
      credentials: true
    },
    transports: ['websocket', 'polling'],
    pingTimeout: 60000,
    pingInterval: 25000
  });

  // Authentication middleware
  io.use(async (socket, next) => {
    try {
      const token = socket.handshake.auth.token || socket.handshake.headers.authorization?.replace('Bearer ', '');
      
      if (!token) {
        return next(new Error('Authentication token required'));
      }

      // Verify token with Laravel (you'll need to implement this endpoint)
      // For now, we'll use a simple JWT verification
      // In production, verify with Laravel API
      try {
        // Option 1: Verify with Laravel API
        const response = await fetch(`${laravelUrl}/api/auth/verify-token`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        });

        if (!response.ok) {
          return next(new Error('Invalid token'));
        }

        const result = await response.json();
        if (result.success && result.user) {
          socket.userId = result.user.id;
          socket.user = result.user;
          next();
        } else {
          return next(new Error('Invalid token response'));
        }
      } catch (error) {
        // Fallback: Simple JWT decode (not secure, only for development)
        // In production, always verify with Laravel
        console.warn('⚠️  Token verification failed, using fallback (development only)');
        try {
          const decoded = jwt.decode(token);
          if (decoded && decoded.sub) {
            socket.userId = decoded.sub;
            socket.user = { id: decoded.sub, name: decoded.name || 'User' };
            next();
          } else {
            next(new Error('Invalid token'));
          }
        } catch (e) {
          next(new Error('Invalid token'));
        }
      }
    } catch (error) {
      next(new Error('Authentication failed: ' + error.message));
    }
  });

  // Connection handler
  io.on('connection', (socket) => {
    const userId = socket.userId;
    console.log(`✅ User connected: ${userId} (socket: ${socket.id})`);

    // Track user connections
    if (!connectedUsers.has(userId)) {
      connectedUsers.set(userId, []);
    }
    connectedUsers.get(userId).push(socket.id);

    // Join user's personal room
    socket.join(`user:${userId}`);

    // Emit connection status to user
    socket.emit('connected', {
      userId,
      socketId: socket.id,
      timestamp: new Date().toISOString()
    });

    // Notify other users that this user is online (optional)
    socket.broadcast.emit('user:online', { userId });

    // ========== COMMENT EVENTS ==========

    // Join post room to receive comment updates
    socket.on('post:join', (data) => {
      const { postId } = data;
      if (!postId) {
        socket.emit('error', { message: 'postId required' });
        return;
      }

      const room = `post:${postId}`;
      socket.join(room);
      
      if (!userRooms.has(socket.id)) {
        userRooms.set(socket.id, new Set());
      }
      userRooms.get(socket.id).add(room);

      console.log(`📝 User ${userId} joined post room: ${room}`);
      socket.emit('post:joined', { postId });
    });

    // Leave post room
    socket.on('post:leave', (data) => {
      const { postId } = data;
      if (postId) {
        const room = `post:${postId}`;
        socket.leave(room);
        if (userRooms.has(socket.id)) {
          userRooms.get(socket.id).delete(room);
        }
        console.log(`📝 User ${userId} left post room: ${room}`);
      }
    });

    // New comment created (broadcast to all users viewing the post)
    socket.on('comment:create', async (data) => {
      const { postId, comment } = data;
      
      if (!postId || !comment) {
        socket.emit('error', { message: 'postId and comment required' });
        return;
      }

      // Broadcast to all users in the post room (except sender)
      socket.to(`post:${postId}`).emit('comment:new', {
        postId,
        comment: {
          ...comment,
          user: socket.user,
          created_at: new Date().toISOString()
        }
      });

      console.log(`💬 Comment created on post ${postId} by user ${userId}`);
    });

    // Comment updated
    socket.on('comment:update', (data) => {
      const { postId, commentId, comment } = data;
      
      socket.to(`post:${postId}`).emit('comment:updated', {
        postId,
        commentId,
        comment
      });
    });

    // Comment deleted
    socket.on('comment:delete', (data) => {
      const { postId, commentId } = data;
      
      socket.to(`post:${postId}`).emit('comment:deleted', {
        postId,
        commentId
      });
    });

    // ========== ZALO EVENTS ==========

    // Join Zalo conversation room
    socket.on('zalo:conversation:join', (data) => {
      const { account_id, recipient_id } = data;
      if (!account_id || !recipient_id) {
        socket.emit('error', { message: 'account_id and recipient_id required' });
        return;
      }

      const room = `zalo:${account_id}:${recipient_id}`;
      socket.join(room);
      
      if (!userRooms.has(socket.id)) {
        userRooms.set(socket.id, new Set());
      }
      userRooms.get(socket.id).add(room);

      console.log(`💬 User ${userId} joined Zalo conversation: ${room}`);
      socket.emit('zalo:conversation:joined', { account_id, recipient_id });
    });

    // Leave Zalo conversation room
    socket.on('zalo:conversation:leave', (data) => {
      const { account_id, recipient_id } = data;
      if (account_id && recipient_id) {
        const room = `zalo:${account_id}:${recipient_id}`;
        socket.leave(room);
        if (userRooms.has(socket.id)) {
          userRooms.get(socket.id).delete(room);
        }
        console.log(`💬 User ${userId} left Zalo conversation: ${room}`);
      }
    });

    // Join Zalo account room (for conversation list updates)
    socket.on('zalo:account:join', (data) => {
      const { account_id } = data;
      if (!account_id) {
        socket.emit('error', { message: 'account_id required' });
        return;
      }

      const room = `zalo:account:${account_id}`;
      socket.join(room);
      
      if (!userRooms.has(socket.id)) {
        userRooms.set(socket.id, new Set());
      }
      userRooms.get(socket.id).add(room);

      console.log(`📋 User ${userId} joined Zalo account room: ${room}`);
      socket.emit('zalo:account:joined', { account_id });
    });

    // Leave Zalo account room
    socket.on('zalo:account:leave', (data) => {
      const { account_id } = data;
      if (account_id) {
        const room = `zalo:account:${account_id}`;
        socket.leave(room);
        if (userRooms.has(socket.id)) {
          userRooms.get(socket.id).delete(room);
        }
        console.log(`📋 User ${userId} left Zalo account room: ${room}`);
      }
    });

    // ========== CLASSROOM EVENTS ==========

    // Join classroom room
    socket.on('classroom:join', (data) => {
      const { class_id } = data;
      if (!class_id) {
        socket.emit('error', { message: 'class_id required' });
        return;
      }

      const room = `classroom:${class_id}`;
      socket.join(room);

      if (!userRooms.has(socket.id)) {
        userRooms.set(socket.id, new Set());
      }
      userRooms.get(socket.id).add(room);

      console.log(`📚 User ${userId} joined classroom: ${room}`);
      socket.emit('classroom:joined', { class_id });
    });

    // Leave classroom room
    socket.on('classroom:leave', (data) => {
      const { class_id } = data;
      if (class_id) {
        const room = `classroom:${class_id}`;
        socket.leave(room);
        if (userRooms.has(socket.id)) {
          userRooms.get(socket.id).delete(room);
        }
        console.log(`📚 User ${userId} left classroom: ${room}`);
      }
    });

    // ========== CHAT EVENTS ==========

    // Join chat room
    socket.on('chat:join', (data) => {
      const { chatId } = data;
      if (!chatId) {
        socket.emit('error', { message: 'chatId required' });
        return;
      }

      const room = `chat:${chatId}`;
      socket.join(room);
      
      if (!userRooms.has(socket.id)) {
        userRooms.set(socket.id, new Set());
      }
      userRooms.get(socket.id).add(room);

      console.log(`💬 User ${userId} joined chat room: ${room}`);
      socket.emit('chat:joined', { chatId });
    });

    // Send message
    socket.on('message:send', (data) => {
      const { chatId, message, toUserId } = data;
      
      if (!message) {
        socket.emit('error', { message: 'message required' });
        return;
      }

      const messageData = {
        from: userId,
        fromUser: socket.user,
        message,
        timestamp: new Date().toISOString()
      };

      if (chatId) {
        // Group chat - broadcast to chat room
        socket.to(`chat:${chatId}`).emit('message:receive', {
          chatId,
          ...messageData
        });
      } else if (toUserId) {
        // Private message - send to specific user
        io.to(`user:${toUserId}`).emit('message:receive', {
          toUserId,
          ...messageData
        });
      } else {
        socket.emit('error', { message: 'chatId or toUserId required' });
        return;
      }

      console.log(`📨 Message sent by user ${userId}`);
    });

    // Typing indicator
    socket.on('typing:start', (data) => {
      const { chatId, postId } = data;
      
      if (chatId) {
        socket.to(`chat:${chatId}`).emit('typing:user', {
          chatId,
          userId,
          userName: socket.user.name,
          isTyping: true
        });
      } else if (postId) {
        socket.to(`post:${postId}`).emit('typing:user', {
          postId,
          userId,
          userName: socket.user.name,
          isTyping: true
        });
      }
    });

    socket.on('typing:stop', (data) => {
      const { chatId, postId } = data;
      
      if (chatId) {
        socket.to(`chat:${chatId}`).emit('typing:user', {
          chatId,
          userId,
          isTyping: false
        });
      } else if (postId) {
        socket.to(`post:${postId}`).emit('typing:user', {
          postId,
          userId,
          isTyping: false
        });
      }
    });

    // ========== NOTIFICATION EVENTS ==========

    // Send notification to user
    socket.on('notification:send', (data) => {
      const { toUserId, notification } = data;
      
      if (!toUserId || !notification) {
        socket.emit('error', { message: 'toUserId and notification required' });
        return;
      }

      io.to(`user:${toUserId}`).emit('notification:receive', {
        ...notification,
        timestamp: new Date().toISOString()
      });
    });

    // ========== DISCONNECTION ==========

    socket.on('disconnect', (reason) => {
      console.log(`❌ User disconnected: ${userId} (socket: ${socket.id}) - Reason: ${reason}`);

      // Remove from connected users
      if (connectedUsers.has(userId)) {
        const sockets = connectedUsers.get(userId);
        const index = sockets.indexOf(socket.id);
        if (index > -1) {
          sockets.splice(index, 1);
        }
        if (sockets.length === 0) {
          connectedUsers.delete(userId);
          // Notify other users that this user is offline
          socket.broadcast.emit('user:offline', { userId });
        }
      }

      // Clean up rooms
      userRooms.delete(socket.id);
    });
  });

  console.log('✅ WebSocket server initialized successfully');
  console.log('   Ready for realtime connections');

  return io;
}

/**
 * Get Socket.IO instance
 */
function getIO() {
  return io;
}

/**
 * Broadcast event to all connected clients
 */
function broadcast(event, data) {
  if (io) {
    io.emit(event, data);
  }
}

/**
 * Send event to specific user
 */
function sendToUser(userId, event, data) {
  if (io) {
    io.to(`user:${userId}`).emit(event, data);
  }
}

/**
 * Send event to post room
 */
function sendToPost(postId, event, data) {
  if (io) {
    io.to(`post:${postId}`).emit(event, data);
  }
}

/**
 * Send event to chat room
 */
function sendToChat(chatId, event, data) {
  if (io) {
    io.to(`chat:${chatId}`).emit(event, data);
  }
}

/**
 * Send event to Zalo conversation room
 */
function sendToZaloConversation(accountId, recipientId, event, data) {
  if (io) {
    const room = `zalo:${accountId}:${recipientId}`;
    io.to(room).emit(event, data);
  }
}

/**
 * Send event to Zalo account room (for conversation list updates)
 */
function sendToZaloAccount(accountId, event, data) {
  if (io) {
    const room = `zalo:account:${accountId}`;
    io.to(room).emit(event, data);
  }
}

/**
 * Get connected users count
 */
function getConnectedUsersCount() {
  return connectedUsers.size;
}

/**
 * Get total connections count
 */
function getTotalConnections() {
  if (!io) return 0;
  return io.engine.clientsCount;
}

/**
 * Broadcast event to all connected clients
 */
function broadcastToAll(event, data) {
  if (!io) {
    console.warn('[RealtimeServer] Cannot broadcast: Socket.IO not initialized');
    return;
  }
  
  console.log(`📡 [RealtimeServer] Broadcasting to all: ${event}`);
  io.emit(event, data);
}

/**
 * Broadcast event to specific Zalo account room
 */
function broadcastToAccount(accountId, event, data) {
  if (!io) {
    console.warn('[RealtimeServer] Cannot broadcast: Socket.IO not initialized');
    return;
  }

  const room = `zalo:account:${accountId}`;
  console.log(`📡 [RealtimeServer] Broadcasting to account ${accountId}: ${event}`);
  io.to(room).emit(event, data);
}

/**
 * Send event to classroom room
 */
function sendToClassroom(classId, event, data) {
  if (!io) {
    console.warn('[RealtimeServer] Cannot send to classroom: Socket.IO not initialized');
    return;
  }

  const room = `classroom:${classId}`;
  console.log(`📡 [RealtimeServer] Sending to classroom ${classId}: ${event}`);
  io.to(room).emit(event, data);
}

/**
 * Send event to a specific room
 */
function emitToRoom(room, event, data) {
  if (!io) {
    console.warn('[RealtimeServer] Cannot emit to room: Socket.IO not initialized');
    return;
  }

  console.log(`📡 [RealtimeServer] Emitting to room ${room}: ${event}`);
  io.to(room).emit(event, data);
}

module.exports = {
  initializeRealtimeServer,
  getIO,
  broadcast,
  sendToUser,
  sendToPost,
  sendToChat,
  sendToZaloConversation,
  sendToZaloAccount,
  getConnectedUsersCount,
  getTotalConnections,
  broadcastToAll,
  broadcastToAccount,
  sendToClassroom,
  emitToRoom
};


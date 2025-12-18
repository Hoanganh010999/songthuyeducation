import { ref } from 'vue';
import { io } from 'socket.io-client';
import { useAuthStore } from '../stores/auth';

// Singleton socket instance shared across all features
let sharedSocket = null;
const isConnected = ref(false);
const reconnectAttempts = ref(0);
const maxReconnectAttempts = 5;
let heartbeatInterval = null;

// Store pending rooms to join when connection is established
const pendingRooms = new Set();

/**
 * Shared WebSocket composable - Singleton pattern
 * Used by all features (Zalo, Classroom, etc.) to share a single connection
 */
export function useWebSocket() {
  const authStore = useAuthStore();

  /**
   * Connect to WebSocket server (singleton - only one connection)
   */
  const connect = () => {
    if (sharedSocket?.connected) {
      console.log('📡 [WebSocket] Already connected');
      return sharedSocket;
    }

    if (sharedSocket) {
      console.log('🔄 [WebSocket] Reconnecting...');
      sharedSocket.connect();
      return sharedSocket;
    }

    const token = authStore.token || localStorage.getItem('auth_token');
    if (!token) {
      console.warn('⚠️  [WebSocket] No auth token, cannot connect');
      return null;
    }

    const wsUrl = import.meta.env.VITE_WS_URL || 'http://localhost:3001';

    console.log('🔌 [WebSocket] Creating new shared connection to:', wsUrl);

    sharedSocket = io(wsUrl, {
      auth: {
        token: token
      },
      transports: ['websocket', 'polling'],
      reconnection: true,
      reconnectionDelay: 1000,
      reconnectionAttempts: maxReconnectAttempts,
      reconnectionDelayMax: 5000,
    });

    // Connection events
    sharedSocket.on('connect', () => {
      console.log('✅ [WebSocket] Connected:', sharedSocket.id);
      isConnected.value = true;
      reconnectAttempts.value = 0;

      // Start heartbeat to detect connection loss
      if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
      }
      heartbeatInterval = setInterval(() => {
        if (sharedSocket && sharedSocket.connected) {
          // Send ping to check if connection is alive
          sharedSocket.emit('ping');
        } else {
          console.warn('⚠️  [WebSocket] Heartbeat detected disconnection, reconnecting...');
          isConnected.value = false;
          if (sharedSocket) {
            sharedSocket.connect();
          }
        }
      }, 30000); // Check every 30 seconds

      // Auto-join all pending rooms when connected
      if (pendingRooms.size > 0) {
        console.log('📥 [WebSocket] Auto-joining pending rooms:', Array.from(pendingRooms));
        pendingRooms.forEach(room => {
          const { event, data } = room;
          sharedSocket.emit(event, data);
        });
      }
    });

    sharedSocket.on('disconnect', (reason) => {
      console.log('❌ [WebSocket] Disconnected:', reason);
      isConnected.value = false;

      // Always try to reconnect, not just on server disconnect
      if (reason === 'io server disconnect' || reason === 'transport close' || reason === 'ping timeout') {
        console.log('🔄 [WebSocket] Attempting to reconnect...');
        setTimeout(() => {
          if (sharedSocket && !sharedSocket.connected) {
            sharedSocket.connect();
          }
        }, 1000);
      }
    });

    sharedSocket.on('connect_error', (error) => {
      console.error('❌ [WebSocket] Connection error:', error.message);
      reconnectAttempts.value++;
    });

    sharedSocket.on('reconnect', (attemptNumber) => {
      console.log('🔄 [WebSocket] Reconnected after', attemptNumber, 'attempts');
      isConnected.value = true;
      reconnectAttempts.value = 0;

      // Rejoin all rooms after reconnection
      if (pendingRooms.size > 0) {
        console.log('📥 [WebSocket] Re-joining rooms after reconnect');
        pendingRooms.forEach(room => {
          const { event, data } = room;
          sharedSocket.emit(event, data);
        });
      }
    });

    sharedSocket.on('error', (error) => {
      console.error('❌ [WebSocket] Error:', error);
    });

    return sharedSocket;
  };

  /**
   * Disconnect from WebSocket server
   */
  const disconnect = () => {
    if (sharedSocket) {
      console.log('🔌 [WebSocket] Disconnecting...');

      // Clear heartbeat
      if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
        heartbeatInterval = null;
      }

      sharedSocket.disconnect();
      sharedSocket = null;
      isConnected.value = false;
      pendingRooms.clear();
    }
  };

  /**
   * Join a room (generic function for any room type)
   * @param {string} event - The join event name (e.g., 'classroom:join', 'zalo:conversation:join')
   * @param {object} data - The data to send with the join event
   * @param {string} roomKey - Unique key to track this room in pendingRooms
   */
  const joinRoom = (event, data, roomKey) => {
    // Store room for auto-rejoin on reconnect
    pendingRooms.add({ event, data, key: roomKey });

    if (!sharedSocket?.connected) {
      console.log(`🔄 [WebSocket] Not connected yet, room will be joined when connection is established`);
      return;
    }

    sharedSocket.emit(event, data);
    console.log(`📥 [WebSocket] Joined room via ${event}:`, data);
  };

  /**
   * Leave a room (generic function for any room type)
   * @param {string} event - The leave event name (e.g., 'classroom:leave', 'zalo:conversation:leave')
   * @param {object} data - The data to send with the leave event
   * @param {string} roomKey - Unique key to remove from pendingRooms
   */
  const leaveRoom = (event, data, roomKey) => {
    // Remove from pending rooms
    pendingRooms.forEach(room => {
      if (room.key === roomKey) {
        pendingRooms.delete(room);
      }
    });

    if (!sharedSocket?.connected) {
      return;
    }

    sharedSocket.emit(event, data);
    console.log(`📤 [WebSocket] Left room via ${event}:`, data);
  };

  /**
   * Listen for an event
   */
  const on = (event, callback) => {
    if (!sharedSocket) {
      console.warn('⚠️  [WebSocket] Socket not initialized');
      return () => {};
    }

    sharedSocket.on(event, callback);

    // Return cleanup function
    return () => {
      if (sharedSocket) {
        sharedSocket.off(event, callback);
      }
    };
  };

  /**
   * Remove event listener
   */
  const off = (event, callback) => {
    if (sharedSocket) {
      sharedSocket.off(event, callback);
    }
  };

  /**
   * Emit an event
   */
  const emit = (event, data) => {
    if (!sharedSocket?.connected) {
      console.warn('⚠️  [WebSocket] Cannot emit, not connected');
      return;
    }

    sharedSocket.emit(event, data);
  };

  /**
   * Get the socket instance
   */
  const getSocket = () => sharedSocket;

  return {
    socket: getSocket,
    isConnected,
    reconnectAttempts,
    connect,
    disconnect,
    joinRoom,
    leaveRoom,
    on,
    off,
    emit,
  };
}

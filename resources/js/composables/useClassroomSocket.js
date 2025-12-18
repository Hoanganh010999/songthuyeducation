import { ref } from 'vue';
import { useWebSocket } from './useWebSocket';

let pendingClassroomId = null; // Store classroom ID to join when connected

/**
 * Composable for Classroom Board WebSocket connection
 * Uses shared WebSocket connection from useWebSocket
 */
export function useClassroomSocket() {
  const ws = useWebSocket();

  /**
   * Connect to WebSocket server (uses shared connection)
   */
  const connect = () => {
    console.log('[Classroom] 🔌 Connecting via shared WebSocket...');
    const socket = ws.connect();

    if (socket && pendingClassroomId) {
      joinClassroom(pendingClassroomId);
    }

    return socket;
  };

  /**
   * Disconnect from WebSocket server
   */
  const disconnect = () => {
    console.log('[Classroom] 🔌 Disconnecting...');
    if (pendingClassroomId) {
      leaveClassroom(pendingClassroomId);
    }
    // Don't disconnect the shared socket, other features might be using it
  };

  /**
   * Join classroom room for real-time updates
   */
  const joinClassroom = (classId) => {
    pendingClassroomId = classId;

    ws.joinRoom(
      'classroom:join',
      { class_id: classId },
      `classroom:${classId}`
    );

    console.log('[Classroom] 📥 Joined classroom room:', classId);
  };

  /**
   * Leave classroom room
   */
  const leaveClassroom = (classId) => {
    ws.leaveRoom(
      'classroom:leave',
      { class_id: classId },
      `classroom:${classId}`
    );

    if (pendingClassroomId === classId) {
      pendingClassroomId = null;
    }

    console.log('[Classroom] 📤 Left classroom room:', classId);
  };

  /**
   * Listen for new posts
   */
  const onPostCreated = (callback) => {
    return ws.on('classroom:post:created', callback);
  };

  /**
   * Listen for post updates
   */
  const onPostUpdated = (callback) => {
    return ws.on('classroom:post:updated', callback);
  };

  /**
   * Listen for post deletions
   */
  const onPostDeleted = (callback) => {
    return ws.on('classroom:post:deleted', callback);
  };

  /**
   * Listen for new comments
   */
  const onCommentCreated = (callback) => {
    return ws.on('classroom:comment:created', callback);
  };

  /**
   * Listen for comment updates
   */
  const onCommentUpdated = (callback) => {
    return ws.on('classroom:comment:updated', callback);
  };

  /**
   * Listen for comment deletions
   */
  const onCommentDeleted = (callback) => {
    return ws.on('classroom:comment:deleted', callback);
  };

  /**
   * Listen for post reactions
   */
  const onPostReaction = (callback) => {
    return ws.on('classroom:post:reaction', callback);
  };

  return {
    socket: ws.socket,
    isConnected: ws.isConnected,
    connect,
    disconnect,
    joinClassroom,
    leaveClassroom,
    onPostCreated,
    onPostUpdated,
    onPostDeleted,
    onCommentCreated,
    onCommentUpdated,
    onCommentDeleted,
    onPostReaction,
  };
}

import { ref } from 'vue';
import { useWebSocket } from './useWebSocket';

/**
 * Composable for Zalo WebSocket connection
 * Uses shared WebSocket connection from useWebSocket
 */
export function useZaloSocket() {
  const ws = useWebSocket();

  /**
   * Connect to WebSocket server (uses shared connection)
   */
  const connect = () => {
    console.log('[Zalo] 🔌 Connecting via shared WebSocket...');
    return ws.connect();
  };

  /**
   * Disconnect from WebSocket server
   */
  const disconnect = () => {
    console.log('[Zalo] 🔌 Disconnecting...');
    // Don't disconnect the shared socket, other features might be using it
  };

  /**
   * Join Zalo conversation room
   */
  const joinConversation = (accountId, recipientId) => {
    ws.joinRoom(
      'zalo:conversation:join',
      { account_id: accountId, recipient_id: recipientId },
      `zalo:${accountId}:${recipientId}`
    );

    console.log('[Zalo] 📥 Joined conversation room:', accountId, recipientId);
  };

  /**
   * Leave Zalo conversation room
   */
  const leaveConversation = (accountId, recipientId) => {
    ws.leaveRoom(
      'zalo:conversation:leave',
      { account_id: accountId, recipient_id: recipientId },
      `zalo:${accountId}:${recipientId}`
    );

    console.log('[Zalo] 📤 Left conversation room');
  };

  /**
   * Join Zalo account room (for conversation list updates)
   */
  const joinAccount = (accountId) => {
    ws.joinRoom(
      'zalo:account:join',
      { account_id: accountId },
      `zalo:account:${accountId}`
    );

    console.log('[Zalo] 📥 Joined account room:', accountId);
  };

  /**
   * Leave Zalo account room
   */
  const leaveAccount = (accountId) => {
    ws.leaveRoom(
      'zalo:account:leave',
      { account_id: accountId },
      `zalo:account:${accountId}`
    );

    console.log('[Zalo] 📤 Left account room');
  };

  /**
   * Listen for new messages
   */
  const onMessage = (callback) => {
    return ws.on('zalo:message:new', callback);
  };

  /**
   * Listen for conversation updates
   */
  const onConversationUpdate = (callback) => {
    return ws.on('zalo:conversation:updated', callback);
  };

  /**
   * Listen for new reactions
   */
  const onReaction = (callback) => {
    return ws.on('zalo:reaction:new', callback);
  };

  /**
   * Listen for conversations updated event (after sync history fixes Unknown)
   */
  const onConversationsUpdated = (callback) => {
    return ws.on('conversations_updated', callback);
  };

  return {
    socket: ws.socket,
    isConnected: ws.isConnected,
    connect,
    disconnect,
    joinConversation,
    leaveConversation,
    joinAccount,
    leaveAccount,
    onMessage,
    onConversationUpdate,
    onReaction,
    onConversationsUpdated,
  };
}

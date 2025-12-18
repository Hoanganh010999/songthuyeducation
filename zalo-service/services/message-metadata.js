/**
 * Message Metadata Store
 * 
 * Lưu metadata của tin nhắn đang gửi (sent_by_user_id, etc.)
 * để sau khi WebSocket nhận confirmation từ Zalo, có thể gửi về Laravel
 */

// Map để lưu metadata của tin nhắn đang gửi
// Key: cliMsgId, Value: { sent_by_user_id, sent_by_user_name, timestamp }
const pendingMessageMetadata = new Map();

// Cleanup old metadata (> 1 hour)
setInterval(() => {
  const now = Date.now();
  for (const [cliMsgId, metadata] of pendingMessageMetadata.entries()) {
    if (now - metadata.timestamp > 3600000) { // 1 hour
      pendingMessageMetadata.delete(cliMsgId);
      console.log(`🗑️  [message-metadata] Cleaned up old metadata for cliMsgId: ${cliMsgId}`);
    }
  }
}, 300000); // Every 5 minutes

module.exports = {
  pendingMessageMetadata,
};


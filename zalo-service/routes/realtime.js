const express = require('express');
const router = express.Router();
const { verifyApiKey } = require('../middleware/auth');
const { sendToZaloConversation } = require('../services/realtimeServer');

/**
 * POST /api/realtime/broadcast-reaction
 * Broadcast reaction event to frontend via Socket.IO
 * Called by Laravel when a reaction is received
 */
router.post('/broadcast-reaction', verifyApiKey, (req, res) => {
  try {
    const { account_id, recipient_id, recipient_type, message_id, zalo_message_id, reaction } = req.body;
    
    if (!account_id || !recipient_id || !message_id) {
      return res.status(400).json({
        success: false,
        message: 'account_id, recipient_id, and message_id are required'
      });
    }
    
    // Broadcast to conversation room
    sendToZaloConversation(account_id, recipient_id, 'zalo:reaction:new', {
      account_id,
      recipient_id,
      recipient_type: recipient_type || 'user',
      message_id,
      zalo_message_id,
      reaction,
    });
    
    console.log('📡 [zalo-service] Reaction broadcasted:', {
      account_id,
      recipient_id,
      message_id,
    });
    
    res.json({
      success: true,
      message: 'Reaction broadcasted successfully'
    });
  } catch (error) {
    console.error('❌ [zalo-service] Broadcast reaction error:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'Failed to broadcast reaction'
    });
  }
});

module.exports = router;


const express = require('express');
const router = express.Router();
const { verifyApiKey } = require('../middleware/auth');
const { broadcastToAll, broadcastToAccount } = require('../services/realtimeServer');

/**
 * Broadcast event to all connected clients or specific account
 * This allows Laravel to push real-time updates to frontend
 */
router.post('/broadcast', verifyApiKey, async (req, res) => {
  try {
    const { event, data, account_id } = req.body;
    
    if (!event) {
      return res.status(400).json({
        success: false,
        message: 'Event name is required',
      });
    }
    
    console.log(`📡 [Socket] Broadcasting event: ${event}`, {
      hasData: !!data,
      accountId: account_id,
      dataKeys: data ? Object.keys(data) : [],
    });
    
    // Broadcast to specific account or all
    if (account_id) {
      broadcastToAccount(account_id, event, data);
      console.log(`   ✅ Broadcasted to account: ${account_id}`);
    } else {
      broadcastToAll(event, data);
      console.log(`   ✅ Broadcasted to all clients`);
    }
    
    return res.json({
      success: true,
      message: 'Event broadcasted successfully',
      event: event,
      account_id: account_id || 'all',
    });
  } catch (error) {
    console.error('❌ [Socket] Broadcast error:', error);
    return res.status(500).json({
      success: false,
      message: 'Failed to broadcast event',
      error: error.message,
    });
  }
});

module.exports = router;


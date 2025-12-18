const express = require('express');
const router = express.Router();
const { verifyApiKey } = require('../middleware/auth');
const { getZaloClient } = require('../services/zaloClient');

/**
 * POST /api/friend/send-request
 * Send friend request to a user
 */
router.post('/send-request', verifyApiKey, async (req, res) => {
  try {
    console.log('📋 [POST /api/friend/send-request] Sending friend request...');
    console.log('   Request body:', JSON.stringify(req.body));
    
    const { userId, message, accountId } = req.body;
    
    if (!userId) {
      return res.status(400).json({
        success: false,
        message: 'userId is required'
      });
    }
    
    const zalo = getZaloClient(accountId);
    if (!zalo) {
      return res.status(400).json({
        success: false,
        message: 'Zalo client not initialized'
      });
    }
    
    // Check if sendFriendRequest method exists
    if (typeof zalo.sendFriendRequest !== 'function') {
      console.error('   ❌ sendFriendRequest method not found');
      console.log('   Available methods:', Object.getOwnPropertyNames(zalo)
        .filter(name => typeof zalo[name] === 'function' && name.toLowerCase().includes('friend'))
        .join(', '));
      return res.status(500).json({
        success: false,
        message: 'sendFriendRequest method not available in zalo client'
      });
    }
    
    console.log('   ✅ Calling sendFriendRequest()...');
    console.log('   User ID:', userId);
    console.log('   Message:', message || '(no message)');
    
    // sendFriendRequest(msg: string, userId: string)
    const friendMessage = message || 'Xin chào! Hãy kết bạn với tôi nhé!';
    const result = await zalo.sendFriendRequest(friendMessage, String(userId));
    
    console.log('   ✅ Friend request sent successfully');
    console.log('   Result:', JSON.stringify(result));
    
    res.json({
      success: true,
      message: 'Friend request sent successfully',
      data: {
        userId: userId,
        result: result,
      }
    });
  } catch (error) {
    console.error('❌ Send friend request error:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'Failed to send friend request',
      error: process.env.NODE_ENV === 'development' ? error.stack : undefined,
    });
  }
});

module.exports = router;


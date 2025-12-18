const express = require('express');
const router = express.Router();
const { verifyApiKey } = require('../middleware/auth');
const { getZaloClient } = require('../services/zaloClient');

/**
 * Retry function with exponential backoff for rate limiting
 */
async function retryWithBackoff(fn, maxRetries = 3, initialDelay = 2000) {
  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      return await fn();
    } catch (error) {
      const isRateLimit = error.message && error.message.includes('429');
      const isLastAttempt = attempt === maxRetries;

      if (isRateLimit && !isLastAttempt) {
        const delay = initialDelay * Math.pow(2, attempt - 1); // 2s, 4s, 8s
        console.log(`   ⏳ Rate limited (429), waiting ${delay/1000}s before retry ${attempt}/${maxRetries}...`);
        await new Promise(resolve => setTimeout(resolve, delay));
        continue;
      }

      // If not rate limit or last attempt, throw error
      throw error;
    }
  }
}

/**
 * GET /api/user/friends
 * Get friends list for specific account
 */
router.get('/friends', verifyApiKey, async (req, res) => {
  try {
    console.log('📋 [GET /api/user/friends] Getting friends list...');

    // Get account ID from header or query
    const accountId = req.headers['x-account-id'] || req.query.account_id;

    if (!accountId) {
      console.error('   ❌ No account_id provided');
      return res.status(400).json({
        success: false,
        message: 'account_id is required (header X-Account-Id or query param)'
      });
    }

    console.log('   Account ID:', accountId);

    const { getSession } = require('../services/zaloClient');
    const zalo = getSession(parseInt(accountId));

    if (!zalo) {
      console.error('   ❌ Zalo session not found for account:', accountId);
      return res.status(400).json({
        success: false,
        message: `Zalo session not found for account ${accountId}. Please login first.`
      });
    }

    console.log('   ✅ Zalo session found');

    // Try different method names for getting friends
    const methodNames = ['getAllFriends', 'getFriends', 'listFriends', 'getFriendList'];
    let friends = null;
    let methodFound = false;

    for (const methodName of methodNames) {
      if (typeof zalo[methodName] === 'function') {
        console.log(`   ✅ Found method: ${methodName}()`);
        methodFound = true;
        try {
          // 🔥 NEW: Use retry with exponential backoff for rate limiting
          friends = await retryWithBackoff(async () => {
            return await zalo[methodName]();
          });
          console.log(`   ✅ ${methodName}() returned ${Array.isArray(friends) ? friends.length : 0} friends`);
          break;
        } catch (error) {
          console.error(`   ❌ ${methodName}() error after retries:`, error.message);
          // If this is a rate limit error after all retries, return specific error
          if (error.message && error.message.includes('429')) {
            return res.status(429).json({
              success: false,
              message: 'Rate limited by Zalo API. Please try again in a few minutes.',
              error: 'RATE_LIMIT_EXCEEDED'
            });
          }
          continue;
        }
      }
    }

    if (!methodFound) {
      console.error('❌ No friends method found!');
      const availableMethods = Object.getOwnPropertyNames(zalo)
        .filter(name => typeof zalo[name] === 'function')
        .sort();

      return res.status(500).json({
        success: false,
        message: 'Friends method not available',
        availableMethods: availableMethods
      });
    }

    if (!friends || !Array.isArray(friends)) {
      console.warn('   ⚠️  No friends returned');
      friends = [];
    }

    console.log('   ✅ Returning', friends.length, 'friends');

    res.json({
      success: true,
      data: friends,
      count: friends.length
    });

  } catch (error) {
    console.error('❌ Get friends error:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'Failed to get friends',
      error: process.env.NODE_ENV === 'development' ? error.stack : undefined
    });
  }
});

/**
 * GET /api/user/info/:userId
 * Get user info by user ID
 */
router.get('/info/:userId', verifyApiKey, async (req, res) => {
  try {
    const { userId } = req.params;
    console.log('📋 [GET /api/user/info/:userId] Getting user info for:', userId);

    // Get account ID from header or query
    const accountId = req.headers['x-account-id'] || req.query.accountId || req.query.account_id;

    if (!accountId) {
      console.error('   ❌ No account_id provided');
      return res.status(400).json({
        success: false,
        message: 'account_id is required (header X-Account-Id or query param)'
      });
    }

    console.log('   Account ID:', accountId);

    const { getSession } = require('../services/zaloClient');
    const zalo = getSession(parseInt(accountId));

    if (!zalo) {
      console.error('   ❌ Zalo session not found for account:', accountId);
      return res.status(400).json({
        success: false,
        message: `Zalo session not found for account ${accountId}. Please login first.`
      });
    }

    console.log('   ✅ Zalo session found');

    // Check if getUserInfo method exists
    if (typeof zalo.getUserInfo !== 'function') {
      console.error('   ❌ getUserInfo method not found');
      return res.status(500).json({
        success: false,
        message: 'getUserInfo method not available in zalo client'
      });
    }

    console.log('   ✅ Calling getUserInfo()...');
    const userInfoRaw = await zalo.getUserInfo(userId);

    console.log('   Raw response type:', typeof userInfoRaw);
    console.log('   Raw response keys:', userInfoRaw ? Object.keys(userInfoRaw) : 'null');

    let userData = null;

    // Extract user data from changed_profiles
    if (userInfoRaw && userInfoRaw.changed_profiles && userInfoRaw.changed_profiles[userId]) {
      userData = userInfoRaw.changed_profiles[userId];
      console.log('   ✅ Extracted user data from changed_profiles');
    } else if (userInfoRaw && userInfoRaw.displayName) {
      // Fallback: direct access
      userData = userInfoRaw;
      console.log('   ✅ Using userInfo directly');
    } else {
      console.warn('   ⚠️  No user data found');
      return res.status(404).json({
        success: false,
        message: 'User not found or no data available'
      });
    }

    // Normalize user data structure
    const normalized = {
      id: userId,
      display_name: userData.displayName || userData.zaloName || userData.name || 'Unknown',
      avatar: userData.avatar || userData.avatarUrl || userData.avatar_url || null,
      avatar_url: userData.avatar || userData.avatarUrl || userData.avatar_url || null,
      cover: userData.cover || null,
      bgavatar: userData.bgavatar || null,
      phone: userData.phoneNumber || userData.phone || null,
      gender: userData.gender || null,
      dob: userData.dob || null,
      sdob: userData.sdob || null,
      status: userData.status || null,
      globalId: userData.globalId || null,
      // Keep all original data
      ...userData
    };

    console.log('   ✅ User info retrieved:', {
      id: normalized.id,
      display_name: normalized.display_name,
      has_avatar: !!normalized.avatar
    });

    res.json({
      success: true,
      data: normalized
    });
  } catch (error) {
    console.error('❌ Get user info error:', error);

    // Check if user not found
    if (error.message && (
      error.message.includes('not found') ||
      error.message.includes('không tìm thấy') ||
      error.message.includes('Not Found')
    )) {
      return res.status(404).json({
        success: false,
        message: 'User not found',
        error: process.env.NODE_ENV === 'development' ? error.message : undefined,
      });
    }

    res.status(500).json({
      success: false,
      message: error.message || 'Failed to get user info',
      error: process.env.NODE_ENV === 'development' ? error.stack : undefined,
    });
  }
});

/**
 * POST /api/user/search
 * Search for a Zalo user by phone number
 */
router.post('/search', verifyApiKey, async (req, res) => {
  try {
    console.log('📋 [POST /api/user/search] Searching for user...');
    console.log('   Request body:', JSON.stringify(req.body));
    
    const { phoneNumber, accountId } = req.body;
    
    if (!phoneNumber) {
      return res.status(400).json({
        success: false,
        message: 'phoneNumber is required'
      });
    }
    
    const zalo = getZaloClient(accountId);
    if (!zalo) {
      return res.status(400).json({
        success: false,
        message: 'Zalo client not initialized'
      });
    }
    
    // Check if findUser method exists
    if (typeof zalo.findUser !== 'function') {
      console.error('   ❌ findUser method not found');
      return res.status(500).json({
        success: false,
        message: 'findUser method not available in zalo client'
      });
    }
    
    console.log('   ✅ Calling findUser()...');
    const result = await zalo.findUser(phoneNumber);

    console.log('   ✅ User found successfully');
    console.log('   Result:', JSON.stringify(result));

    // Check if user is friend
    let isFriend = false;
    try {
      if (result.uid || result.userId) {
        const userId = result.uid || result.userId;
        // Try to get friend list to check if this user is a friend
        if (typeof zalo.getAllFriends === 'function') {
          const friends = await zalo.getAllFriends();
          if (Array.isArray(friends)) {
            isFriend = friends.some(friend =>
              friend.uid === userId ||
              friend.userId === userId ||
              friend.id === userId
            );
            console.log('   📋 Friend check: isFriend =', isFriend, '(checked against', friends.length, 'friends)');
          }
        }
      }
    } catch (error) {
      console.warn('   ⚠️ Could not check friend status:', error.message);
    }

    // Normalize response
    const userData = {
      id: result.uid || result.userId,
      display_name: result.display_name || result.zalo_name,
      zalo_name: result.zalo_name,
      avatar: result.avatar,
      phone: phoneNumber,
      gender: result.gender,
      dob: result.dob,
      globalId: result.globalId,
      isFriend: isFriend,  // Use checked friend status
    };

    res.json({
      success: true,
      message: 'User found successfully',
      data: userData,
    });
  } catch (error) {
    console.error('❌ Search user error:', error);
    
    // Check if user not found
    if (error.message && (
      error.message.includes('not found') ||
      error.message.includes('không tìm thấy') ||
      error.message.includes('Not Found')
    )) {
      return res.status(404).json({
        success: false,
        message: 'User not found',
        error: process.env.NODE_ENV === 'development' ? error.message : undefined,
      });
    }
    
    res.status(500).json({
      success: false,
      message: error.message || 'Failed to search user',
      error: process.env.NODE_ENV === 'development' ? error.stack : undefined,
    });
  }
});

module.exports = router;

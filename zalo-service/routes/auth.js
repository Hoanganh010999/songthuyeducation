/**
 * AUTH ROUTES - MULTI-SESSION VERSION
 *
 * Supports multiple Zalo accounts connected simultaneously
 * Includes Telegram notification for QR codes
 */

const express = require('express');
const router = express.Router();
const path = require('path');
const fs = require('fs');
const { verifyApiKey } = require('../middleware/auth');
const {
  initializeZalo,
  isAccountReady,
  switchAccount,
  getAllSessions,
  disconnectAccount,
  getSession,
  getActiveAccountId
} = require('../services/zaloClient');
const telegram = require('../services/telegram');

// ============================================================================
// INITIALIZATION
// ============================================================================

/**
 * POST /api/auth/initialize
 * Initialize Zalo connection with QR code for a specific account
 *
 * Body params:
 * - accountId: (optional) Account ID to initialize. If not provided, auto-generate.
 * - forceNew: (optional) Force new login even if session exists
 * - sendToTelegram: (optional) Also send QR code to Telegram
 */
router.post('/initialize', verifyApiKey, async (req, res) => {
  try {
    console.log('🔐 [POST /api/auth/initialize] Multi-session initialization...');
    console.log('   Request body:', JSON.stringify(req.body));

    // Get accountId from request or use X-Account-Id header
    let accountId = req.body.accountId || req.body.account_id || req.headers['x-account-id'];

    // If no accountId provided, generate one based on timestamp
    // Laravel should provide accountId after creating ZaloAccount record
    if (!accountId) {
      // Temporary ID - Laravel should replace this with real DB ID
      accountId = Date.now();
      console.log(`   ⚠️  No accountId provided, using temporary ID: ${accountId}`);
      console.log(`   ⚠️  Laravel should provide accountId from ZaloAccount record`);
    } else {
      accountId = parseInt(accountId);
      console.log(`   Account ID: ${accountId}`);
    }

    const forceNew = req.body.forceNew === true || req.body.force_new === true;
    const sendToTelegram = req.body.sendToTelegram === true || req.body.send_to_telegram === true;
    console.log(`   Force new: ${forceNew}`);
    console.log(`   Send to Telegram: ${sendToTelegram}`);

    let qrCodeDataUrl = null;
    let qrCallbackReceived = false;
    let qrFilePath = null;

    console.log(`🔐 Initializing Zalo for account ${accountId}...`);

    // Initialize with QR callback
    await initializeZalo(accountId, (qrBase64) => {
      console.log(`✅ QR Code callback received for account ${accountId}`);
      console.log('   QR Code length:', qrBase64 ? qrBase64.length : 0);
      qrCodeDataUrl = qrBase64;
      qrCallbackReceived = true;
    }, forceNew);

    console.log('   QR callback received:', qrCallbackReceived);
    console.log('   QR Code data URL:', qrCodeDataUrl ? 'Present' : 'Missing');

    // Find QR file path for Telegram sending
    const possibleQrPaths = [
      path.join(__dirname, '..', `qr_${accountId}.png`),
      path.join(__dirname, '..', 'qr.png')
    ];

    for (const qrPath of possibleQrPaths) {
      if (fs.existsSync(qrPath)) {
        qrFilePath = qrPath;
        break;
      }
    }

    if (qrCodeDataUrl) {
      console.log(`✅ Sending QR code for account ${accountId} to client`);

      // Send to Telegram if requested
      if (sendToTelegram && qrFilePath) {
        console.log(`📱 Sending QR code to Telegram for account ${accountId}...`);
        await telegram.sendQRCode(accountId, qrFilePath);
      }

      res.json({
        success: true,
        message: 'QR Code generated successfully. Please scan with Zalo app.',
        qrCode: qrCodeDataUrl,
        accountId: accountId,
        sentToTelegram: sendToTelegram && qrFilePath ? true : false
      });
    } else {
      // If already logged in
      if (isAccountReady(accountId) && !forceNew) {
        res.status(400).json({
          success: false,
          message: `Account ${accountId} is already logged in. Use forceNew=true to re-login.`,
          alreadyLoggedIn: true,
          accountId: accountId,
        });
      } else {
        res.status(500).json({
          success: false,
          message: 'Failed to generate QR code. Please check zalo-service logs.',
          accountId: accountId,
        });
      }
    }
  } catch (error) {
    console.error('❌ Initialize endpoint error:', error);
    console.error('   Error message:', error.message);
    console.error('   Error stack:', error.stack);
    res.status(500).json({
      success: false,
      message: error.message || 'Failed to initialize Zalo',
    });
  }
});

/**
 * POST /api/auth/request-qr-telegram
 * Request a new QR code and send it to Telegram
 * Used for quick re-login when session expires
 *
 * Body params:
 * - accountId: Account ID to generate QR for
 */
router.post('/request-qr-telegram', verifyApiKey, async (req, res) => {
  try {
    let accountId = req.body.accountId || req.body.account_id || req.headers['x-account-id'];

    if (!accountId) {
      return res.status(400).json({
        success: false,
        message: 'accountId is required'
      });
    }

    accountId = parseInt(accountId);
    console.log(`📱 [POST /api/auth/request-qr-telegram] Requesting QR for account ${accountId}...`);

    // Check if Telegram is configured
    if (!telegram.isConfigured()) {
      return res.status(400).json({
        success: false,
        message: 'Telegram is not configured. Please set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID environment variables.'
      });
    }

    let qrCodeDataUrl = null;
    let qrCallbackReceived = false;

    // Initialize with force new to get fresh QR
    await initializeZalo(accountId, (qrBase64) => {
      console.log(`✅ QR Code callback received for account ${accountId}`);
      qrCodeDataUrl = qrBase64;
      qrCallbackReceived = true;
    }, true); // forceNew = true

    // Find QR file path
    const possibleQrPaths = [
      path.join(__dirname, '..', `qr_${accountId}.png`),
      path.join(__dirname, '..', 'qr.png')
    ];

    let qrFilePath = null;
    for (const qrPath of possibleQrPaths) {
      if (fs.existsSync(qrPath)) {
        qrFilePath = qrPath;
        break;
      }
    }

    if (qrFilePath) {
      console.log(`📤 Sending QR code to Telegram for account ${accountId}...`);
      const sent = await telegram.sendQRCode(accountId, qrFilePath);

      res.json({
        success: true,
        message: sent ? 'QR code sent to Telegram successfully' : 'QR code generated but failed to send to Telegram',
        accountId: accountId,
        sentToTelegram: sent,
        qrCode: qrCodeDataUrl // Also return QR for web display
      });
    } else {
      res.status(500).json({
        success: false,
        message: 'Failed to generate QR code file',
        accountId: accountId
      });
    }

  } catch (error) {
    console.error('❌ Request QR Telegram error:', error);
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
});

/**
 * POST /api/auth/test-telegram
 * Test Telegram notification
 */
router.post('/test-telegram', verifyApiKey, async (req, res) => {
  try {
    console.log('🧪 [POST /api/auth/test-telegram] Testing Telegram...');

    if (!telegram.isConfigured()) {
      return res.status(400).json({
        success: false,
        message: 'Telegram is not configured. Please set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID environment variables.'
      });
    }

    const sent = await telegram.sendTestMessage();

    res.json({
      success: sent,
      message: sent ? 'Test message sent to Telegram' : 'Failed to send test message'
    });

  } catch (error) {
    console.error('❌ Test Telegram error:', error);
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
});

// ============================================================================
// STATUS
// ============================================================================

/**
 * GET /api/auth/status
 * Check Zalo connection status for a specific account
 *
 * Query params or headers:
 * - accountId: (optional) Account ID to check. If not provided, check active account.
 */
router.get('/status', verifyApiKey, (req, res) => {
  try {
    // Get accountId from query or header
    let accountId = req.query.accountId || req.query.account_id || req.headers['x-account-id'];

    if (accountId) {
      accountId = parseInt(accountId);
    }

    const ready = isAccountReady(accountId);

    console.log('📊 Status check:');
    console.log(`   accountId: ${accountId || 'active'}`);
    console.log(`   isReady: ${ready}`);
    console.log('   timestamp:', new Date().toISOString());

    res.json({
      success: true,
      isReady: ready,
      accountId: accountId || getActiveAccountId(),
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('❌ Status endpoint error:', error);
    res.status(500).json({
      success: false,
      message: error.message,
    });
  }
});

// ============================================================================
// ACCOUNT INFO
// ============================================================================

/**
 * GET /api/auth/account-info
 * Get logged in account information
 *
 * Query params or headers:
 * - accountId: (optional) Account ID. If not provided, use active account.
 */
router.get('/account-info', verifyApiKey, async (req, res) => {
  try {
    console.log('📋 [GET /api/auth/account-info] Getting account info...');

    // Get accountId from query or header
    let accountId = req.query.accountId || req.query.account_id || req.headers['x-account-id'];

    if (accountId) {
      accountId = parseInt(accountId);
      console.log(`   Requested account ID: ${accountId}`);
    } else {
      console.log('   No accountId provided, using active account');
    }

    const zalo = getSession(accountId);

    if (!zalo) {
      return res.status(400).json({
        success: false,
        message: accountId
          ? `Zalo client for account ${accountId} not initialized. Please login first.`
          : 'No active Zalo session. Please login first.',
        error_code: 'NO_SESSION',
      });
    }

    let accountInfo = {
      zalo_id: null,
      name: null,
      phone: null,
      avatar_url: null,
      cookie: null,
      imei: process.env.ZALO_IMEI || null,
      user_agent: process.env.ZALO_USER_AGENT || null,
    };

    // Try to get cookie
    try {
      if (typeof zalo.getCookie === 'function') {
        const cookieData = await zalo.getCookie();
        console.log('   Cookie retrieved');

        if (cookieData) {
          let cookiesArray = null;

          if (cookieData.cookies && Array.isArray(cookieData.cookies)) {
            cookiesArray = cookieData.cookies;
            accountInfo.cookie = cookieData;
          } else if (Array.isArray(cookieData)) {
            cookiesArray = cookieData;
            accountInfo.cookie = cookieData;
          } else if (typeof cookieData === 'object') {
            accountInfo.cookie = cookieData;
          }

          // Try to extract zalo_id from cookies
          if (cookiesArray && cookiesArray.length > 0) {
            for (const cookieItem of cookiesArray) {
              if (cookieItem && typeof cookieItem === 'object') {
                const cookieValue = cookieItem.value || '';
                if (cookieValue && /^\d{15,}$/.test(cookieValue)) {
                  accountInfo.zalo_id = cookieValue;
                  break;
                }
              }
            }
          }
        }
      }
    } catch (error) {
      console.error('   Error getting cookie:', error.message);
    }

    // Try getOwnId()
    try {
      if (typeof zalo.getOwnId === 'function') {
        const ownId = await zalo.getOwnId();
        if (ownId) {
          accountInfo.zalo_id = ownId;
          console.log(`   ✅ Got zalo_id from getOwnId(): ${ownId}`);
        }
      }
    } catch (error) {
      console.error('   Error getting own ID:', error.message);
    }

    // Try fetchAccountInfo() for name and avatar
    try {
      if (typeof zalo.fetchAccountInfo === 'function') {
        console.log('   🔍 Trying fetchAccountInfo...');
        const accountData = await zalo.fetchAccountInfo();

        if (accountData && accountData.profile) {
          const profile = accountData.profile;

          if (profile.userId) {
            accountInfo.zalo_id = profile.userId;
            console.log(`   ✅ Got zalo_id from fetchAccountInfo: ${profile.userId}`);
          }

          if (profile.displayName || profile.zaloName) {
            accountInfo.name = profile.displayName || profile.zaloName;
            console.log(`   ✅ Got name: ${accountInfo.name}`);
          }

          if (profile.phoneNumber) {
            accountInfo.phone = profile.phoneNumber;
          }

          if (profile.avatar) {
            accountInfo.avatar_url = profile.avatar;
            console.log(`   ✅ Got avatar_url: ${accountInfo.avatar_url.substring(0, 50)}...`);
          }
        }
      }
    } catch (error) {
      console.error('   Error fetching account info:', error.message);
    }

    // Generate unique identifier from cookie if zalo_id is missing
    if (!accountInfo.zalo_id && accountInfo.cookie) {
      const crypto = require('crypto');
      const cookieHash = crypto.createHash('md5')
        .update(JSON.stringify(accountInfo.cookie))
        .digest('hex');
      accountInfo.zalo_id = `cookie_${cookieHash.substring(0, 16)}`;
      console.log(`   ⚠️  No zalo_id, generated from cookie hash: ${accountInfo.zalo_id}`);
    }

    // Final validation - require at least cookie
    if (!accountInfo.cookie) {
      console.error('   ❌ CRITICAL: No cookie data available!');
      return res.status(400).json({
        success: false,
        message: 'Could not get cookie data. Login may still be in progress.',
        error_code: 'COOKIE_MISSING',
      });
    }

    console.log('✅ Account info retrieved:', {
      has_zalo_id: !!accountInfo.zalo_id,
      has_name: !!accountInfo.name,
      has_avatar_url: !!accountInfo.avatar_url,
      has_cookie: !!accountInfo.cookie,
      zalo_id: accountInfo.zalo_id,
    });

    res.json({
      success: true,
      data: accountInfo,
      zalo_account_id: accountInfo.zalo_id, // Ensure this field is set for Laravel compatibility
    });

  } catch (error) {
    console.error('❌ Get account info error:', error);
    console.error('   Stack:', error.stack);
    res.status(500).json({
      success: false,
      message: error.message,
    });
  }
});

// ============================================================================
// MULTI-SESSION MANAGEMENT
// ============================================================================

/**
 * POST /api/auth/switch
 * Switch active account
 *
 * Body params:
 * - accountId: Account ID to switch to
 */
router.post('/switch', verifyApiKey, async (req, res) => {
  try {
    const accountId = parseInt(req.body.accountId || req.body.account_id);

    if (!accountId) {
      return res.status(400).json({
        success: false,
        message: 'accountId is required',
      });
    }

    console.log(`🔄 [POST /api/auth/switch] Switching to account ${accountId}...`);

    switchAccount(accountId);

    console.log(`✅ Switched to account ${accountId}`);

    res.json({
      success: true,
      message: `Switched to account ${accountId}`,
      activeAccountId: accountId,
    });

  } catch (error) {
    console.error('❌ Switch account error:', error);
    res.status(400).json({
      success: false,
      message: error.message,
    });
  }
});

/**
 * GET /api/auth/sessions
 * Get all active sessions
 */
router.get('/sessions', verifyApiKey, (req, res) => {
  try {
    console.log('📋 [GET /api/auth/sessions] Getting all sessions...');

    const sessionsData = getAllSessions();

    console.log(`   Total sessions: ${sessionsData.total}`);
    console.log(`   Active account: ${sessionsData.activeAccountId}`);

    res.json({
      success: true,
      ...sessionsData,
    });

  } catch (error) {
    console.error('❌ Get sessions error:', error);
    res.status(500).json({
      success: false,
      message: error.message,
    });
  }
});

/**
 * POST /api/auth/disconnect
 * Disconnect a specific account
 *
 * Body params:
 * - accountId: Account ID to disconnect
 */
router.post('/disconnect', verifyApiKey, async (req, res) => {
  try {
    const accountId = parseInt(req.body.accountId || req.body.account_id);

    if (!accountId) {
      return res.status(400).json({
        success: false,
        message: 'accountId is required',
      });
    }

    console.log(`🔌 [POST /api/auth/disconnect] Disconnecting account ${accountId}...`);

    const result = await disconnectAccount(accountId);

    if (result) {
      console.log(`✅ Account ${accountId} disconnected`);
      res.json({
        success: true,
        message: `Account ${accountId} disconnected`,
      });
    } else {
      res.status(404).json({
        success: false,
        message: `Account ${accountId} not found`,
      });
    }

  } catch (error) {
    console.error('❌ Disconnect account error:', error);
    res.status(500).json({
      success: false,
      message: error.message,
    });
  }
});

module.exports = router;

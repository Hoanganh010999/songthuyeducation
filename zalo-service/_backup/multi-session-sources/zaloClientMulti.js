/**
 * ZALO CLIENT - MULTI-SESSION ARCHITECTURE
 *
 * Supports multiple Zalo accounts connected simultaneously
 * Each account has its own session stored in a Map
 *
 * Architecture:
 * - sessions: Map<accountId, Zalo API instance>
 * - activeAccountId: Currently active account ID
 * - sessionData: Map<accountId, {isInitialized, loginCompleted, wsListener, keepAliveInterval}>
 */

const { Zalo } = require('zalo-api-final');
const fs = require('fs');
const path = require('path');

// ============================================================================
// MULTI-SESSION STATE
// ============================================================================

/**
 * Map of all active sessions
 * Key: accountId (number)
 * Value: Zalo API instance
 */
const sessions = new Map();

/**
 * Map of session metadata
 * Key: accountId (number)
 * Value: { isInitialized, loginCompleted, loginInProgress, wsListener, keepAliveInterval }
 */
const sessionData = new Map();

/**
 * Currently active account ID
 * API calls without explicit accountId will use this account
 */
let activeAccountId = null;

/**
 * Default keep-alive interval (45 seconds)
 */
const KEEP_ALIVE_INTERVAL = 45000;

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Get session for a specific account
 * If no accountId provided, use activeAccountId
 */
function getSession(accountId = null) {
  const targetId = accountId || activeAccountId;

  if (!targetId) {
    console.log('⚠️  No accountId provided and no active account set');
    return null;
  }

  const session = sessions.get(targetId);
  if (!session) {
    console.log(`⚠️  No session found for account ID: ${targetId}`);
    return null;
  }

  return session;
}

/**
 * Get session data (metadata) for an account
 */
function getSessionData(accountId) {
  if (!sessionData.has(accountId)) {
    // Initialize default data
    sessionData.set(accountId, {
      isInitialized: false,
      loginCompleted: false,
      loginInProgress: false,
      wsListener: null,
      keepAliveInterval: null,
      isRestarting: false,
      restartTimeout: null
    });
  }
  return sessionData.get(accountId);
}

/**
 * Update session data
 */
function updateSessionData(accountId, updates) {
  const data = getSessionData(accountId);
  Object.assign(data, updates);
  sessionData.set(accountId, data);
}

/**
 * Generate session ID for storage
 */
function getSessionId(accountId) {
  return `zalo_${accountId}`;
}

/**
 * Get sessions directory path
 */
function getSessionsDir() {
  return path.join(__dirname, '..', 'sessions');
}

/**
 * Ensure sessions directory exists
 */
function ensureSessionsDir() {
  const sessionsDir = getSessionsDir();
  if (!fs.existsSync(sessionsDir)) {
    fs.mkdirSync(sessionsDir, { recursive: true });
    console.log('📁 Created sessions directory:', sessionsDir);
  }
  return sessionsDir;
}

// ============================================================================
// INITIALIZATION
// ============================================================================

/**
 * Initialize Zalo API client with QR Code login
 *
 * @param {number} accountId - Account ID to initialize
 * @param {function} qrCallback - Callback for QR code
 * @param {boolean} forceNew - Force new login even if session exists
 * @returns {Promise<Zalo>} Zalo API instance
 */
async function initializeZalo(accountId, qrCallback, forceNew = false) {
  try {
    console.log(`🔧 [Multi-Session] Initializing account ID: ${accountId}`);
    console.log(`   forceNew: ${forceNew}`);
    console.log(`   Total sessions: ${sessions.size}`);
    console.log(`   Active sessions: ${Array.from(sessions.keys()).join(', ')}`);

    const data = getSessionData(accountId);
    const existingSession = sessions.get(accountId);

    // If already initialized and login completed, return existing instance
    if (!forceNew && data.isInitialized && data.loginCompleted && existingSession) {
      console.log(`✅ Using existing session for account ${accountId}`);

      // Verify it's an API instance
      if (typeof existingSession.getAllFriends === 'function') {
        if (qrCallback && typeof qrCallback === 'function') {
          console.log('⚠️  Already logged in, cannot generate new QR code');
          console.log('   Use forceNew=true to create new session');
        }
        return existingSession;
      } else {
        console.log('⚠️  Existing session is not valid API instance, reinitializing...');
        updateSessionData(accountId, {
          isInitialized: false,
          loginCompleted: false
        });
        sessions.delete(accountId);
      }
    }

    // If forceNew, reset state for this account only
    if (forceNew) {
      console.log(`🔄 Force new login for account ${accountId}, resetting state...`);
      updateSessionData(accountId, {
        isInitialized: false,
        loginCompleted: false,
        loginInProgress: false
      });

      // Note: We DON'T clear other sessions or activeAccountId
      // Only this specific account is reset
    }

    console.log(`🔧 Creating new Zalo client for account ${accountId}...`);

    // Create new Zalo instance
    const zaloClient = new Zalo({
      cookie: '', // Will be set via QR login
      imei: process.env.ZALO_IMEI || '',
      userAgent: process.env.ZALO_USER_AGENT || '',
      selfListen: true, // Enable receiving messages from other devices
    });
    console.log(`   ✅ selfListen enabled for account ${accountId}`);

    // QR login process
    console.log(`🔐 Initiating QR Code login for account ${accountId}...`);

    updateSessionData(accountId, {
      loginInProgress: true,
      loginCompleted: false
    });

    const qrFilePath = path.join(__dirname, '..', `qr_${accountId}.png`);

    // Clean up old QR file if exists
    try {
      if (fs.existsSync(qrFilePath)) {
        fs.unlinkSync(qrFilePath);
        console.log(`🧹 Cleaned up old QR file for account ${accountId}`);
      }
    } catch (error) {
      console.log(`⚠️  Could not delete old QR file: ${error.message}`);
    }

    let qrCodeString = null;
    let qrReceived = false;
    let qrBase64 = null;

    console.log(`📞 Starting loginQR with callback for account ${accountId}...`);

    // loginQR() returns Promise<API> - the API instance
    const loginPromise = zaloClient.loginQR((qr) => {
      console.log(`📱 QR Code callback received for account ${accountId}!`);
      console.log('   QR type:', typeof qr);
      console.log('   QR length:', qr ? qr.length : 0);

      qrCodeString = qr;
      qrReceived = true;

      // Convert QR string to base64 image data URL
      if (qr && qr.startsWith('data:')) {
        qrBase64 = qr;
      } else if (qr) {
        qrBase64 = `data:image/png;base64,${qr}`;
      }

      // Call custom callback if provided
      if (qrCallback && typeof qrCallback === 'function') {
        console.log(`📤 Calling custom QR callback for account ${accountId}...`);
        qrCallback(qrBase64);
      }
    });

    // Handle login completion
    loginPromise.then(async (apiInstance) => {
      console.log(`\n✅✅✅ QR login successful for account ${accountId}!`);
      console.log('   Received API instance');

      // IMPORTANT: Store API instance in sessions Map
      sessions.set(accountId, apiInstance);
      console.log(`   ✅ Stored session for account ${accountId} in Map`);
      console.log(`   Total sessions now: ${sessions.size}`);

      updateSessionData(accountId, {
        isInitialized: true,
        loginCompleted: true,
        loginInProgress: false
      });

      // Set as active if no active account
      if (!activeAccountId) {
        activeAccountId = accountId;
        console.log(`   ✅ Set account ${accountId} as active (first account)`);
      }

      // Try to get and save credentials
      try {
        if (typeof apiInstance.getCookie === 'function') {
          const cookie = await apiInstance.getCookie();
          if (cookie && cookie.cookies) {
            await saveCredentialsForAccount(accountId, cookie);
          }
        }
      } catch (error) {
        console.error(`   ⚠️  Failed to save credentials for account ${accountId}:`, error.message);
      }

      // Start keep-alive for this session
      startKeepAliveForAccount(accountId);

      // Start WebSocket listener for this session
      startWebSocketListenerForAccount(accountId);

      console.log(`🎉 Account ${accountId} fully initialized and ready!`);
    }).catch((error) => {
      console.error(`❌ Login failed for account ${accountId}:`, error);
      updateSessionData(accountId, {
        loginInProgress: false,
        loginCompleted: false
      });
    });

    // Return the zaloClient (not API instance yet)
    // The API instance will be stored in sessions Map after successful login
    return zaloClient;

  } catch (error) {
    console.error(`❌ Initialize error for account ${accountId}:`, error);
    updateSessionData(accountId, {
      loginInProgress: false,
      loginCompleted: false
    });
    throw error;
  }
}

// ============================================================================
// SESSION MANAGEMENT
// ============================================================================

/**
 * Check if a specific account is ready
 */
function isAccountReady(accountId = null) {
  const targetId = accountId || activeAccountId;

  if (!targetId) {
    return false;
  }

  const data = getSessionData(targetId);
  const session = sessions.get(targetId);

  return data.loginCompleted && data.isInitialized && session !== null;
}

/**
 * Switch active account
 */
function switchAccount(accountId) {
  console.log(`🔄 Switching to account ${accountId}...`);

  if (!sessions.has(accountId)) {
    throw new Error(`Account ${accountId} not found in sessions`);
  }

  const data = getSessionData(accountId);
  if (!data.loginCompleted || !data.isInitialized) {
    throw new Error(`Account ${accountId} is not ready`);
  }

  const oldActive = activeAccountId;
  activeAccountId = accountId;

  console.log(`✅ Switched from account ${oldActive} to ${accountId}`);
  console.log(`   Active account is now: ${activeAccountId}`);

  return true;
}

/**
 * Get all active sessions
 */
function getAllSessions() {
  const sessionsList = [];

  for (const [accountId, session] of sessions.entries()) {
    const data = getSessionData(accountId);

    sessionsList.push({
      accountId: accountId,
      sessionId: getSessionId(accountId),
      isConnected: data.loginCompleted && data.isInitialized,
      isActive: accountId === activeAccountId
    });
  }

  return {
    sessions: sessionsList,
    activeAccountId: activeAccountId,
    total: sessionsList.length
  };
}

/**
 * Disconnect a specific account
 */
async function disconnectAccount(accountId) {
  console.log(`🔌 Disconnecting account ${accountId}...`);

  if (!sessions.has(accountId)) {
    console.log(`   Account ${accountId} not found in sessions`);
    return false;
  }

  // Stop keep-alive
  const data = getSessionData(accountId);
  if (data.keepAliveInterval) {
    clearInterval(data.keepAliveInterval);
    updateSessionData(accountId, { keepAliveInterval: null });
  }

  // Stop WebSocket listener
  if (data.wsListener) {
    try {
      const session = sessions.get(accountId);
      if (session && session.listener && typeof session.listener.stopListening === 'function') {
        await session.listener.stopListening();
      }
    } catch (error) {
      console.error(`   Error stopping listener: ${error.message}`);
    }
    updateSessionData(accountId, { wsListener: null });
  }

  // Remove from sessions
  sessions.delete(accountId);
  sessionData.delete(accountId);

  // If this was active account, switch to another or clear
  if (activeAccountId === accountId) {
    const remainingSessions = Array.from(sessions.keys());
    if (remainingSessions.length > 0) {
      activeAccountId = remainingSessions[0];
      console.log(`   Switched active account to ${activeAccountId}`);
    } else {
      activeAccountId = null;
      console.log(`   No remaining sessions, cleared active account`);
    }
  }

  console.log(`✅ Account ${accountId} disconnected`);
  console.log(`   Remaining sessions: ${sessions.size}`);

  return true;
}

/**
 * Get current Zalo client (for backward compatibility)
 */
function getZaloClient(accountId = null) {
  return getSession(accountId);
}

// ============================================================================
// KEEP-ALIVE
// ============================================================================

/**
 * Start keep-alive for a specific account
 */
function startKeepAliveForAccount(accountId) {
  const session = sessions.get(accountId);
  const data = getSessionData(accountId);

  if (!session || typeof session.keepAlive !== 'function') {
    console.log(`⚠️  Cannot start keep-alive for account ${accountId}: No valid session`);
    return;
  }

  // Clear existing interval if any
  if (data.keepAliveInterval) {
    clearInterval(data.keepAliveInterval);
  }

  console.log(`🔄 Starting keep-alive for account ${accountId} (interval: ${KEEP_ALIVE_INTERVAL}ms)`);

  const interval = setInterval(async () => {
    try {
      const currentSession = sessions.get(accountId);
      if (currentSession && typeof currentSession.keepAlive === 'function') {
        await currentSession.keepAlive();
        // Silent - don't log every keep-alive
      }
    } catch (error) {
      console.error(`❌ Keep-alive failed for account ${accountId}:`, error.message);
    }
  }, KEEP_ALIVE_INTERVAL);

  updateSessionData(accountId, { keepAliveInterval: interval });
  console.log(`✅ Keep-alive started for account ${accountId}`);
}

/**
 * Stop keep-alive for all accounts
 */
function stopKeepAlive() {
  console.log('🛑 Stopping keep-alive for all accounts...');

  for (const [accountId, data] of sessionData.entries()) {
    if (data.keepAliveInterval) {
      clearInterval(data.keepAliveInterval);
      console.log(`   Stopped keep-alive for account ${accountId}`);
    }
  }
}

// ============================================================================
// WEBSOCKET LISTENER
// ============================================================================

/**
 * Start WebSocket listener for a specific account
 */
async function startWebSocketListenerForAccount(accountId) {
  const session = sessions.get(accountId);
  const data = getSessionData(accountId);

  if (!session) {
    console.log(`⚠️  Cannot start listener for account ${accountId}: No session`);
    return;
  }

  console.log(`🎧 Starting WebSocket listener for account ${accountId}...`);

  try {
    if (!session.listener || typeof session.listener.start !== 'function') {
      console.log(`⚠️  No listener available for account ${accountId}`);
      return;
    }

    // Start listening
    session.listener.start(async (error, message) => {
      if (error) {
        console.error(`❌ Listener error for account ${accountId}:`, error);
        return;
      }

      // Forward message to Laravel webhook
      // Include accountId in the webhook
      try {
        const laravelUrl = process.env.LARAVEL_URL || 'http://127.0.0.1:8000';
        const webhookUrl = `${laravelUrl}/api/zalo/webhook/message`;

        // Add accountId to message data
        const messageWithAccount = {
          ...message,
          account_id: accountId
        };

        // Send to Laravel (implement this based on your needs)
        // await sendToLaravelWebhook(webhookUrl, messageWithAccount);
      } catch (webhookError) {
        console.error(`Error sending to webhook for account ${accountId}:`, webhookError);
      }
    });

    updateSessionData(accountId, { wsListener: true });
    console.log(`✅ WebSocket listener started for account ${accountId}`);

  } catch (error) {
    console.error(`❌ Failed to start listener for account ${accountId}:`, error);
  }
}

/**
 * Stop all WebSocket listeners
 */
async function stopWebSocketListener() {
  console.log('🛑 Stopping WebSocket listeners for all accounts...');

  for (const [accountId, session] of sessions.entries()) {
    try {
      if (session.listener && typeof session.listener.stopListening === 'function') {
        await session.listener.stopListening();
        console.log(`   Stopped listener for account ${accountId}`);
      }
    } catch (error) {
      console.error(`   Error stopping listener for account ${accountId}:`, error.message);
    }
  }
}

// ============================================================================
// CREDENTIALS MANAGEMENT
// ============================================================================

/**
 * Save credentials for a specific account
 */
async function saveCredentialsForAccount(accountId, cookie) {
  console.log(`💾 Saving credentials for account ${accountId}...`);

  // Save to sessions directory
  ensureSessionsDir();
  const sessionFile = path.join(getSessionsDir(), `${getSessionId(accountId)}.json`);

  try {
    const credentialsData = {
      accountId: accountId,
      cookie: cookie,
      imei: process.env.ZALO_IMEI || '',
      userAgent: process.env.ZALO_USER_AGENT || '',
      savedAt: new Date().toISOString()
    };

    fs.writeFileSync(sessionFile, JSON.stringify(credentialsData, null, 2));
    console.log(`   ✅ Credentials saved to: ${sessionFile}`);
  } catch (error) {
    console.error(`   ❌ Failed to save credentials: ${error.message}`);
  }
}

// ============================================================================
// EXPORTS
// ============================================================================

module.exports = {
  // Initialization
  initializeZalo,

  // Session management
  isAccountReady,
  isZaloReady: () => isAccountReady(), // Backward compatibility
  switchAccount,
  getAllSessions,
  disconnectAccount,
  getZaloClient,
  getSession,

  // Multi-session specific
  sessions,
  sessionData,
  getActiveAccountId: () => activeAccountId,
  setActiveAccountId: (id) => { activeAccountId = id; },

  // Keep-alive
  stopKeepAlive,

  // WebSocket
  stopWebSocketListener,
};

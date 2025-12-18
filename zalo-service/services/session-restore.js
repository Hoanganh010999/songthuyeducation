/**
 * SESSION RESTORE SERVICE - IMPROVED VERSION
 *
 * Improvements:
 * - Retry with exponential backoff before giving up
 * - Rate limit QR generation
 * - Better error classification (network vs auth errors)
 * - Smarter alert handling
 */

const fs = require('fs');
const path = require('path');
const { Zalo } = require('zalo-api-final');
const telegram = require('./telegram');

// Configuration
const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.0';
const DEFAULT_IMEI = 'default_imei_' + Date.now();
const MAX_RESTORE_RETRIES = 3;
const RETRY_DELAY_BASE = 5000; // 5 seconds
const QR_GENERATION_DELAY = 10000; // Wait 10s before generating QR after failure

/**
 * Get sessions directory path
 */
function getSessionsDir() {
  return path.join(__dirname, '..', 'sessions');
}

/**
 * Check if error is network-related
 */
function isNetworkError(error) {
  const networkErrors = [
    'ENOTFOUND', 'ETIMEDOUT', 'ECONNREFUSED', 'ECONNRESET',
    'EAI_AGAIN', 'ENETUNREACH', 'EHOSTUNREACH', 'fetch failed',
    'network', 'timeout', 'socket hang up'
  ];
  const errorStr = error.message?.toLowerCase() || '';
  return networkErrors.some(e => errorStr.includes(e.toLowerCase()));
}

/**
 * Check if error is authentication-related
 */
function isAuthError(error) {
  const authErrors = [
    'Đăng nhập thất bại', 'login failed', 'invalid session',
    'expired', 'unauthorized', 'invalid token', 'credential'
  ];
  const errorStr = error.message?.toLowerCase() || '';
  return authErrors.some(e => errorStr.includes(e.toLowerCase()));
}

/**
 * Sleep utility
 */
function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Calculate retry delay with exponential backoff and jitter
 */
function getRetryDelay(attempt) {
  const delay = RETRY_DELAY_BASE * Math.pow(2, attempt);
  const jitter = delay * 0.2 * (Math.random() - 0.5);
  return Math.floor(delay + jitter);
}

/**
 * Load all saved sessions from files
 */
function loadSavedSessions() {
  const sessionsDir = getSessionsDir();
  const sessions = [];

  if (!fs.existsSync(sessionsDir)) {
    console.log('[SessionRestore] Sessions directory not found');
    return sessions;
  }

  const files = fs.readdirSync(sessionsDir);

  for (const file of files) {
    // Skip invalid, temp files and non-json files
    if (!file.endsWith('.json') || file.startsWith('temp_') || file.startsWith('invalid_')) {
      continue;
    }

    try {
      const filePath = path.join(sessionsDir, file);
      const data = JSON.parse(fs.readFileSync(filePath, 'utf8'));

      if (data.accountId && data.cookie) {
        sessions.push({
          file: file,
          accountId: data.accountId,
          cookie: data.cookie,
          imei: data.imei || DEFAULT_IMEI,
          userAgent: data.userAgent || DEFAULT_USER_AGENT,
          savedAt: data.savedAt
        });
        console.log(`[SessionRestore] Found session: account ${data.accountId} (saved: ${data.savedAt})`);
      }
    } catch (error) {
      console.error(`[SessionRestore] Error reading ${file}:`, error.message);
    }
  }

  return sessions;
}

/**
 * Attempt to restore a single session with retries
 */
async function attemptRestore(sessionData, attempt = 0) {
  const { accountId, cookie, imei, userAgent } = sessionData;

  try {
    console.log(`[SessionRestore] Attempt ${attempt + 1}/${MAX_RESTORE_RETRIES} for account ${accountId}...`);

    // Create Zalo client
    const zaloClient = new Zalo({
      logging: false,
      selfListen: true
    });

    // Prepare credentials
    const credentials = {
      cookie: cookie,
      imei: imei || DEFAULT_IMEI,
      userAgent: userAgent || DEFAULT_USER_AGENT,
      language: 'vi'
    };

    // Login with credentials
    const api = await zaloClient.login(credentials);

    if (!api) {
      throw new Error('API instance is null after login');
    }

    // Verify session works
    if (typeof api.getOwnId === 'function') {
      const ownId = await api.getOwnId();
      console.log(`[SessionRestore] ✅ Account ${accountId} verified, zalo_id: ${ownId}`);
    }

    return { success: true, api };

  } catch (error) {
    console.warn(`[SessionRestore] Attempt ${attempt + 1} failed for account ${accountId}:`, error.message);

    // Check error type
    const errorInfo = {
      isNetwork: isNetworkError(error),
      isAuth: isAuthError(error),
      message: error.message
    };

    // If network error and more retries available, retry
    if (errorInfo.isNetwork && attempt < MAX_RESTORE_RETRIES - 1) {
      const delay = getRetryDelay(attempt);
      console.log(`[SessionRestore] 🌐 Network error, retrying in ${delay}ms...`);
      await sleep(delay);
      return attemptRestore(sessionData, attempt + 1);
    }

    // If auth error, no point retrying
    if (errorInfo.isAuth) {
      console.log(`[SessionRestore] 🔐 Auth error, session needs re-login`);
    }

    return { success: false, error: errorInfo };
  }
}

/**
 * Restore a single session with retry logic
 */
async function restoreSession(sessionData, sessionsMap, sessionDataMap, startKeepAlive, startWebSocketListener, startHealthMonitor) {
  const { accountId } = sessionData;

  console.log(`[SessionRestore] Restoring session for account ${accountId}...`);
  console.log(`[SessionRestore] Logging in account ${accountId} with saved cookie...`);

  // Attempt restore with retries
  const result = await attemptRestore(sessionData);

  if (result.success) {
    const api = result.api;

    // Store in sessions map
    sessionsMap.set(accountId, api);

    // Store metadata
    sessionDataMap.set(accountId, {
      isInitialized: true,
      loginCompleted: true,
      loginInProgress: false,
      wsListener: null,
      keepAliveInterval: null,
      restoredFromFile: true
    });

    // Start services
    if (typeof startKeepAlive === 'function') {
      startKeepAlive(accountId);
    }
    if (typeof startWebSocketListener === 'function') {
      startWebSocketListener(accountId);
    }
    if (typeof startHealthMonitor === 'function') {
      startHealthMonitor(accountId);
    }

    console.log(`[SessionRestore] ✅ Session restored for account ${accountId}`);

    // Notify success
    try {
      await telegram.sendConnectionRestored(accountId, result.api.getOwnId ? await result.api.getOwnId() : 'unknown');
    } catch (e) {
      // Ignore telegram errors
    }

    return true;
  }

  // Restore failed
  console.error(`[SessionRestore] ❌ Failed to restore account ${accountId}: ${result.error?.message}`);

  // Send Telegram alert (only once)
  try {
    const errorType = result.error?.isNetwork ? 'Network error' : (result.error?.isAuth ? 'Session expired' : 'Unknown error');
    await telegram.sendDisconnectAlert(accountId, `${errorType}: ${result.error?.message}`);
    console.log(`[SessionRestore] Telegram alert sent for account ${accountId}`);
  } catch (telegramError) {
    console.warn('[SessionRestore] Failed to send Telegram alert:', telegramError.message);
  }

  // Mark session file as invalid (only for auth errors)
  if (result.error?.isAuth) {
    try {
      const sessionsDir = getSessionsDir();
      const oldFile = path.join(sessionsDir, `zalo_${accountId}.json`);
      const invalidFile = path.join(sessionsDir, `invalid_zalo_${accountId}.json`);
      if (fs.existsSync(oldFile)) {
        fs.renameSync(oldFile, invalidFile);
        console.log('[SessionRestore] Marked session file as invalid');
      }
    } catch (e) {
      // Ignore
    }
  }

  return { success: false, accountId, error: result.error };
}

/**
 * Restore session with QR fallback (rate limited)
 */
async function restoreSessionWithQR(sessionData, sessionsMap, sessionDataMap, callbacks) {
  const result = await restoreSession(
    sessionData,
    sessionsMap,
    sessionDataMap,
    callbacks.startKeepAlive,
    callbacks.startWebSocketListener,
    callbacks.startHealthMonitor
  );

  // If restore succeeded, return true
  if (result === true) {
    return true;
  }

  // If restore failed and triggerQRLogin is available
  if (result && !result.success && typeof callbacks.triggerQRLogin === 'function') {
    const accountId = result.accountId || sessionData.accountId;

    // Only generate QR for auth errors, not network errors
    // For network errors, the session might still be valid
    if (result.error?.isNetwork) {
      console.log(`[SessionRestore] ⏳ Network error for account ${accountId}, skipping QR (session might still be valid)`);
      console.log(`[SessionRestore] 💡 Will retry on next health check`);
      return false;
    }

    // Wait a bit before generating QR to avoid race conditions
    console.log(`[SessionRestore] ⏳ Waiting ${QR_GENERATION_DELAY / 1000}s before generating QR...`);
    await sleep(QR_GENERATION_DELAY);

    console.log(`[SessionRestore] 🔄 Triggering QR login for account ${accountId}...`);

    try {
      const qrPath = await callbacks.triggerQRLogin(accountId);
      if (qrPath) {
        await telegram.sendQRCode(accountId, qrPath);
        console.log(`[SessionRestore] ✅ QR code sent to Telegram for account ${accountId}`);
      }
    } catch (qrError) {
      console.error('[SessionRestore] ❌ Failed to generate/send QR:', qrError.message);
    }
  }

  return false;
}

/**
 * Load invalid sessions (accounts that need re-login)
 */
function loadInvalidSessions() {
  const sessionsDir = getSessionsDir();
  const invalidSessions = [];

  if (!fs.existsSync(sessionsDir)) {
    return invalidSessions;
  }

  const files = fs.readdirSync(sessionsDir);

  for (const file of files) {
    // Only look for invalid_zalo_*.json files
    if (!file.startsWith('invalid_zalo_') || !file.endsWith('.json')) {
      continue;
    }

    try {
      const filePath = path.join(sessionsDir, file);
      const data = JSON.parse(fs.readFileSync(filePath, 'utf8'));

      if (data.accountId) {
        invalidSessions.push({
          file: file,
          accountId: data.accountId,
          savedAt: data.savedAt
        });
        console.log(`[SessionRestore] Found INVALID session: account ${data.accountId}`);
      }
    } catch (error) {
      console.error(`[SessionRestore] Error reading ${file}:`, error.message);
    }
  }

  return invalidSessions;
}

/**
 * Restore all saved sessions
 */
async function restoreAllSessions(sessionsMap, sessionDataMap, callbacks = {}) {
  console.log('[SessionRestore] ============================================');
  console.log('[SessionRestore] Starting automatic session restore (improved)');
  console.log('[SessionRestore] ============================================');
  console.log(`[SessionRestore] Config: max_retries=${MAX_RESTORE_RETRIES}, base_delay=${RETRY_DELAY_BASE}ms`);

  const savedSessions = loadSavedSessions();
  const invalidSessions = loadInvalidSessions();

  // Handle invalid sessions - send alerts and trigger QR
  if (invalidSessions.length > 0 && callbacks.triggerQRLogin) {
    console.log(`[SessionRestore] Found ${invalidSessions.length} INVALID session(s) - triggering QR login...`);

    for (const invalid of invalidSessions) {
      console.log(`[SessionRestore] 🚨 Account ${invalid.accountId} needs re-login`);

      // Send disconnect alert
      try {
        await telegram.sendDisconnectAlert(invalid.accountId, 'Session đã hết hạn. Vui lòng quét lại mã QR.');
        console.log(`[SessionRestore] ✅ Disconnect alert sent for account ${invalid.accountId}`);
      } catch (e) {
        console.warn(`[SessionRestore] Failed to send disconnect alert: ${e.message}`);
      }

      // Trigger QR login
      try {
        console.log(`[SessionRestore] 🔄 Generating QR for account ${invalid.accountId}...`);
        const qrPath = await callbacks.triggerQRLogin(invalid.accountId);
        if (qrPath) {
          await telegram.sendQRCode(invalid.accountId, qrPath);
          console.log(`[SessionRestore] ✅ QR code sent to Telegram for account ${invalid.accountId}`);
        }
      } catch (qrError) {
        console.error(`[SessionRestore] ❌ Failed to generate/send QR: ${qrError.message}`);
      }

      // Delay between accounts
      if (invalidSessions.length > 1) {
        await sleep(3000);
      }
    }
  }

  if (savedSessions.length === 0) {
    console.log('[SessionRestore] No valid saved sessions found');
    return { total: invalidSessions.length, restored: 0, failed: invalidSessions.length, firstAccountId: null };
  }

  console.log(`[SessionRestore] Found ${savedSessions.length} saved session(s)`);

  let restored = 0;
  let failed = 0;
  let firstAccountId = null;

  for (const sessionData of savedSessions) {
    const success = await restoreSessionWithQR(
      sessionData,
      sessionsMap,
      sessionDataMap,
      callbacks
    );

    if (success) {
      restored++;
      if (!firstAccountId) {
        firstAccountId = sessionData.accountId;
      }
    } else {
      failed++;
    }

    // Small delay between accounts to avoid overwhelming Zalo servers
    if (savedSessions.length > 1) {
      await sleep(2000);
    }
  }

  console.log('[SessionRestore] ============================================');
  console.log(`[SessionRestore] Restore complete: ${restored} restored, ${failed} failed`);
  console.log('[SessionRestore] ============================================');

  return {
    total: savedSessions.length,
    restored,
    failed,
    firstAccountId
  };
}

module.exports = {
  loadSavedSessions,
  restoreSession,
  restoreAllSessions,
  attemptRestore
};

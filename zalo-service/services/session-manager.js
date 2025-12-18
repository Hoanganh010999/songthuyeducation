/**
 * SESSION MANAGER SERVICE - IMPROVED VERSION
 *
 * Improvements:
 * - Retry with exponential backoff before triggering QR
 * - Rate limit QR generation (max 1 per 5 minutes per account)
 * - Smart alert system (no Telegram spam)
 * - Better session health check with grace period
 * - Network error detection and handling
 */

const fs = require('fs');
const path = require('path');
const http = require('http');
const https = require('https');
const telegram = require('./telegram');

// Configuration
const SAVE_INTERVAL = 60 * 60 * 1000; // Save every 1 hour
const CHECK_INTERVAL = 60 * 1000; // Check health every 1 minute
const QR_RATE_LIMIT = 5 * 60 * 1000; // Only allow QR generation every 5 minutes
const MAX_RETRY_ATTEMPTS = 5; // Max retries before triggering QR
const INITIAL_RETRY_DELAY = 5000; // 5 seconds initial delay
const MAX_RETRY_DELAY = 60000; // Max 1 minute delay
const ALERT_COOLDOWN = 10 * 60 * 1000; // 10 minutes between same alerts
const NETWORK_ERROR_GRACE_PERIOD = 3 * 60 * 1000; // 3 minutes grace for network errors

let saveTimer = null;
let checkTimer = null;
let sessionsRef = null;
let sessionDataRef = null;
let triggerQRLoginRef = null;

// State tracking
let keepAliveFailureCount = new Map(); // Track consecutive failures per account
let lastQRTriggerTime = new Map(); // Track last QR trigger time per account
let lastAlertTime = new Map(); // Track last alert time per account per type
let retryState = new Map(); // Track retry state per account
let networkErrorStart = new Map(); // Track when network errors started

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
  }
  return sessionsDir;
}

/**
 * Check if it's a network-related error
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
 * Check if it's an authentication error
 */
function isAuthError(error) {
  const authErrors = [
    'Đăng nhập', 'login', 'session', 'expired', 'unauthorized',
    'invalid token', 'authentication', 'credential'
  ];
  const errorStr = error.message?.toLowerCase() || '';
  return authErrors.some(e => errorStr.includes(e.toLowerCase()));
}

/**
 * Calculate exponential backoff delay
 */
function getRetryDelay(attempt) {
  const delay = Math.min(INITIAL_RETRY_DELAY * Math.pow(2, attempt), MAX_RETRY_DELAY);
  // Add jitter (±20%)
  const jitter = delay * 0.2 * (Math.random() - 0.5);
  return Math.floor(delay + jitter);
}

/**
 * Check if we can send alert (rate limiting)
 */
function canSendAlert(accountId, alertType) {
  const key = `${accountId}_${alertType}`;
  const lastTime = lastAlertTime.get(key) || 0;
  const now = Date.now();

  if (now - lastTime < ALERT_COOLDOWN) {
    return false;
  }

  lastAlertTime.set(key, now);
  return true;
}

/**
 * Check if we can trigger QR (rate limiting)
 */
function canTriggerQR(accountId) {
  const lastTime = lastQRTriggerTime.get(accountId) || 0;
  const now = Date.now();

  if (now - lastTime < QR_RATE_LIMIT) {
    const waitTime = Math.ceil((QR_RATE_LIMIT - (now - lastTime)) / 1000);
    console.log(`[SessionManager] ⏳ QR rate limited for account ${accountId}, wait ${waitTime}s`);
    return false;
  }

  return true;
}

/**
 * Save a single session to file
 */
async function saveSessionToFile(accountId, session) {
  try {
    if (!session || typeof session.getCookie !== 'function') {
      console.log(`[SessionManager] Cannot save account ${accountId}: Invalid session`);
      return false;
    }

    const cookie = await session.getCookie();
    if (!cookie) {
      console.log(`[SessionManager] Cannot save account ${accountId}: No cookie`);
      return false;
    }

    ensureSessionsDir();
    const sessionFile = path.join(getSessionsDir(), `zalo_${accountId}.json`);

    const credentialsData = {
      accountId: accountId,
      cookie: cookie,
      imei: process.env.ZALO_IMEI || '',
      userAgent: process.env.ZALO_USER_AGENT || '',
      savedAt: new Date().toISOString()
    };

    fs.writeFileSync(sessionFile, JSON.stringify(credentialsData, null, 2));
    console.log(`[SessionManager] ✅ Saved session for account ${accountId}`);
    return true;
  } catch (error) {
    console.error(`[SessionManager] ❌ Failed to save account ${accountId}:`, error.message);
    return false;
  }
}

/**
 * Save all active sessions to files
 */
async function saveAllSessions() {
  if (!sessionsRef) {
    console.log('[SessionManager] No sessions reference set');
    return { saved: 0, failed: 0 };
  }

  console.log('[SessionManager] 💾 Saving all sessions...');
  let saved = 0;
  let failed = 0;

  for (const [accountId, session] of sessionsRef.entries()) {
    if (typeof accountId !== 'number') continue;

    const success = await saveSessionToFile(accountId, session);
    if (success) {
      saved++;
    } else {
      failed++;
    }
  }

  console.log(`[SessionManager] Save complete: ${saved} saved, ${failed} failed`);
  return { saved, failed };
}

/**
 * Notify Laravel about session status
 */
async function notifyLaravel(accountId, status, message) {
  const laravelUrl = process.env.LARAVEL_URL || 'http://127.0.0.1:8000';
  const apiKey = process.env.API_SECRET_KEY;

  if (!apiKey) {
    console.warn('[SessionManager] No API_SECRET_KEY, cannot notify Laravel');
    return false;
  }

  try {
    const url = new URL('/api/zalo/session-status', laravelUrl);
    const isHttps = url.protocol === 'https:';
    const httpModule = isHttps ? https : http;

    const postData = JSON.stringify({
      account_id: accountId,
      status: status,
      message: message,
      timestamp: new Date().toISOString()
    });

    return new Promise((resolve, reject) => {
      const req = httpModule.request({
        hostname: url.hostname,
        port: url.port || (isHttps ? 443 : 80),
        path: url.pathname,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(postData),
          'X-API-Key': apiKey
        },
        rejectUnauthorized: false,
        timeout: 10000
      }, (res) => {
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
          console.log(`[SessionManager] Laravel notified: ${status} for account ${accountId}`);
          resolve(true);
        });
      });

      req.on('error', (error) => {
        console.error('[SessionManager] Failed to notify Laravel:', error.message);
        resolve(false);
      });

      req.on('timeout', () => {
        req.destroy();
        resolve(false);
      });

      req.write(postData);
      req.end();
    });
  } catch (error) {
    console.error('[SessionManager] Error notifying Laravel:', error.message);
    return false;
  }
}

/**
 * Handle session expired with rate limiting and smart alerts
 */
async function handleSessionExpired(accountId, reason, skipQR = false) {
  console.log(`[SessionManager] 🚨 Session issue for account ${accountId}: ${reason}`);

  // Send Telegram alert (rate limited)
  if (canSendAlert(accountId, 'disconnect')) {
    await telegram.sendDisconnectAlert(accountId, reason);
  } else {
    console.log(`[SessionManager] ⏳ Telegram alert skipped (cooldown) for account ${accountId}`);
  }

  // Notify Laravel
  await notifyLaravel(accountId, 'expired', reason);

  // Trigger QR login if allowed
  if (!skipQR && triggerQRLoginRef && canTriggerQR(accountId)) {
    console.log(`[SessionManager] 🔄 Triggering QR login for account ${accountId}...`);
    lastQRTriggerTime.set(accountId, Date.now());

    try {
      const qrPath = await triggerQRLoginRef(accountId);
      if (qrPath) {
        // Send QR code to Telegram (always send QR, not rate limited)
        await telegram.sendQRCode(accountId, qrPath);
      }
    } catch (error) {
      console.error(`[SessionManager] ❌ Failed to trigger QR login:`, error.message);
    }
  } else if (!canTriggerQR(accountId)) {
    console.log(`[SessionManager] ⏳ QR generation skipped (rate limited) for account ${accountId}`);
  }
}

/**
 * Retry session verification with exponential backoff
 */
async function retrySessionVerification(accountId, session, maxAttempts = MAX_RETRY_ATTEMPTS) {
  const state = retryState.get(accountId) || { attempt: 0, lastError: null };

  for (let attempt = state.attempt; attempt < maxAttempts; attempt++) {
    try {
      if (session && typeof session.getOwnId === 'function') {
        await session.getOwnId();
        // Success - reset state
        retryState.delete(accountId);
        networkErrorStart.delete(accountId);
        return { success: true };
      }
      return { success: false, reason: 'Invalid session object' };
    } catch (error) {
      state.attempt = attempt + 1;
      state.lastError = error.message;
      retryState.set(accountId, state);

      // Check if network error
      if (isNetworkError(error)) {
        // Track network error start time
        if (!networkErrorStart.has(accountId)) {
          networkErrorStart.set(accountId, Date.now());
        }

        const errorDuration = Date.now() - networkErrorStart.get(accountId);

        // If within grace period, don't trigger QR
        if (errorDuration < NETWORK_ERROR_GRACE_PERIOD) {
          console.log(`[SessionManager] 🌐 Network error for account ${accountId}, waiting (${Math.ceil((NETWORK_ERROR_GRACE_PERIOD - errorDuration) / 1000)}s grace remaining)`);

          // Wait and retry
          if (attempt < maxAttempts - 1) {
            const delay = getRetryDelay(attempt);
            console.log(`[SessionManager] ⏳ Retry ${attempt + 1}/${maxAttempts} for account ${accountId} in ${delay}ms`);
            await new Promise(resolve => setTimeout(resolve, delay));
            continue;
          }
        }

        return { success: false, reason: `Network error: ${error.message}`, isNetwork: true };
      }

      // Auth error - don't retry, need re-login
      if (isAuthError(error)) {
        return { success: false, reason: `Auth error: ${error.message}`, isAuth: true };
      }

      // Other errors - retry with backoff
      if (attempt < maxAttempts - 1) {
        const delay = getRetryDelay(attempt);
        console.log(`[SessionManager] ⏳ Retry ${attempt + 1}/${maxAttempts} for account ${accountId} in ${delay}ms`);
        await new Promise(resolve => setTimeout(resolve, delay));
      }
    }
  }

  return { success: false, reason: state.lastError || 'Max retries exceeded' };
}

/**
 * Check session health with improved error handling
 */
async function checkSessionHealth() {
  if (!sessionsRef || !sessionDataRef) {
    return;
  }

  // Log less frequently to reduce noise
  const shouldLog = Math.random() < 0.1; // 10% chance
  if (shouldLog) {
    console.log('[SessionManager] 🔍 Checking session health...');
  }

  for (const [accountId, data] of sessionDataRef.entries()) {
    if (typeof accountId !== 'number') continue;

    const session = sessionsRef.get(accountId);

    // Check if session is supposed to be active
    if (data.loginCompleted && data.isInitialized) {
      const result = await retrySessionVerification(accountId, session, 3);

      if (result.success) {
        // Session is healthy - reset all failure tracking
        keepAliveFailureCount.set(accountId, 0);
        networkErrorStart.delete(accountId);
        retryState.delete(accountId);
      } else {
        // Track failure
        const failures = (keepAliveFailureCount.get(accountId) || 0) + 1;
        keepAliveFailureCount.set(accountId, failures);

        console.warn(`[SessionManager] ⚠️ Account ${accountId} health check failed (${failures}x): ${result.reason}`);

        // Handle based on error type
        if (result.isAuth) {
          // Auth error - need re-login immediately
          await handleSessionExpired(accountId, result.reason);
          data.loginCompleted = false;
          data.isInitialized = false;
          keepAliveFailureCount.set(accountId, 0);
        } else if (result.isNetwork) {
          // Network error - be more patient
          if (failures >= 5) {
            // Send warning but don't trigger QR yet
            if (canSendAlert(accountId, 'network')) {
              await telegram.sendKeepAliveFailure(accountId, `Network issues detected: ${result.reason}`);
            }
          }
          if (failures >= 10) {
            // After 10 consecutive failures, trigger QR
            await handleSessionExpired(accountId, `Persistent network issues: ${result.reason}`);
            data.loginCompleted = false;
            data.isInitialized = false;
            keepAliveFailureCount.set(accountId, 0);
          }
        } else {
          // Other errors
          if (failures >= 3) {
            await handleSessionExpired(accountId, result.reason);
            data.loginCompleted = false;
            data.isInitialized = false;
            keepAliveFailureCount.set(accountId, 0);
          } else if (failures === 1 && canSendAlert(accountId, 'warning')) {
            await telegram.sendKeepAliveFailure(accountId, result.reason);
          }
        }
      }
    }
  }
}

/**
 * Start the session manager
 */
async function start(sessions, sessionData, options = {}) {
  sessionsRef = sessions;
  sessionDataRef = sessionData;
  triggerQRLoginRef = options.triggerQRLogin || null;

  console.log('[SessionManager] 🚀 Starting improved session manager...');
  console.log(`[SessionManager]    Save interval: ${SAVE_INTERVAL / 60000} minutes`);
  console.log(`[SessionManager]    Health check interval: ${CHECK_INTERVAL / 1000} seconds`);
  console.log(`[SessionManager]    QR rate limit: ${QR_RATE_LIMIT / 60000} minutes`);
  console.log(`[SessionManager]    Max retry attempts: ${MAX_RETRY_ATTEMPTS}`);
  console.log(`[SessionManager]    Alert cooldown: ${ALERT_COOLDOWN / 60000} minutes`);
  console.log(`[SessionManager]    Network grace period: ${NETWORK_ERROR_GRACE_PERIOD / 60000} minutes`);

  const telegramConfigured = await telegram.isConfigured();
  console.log(`[SessionManager]    Telegram alerts: ${telegramConfigured ? '✅ Enabled' : '❌ Not configured'}`);

  if (telegramConfigured) {
    await telegram.updateSettingsCache();
    console.log('[SessionManager]    Telegram settings loaded from Laravel API');
  }

  // Periodic session save
  saveTimer = setInterval(async () => {
    await saveAllSessions();
  }, SAVE_INTERVAL);

  // Periodic health check
  checkTimer = setInterval(async () => {
    await checkSessionHealth();
  }, CHECK_INTERVAL);

  // Initial save after 1 minute
  setTimeout(async () => {
    await saveAllSessions();
  }, 60000);

  console.log('[SessionManager] ✅ Improved session manager started');
}

/**
 * Stop the session manager
 */
function stop() {
  console.log('[SessionManager] 🛑 Stopping session manager...');

  if (saveTimer) {
    clearInterval(saveTimer);
    saveTimer = null;
  }

  if (checkTimer) {
    clearInterval(checkTimer);
    checkTimer = null;
  }

  saveAllSessions().then(() => {
    console.log('[SessionManager] Final save completed');
  });

  console.log('[SessionManager] ✅ Session manager stopped');
}

/**
 * Force save all sessions immediately
 */
async function forceSave() {
  return await saveAllSessions();
}

/**
 * Reset failure count for an account
 */
function resetFailureCount(accountId) {
  keepAliveFailureCount.set(accountId, 0);
  retryState.delete(accountId);
  networkErrorStart.delete(accountId);
}

/**
 * Send connection restored notification
 */
async function notifyConnectionRestored(accountId, zaloId) {
  await telegram.sendConnectionRestored(accountId, zaloId);
  await notifyLaravel(accountId, 'connected', 'Session restored successfully');
  resetFailureCount(accountId);

  // Reset QR rate limit on successful connection
  lastQRTriggerTime.delete(accountId);
}

/**
 * Get current state for debugging
 */
function getState() {
  return {
    failureCounts: Object.fromEntries(keepAliveFailureCount),
    lastQRTriggers: Object.fromEntries(lastQRTriggerTime),
    lastAlerts: Object.fromEntries(lastAlertTime),
    retryStates: Object.fromEntries(retryState),
    networkErrors: Object.fromEntries(networkErrorStart)
  };
}

module.exports = {
  start,
  stop,
  forceSave,
  saveSessionToFile,
  saveAllSessions,
  notifyLaravel,
  checkSessionHealth,
  resetFailureCount,
  notifyConnectionRestored,
  handleSessionExpired,
  getState,
  canTriggerQR,
  canSendAlert
};

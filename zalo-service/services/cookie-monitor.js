/**
 * COOKIE EXPIRY MONITOR SERVICE
 *
 * Monitors cookie expiration dates and sends alerts before they expire.
 * This ensures continuity by warning users to re-login BEFORE cookies expire.
 */

const fs = require('fs');
const path = require('path');
const telegram = require('./telegram');

// Configuration
const CHECK_INTERVAL = 24 * 60 * 60 * 1000; // Check once per day
const WARNING_DAYS_BEFORE = 7; // Warn 7 days before expiry
const CRITICAL_DAYS_BEFORE = 3; // Critical alert 3 days before expiry

let checkTimer = null;
let lastWarningTime = new Map(); // Track when we last sent warning per account

/**
 * Get sessions directory path
 */
function getSessionsDir() {
  return path.join(__dirname, '..', 'sessions');
}

/**
 * Parse cookie and extract expiry information
 */
function getCookieExpiry(cookieData) {
  if (!cookieData || !cookieData.cookies) {
    return null;
  }

  let earliestExpiry = null;
  let criticalCookie = null;

  for (const cookie of cookieData.cookies) {
    if (!cookie.maxAge || !cookie.creation) continue;

    // Calculate expiry date
    const creationDate = new Date(cookie.creation);
    const expiryDate = new Date(creationDate.getTime() + (cookie.maxAge * 1000));

    // Track the earliest expiring critical cookie
    if (cookie.key === 'zpw_sek' || cookie.key === 'zpsid') {
      if (!earliestExpiry || expiryDate < earliestExpiry) {
        earliestExpiry = expiryDate;
        criticalCookie = cookie.key;
      }
    }
  }

  if (!earliestExpiry) {
    return null;
  }

  const now = new Date();
  const daysRemaining = Math.floor((earliestExpiry - now) / (24 * 60 * 60 * 1000));

  return {
    expiryDate: earliestExpiry,
    daysRemaining,
    criticalCookie,
    isExpired: daysRemaining <= 0,
    isCritical: daysRemaining <= CRITICAL_DAYS_BEFORE,
    isWarning: daysRemaining <= WARNING_DAYS_BEFORE
  };
}

/**
 * Check all session files for cookie expiry
 */
async function checkAllCookieExpiry() {
  const sessionsDir = getSessionsDir();

  if (!fs.existsSync(sessionsDir)) {
    console.log('[CookieMonitor] Sessions directory not found');
    return [];
  }

  const results = [];
  const files = fs.readdirSync(sessionsDir);

  for (const file of files) {
    // Only check valid session files
    if (!file.startsWith('zalo_') || !file.endsWith('.json')) {
      continue;
    }

    try {
      const filePath = path.join(sessionsDir, file);
      const data = JSON.parse(fs.readFileSync(filePath, 'utf8'));

      if (!data.accountId || !data.cookie) {
        continue;
      }

      const expiryInfo = getCookieExpiry(data.cookie);

      if (expiryInfo) {
        results.push({
          accountId: data.accountId,
          file: file,
          ...expiryInfo
        });

        // Log status
        if (expiryInfo.isExpired) {
          console.log(`[CookieMonitor] ❌ Account ${data.accountId}: Cookie EXPIRED!`);
        } else if (expiryInfo.isCritical) {
          console.log(`[CookieMonitor] 🚨 Account ${data.accountId}: Cookie expires in ${expiryInfo.daysRemaining} days (CRITICAL)`);
        } else if (expiryInfo.isWarning) {
          console.log(`[CookieMonitor] ⚠️ Account ${data.accountId}: Cookie expires in ${expiryInfo.daysRemaining} days`);
        } else {
          console.log(`[CookieMonitor] ✅ Account ${data.accountId}: Cookie valid for ${expiryInfo.daysRemaining} days`);
        }
      }
    } catch (error) {
      console.error(`[CookieMonitor] Error reading ${file}:`, error.message);
    }
  }

  return results;
}

/**
 * Send expiry alerts via Telegram
 */
async function sendExpiryAlerts(results) {
  for (const result of results) {
    const { accountId, daysRemaining, isExpired, isCritical, isWarning, expiryDate } = result;

    // Check if we should send alert (rate limit: max once per day)
    const lastWarning = lastWarningTime.get(accountId) || 0;
    const now = Date.now();

    if (now - lastWarning < CHECK_INTERVAL) {
      continue; // Already warned today
    }

    let message = null;
    let emoji = '';

    if (isExpired) {
      emoji = '❌';
      message = `Cookie đã HẾT HẠN!\nVui lòng đăng nhập lại ngay để tránh gián đoạn.`;
    } else if (isCritical) {
      emoji = '🚨';
      message = `Cookie sẽ hết hạn trong ${daysRemaining} ngày (${expiryDate.toLocaleDateString('vi-VN')}).\nVui lòng đăng nhập lại SỚM để tránh gián đoạn.`;
    } else if (isWarning) {
      emoji = '⚠️';
      message = `Cookie sẽ hết hạn trong ${daysRemaining} ngày (${expiryDate.toLocaleDateString('vi-VN')}).\nHãy chuẩn bị đăng nhập lại khi cần.`;
    }

    if (message) {
      try {
        await telegram.sendMessage(accountId, `${emoji} *CẢNH BÁO COOKIE*\n\n${message}`);
        lastWarningTime.set(accountId, now);
        console.log(`[CookieMonitor] Sent expiry alert to account ${accountId}`);
      } catch (error) {
        console.error(`[CookieMonitor] Failed to send alert for account ${accountId}:`, error.message);
      }
    }
  }
}

/**
 * Run a check and send alerts if needed
 */
async function runCheck() {
  console.log('[CookieMonitor] 🔍 Checking cookie expiry...');

  const results = await checkAllCookieExpiry();

  if (results.length === 0) {
    console.log('[CookieMonitor] No sessions to check');
    return;
  }

  // Send alerts for expiring cookies
  const expiringResults = results.filter(r => r.isWarning || r.isCritical || r.isExpired);

  if (expiringResults.length > 0) {
    await sendExpiryAlerts(expiringResults);
  }

  console.log(`[CookieMonitor] Check complete: ${results.length} sessions, ${expiringResults.length} expiring`);
}

/**
 * Start the cookie monitor
 */
function start() {
  console.log('[CookieMonitor] 🚀 Starting cookie expiry monitor...');
  console.log(`[CookieMonitor]    Check interval: ${CHECK_INTERVAL / 3600000} hours`);
  console.log(`[CookieMonitor]    Warning threshold: ${WARNING_DAYS_BEFORE} days`);
  console.log(`[CookieMonitor]    Critical threshold: ${CRITICAL_DAYS_BEFORE} days`);

  // Run initial check after 5 minutes
  setTimeout(async () => {
    await runCheck();
  }, 5 * 60 * 1000);

  // Schedule periodic checks
  checkTimer = setInterval(async () => {
    await runCheck();
  }, CHECK_INTERVAL);

  console.log('[CookieMonitor] ✅ Cookie expiry monitor started');
}

/**
 * Stop the cookie monitor
 */
function stop() {
  console.log('[CookieMonitor] 🛑 Stopping cookie expiry monitor...');

  if (checkTimer) {
    clearInterval(checkTimer);
    checkTimer = null;
  }

  console.log('[CookieMonitor] ✅ Cookie expiry monitor stopped');
}

/**
 * Get expiry status for a specific account
 */
async function getExpiryStatus(accountId) {
  const sessionsDir = getSessionsDir();
  const filePath = path.join(sessionsDir, `zalo_${accountId}.json`);

  if (!fs.existsSync(filePath)) {
    return { error: 'Session file not found' };
  }

  try {
    const data = JSON.parse(fs.readFileSync(filePath, 'utf8'));
    const expiryInfo = getCookieExpiry(data.cookie);

    if (!expiryInfo) {
      return { error: 'Could not determine expiry' };
    }

    return {
      accountId,
      ...expiryInfo,
      expiryDateFormatted: expiryInfo.expiryDate.toLocaleDateString('vi-VN'),
      status: expiryInfo.isExpired ? 'expired' :
              expiryInfo.isCritical ? 'critical' :
              expiryInfo.isWarning ? 'warning' : 'ok'
    };
  } catch (error) {
    return { error: error.message };
  }
}

module.exports = {
  start,
  stop,
  runCheck,
  checkAllCookieExpiry,
  getExpiryStatus,
  getCookieExpiry
};

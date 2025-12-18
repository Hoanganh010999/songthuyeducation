/**
 * STATUS PING SERVICE
 * Uses keepAlive API to prevent session expiration
 * Sends status reports to Telegram
 */

const PING_INTERVAL = 30 * 60 * 1000; // 30 minutes
const KEEPALIVE_INTERVAL = 10 * 60 * 1000; // 10 minutes

// Track statistics per account
const accountStats = new Map();
const pingIntervals = new Map();
const keepAliveIntervals = new Map();

/**
 * Initialize stats tracking for an account
 */
function initStats(accountId) {
  if (!accountStats.has(accountId)) {
    accountStats.set(accountId, {
      loginTime: new Date(),
      messageReceived: 0,
      messageSent: 0,
      lastPingTime: null,
      lastKeepAlive: null,
      pingCount: 0,
      keepAliveCount: 0
    });
  }
  return accountStats.get(accountId);
}

/**
 * Track incoming message
 */
function trackMessageReceived(accountId) {
  const stats = initStats(accountId);
  stats.messageReceived++;
}

/**
 * Track outgoing message
 */
function trackMessageSent(accountId) {
  const stats = initStats(accountId);
  stats.messageSent++;
}

/**
 * Get stats for an account
 */
function getStats(accountId) {
  return accountStats.get(accountId) || initStats(accountId);
}

/**
 * Reset message counts (called after each ping)
 */
function resetMessageCounts(accountId) {
  const stats = getStats(accountId);
  stats.messageReceived = 0;
  stats.messageSent = 0;
}

/**
 * Format duration in Vietnamese
 */
function formatDuration(ms) {
  const seconds = Math.floor(ms / 1000);
  const minutes = Math.floor(seconds / 60);
  const hours = Math.floor(minutes / 60);
  
  if (hours > 0) {
    return hours + ' giờ ' + (minutes % 60) + ' phút';
  }
  return minutes + ' phút';
}

/**
 * Call keepAlive API to prevent session expiration
 */
async function callKeepAlive(accountId, api) {
  try {
    const stats = getStats(accountId);
    stats.keepAliveCount++;
    stats.lastKeepAlive = new Date();
    
    await api.keepAlive();
    console.log('[StatusPing] ✅ KeepAlive #' + stats.keepAliveCount + ' for account ' + accountId);
    return true;
  } catch (error) {
    console.error('[StatusPing] ❌ KeepAlive error for account ' + accountId + ':', error.message);
    return false;
  }
}

/**
 * Send status report to Telegram
 */
async function sendStatusPing(accountId, api) {
  try {
    const telegram = require('./telegram');
    const stats = getStats(accountId);
    const now = new Date();
    const uptime = now - stats.loginTime;
    
    stats.pingCount++;
    stats.lastPingTime = now;
    
    const timestamp = now.toLocaleString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh' });
    
    const statusMessage = 
      '📊 <b>BÁO CÁO TRẠNG THÁI ZALO</b>\n' +
      '━━━━━━━━━━━━━━━━━━━━━\n' +
      '⏰ <b>Thời gian:</b> ' + timestamp + '\n' +
      '🟢 <b>Trạng thái:</b> Đang hoạt động\n' +
      '⏱️ <b>Thời gian online:</b> ' + formatDuration(uptime) + '\n\n' +
      '📨 <b>Thống kê 30 phút qua:</b>\n' +
      '   • Tin nhắn đến: ' + stats.messageReceived + '\n' +
      '   • Tin nhắn gửi: ' + stats.messageSent + '\n\n' +
      '🔄 Ping #' + stats.pingCount + ' | KeepAlive #' + stats.keepAliveCount;

    await telegram.sendStatusReport(accountId, statusMessage);
    console.log('[StatusPing] ✅ Status ping #' + stats.pingCount + ' sent to Telegram for account ' + accountId);
    
    // Reset message counts for next period
    resetMessageCounts(accountId);
    
    return true;
  } catch (error) {
    console.error('[StatusPing] ❌ Error sending status ping for account ' + accountId + ':', error.message);
    return false;
  }
}

/**
 * Start status ping and keepAlive for an account
 */
function startStatusPing(accountId, api) {
  if (pingIntervals.has(accountId)) {
    console.log('[StatusPing] Already running for account ' + accountId);
    return;
  }

  console.log('[StatusPing] 🚀 Starting for account ' + accountId);
  console.log('[StatusPing]    - KeepAlive: every 10 minutes');
  console.log('[StatusPing]    - Status report: every 30 minutes');
  
  // Initialize stats
  initStats(accountId);
  
  // KeepAlive every 10 minutes to prevent session expiration
  const keepAliveInterval = setInterval(async () => {
    await callKeepAlive(accountId, api);
  }, KEEPALIVE_INTERVAL);
  keepAliveIntervals.set(accountId, keepAliveInterval);
  
  // First keepAlive after 1 minute
  setTimeout(async () => {
    await callKeepAlive(accountId, api);
  }, 60000);
  
  // Status report every 30 minutes to Telegram
  const statusInterval = setInterval(async () => {
    await sendStatusPing(accountId, api);
  }, PING_INTERVAL);
  pingIntervals.set(accountId, statusInterval);
  
  // First status after 2 minutes
  setTimeout(async () => {
    await sendStatusPing(accountId, api);
  }, 120000);
}

/**
 * Stop status ping for an account
 */
function stopStatusPing(accountId) {
  const pingInterval = pingIntervals.get(accountId);
  const keepAlive = keepAliveIntervals.get(accountId);
  
  if (pingInterval) {
    clearInterval(pingInterval);
    pingIntervals.delete(accountId);
  }
  if (keepAlive) {
    clearInterval(keepAlive);
    keepAliveIntervals.delete(accountId);
  }
  if (pingInterval || keepAlive) {
    accountStats.delete(accountId);
    console.log('[StatusPing] Stopped for account ' + accountId);
  }
}

/**
 * Stop all status pings
 */
function stopAll() {
  for (const [accountId, interval] of pingIntervals) {
    clearInterval(interval);
  }
  for (const [accountId, interval] of keepAliveIntervals) {
    clearInterval(interval);
  }
  pingIntervals.clear();
  keepAliveIntervals.clear();
  accountStats.clear();
  console.log('[StatusPing] Stopped all');
}

module.exports = {
  initStats,
  trackMessageReceived,
  trackMessageSent,
  getStats,
  sendStatusPing,
  startStatusPing,
  stopStatusPing,
  stopAll
};

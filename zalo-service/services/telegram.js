/**
 * TELEGRAM NOTIFICATION SERVICE
 *
 * Sends alerts and QR codes to Telegram when Zalo session issues occur.
 * Fetches Telegram settings from Laravel API.
 */

const fs = require('fs');
const path = require('path');
const https = require('https');
const http = require('http');
const FormData = require('form-data');

// Cache for Telegram settings (refreshed periodically)
let settingsCache = new Map();
let lastSettingsFetch = 0;
const SETTINGS_CACHE_TTL = 5 * 60 * 1000; // 5 minutes

/**
 * Fetch Telegram settings from Laravel API
 */
async function fetchTelegramSettings() {
  const now = Date.now();

  // Return cached if still valid
  if (now - lastSettingsFetch < SETTINGS_CACHE_TTL && settingsCache.size > 0) {
    return settingsCache;
  }

  const laravelUrl = process.env.LARAVEL_URL || 'http://127.0.0.1:8000';
  const apiKey = process.env.API_SECRET_KEY;

  if (!apiKey) {
    console.warn('[Telegram] No API_SECRET_KEY configured');
    return settingsCache;
  }

  try {
    const url = new URL('/api/zalo/telegram-settings', laravelUrl);
    const isHttps = url.protocol === 'https:';
    const httpModule = isHttps ? https : http;

    return new Promise((resolve) => {
      const req = httpModule.request({
        hostname: url.hostname,
        port: url.port || (isHttps ? 443 : 80),
        path: url.pathname,
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-API-Key': apiKey,
        },
        rejectUnauthorized: false,
        timeout: 10000,
      }, (res) => {
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
          try {
            const json = JSON.parse(data);
            if (json.success && Array.isArray(json.data)) {
              settingsCache = new Map();
              json.data.forEach(item => {
                settingsCache.set(item.account_id, {
                  botToken: item.telegram_bot_token,
                  chatId: item.telegram_chat_id,
                });
              });
              lastSettingsFetch = now;
              console.log(`[Telegram] Fetched settings for ${settingsCache.size} account(s)`);
            }
          } catch (e) {
            console.error('[Telegram] Failed to parse settings response:', e.message);
          }
          resolve(settingsCache);
        });
      });

      req.on('error', (error) => {
        console.error('[Telegram] Failed to fetch settings:', error.message);
        resolve(settingsCache);
      });

      req.on('timeout', () => {
        req.destroy();
        resolve(settingsCache);
      });

      req.end();
    });
  } catch (error) {
    console.error('[Telegram] Error fetching settings:', error.message);
    return settingsCache;
  }
}

/**
 * Get Telegram settings for an account
 */
async function getSettings(accountId) {
  await fetchTelegramSettings();
  return settingsCache.get(accountId) || null;
}

/**
 * Send a text message to Telegram (Markdown format)
 */
async function sendMessage(accountId, message) {
  return sendMessageWithFormat(accountId, message, 'Markdown');
}

/**
 * Send a text message to Telegram (HTML format)
 */
async function sendMessageHTML(accountId, message) {
  return sendMessageWithFormat(accountId, message, 'HTML');
}

/**
 * Send a text message to Telegram with specified parse_mode
 */
async function sendMessageWithFormat(accountId, message, parseMode = 'Markdown') {
  const settings = await getSettings(accountId);

  if (!settings || !settings.botToken || !settings.chatId) {
    console.log(`[Telegram] No settings configured for account ${accountId}`);
    return false;
  }

  const { botToken, chatId } = settings;

  try {
    return new Promise((resolve) => {
      const postData = JSON.stringify({
        chat_id: chatId,
        text: message,
        parse_mode: parseMode,
      });

      const req = https.request({
        hostname: 'api.telegram.org',
        port: 443,
        path: `/bot${botToken}/sendMessage`,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(postData),
        },
        timeout: 10000,
      }, (res) => {
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
          try {
            const json = JSON.parse(data);
            if (json.ok) {
              console.log(`[Telegram] Message sent to account ${accountId}`);
              resolve(true);
            } else {
              console.error(`[Telegram] API error:`, json.description);
              resolve(false);
            }
          } catch (e) {
            console.error('[Telegram] Failed to parse response:', e.message);
            resolve(false);
          }
        });
      });

      req.on('error', (error) => {
        console.error('[Telegram] Send message error:', error.message);
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
    console.error('[Telegram] Error sending message:', error.message);
    return false;
  }
}

/**
 * Send a disconnect alert to Telegram
 */
async function sendDisconnectAlert(accountId, reason) {
  const timestamp = new Date().toLocaleString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh' });

  const message = `🚨 *ZALO BỊ NGẮT KẾT NỐI*

📱 Account ID: \`${accountId}\`
⏰ Thời gian: ${timestamp}
❌ Lý do: ${reason}

⚠️ Vui lòng quét lại mã QR để kết nối lại.`;

  return sendMessage(accountId, message);
}

/**
 * Send a QR code image to Telegram
 */
async function sendQRCode(accountId, qrFilePath) {
  const settings = await getSettings(accountId);

  if (!settings || !settings.botToken || !settings.chatId) {
    console.log(`[Telegram] No settings configured for account ${accountId}`);
    return false;
  }

  const { botToken, chatId } = settings;

  // Check if file exists
  if (!fs.existsSync(qrFilePath)) {
    console.error(`[Telegram] QR file not found: ${qrFilePath}`);
    return false;
  }

  try {
    const timestamp = new Date().toLocaleString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh' });
    const caption = `📱 *MÃ QR ĐĂNG NHẬP ZALO*

Account ID: \`${accountId}\`
⏰ Tạo lúc: ${timestamp}

⚠️ Quét mã QR này bằng Zalo để kết nối lại.
⏳ Mã có hiệu lực trong 2 phút.`;

    // Read file and send as multipart form
    const form = new FormData();
    form.append('chat_id', chatId);
    form.append('caption', caption);
    form.append('parse_mode', 'Markdown');
    form.append('photo', fs.createReadStream(qrFilePath));

    return new Promise((resolve) => {
      const req = https.request({
        hostname: 'api.telegram.org',
        port: 443,
        path: `/bot${botToken}/sendPhoto`,
        method: 'POST',
        headers: form.getHeaders(),
        timeout: 30000,
      }, (res) => {
        let data = '';
        res.on('data', chunk => data += chunk);
        res.on('end', () => {
          try {
            const json = JSON.parse(data);
            if (json.ok) {
              console.log(`[Telegram] QR code sent to account ${accountId}`);
              resolve(true);
            } else {
              console.error(`[Telegram] API error:`, json.description);
              resolve(false);
            }
          } catch (e) {
            console.error('[Telegram] Failed to parse response:', e.message);
            resolve(false);
          }
        });
      });

      req.on('error', (error) => {
        console.error('[Telegram] Send QR code error:', error.message);
        resolve(false);
      });

      req.on('timeout', () => {
        req.destroy();
        resolve(false);
      });

      form.pipe(req);
    });
  } catch (error) {
    console.error('[Telegram] Error sending QR code:', error.message);
    return false;
  }
}

/**
 * Send connection restored alert
 */
async function sendConnectionRestored(accountId, zaloId = null) {
  const timestamp = new Date().toLocaleString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh' });

  let message = `✅ *ZALO ĐÃ KẾT NỐI LẠI*

📱 Account ID: \`${accountId}\``;

  if (zaloId) {
    message += `\n📞 Zalo ID: \`${zaloId}\``;
  }

  message += `
⏰ Thời gian: ${timestamp}

🎉 Kết nối đã được khôi phục thành công!`;

  return sendMessage(accountId, message);
}

/**
 * Send network error warning
 */
async function sendNetworkWarning(accountId, errorMessage) {
  const timestamp = new Date().toLocaleString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh' });

  const message = `⚠️ *CẢNH BÁO MẠNG*

📱 Account ID: \`${accountId}\`
⏰ Thời gian: ${timestamp}
🌐 Lỗi: ${errorMessage}

Hệ thống đang cố gắng kết nối lại...`;

  return sendMessage(accountId, message);
}

/**
 * Send keep-alive failure alert
 */
async function sendKeepAliveFailure(accountId, reason) {
  const timestamp = new Date().toLocaleString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh' });

  const message = `⚠️ *CẢNH BÁO KẾT NỐI*

📱 Account ID: \`${accountId}\`
⏰ Thời gian: ${timestamp}
❌ Lý do: ${reason}

Hệ thống đang thử kết nối lại...`;

  return sendMessage(accountId, message);
}

/**
 * Send login success alert
 */
async function sendLoginSuccessAlert(accountId, zaloId, accountName = null) {
  const timestamp = new Date().toLocaleString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh' });

  let message = `✅ *ĐĂNG NHẬP ZALO THÀNH CÔNG*

📱 Account ID: \`${accountId}\``;

  // Show account name prominently if available
  if (accountName) {
    message += `\n👤 Tên tài khoản: *${accountName}*`;
  }

  // Only show Zalo ID if available and valid
  if (zaloId && zaloId !== 'undefined' && zaloId !== 'null') {
    message += `\n📞 Zalo ID: \`${zaloId}\``;
  }

  message += `
⏰ Thời gian: ${timestamp}

🎉 Kết nối Zalo đã sẵn sàng!`;

  return sendMessage(accountId, message);
}

/**
 * Send status report (ping notification)
 * Accepts either a pre-formatted message string or statusData object
 */
async function sendStatusReport(accountId, statusDataOrMessage = {}) {
  // If it's a string, send it directly (for backward compatibility with status-ping.js)
  if (typeof statusDataOrMessage === 'string') {
    return sendMessageHTML(accountId, statusDataOrMessage);
  }

  // Otherwise, format from object
  const timestamp = new Date().toLocaleString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh' });

  const {
    uptime = 'N/A',
    messagesReceived = 0,
    messagesSent = 0,
    isHealthy = true,
    zaloId = 'N/A'
  } = statusDataOrMessage;

  const statusEmoji = isHealthy ? '✅' : '⚠️';
  const statusText = isHealthy ? 'Hoạt động tốt' : 'Có vấn đề';

  const message = `📊 *BÁO CÁO TRẠNG THÁI ZALO*

📱 Account ID: \`${accountId}\`
📞 Zalo ID: \`${zaloId}\`
⏰ Thời gian: ${timestamp}

${statusEmoji} Trạng thái: ${statusText}
⏱️ Uptime: ${uptime}
📥 Tin nhắn nhận: ${messagesReceived}
📤 Tin nhắn gửi: ${messagesSent}

🔄 Hệ thống đang hoạt động bình thường.`;

  return sendMessage(accountId, message);
}

/**
 * Check if any Telegram settings are configured
 */
async function isConfigured() {
  await fetchTelegramSettings();
  return settingsCache.size > 0;
}

/**
 * Force update settings cache
 */
async function updateSettingsCache() {
  lastSettingsFetch = 0; // Reset cache TTL
  return fetchTelegramSettings();
}

/**
 * Send a test message to verify Telegram configuration
 */
async function sendTestMessage() {
  await fetchTelegramSettings();

  if (settingsCache.size === 0) {
    console.log('[Telegram] No accounts configured for test message');
    return false;
  }

  const timestamp = new Date().toLocaleString('vi-VN', { timeZone: 'Asia/Ho_Chi_Minh' });
  const message = `🧪 *KIỂM TRA KẾT NỐI TELEGRAM*

⏰ Thời gian: ${timestamp}

✅ Telegram đã được cấu hình thành công!
Bạn sẽ nhận được thông báo khi Zalo bị ngắt kết nối.`;

  // Send to all configured accounts
  let sent = false;
  for (const [accountId] of settingsCache) {
    const result = await sendMessage(accountId, message);
    if (result) sent = true;
  }

  return sent;
}

module.exports = {
  sendMessage,
  sendMessageHTML,
  sendDisconnectAlert,
  sendQRCode,
  sendConnectionRestored,
  sendNetworkWarning,
  sendKeepAliveFailure,
  sendLoginSuccessAlert,
  sendStatusReport,
  sendTestMessage,
  getSettings,
  fetchTelegramSettings,
  isConfigured,
  updateSettingsCache,
};

require('dotenv').config();
const express = require('express');
const http = require('http');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');
const bodyParser = require('body-parser');
const path = require('path');
const fs = require('fs');

const app = express();
const server = http.createServer(app);
const PORT = process.env.PORT || 3001;

// Middleware
app.use(helmet());
app.use(cors({
  origin: [
    'http://localhost:8000',
    'http://127.0.0.1:8000',
    'http://localhost:3000',
    'http://127.0.0.1:3000'
  ],
  credentials: true
}));
app.use(morgan('combined'));
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// API Routes
const authRoutes = require('./routes/auth');
const messageRoutes = require('./routes/message');
const userRoutes = require('./routes/user');
const groupRoutes = require('./routes/group');
const friendRoutes = require('./routes/friend');
const realtimeRoutes = require('./routes/realtime');
const socketRoutes = require('./routes/socket');
const sessionSharingRoutes = require('./routes/session-sharing');
const {
  initializeZalo,
  stopKeepAlive,
  getAllHealthStatus,
  sessions,
  sessionData,
  setActiveAccountId,
  startKeepAliveForAccount,
  startWebSocketListenerForAccount,
  startHealthMonitorForAccount
} = require('./services/zaloClient');

// Session restore service
const { restoreAllSessions } = require('./services/session-restore');

// Session manager service (periodic save & notifications)
const sessionManager = require('./services/session-manager');
const cookieMonitor = require('./services/cookie-monitor');

// Initialize WebSocket server for realtime features
const { initializeRealtimeServer, getTotalConnections, getConnectedUsersCount, emitToRoom } = require('./services/realtimeServer');

app.use('/api/auth', authRoutes);
app.use('/api/message', messageRoutes);
app.use('/api/user', userRoutes);
app.use('/api/group', groupRoutes);
app.use('/api/friend', friendRoutes);
app.use('/api/realtime', realtimeRoutes);
app.use('/api/socket', socketRoutes);
app.use('/api/session', sessionSharingRoutes);

// Health check
app.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    service: 'Zalo API Service',
    timestamp: new Date().toISOString(),
    websocket: {
      enabled: true,
      connections: getTotalConnections(),
      users: getConnectedUsersCount()
    }
  });
});

// Connection health monitoring status
app.get('/api/health/connections', (req, res) => {
  try {
    const healthStatus = getAllHealthStatus();

    res.json({
      success: true,
      timestamp: new Date().toISOString(),
      accounts: healthStatus
    });
  } catch (error) {
    console.error('[API] Error getting health status:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to get health status'
    });
  }
});

// Force save sessions endpoint
app.post('/api/sessions/save', async (req, res) => {
  try {
    const result = await sessionManager.forceSave();
    res.json({
      success: true,
      message: 'Sessions saved',
      ...result
    });
  } catch (error) {
    console.error('[API] Error saving sessions:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to save sessions'
    });
  }
});

// API endpoint for Laravel to emit WebSocket events
app.post('/api/emit', (req, res) => {
  const { room, event, data } = req.body;

  if (!room || !event || !data) {
    return res.status(400).json({
      success: false,
      error: 'Missing required fields: room, event, data'
    });
  }

  try {
    // Emit to specific room
    emitToRoom(room, event, data);

    console.log(`[API] Emitted ${event} to room ${room}`);

    res.json({
      success: true,
      message: `Event ${event} emitted to room ${room}`
    });
  } catch (error) {
    console.error('[API] Error emitting event:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to emit event'
    });
  }
});

// Error handling
app.use((err, req, res, next) => {
  console.error('Error:', err);
  res.status(err.status || 500).json({
    success: false,
    message: err.message || 'Internal server error'
  });
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({
    success: false,
    message: 'Endpoint not found'
  });
});

/**
 * Trigger QR login for an account and return the QR file path
 * Used by session-manager when session expires
 */
async function triggerQRLogin(accountId) {
  console.log(`[Server] 🔄 Triggering QR login for account ${accountId}...`);

  return new Promise((resolve, reject) => {
    let qrFilePath = null;
    let resolved = false;

    // Initialize with QR callback
    initializeZalo(accountId, (qrBase64) => {
      console.log(`[Server] ✅ QR Code callback received for account ${accountId}`);

      // Find the QR file path
      const possiblePaths = [
        path.join(__dirname, `qr_${accountId}.png`),
        path.join(__dirname, 'qr.png')
      ];

      for (const qrPath of possiblePaths) {
        if (fs.existsSync(qrPath)) {
          qrFilePath = qrPath;
          break;
        }
      }

      if (!resolved) {
        resolved = true;
        resolve(qrFilePath);
      }
    }, true); // forceNew = true

    // Timeout after 10 seconds
    setTimeout(() => {
      if (!resolved) {
        resolved = true;

        // Try to find QR file anyway
        const possiblePaths = [
          path.join(__dirname, `qr_${accountId}.png`),
          path.join(__dirname, 'qr.png')
        ];

        for (const qrPath of possiblePaths) {
          if (fs.existsSync(qrPath)) {
            qrFilePath = qrPath;
            break;
          }
        }

        if (qrFilePath) {
          resolve(qrFilePath);
        } else {
          reject(new Error('QR code generation timeout'));
        }
      }
    }, 10000);
  });
}

// Start server
server.listen(PORT, async () => {
  console.log(`🚀 Zalo Service running on port ${PORT}`);
  console.log(`📍 Environment: ${process.env.NODE_ENV}`);
  console.log(`🔗 Health check: http://localhost:${PORT}/health`);

  // Initialize WebSocket server for realtime features
  const laravelUrl = process.env.LARAVEL_URL || 'http://127.0.0.1:8000';
  initializeRealtimeServer(server, laravelUrl);
  console.log(`🔌 WebSocket server ready for realtime connections`);
  console.log(`   Connect from: ${laravelUrl}`);

  // AUTO-RESTORE: Try to restore sessions from saved files
  console.log('');
  console.log('🔄 Attempting to restore saved sessions...');

  try {
    const restoreResult = await restoreAllSessions(sessions, sessionData, {
      startKeepAlive: startKeepAliveForAccount,
      startWebSocketListener: startWebSocketListenerForAccount,
      startHealthMonitor: startHealthMonitorForAccount,
      triggerQRLogin: triggerQRLogin  // For auto QR generation on restore failure
    });

    if (restoreResult.restored > 0) {
      console.log(`✅ Auto-restored ${restoreResult.restored} session(s)`);

      // Set the first restored account as active
      if (restoreResult.firstAccountId) {
        setActiveAccountId(restoreResult.firstAccountId);
        console.log(`   Active account set to: ${restoreResult.firstAccountId}`);
      }
    } else {
      console.log('ℹ️  No sessions restored. Login required via QR code.');
    }
  } catch (error) {
    console.error('❌ Session restore failed:', error.message);
  }

  // Start session manager (periodic save & health check)
  // Pass triggerQRLogin function for automatic QR generation when session expires
  console.log('');
  sessionManager.start(sessions, sessionData, {
    triggerQRLogin: triggerQRLogin
  });

  // Start cookie expiry monitor
  cookieMonitor.start();

  console.log('');
  console.log('ℹ️  Multi-session mode enabled');
  console.log('   Use POST /api/auth/initialize with accountId to login new accounts');
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('🛑 SIGTERM received, shutting down gracefully...');
  cookieMonitor.stop();
  sessionManager.stop();
  stopKeepAlive();
  const { stopWebSocketListener } = require('./services/zaloClient');
  stopWebSocketListener();
  process.exit(0);
});

process.on('SIGINT', () => {
  console.log('🛑 SIGINT received, shutting down gracefully...');
  cookieMonitor.stop();
  sessionManager.stop();
  stopKeepAlive();
  const { stopWebSocketListener } = require('./services/zaloClient');
  stopWebSocketListener();
  process.exit(0);
});

module.exports = app;

// Cookie expiry status endpoint
app.get('/api/health/cookie-expiry', async (req, res) => {
  try {
    const accountId = req.query.accountId || req.query.account_id;
    
    if (accountId) {
      const status = await cookieMonitor.getExpiryStatus(parseInt(accountId));
      return res.json({ success: true, ...status });
    }
    
    const allStatus = await cookieMonitor.checkAllCookieExpiry();
    res.json({
      success: true,
      timestamp: new Date().toISOString(),
      accounts: allStatus
    });
  } catch (error) {
    console.error('[API] Error getting cookie expiry status:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to get cookie expiry status'
    });
  }
});

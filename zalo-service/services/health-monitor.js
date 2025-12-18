// Simple logger using console
const logger = {
  info: (...args) => console.log(...args),
  warn: (...args) => console.warn(...args),
  error: (...args) => console.error(...args),
  debug: (...args) => console.log(...args),
};

class HealthMonitor {
  constructor(zaloService) {
    this.zaloService = zaloService;
    this.lastIncomingMessageTime = new Date();
    this.lastOutgoingMessageTime = new Date();
    this.checkInterval = 5 * 60 * 1000; // 5 minutes
    this.maxNoMessageDuration = 30 * 60 * 1000; // 30 minutes
    this.monitorTimer = null;
    this.isMonitoring = false;
  }

  /**
   * Start monitoring health
   */
  start() {
    if (this.isMonitoring) {
      logger.warn('[HealthMonitor] Already monitoring');
      return;
    }

    logger.info('[HealthMonitor] Starting health monitoring...');
    this.isMonitoring = true;
    this.lastIncomingMessageTime = new Date();
    this.lastOutgoingMessageTime = new Date();

    // Check every 5 minutes
    this.monitorTimer = setInterval(() => {
      this.checkHealth();
    }, this.checkInterval);

    logger.info('[HealthMonitor] Health monitoring started');
  }

  /**
   * Stop monitoring
   */
  stop() {
    if (this.monitorTimer) {
      clearInterval(this.monitorTimer);
      this.monitorTimer = null;
    }
    this.isMonitoring = false;
    logger.info('[HealthMonitor] Health monitoring stopped');
  }

  /**
   * Record incoming message activity
   */
  recordIncomingMessage() {
    this.lastIncomingMessageTime = new Date();
    logger.debug('[HealthMonitor] Recorded incoming message activity');
  }

  /**
   * Record outgoing message activity
   */
  recordOutgoingMessage() {
    this.lastOutgoingMessageTime = new Date();
    logger.debug('[HealthMonitor] Recorded outgoing message activity');
  }

  /**
   * Check connection health
   */
  async checkHealth() {
    const now = new Date();
    const timeSinceLastIncoming = now - this.lastIncomingMessageTime;
    const timeSinceLastOutgoing = now - this.lastOutgoingMessageTime;

    logger.info('[HealthMonitor] Health check:', {
      timeSinceLastIncomingMin: Math.floor(timeSinceLastIncoming / 60000),
      timeSinceLastOutgoingMin: Math.floor(timeSinceLastOutgoing / 60000),
      threshold: Math.floor(this.maxNoMessageDuration / 60000)
    });

    // If we can send messages but haven't received any for 30+ minutes
    // This indicates a half-dead connection
    if (
      timeSinceLastIncoming > this.maxNoMessageDuration &&
      timeSinceLastOutgoing < this.maxNoMessageDuration
    ) {
      logger.warn('[HealthMonitor] ⚠️ Detected half-dead connection:', {
        canSend: true,
        canReceive: false,
        lastIncoming: this.lastIncomingMessageTime.toISOString(),
        lastOutgoing: this.lastOutgoingMessageTime.toISOString()
      });

      await this.recoverConnection();
    }

    // If no activity at all for 60+ minutes
    const noActivityDuration = 60 * 60 * 1000; // 60 minutes
    if (
      timeSinceLastIncoming > noActivityDuration &&
      timeSinceLastOutgoing > noActivityDuration
    ) {
      logger.warn('[HealthMonitor] ⚠️ No activity detected for 60+ minutes');
      // Don't auto-recover in this case, might be normal inactivity
    }
  }

  /**
   * Attempt to recover the connection
   */
  async recoverConnection() {
    try {
      logger.info('[HealthMonitor] 🔧 Attempting to recover connection...');

      // Get the Zalo client
      const zalo = this.zaloService.getClient();

      if (!zalo) {
        logger.error('[HealthMonitor] No Zalo client available');
        return false;
      }

      logger.info('[HealthMonitor] Restarting message listener...');

      // Stop and restart the listener
      await this.zaloService.stopListener();
      await new Promise(resolve => setTimeout(resolve, 2000)); // Wait 2s

      await this.zaloService.startListener();

      // Reset the timer
      this.lastIncomingMessageTime = new Date();

      logger.info('[HealthMonitor] ✅ Connection recovered successfully');
      return true;

    } catch (error) {
      logger.error('[HealthMonitor] ❌ Failed to recover connection:', error);
      return false;
    }
  }

  /**
   * Get current health status
   */
  getStatus() {
    const now = new Date();
    return {
      isMonitoring: this.isMonitoring,
      lastIncomingMessageTime: this.lastIncomingMessageTime.toISOString(),
      lastOutgoingMessageTime: this.lastOutgoingMessageTime.toISOString(),
      timeSinceLastIncomingMinutes: Math.floor((now - this.lastIncomingMessageTime) / 60000),
      timeSinceLastOutgoingMinutes: Math.floor((now - this.lastOutgoingMessageTime) / 60000),
      isHealthy: (now - this.lastIncomingMessageTime) < this.maxNoMessageDuration
    };
  }
}

module.exports = HealthMonitor;

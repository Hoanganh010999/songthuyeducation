/**
 * ZALO SESSION MONITOR & AUTO-RECONNECT
 * Monitors Zalo sessions and auto-reconnects on failure
 */

const axios = require('axios');
const { exec } = require('child_process');

const LARAVEL_URL = process.env.LARAVEL_URL || 'http://127.0.0.1:8000';
const CHECK_INTERVAL = 60000; // Check every 60 seconds
const RECONNECT_THRESHOLD = 3; // Reconnect after 3 failed checks

let failedChecks = new Map(); // Track failed checks per account

async function checkZaloSessions() {
    try {
        console.log('[Monitor] Checking Zalo sessions...');
        
        // Get all Zalo accounts from Laravel
        const response = await axios.get(`${LARAVEL_URL}/api/zalo/accounts/active`, {
            timeout: 5000
        });
        
        const accounts = response.data.data || [];
        
        for (const account of accounts) {
            await checkSingleSession(account);
        }
        
    } catch (error) {
        console.error('[Monitor] Error checking sessions:', error.message);
    }
}

async function checkSingleSession(account) {
    try {
        // Test if account can receive messages
        const healthCheck = await axios.post(`${LARAVEL_URL}/api/zalo/health-check`, {
            account_id: account.id
        }, { timeout: 10000 });
        
        if (healthCheck.data.status === 'ok') {
            // Reset failed counter
            failedChecks.delete(account.id);
            console.log(`[Monitor] ✓ Account ${account.phone} is healthy`);
        } else {
            handleFailedCheck(account);
        }
        
    } catch (error) {
        handleFailedCheck(account);
    }
}

async function handleFailedCheck(account) {
    const failures = (failedChecks.get(account.id) || 0) + 1;
    failedChecks.set(account.id, failures);
    
    console.warn(`[Monitor] ⚠ Account ${account.phone} failed check (${failures}/${RECONNECT_THRESHOLD})`);
    
    if (failures >= RECONNECT_THRESHOLD) {
        console.error(`[Monitor] ❌ Account ${account.phone} disconnected\! Attempting reconnect...`);
        await reconnectAccount(account);
        failedChecks.delete(account.id);
    }
}

async function reconnectAccount(account) {
    try {
        // Call Laravel API to trigger reconnection
        await axios.post(`${LARAVEL_URL}/api/zalo/reconnect`, {
            account_id: account.id
        }, { timeout: 30000 });
        
        console.log(`[Monitor] ✓ Reconnection initiated for ${account.phone}`);
        
    } catch (error) {
        console.error(`[Monitor] Failed to reconnect ${account.phone}:`, error.message);
        
        // Last resort: restart entire zalo-service
        if (failedChecks.get(account.id) > RECONNECT_THRESHOLD * 2) {
            console.error('[Monitor] Multiple reconnect failures. Restarting service...');
            restartService();
        }
    }
}

function restartService() {
    exec('pm2 restart school-zalo', (error, stdout, stderr) => {
        if (error) {
            console.error('[Monitor] Failed to restart:', error);
        } else {
            console.log('[Monitor] Service restarted successfully');
        }
    });
}

// Graceful shutdown
process.on('SIGTERM', () => {
    console.log('[Monitor] Shutting down gracefully...');
    process.exit(0);
});

process.on('SIGINT', () => {
    console.log('[Monitor] Shutting down gracefully...');
    process.exit(0);
});

// Start monitoring
console.log('[Monitor] Starting Zalo session monitor...');
console.log(`[Monitor] Check interval: ${CHECK_INTERVAL}ms`);
console.log(`[Monitor] Reconnect threshold: ${RECONNECT_THRESHOLD} failures`);

// Initial check
checkZaloSessions();

// Schedule periodic checks
setInterval(checkZaloSessions, CHECK_INTERVAL);

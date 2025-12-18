#\!/bin/bash

##############################################
# ZALO SERVICE HEALTH CHECK
# Auto-restart if service is unhealthy
##############################################

SERVICE_NAME="school-zalo"
HEALTH_URL="http://localhost:3001/health"
MAX_FAILURES=3
FAILURE_COUNT=0

LOG_FILE="/var/log/zalo-health-check.log"

log_message() {
    echo "[$(date +%Y-%m-%d %H:%M:%S)] $1" | tee -a "$LOG_FILE"
}

check_health() {
    response=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$HEALTH_URL")
    
    if [ "$response" = "200" ]; then
        return 0
    else
        return 1
    fi
}

restart_service() {
    log_message "⚠️  RESTARTING $SERVICE_NAME due to health check failure"
    pm2 restart "$SERVICE_NAME"
    
    # Wait for service to start
    sleep 10
    
    # Verify restart
    if check_health; then
        log_message "✅ Service restarted successfully"
        FAILURE_COUNT=0
    else
        log_message "❌ Service restart failed"
    fi
}

# Main loop
while true; do
    if check_health; then
        if [ $FAILURE_COUNT -gt 0 ]; then
            log_message "✅ Service recovered (was failing)"
        fi
        FAILURE_COUNT=0
    else
        ((FAILURE_COUNT++))
        log_message "❌ Health check failed ($FAILURE_COUNT/$MAX_FAILURES)"
        
        if [ $FAILURE_COUNT -ge $MAX_FAILURES ]; then
            restart_service
        fi
    fi
    
    # Check every 30 seconds
    sleep 30
done

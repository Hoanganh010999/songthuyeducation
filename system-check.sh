#!/bin/bash

################################################################################
# SYSTEM CHECK & FINAL SETUP
# Kiểm tra và hoàn tất deployment
################################################################################

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}  SYSTEM CHECK & FINAL SETUP          ${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""

APP_DIR="/var/www/school"

echo -e "${BLUE}[1] Checking system info...${NC}"
echo "OS: $(cat /etc/os-release | grep PRETTY_NAME | cut -d= -f2)"
echo "Kernel: $(uname -r)"
echo "Uptime: $(uptime -p)"
echo ""

echo -e "${BLUE}[2] Checking installed packages...${NC}"
echo "PHP: $(php -v | head -1)"
echo "Composer: $(composer -V 2>/dev/null || echo 'Not installed')"
echo "Node.js: $(node -v 2>/dev/null || echo 'Not installed')"
echo "NPM: $(npm -v 2>/dev/null || echo 'Not installed')"
echo "MySQL: $(mysql -V | head -1)"
echo "Nginx: $(nginx -v 2>&1)"
echo "Redis: $(redis-cli --version 2>/dev/null || echo 'Not installed')"
echo "PM2: $(pm2 -v 2>/dev/null || echo 'Not installed')"
echo ""

echo -e "${BLUE}[3] Checking services status...${NC}"
systemctl is-active nginx && echo -e "${GREEN}✓ Nginx is running${NC}" || echo -e "${RED}✗ Nginx is stopped${NC}"
systemctl is-active php8.2-fpm && echo -e "${GREEN}✓ PHP-FPM is running${NC}" || echo -e "${RED}✗ PHP-FPM is stopped${NC}"
systemctl is-active mysql && echo -e "${GREEN}✓ MySQL is running${NC}" || echo -e "${RED}✗ MySQL is stopped${NC}"
systemctl is-active redis-server && echo -e "${GREEN}✓ Redis is running${NC}" || echo -e "${RED}✗ Redis is stopped${NC}"
echo ""

echo -e "${BLUE}[4] Checking PM2 processes...${NC}"
pm2 list
echo ""

echo -e "${BLUE}[5] Checking Laravel app...${NC}"
cd $APP_DIR
if [ -f "artisan" ]; then
    echo -e "${GREEN}✓ Laravel app found${NC}"
    php artisan --version

    if [ -f ".env" ]; then
        echo -e "${GREEN}✓ .env file exists${NC}"
    else
        echo -e "${RED}✗ .env file missing${NC}"
    fi
else
    echo -e "${RED}✗ Laravel app not found${NC}"
    exit 1
fi
echo ""

echo -e "${BLUE}[6] Checking database connection...${NC}"
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected!';" && echo -e "${GREEN}✓ Database connection OK${NC}" || echo -e "${RED}✗ Database connection failed${NC}"
echo ""

echo -e "${BLUE}[7] Checking file permissions...${NC}"
ls -ld storage && echo -e "${GREEN}✓ storage/ exists${NC}" || echo -e "${RED}✗ storage/ missing${NC}"
ls -ld bootstrap/cache && echo -e "${GREEN}✓ bootstrap/cache/ exists${NC}" || echo -e "${RED}✗ bootstrap/cache/ missing${NC}"
echo ""

echo -e "${BLUE}[8] Checking Nginx configuration...${NC}"
nginx -t && echo -e "${GREEN}✓ Nginx config is valid${NC}" || echo -e "${RED}✗ Nginx config has errors${NC}"
echo ""

echo -e "${BLUE}[9] Checking disk space...${NC}"
df -h /
echo ""

echo -e "${BLUE}[10] Checking memory usage...${NC}"
free -h
echo ""

echo -e "${BLUE}[11] Running final setup...${NC}"

# Import database if not done
echo "Checking database tables..."
TABLE_COUNT=$(mysql -uschool_user -pSchool@2024 school_db -e "SHOW TABLES;" 2>/dev/null | wc -l)
if [ "$TABLE_COUNT" -lt 5 ]; then
    echo "Database seems empty, importing..."
    DB_FILE=$(find /root -name "database.sql" -path "*/school*" | head -1)
    if [ -f "$DB_FILE" ]; then
        mysql -uschool_user -pSchool@2024 school_db < "$DB_FILE" && echo -e "${GREEN}✓ Database imported${NC}"
    else
        echo "Running migrations..."
        php artisan migrate --force && echo -e "${GREEN}✓ Migrations completed${NC}"
    fi
else
    echo -e "${GREEN}✓ Database has $((TABLE_COUNT - 1)) tables${NC}"
fi

# Setup Zalo service if not running
if ! pm2 list | grep -q "school-zalo"; then
    echo "Starting Zalo service..."
    cd $APP_DIR/zalo-service
    [ ! -f ".env" ] && echo "NODE_ENV=production
PORT=3001
LARAVEL_URL=http://127.0.0.1:8000" > .env
    npm install --production
    pm2 start server.js --name school-zalo
    pm2 save
    echo -e "${GREEN}✓ Zalo service started${NC}"
fi

# Setup queue worker
if ! supervisorctl status 2>/dev/null | grep -q "school-worker"; then
    echo "Setting up queue worker..."
    apt install -y supervisor
    cat > /etc/supervisor/conf.d/school-worker.conf << EOF
[program:school-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_DIR/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/worker.log
EOF
    supervisorctl reread
    supervisorctl update
    echo -e "${GREEN}✓ Queue worker configured${NC}"
fi

# Setup cron
if ! crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
    echo "Setting up Laravel scheduler..."
    (crontab -l 2>/dev/null; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -
    echo -e "${GREEN}✓ Laravel scheduler configured${NC}"
fi

# Clear and cache
cd $APP_DIR
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Cache rebuilt${NC}"

# Fix permissions
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR/storage
chmod -R 755 $APP_DIR/bootstrap/cache
echo -e "${GREEN}✓ Permissions fixed${NC}"

echo ""
echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}  ✅ SYSTEM CHECK COMPLETED!          ${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""

echo -e "${BLUE}System Summary:${NC}"
echo "  Website URL: http://103.121.90.143"
echo "  App Directory: $APP_DIR"
echo "  Database: school_db"
echo ""

echo -e "${YELLOW}Next steps:${NC}"
echo "1. Visit website: http://103.121.90.143"
echo "2. Test login and features"
echo "3. Check logs: tail -f $APP_DIR/storage/logs/laravel.log"
echo "4. Monitor PM2: pm2 monit"
echo ""

echo -e "${YELLOW}Important commands:${NC}"
echo "  Check services: systemctl status nginx mysql php8.2-fpm"
echo "  Check PM2: pm2 status"
echo "  Check logs: tail -f /var/log/nginx/error.log"
echo "  Restart Nginx: systemctl restart nginx"
echo "  Restart PM2: pm2 restart school-zalo"
echo ""

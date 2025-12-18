#!/bin/bash

################################################################################
# VPS DEPLOYMENT SCRIPT CHO DỰ ÁN SCHOOL
#
# Script này sẽ cài đặt và cấu hình:
# 1. Nginx web server
# 2. PHP 8.2 + PHP-FPM
# 3. MySQL 8.0
# 4. Node.js 20 LTS + PM2
# 5. Redis
# 6. Composer
# 7. SSL Certificate (Let's Encrypt)
# 8. Firewall (UFW)
# 9. Deploy Laravel app
# 10. Deploy Zalo service
#
# Hệ điều hành: Ubuntu 22.04 LTS
# Sử dụng: sudo bash deploy-vps.sh
################################################################################

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Please run as root (use sudo)${NC}"
    exit 1
fi

echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}  VPS DEPLOYMENT - SCHOOL PROJECT    ${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""

# Configuration - CẬP NHẬT CÁC THÔNG TIN NÀY
read -p "Enter your domain (e.g., school.yourdomain.com): " DOMAIN
read -p "Enter your email for SSL certificate: " EMAIL
read -p "Enter database name [school_db]: " DB_NAME
DB_NAME=${DB_NAME:-school_db}
read -p "Enter database username [school_user]: " DB_USER
DB_USER=${DB_USER:-school_user}
read -sp "Enter database password: " DB_PASSWORD
echo ""

APP_DIR="/var/www/school"
PHP_VERSION="8.2"

echo ""
echo -e "${BLUE}Configuration:${NC}"
echo "  Domain: $DOMAIN"
echo "  Email: $EMAIL"
echo "  Database: $DB_NAME"
echo "  DB User: $DB_USER"
echo "  App Directory: $APP_DIR"
echo ""
read -p "Continue with this configuration? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Deployment cancelled."
    exit 1
fi

echo ""
echo -e "${YELLOW}🔄 Step 1: Update system...${NC}"
apt update && apt upgrade -y

echo -e "${YELLOW}📦 Step 2: Install essential packages...${NC}"
apt install -y software-properties-common curl wget git unzip vim ufw

echo -e "${YELLOW}🐘 Step 3: Install PHP ${PHP_VERSION}...${NC}"
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y \
    php${PHP_VERSION} \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-common \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-intl

# Configure PHP
sed -i "s/upload_max_filesize = .*/upload_max_filesize = 100M/" /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i "s/post_max_size = .*/post_max_size = 100M/" /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i "s/memory_limit = .*/memory_limit = 512M/" /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i "s/max_execution_time = .*/max_execution_time = 300/" /etc/php/${PHP_VERSION}/fpm/php.ini

# Enable OPcache
sed -i "s/;opcache.enable=1/opcache.enable=1/" /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i "s/;opcache.memory_consumption=.*/opcache.memory_consumption=256/" /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i "s/;opcache.max_accelerated_files=.*/opcache.max_accelerated_files=20000/" /etc/php/${PHP_VERSION}/fpm/php.ini

systemctl restart php${PHP_VERSION}-fpm

echo -e "${GREEN}  ✓ PHP ${PHP_VERSION} installed${NC}"

echo -e "${YELLOW}📦 Step 4: Install Composer...${NC}"
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
echo -e "${GREEN}  ✓ Composer installed${NC}"

echo -e "${YELLOW}🗄️  Step 5: Install MySQL 8.0...${NC}"
apt install -y mysql-server

# Secure MySQL installation (automated)
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_PASSWORD}';"
mysql -e "DELETE FROM mysql.user WHERE User='';"
mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
mysql -e "DROP DATABASE IF EXISTS test;"
mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
mysql -e "FLUSH PRIVILEGES;"

# Create database and user
mysql -uroot -p${DB_PASSWORD} -e "CREATE DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -p${DB_PASSWORD} -e "CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
mysql -uroot -p${DB_PASSWORD} -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -uroot -p${DB_PASSWORD} -e "FLUSH PRIVILEGES;"

echo -e "${GREEN}  ✓ MySQL installed and configured${NC}"

echo -e "${YELLOW}🔴 Step 6: Install Redis...${NC}"
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server
echo -e "${GREEN}  ✓ Redis installed${NC}"

echo -e "${YELLOW}🟢 Step 7: Install Node.js 20 LTS...${NC}"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Install PM2 globally
npm install -g pm2

# Setup PM2 startup
pm2 startup systemd -u root --hp /root
systemctl enable pm2-root

echo -e "${GREEN}  ✓ Node.js 20 and PM2 installed${NC}"

echo -e "${YELLOW}🌐 Step 8: Install Nginx...${NC}"
apt install -y nginx
systemctl enable nginx

echo -e "${GREEN}  ✓ Nginx installed${NC}"

echo -e "${YELLOW}🔒 Step 9: Install Certbot (Let's Encrypt)...${NC}"
apt install -y certbot python3-certbot-nginx
echo -e "${GREEN}  ✓ Certbot installed${NC}"

echo -e "${YELLOW}📁 Step 10: Create application directory...${NC}"
mkdir -p $APP_DIR
cd $APP_DIR
echo -e "${GREEN}  ✓ Directory created: $APP_DIR${NC}"

echo -e "${YELLOW}📥 Step 11: Deploy Laravel application...${NC}"
echo "  Please upload your backup file to /root/"
echo "  Then run: tar -xzf school-backup-*.tar.gz"
echo "  Then copy files: cp -r school-backup-*/. $APP_DIR/"
read -p "Press Enter when files are uploaded and extracted..."

# Set permissions
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR/storage
chmod -R 755 $APP_DIR/bootstrap/cache

# Install dependencies
cd $APP_DIR
composer install --no-dev --optimize-autoloader
npm install
npm run build

echo -e "${GREEN}  ✓ Laravel app deployed${NC}"

echo -e "${YELLOW}⚙️  Step 12: Configure .env...${NC}"
if [ ! -f "$APP_DIR/.env" ]; then
    cp $APP_DIR/.env.vps.example $APP_DIR/.env

    # Update .env
    sed -i "s/APP_URL=.*/APP_URL=https:\/\/${DOMAIN}/" $APP_DIR/.env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" $APP_DIR/.env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" $APP_DIR/.env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" $APP_DIR/.env
    sed -i "s/VITE_WS_URL=.*/VITE_WS_URL=https:\/\/${DOMAIN}/" $APP_DIR/.env

    # Generate app key
    php artisan key:generate

    echo -e "${GREEN}  ✓ .env configured${NC}"
else
    echo -e "${YELLOW}  .env already exists, skipping...${NC}"
fi

echo -e "${YELLOW}🗄️  Step 13: Run migrations...${NC}"
php artisan migrate --force
php artisan db:seed --force 2>/dev/null || true
echo -e "${GREEN}  ✓ Migrations completed${NC}"

echo -e "${YELLOW}🎯 Step 14: Optimize Laravel...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
echo -e "${GREEN}  ✓ Laravel optimized${NC}"

echo -e "${YELLOW}🔌 Step 15: Setup Zalo Service with PM2...${NC}"
cd $APP_DIR/zalo-service

# Install dependencies
npm install --production

# Create .env for zalo-service
if [ ! -f ".env" ]; then
    cat > .env << EOF
NODE_ENV=production
PORT=3001
LARAVEL_URL=http://127.0.0.1:8000
EOF
fi

# Start with PM2
pm2 start server.js --name school-zalo
pm2 save

echo -e "${GREEN}  ✓ Zalo service started with PM2${NC}"

echo -e "${YELLOW}👷 Step 16: Setup Laravel Queue Worker...${NC}"
apt install -y supervisor

cat > /etc/supervisor/conf.d/school-worker.conf << EOF
[program:school-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_DIR/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/worker.log
stopwaitsecs=3600
EOF

supervisorctl reread
supervisorctl update
supervisorctl start school-worker:*

echo -e "${GREEN}  ✓ Queue worker configured${NC}"

echo -e "${YELLOW}⏰ Step 17: Setup Cron Job...${NC}"
(crontab -l 2>/dev/null; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -
echo -e "${GREEN}  ✓ Cron job added${NC}"

echo -e "${YELLOW}🌐 Step 18: Configure Nginx...${NC}"
cat > /etc/nginx/sites-available/$DOMAIN << 'NGINXCONF'
# Laravel App
server {
    listen 80;
    listen [::]:80;
    server_name DOMAIN_PLACEHOLDER;
    root /var/www/school/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # WebSocket proxy
    location /socket.io/ {
        proxy_pass http://127.0.0.1:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
NGINXCONF

sed -i "s/DOMAIN_PLACEHOLDER/$DOMAIN/" /etc/nginx/sites-available/$DOMAIN

# Enable site
ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test nginx config
nginx -t

# Restart nginx
systemctl restart nginx

echo -e "${GREEN}  ✓ Nginx configured${NC}"

echo -e "${YELLOW}🔒 Step 19: Setup SSL Certificate...${NC}"
certbot --nginx -d $DOMAIN --non-interactive --agree-tos --email $EMAIL --redirect

# Setup auto-renewal
systemctl enable certbot.timer
systemctl start certbot.timer

echo -e "${GREEN}  ✓ SSL certificate installed${NC}"

echo -e "${YELLOW}🔥 Step 20: Configure Firewall (UFW)...${NC}"
ufw --force enable
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw status

echo -e "${GREEN}  ✓ Firewall configured${NC}"

echo -e "${YELLOW}📊 Step 21: Setup monitoring...${NC}"
# Install netdata (optional)
read -p "Install Netdata for monitoring? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    bash <(curl -Ss https://my-netdata.io/kickstart.sh) --dont-wait
    echo -e "${GREEN}  ✓ Netdata installed (access at http://$DOMAIN:19999)${NC}"
fi

echo -e "${YELLOW}🔐 Step 22: Setup automatic backups...${NC}"
mkdir -p /root/backups

cat > /root/backup-daily.sh << 'BACKUPSCRIPT'
#!/bin/bash
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/root/backups"
APP_DIR="/var/www/school"

# Backup database
mysqldump -u DB_USER_PLACEHOLDER -pDB_PASSWORD_PLACEHOLDER DB_NAME_PLACEHOLDER > $BACKUP_DIR/db-$TIMESTAMP.sql

# Backup files
tar -czf $BACKUP_DIR/files-$TIMESTAMP.tar.gz -C $APP_DIR storage .env

# Keep only last 7 days
find $BACKUP_DIR -name "db-*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "files-*.tar.gz" -mtime +7 -delete

echo "Backup completed: $TIMESTAMP"
BACKUPSCRIPT

sed -i "s/DB_USER_PLACEHOLDER/$DB_USER/" /root/backup-daily.sh
sed -i "s/DB_PASSWORD_PLACEHOLDER/$DB_PASSWORD/" /root/backup-daily.sh
sed -i "s/DB_NAME_PLACEHOLDER/$DB_NAME/" /root/backup-daily.sh

chmod +x /root/backup-daily.sh

# Add to crontab (daily at 2 AM)
(crontab -l 2>/dev/null; echo "0 2 * * * /root/backup-daily.sh >> /var/log/backup.log 2>&1") | crontab -

echo -e "${GREEN}  ✓ Daily backup configured${NC}"

echo ""
echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}  ✅ DEPLOYMENT HOÀN TẤT!            ${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""
echo -e "${BLUE}📝 Thông tin hệ thống:${NC}"
echo ""
echo -e "🌐 Domain: ${GREEN}https://$DOMAIN${NC}"
echo -e "📂 App Directory: ${GREEN}$APP_DIR${NC}"
echo -e "🗄️  Database: ${GREEN}$DB_NAME${NC}"
echo -e "👤 DB User: ${GREEN}$DB_USER${NC}"
echo ""
echo -e "${BLUE}🔧 Services:${NC}"
echo "  Nginx:       systemctl status nginx"
echo "  PHP-FPM:     systemctl status php${PHP_VERSION}-fpm"
echo "  MySQL:       systemctl status mysql"
echo "  Redis:       systemctl status redis-server"
echo "  Zalo:        pm2 status"
echo "  Queue:       supervisorctl status school-worker"
echo ""
echo -e "${BLUE}📊 Logs:${NC}"
echo "  Nginx:       tail -f /var/log/nginx/error.log"
echo "  Laravel:     tail -f $APP_DIR/storage/logs/laravel.log"
echo "  Zalo:        pm2 logs school-zalo"
echo "  Queue:       tail -f $APP_DIR/storage/logs/worker.log"
echo ""
echo -e "${BLUE}🔐 Security:${NC}"
echo "  Firewall:    ufw status"
echo "  SSL:         certbot certificates"
echo ""
echo -e "${YELLOW}⚠️  QUAN TRỌNG:${NC}"
echo "  1. Đổi SSH port (khuyến nghị): /etc/ssh/sshd_config"
echo "  2. Setup SSH key authentication"
echo "  3. Disable password login"
echo "  4. Cấu hình backup tự động lên cloud"
echo "  5. Kiểm tra website: https://$DOMAIN"
echo ""
echo -e "${GREEN}Chúc mừng! Hệ thống đã sẵn sàng! 🎉${NC}"
echo ""
